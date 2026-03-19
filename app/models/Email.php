<?php
/**
 * Model: Email
 * Gerenciamento de contas de e-mail, leitura via IMAP e envio via SMTP
 */

class Email {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // ==========================================
    //  CONTAS DE E-MAIL (CRUD)
    // ==========================================

    /**
     * Listar contas do usuário
     */
    public function listarContas($usuarioId) {
        return $this->db->fetchAll(
            "SELECT id, nome_conta, email, imap_host, imap_porta, imap_seguranca,
                    smtp_host, smtp_porta, smtp_seguranca, usuario_email, ativo, ultimo_sync
             FROM email_contas WHERE usuario_id = ? ORDER BY nome_conta",
            [$usuarioId]
        );
    }

    /**
     * Obter conta por ID (valida pertencimento ao usuário)
     */
    public function getConta($contaId, $usuarioId) {
        return $this->db->fetch(
            "SELECT * FROM email_contas WHERE id = ? AND usuario_id = ?",
            [$contaId, $usuarioId]
        );
    }

    /**
     * Salvar conta (criar ou atualizar)
     */
    public function salvarConta($dados, $usuarioId) {
        $senhaEncriptada = $this->encriptarSenha($dados['senha_email']);

        $contaData = [
            'usuario_id'     => $usuarioId,
            'nome_conta'     => $dados['nome_conta'] ?? 'Meu E-mail',
            'email'          => $dados['email'],
            'imap_host'      => $dados['imap_host'],
            'imap_porta'     => (int)($dados['imap_porta'] ?? 993),
            'imap_seguranca' => $dados['imap_seguranca'] ?? 'ssl',
            'smtp_host'      => $dados['smtp_host'],
            'smtp_porta'     => (int)($dados['smtp_porta'] ?? 587),
            'smtp_seguranca' => $dados['smtp_seguranca'] ?? 'tls',
            'usuario_email'  => $dados['usuario_email'],
            'senha_email'    => $senhaEncriptada,
            'ativo'          => 1,
        ];

        if (!empty($dados['id'])) {
            // Atualizar — se a senha veio vazia, manter a atual
            if (empty($dados['senha_email'])) {
                unset($contaData['senha_email']);
            }
            unset($contaData['usuario_id']);
            $this->db->update('email_contas', $contaData, 'id = ? AND usuario_id = ?', [$dados['id'], $usuarioId]);
            return (int)$dados['id'];
        } else {
            return $this->db->insert('email_contas', $contaData);
        }
    }

    /**
     * Excluir conta
     */
    public function excluirConta($contaId, $usuarioId) {
        return $this->db->delete('email_contas', 'id = ? AND usuario_id = ?', [$contaId, $usuarioId]);
    }

    /**
     * Testar conexão IMAP
     */
    public function testarConexao($dados) {
        $mailbox = $this->buildImapString($dados['imap_host'], $dados['imap_porta'], $dados['imap_seguranca']);
        $senha = $dados['senha_email'];

        // Se é uma conta existente e a senha veio vazia, buscar do banco
        if (empty($senha) && !empty($dados['id']) && !empty($dados['usuario_id'])) {
            $conta = $this->getConta($dados['id'], $dados['usuario_id']);
            if ($conta) {
                $senha = $this->decriptarSenha($conta['senha_email']);
            }
        }

        $imap = @imap_open($mailbox, $dados['usuario_email'], $senha, 0, 1, [
            'DISABLE_AUTHENTICATOR' => 'GSSAPI'
        ]);

        if (!$imap) {
            $erros = imap_errors() ?: [];
            $alertas = imap_alerts() ?: [];
            throw new \Exception('Falha na conexão IMAP: ' . implode('; ', array_merge($erros, $alertas)));
        }

        $check = imap_check($imap);
        imap_close($imap);

        return [
            'success'  => true,
            'mailbox'  => $check->Mailbox ?? '',
            'messages' => $check->Nmsgs ?? 0,
        ];
    }

    // ==========================================
    //  LEITURA DE E-MAILS (IMAP)
    // ==========================================

    /**
     * Conectar via IMAP
     */
    private function imapConnect($conta, $folder = 'INBOX') {
        $senha = $this->decriptarSenha($conta['senha_email']);
        $mailbox = $this->buildImapString($conta['imap_host'], $conta['imap_porta'], $conta['imap_seguranca'], $folder);

        $imap = @imap_open($mailbox, $conta['usuario_email'], $senha, 0, 1, [
            'DISABLE_AUTHENTICATOR' => 'GSSAPI'
        ]);

        if (!$imap) {
            $erros = imap_errors() ?: [];
            throw new \Exception('Conexão IMAP falhou: ' . implode('; ', $erros));
        }

        return $imap;
    }

    /**
     * Listar pastas/folders do e-mail
     */
    public function listarPastas($contaId, $usuarioId) {
        $conta = $this->getConta($contaId, $usuarioId);
        if (!$conta) throw new \Exception('Conta não encontrada');

        $senha = $this->decriptarSenha($conta['senha_email']);
        $mailbox = $this->buildImapString($conta['imap_host'], $conta['imap_porta'], $conta['imap_seguranca']);

        $imap = @imap_open($mailbox, $conta['usuario_email'], $senha, 0, 1, [
            'DISABLE_AUTHENTICATOR' => 'GSSAPI'
        ]);
        if (!$imap) throw new \Exception('Conexão IMAP falhou');

        $folders = imap_list($imap, $mailbox, '*');
        imap_close($imap);

        if (!$folders) return [];

        $result = [];
        foreach ($folders as $f) {
            $folderName = str_replace($mailbox, '', imap_utf7_decode($f));
            $folderName = ltrim($folderName, '/.');

            // Mapear nomes comuns
            $icon = 'fa-folder';
            $label = $folderName;
            $lower = mb_strtolower($folderName);
            if (strpos($lower, 'inbox') !== false || $lower === 'caixa de entrada') {
                $icon = 'fa-inbox'; $label = 'Caixa de Entrada';
            } elseif (strpos($lower, 'sent') !== false || strpos($lower, 'enviado') !== false) {
                $icon = 'fa-paper-plane'; $label = 'Enviados';
            } elseif (strpos($lower, 'draft') !== false || strpos($lower, 'rascunho') !== false) {
                $icon = 'fa-file-alt'; $label = 'Rascunhos';
            } elseif (strpos($lower, 'trash') !== false || strpos($lower, 'lixo') !== false || strpos($lower, 'lixeira') !== false) {
                $icon = 'fa-trash'; $label = 'Lixeira';
            } elseif (strpos($lower, 'spam') !== false || strpos($lower, 'junk') !== false) {
                $icon = 'fa-exclamation-triangle'; $label = 'Spam';
            } elseif (strpos($lower, 'archive') !== false || strpos($lower, 'arquivo') !== false) {
                $icon = 'fa-archive'; $label = 'Arquivo';
            } elseif (strpos($lower, 'star') !== false || strpos($lower, 'flagged') !== false || strpos($lower, 'important') !== false) {
                $icon = 'fa-star'; $label = 'Importantes';
            }

            $result[] = [
                'name'  => $folderName,
                'label' => $label,
                'icon'  => $icon,
                'raw'   => $f,
            ];
        }

        return $result;
    }

    /**
     * Listar e-mails de uma pasta (paginado)
     */
    public function listarEmails($contaId, $usuarioId, $folder = 'INBOX', $page = 1, $perPage = 25, $busca = '') {
        $conta = $this->getConta($contaId, $usuarioId);
        if (!$conta) throw new \Exception('Conta não encontrada');

        $imap = $this->imapConnect($conta, $folder);

        try {
            // Busca
            $criteria = 'ALL';
            if (!empty($busca)) {
                // Buscar em subject, from, body
                $criteria = 'OR OR SUBJECT "' . addslashes($busca) . '" FROM "' . addslashes($busca) . '" BODY "' . addslashes($busca) . '"';
            }

            $msgNums = imap_search($imap, $criteria, SE_UID);
            if (!$msgNums) {
                imap_close($imap);
                return ['emails' => [], 'total' => 0, 'page' => $page, 'pages' => 0, 'folder' => $folder];
            }

            // Ordem decrescente (mais recentes primeiro)
            rsort($msgNums);
            $total = count($msgNums);
            $pages = ceil($total / $perPage);
            $offset = ($page - 1) * $perPage;
            $pageUIDs = array_slice($msgNums, $offset, $perPage);

            $emails = [];
            foreach ($pageUIDs as $uid) {
                $overview = imap_fetch_overview($imap, $uid, FT_UID);
                if (!$overview) continue;

                $ov = $overview[0];
                $from = isset($ov->from) ? $this->decodeMimeHeader($ov->from) : '';
                $subject = isset($ov->subject) ? $this->decodeMimeHeader($ov->subject) : '(sem assunto)';

                // Extrair nome e email do remetente
                $fromParsed = $this->parseAddress($from);

                $emails[] = [
                    'uid'     => $uid,
                    'msgno'   => $ov->msgno ?? 0,
                    'from'    => $fromParsed['name'] ?: $fromParsed['email'],
                    'from_email' => $fromParsed['email'],
                    'to'      => isset($ov->to) ? $this->decodeMimeHeader($ov->to) : '',
                    'subject' => $subject,
                    'date'    => isset($ov->date) ? date('Y-m-d H:i', strtotime($ov->date)) : '',
                    'seen'    => (int)($ov->seen ?? 0),
                    'flagged' => (int)($ov->flagged ?? 0),
                    'answered'=> (int)($ov->answered ?? 0),
                    'size'    => (int)($ov->size ?? 0),
                    'has_attachment' => false, // será detectado ao abrir
                ];
            }

            // Atualizar último sync
            $this->db->update('email_contas', ['ultimo_sync' => date('Y-m-d H:i:s')], 'id = ?', [$contaId]);

            imap_close($imap);

            return [
                'emails' => $emails,
                'total'  => $total,
                'page'   => $page,
                'pages'  => $pages,
                'folder' => $folder,
            ];

        } catch (\Exception $e) {
            imap_close($imap);
            throw $e;
        }
    }

    /**
     * Ler um e-mail específico (completo com body e anexos)
     */
    public function lerEmail($contaId, $usuarioId, $uid, $folder = 'INBOX') {
        $conta = $this->getConta($contaId, $usuarioId);
        if (!$conta) throw new \Exception('Conta não encontrada');

        $imap = $this->imapConnect($conta, $folder);

        try {
            $overview = imap_fetch_overview($imap, $uid, FT_UID);
            if (!$overview) {
                imap_close($imap);
                throw new \Exception('E-mail não encontrado');
            }

            $ov = $overview[0];
            $header = imap_headerinfo($imap, imap_msgno($imap, $uid));

            // Marcar como lido
            imap_setflag_full($imap, $uid, '\\Seen', ST_UID);

            // Extrair body
            $body = $this->getEmailBody($imap, $uid);

            // Extrair anexos
            $attachments = $this->getAttachments($imap, $uid);

            // Parse addresses
            $from = isset($ov->from) ? $this->decodeMimeHeader($ov->from) : '';
            $fromParsed = $this->parseAddress($from);
            $to = isset($ov->to) ? $this->decodeMimeHeader($ov->to) : '';
            $cc = '';
            if (isset($header->ccaddress)) {
                $cc = $this->decodeMimeHeader($header->ccaddress);
            }

            $email = [
                'uid'       => $uid,
                'from'      => $fromParsed['name'] ?: $fromParsed['email'],
                'from_email'=> $fromParsed['email'],
                'to'        => $to,
                'cc'        => $cc,
                'subject'   => isset($ov->subject) ? $this->decodeMimeHeader($ov->subject) : '(sem assunto)',
                'date'      => isset($ov->date) ? date('Y-m-d H:i', strtotime($ov->date)) : '',
                'body_html' => $body['html'] ?? '',
                'body_text' => $body['text'] ?? '',
                'seen'      => 1,
                'flagged'   => (int)($ov->flagged ?? 0),
                'answered'  => (int)($ov->answered ?? 0),
                'attachments' => $attachments,
                'size'      => (int)($ov->size ?? 0),
            ];

            imap_close($imap);
            return $email;

        } catch (\Exception $e) {
            imap_close($imap);
            throw $e;
        }
    }

    /**
     * Extrair o body do e-mail (HTML e texto)
     */
    private function getEmailBody($imap, $uid) {
        $structure = imap_fetchstructure($imap, $uid, FT_UID);
        $body = ['html' => '', 'text' => ''];

        if (!$structure) return $body;

        if (empty($structure->parts)) {
            // Mensagem simples (sem partes)
            $content = imap_fetchbody($imap, $uid, '1', FT_UID);
            $content = $this->decodeBody($content, $structure->encoding ?? 0);
            $charset = $this->getCharset($structure);
            $content = $this->convertToUTF8($content, $charset);

            if ($structure->subtype === 'HTML') {
                $body['html'] = $content;
            } else {
                $body['text'] = $content;
            }
        } else {
            // Mensagem multipart
            $this->parseParts($imap, $uid, $structure->parts, $body, '');
        }

        // Se não tem HTML mas tem texto, converter texto para HTML
        if (empty($body['html']) && !empty($body['text'])) {
            $body['html'] = '<pre style="white-space:pre-wrap;font-family:inherit">' . htmlspecialchars($body['text']) . '</pre>';
        }

        return $body;
    }

    /**
     * Parse recursivo de parts multipart
     */
    private function parseParts($imap, $uid, $parts, &$body, $prefix) {
        foreach ($parts as $index => $part) {
            $partNum = $prefix ? $prefix . '.' . ($index + 1) : (string)($index + 1);

            if ($part->type === 0) { // TEXT
                $content = imap_fetchbody($imap, $uid, $partNum, FT_UID);
                $content = $this->decodeBody($content, $part->encoding ?? 0);
                $charset = $this->getCharset($part);
                $content = $this->convertToUTF8($content, $charset);

                if (strtoupper($part->subtype) === 'HTML') {
                    $body['html'] .= $content;
                } elseif (strtoupper($part->subtype) === 'PLAIN') {
                    $body['text'] .= $content;
                }
            }

            if (!empty($part->parts)) {
                $this->parseParts($imap, $uid, $part->parts, $body, $partNum);
            }
        }
    }

    /**
     * Extrair lista de anexos (metadados, sem conteúdo)
     */
    private function getAttachments($imap, $uid) {
        $structure = imap_fetchstructure($imap, $uid, FT_UID);
        $attachments = [];

        if (empty($structure->parts)) return $attachments;

        foreach ($structure->parts as $index => $part) {
            $this->collectAttachments($part, $index + 1, $attachments);
        }

        return $attachments;
    }

    private function collectAttachments($part, $partNum, &$attachments) {
        $filename = '';

        // Verificar disposition
        if (!empty($part->dparameters)) {
            foreach ($part->dparameters as $param) {
                if (strtoupper($param->attribute) === 'FILENAME') {
                    $filename = $this->decodeMimeHeader($param->value);
                }
            }
        }

        // Verificar parameters (fallback)
        if (empty($filename) && !empty($part->parameters)) {
            foreach ($part->parameters as $param) {
                if (strtoupper($param->attribute) === 'NAME') {
                    $filename = $this->decodeMimeHeader($param->value);
                }
            }
        }

        if (!empty($filename)) {
            $attachments[] = [
                'partNum'  => $partNum,
                'filename' => $filename,
                'size'     => $part->bytes ?? 0,
                'type'     => $this->getMimeType($part),
            ];
        }

        // Recurso em sub-parts
        if (!empty($part->parts)) {
            foreach ($part->parts as $subIndex => $subPart) {
                $this->collectAttachments($subPart, $partNum . '.' . ($subIndex + 1), $attachments);
            }
        }
    }

    /**
     * Baixar um anexo específico
     */
    public function baixarAnexo($contaId, $usuarioId, $uid, $partNum, $folder = 'INBOX') {
        $conta = $this->getConta($contaId, $usuarioId);
        if (!$conta) throw new \Exception('Conta não encontrada');

        $imap = $this->imapConnect($conta, $folder);

        try {
            $structure = imap_fetchstructure($imap, $uid, FT_UID);
            $part = $this->getPartByNumber($structure, $partNum);

            $content = imap_fetchbody($imap, $uid, $partNum, FT_UID);
            $content = $this->decodeBody($content, $part->encoding ?? 0);

            $filename = 'attachment';
            if (!empty($part->dparameters)) {
                foreach ($part->dparameters as $param) {
                    if (strtoupper($param->attribute) === 'FILENAME') {
                        $filename = $this->decodeMimeHeader($param->value);
                    }
                }
            }
            if ($filename === 'attachment' && !empty($part->parameters)) {
                foreach ($part->parameters as $param) {
                    if (strtoupper($param->attribute) === 'NAME') {
                        $filename = $this->decodeMimeHeader($param->value);
                    }
                }
            }

            $mime = $this->getMimeType($part);

            imap_close($imap);

            return [
                'filename' => $filename,
                'mime'     => $mime,
                'content'  => $content,
            ];

        } catch (\Exception $e) {
            imap_close($imap);
            throw $e;
        }
    }

    private function getPartByNumber($structure, $partNum) {
        $parts = explode('.', $partNum);
        $current = $structure;

        foreach ($parts as $num) {
            $idx = (int)$num - 1;
            if (isset($current->parts[$idx])) {
                $current = $current->parts[$idx];
            }
        }

        return $current;
    }

    // ==========================================
    //  AÇÕES DE E-MAIL
    // ==========================================

    /**
     * Marcar como lido/não lido
     */
    public function marcarLido($contaId, $usuarioId, $uid, $lido, $folder = 'INBOX') {
        $conta = $this->getConta($contaId, $usuarioId);
        if (!$conta) throw new \Exception('Conta não encontrada');

        $imap = $this->imapConnect($conta, $folder);

        if ($lido) {
            imap_setflag_full($imap, $uid, '\\Seen', ST_UID);
        } else {
            imap_clearflag_full($imap, $uid, '\\Seen', ST_UID);
        }

        imap_close($imap);
        return true;
    }

    /**
     * Marcar como importante (flagged)
     */
    public function marcarImportante($contaId, $usuarioId, $uid, $flagged, $folder = 'INBOX') {
        $conta = $this->getConta($contaId, $usuarioId);
        if (!$conta) throw new \Exception('Conta não encontrada');

        $imap = $this->imapConnect($conta, $folder);

        if ($flagged) {
            imap_setflag_full($imap, $uid, '\\Flagged', ST_UID);
        } else {
            imap_clearflag_full($imap, $uid, '\\Flagged', ST_UID);
        }

        imap_close($imap);
        return true;
    }

    /**
     * Mover e-mail para outra pasta
     */
    public function moverEmail($contaId, $usuarioId, $uid, $destFolder, $folder = 'INBOX') {
        $conta = $this->getConta($contaId, $usuarioId);
        if (!$conta) throw new \Exception('Conta não encontrada');

        $imap = $this->imapConnect($conta, $folder);

        $success = imap_mail_move($imap, $uid, $destFolder, CP_UID);
        if (!$success) {
            imap_close($imap);
            throw new \Exception('Erro ao mover e-mail');
        }

        imap_expunge($imap);
        imap_close($imap);
        return true;
    }

    /**
     * Excluir e-mail (move para lixeira)
     */
    public function excluirEmail($contaId, $usuarioId, $uid, $folder = 'INBOX') {
        $conta = $this->getConta($contaId, $usuarioId);
        if (!$conta) throw new \Exception('Conta não encontrada');

        $imap = $this->imapConnect($conta, $folder);

        imap_delete($imap, $uid, FT_UID);
        imap_expunge($imap);

        imap_close($imap);
        return true;
    }

    // ==========================================
    //  ENVIO DE E-MAIL (SMTP)
    // ==========================================

    /**
     * Enviar e-mail via SMTP usando fsockopen
     */
    public function enviarEmail($contaId, $usuarioId, $dadosEmail) {
        $conta = $this->getConta($contaId, $usuarioId);
        if (!$conta) throw new \Exception('Conta não encontrada');

        $senha = $this->decriptarSenha($conta['senha_email']);
        $to = $dadosEmail['to'];
        $subject = $dadosEmail['subject'] ?? '(sem assunto)';
        $bodyHtml = $dadosEmail['body'] ?? '';
        $cc = $dadosEmail['cc'] ?? '';
        $bcc = $dadosEmail['bcc'] ?? '';
        $replyTo = $dadosEmail['reply_to'] ?? '';
        $inReplyTo = $dadosEmail['in_reply_to'] ?? '';

        // Construir a mensagem MIME
        $boundary = md5(uniqid(time()));

        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "From: {$conta['nome_conta']} <{$conta['email']}>\r\n";
        $headers .= "To: {$to}\r\n";
        if ($cc) $headers .= "Cc: {$cc}\r\n";
        if ($bcc) $headers .= "Bcc: {$bcc}\r\n";
        if ($replyTo) $headers .= "Reply-To: {$replyTo}\r\n";
        if ($inReplyTo) $headers .= "In-Reply-To: {$inReplyTo}\r\n";
        $headers .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
        $headers .= "Date: " . date('r') . "\r\n";
        $headers .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";
        $headers .= "X-Mailer: HelpDesk-TI\r\n";

        // Body
        $textBody = strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>'], "\n", $bodyHtml));

        $message = "--{$boundary}\r\n";
        $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $message .= chunk_split(base64_encode($textBody)) . "\r\n";
        $message .= "--{$boundary}\r\n";
        $message .= "Content-Type: text/html; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $message .= chunk_split(base64_encode($bodyHtml)) . "\r\n";
        $message .= "--{$boundary}--\r\n";

        // Enviar via SMTP
        $this->smtpSend(
            $conta['smtp_host'],
            $conta['smtp_porta'],
            $conta['smtp_seguranca'],
            $conta['usuario_email'],
            $senha,
            $conta['email'],
            $to,
            $headers . "\r\n" . $message,
            $cc,
            $bcc
        );

        return true;
    }

    /**
     * Envio SMTP via socket
     */
    private function smtpSend($host, $port, $security, $user, $pass, $from, $to, $data, $cc = '', $bcc = '') {
        $prefix = ($security === 'ssl') ? 'ssl://' : '';
        $socket = @fsockopen($prefix . $host, $port, $errno, $errstr, 30);

        if (!$socket) {
            throw new \Exception("Falha ao conectar SMTP: {$errstr} ({$errno})");
        }

        $this->smtpRead($socket); // greeting

        // EHLO
        $this->smtpCmd($socket, "EHLO helpdesk-ti");

        // STARTTLS se necessário
        if ($security === 'tls') {
            $this->smtpCmd($socket, "STARTTLS", 220);
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT)) {
                throw new \Exception('Falha ao ativar TLS');
            }
            $this->smtpCmd($socket, "EHLO helpdesk-ti");
        }

        // AUTH LOGIN
        $this->smtpCmd($socket, "AUTH LOGIN", 334);
        $this->smtpCmd($socket, base64_encode($user), 334);
        $this->smtpCmd($socket, base64_encode($pass), 235);

        // MAIL FROM
        $this->smtpCmd($socket, "MAIL FROM:<{$from}>", 250);

        // RCPT TO — todos os destinatários
        $recipients = array_map('trim', explode(',', $to));
        if ($cc) $recipients = array_merge($recipients, array_map('trim', explode(',', $cc)));
        if ($bcc) $recipients = array_merge($recipients, array_map('trim', explode(',', $bcc)));

        foreach ($recipients as $rcpt) {
            $rcpt = trim($rcpt);
            // Extrair email de "Nome <email>" format
            if (preg_match('/<(.+?)>/', $rcpt, $m)) $rcpt = $m[1];
            if (!empty($rcpt)) {
                $this->smtpCmd($socket, "RCPT TO:<{$rcpt}>", 250);
            }
        }

        // DATA
        $this->smtpCmd($socket, "DATA", 354);
        fwrite($socket, $data . "\r\n.\r\n");
        $this->smtpRead($socket);

        // QUIT
        $this->smtpCmd($socket, "QUIT");
        fclose($socket);
    }

    private function smtpCmd($socket, $cmd, $expectedCode = null) {
        fwrite($socket, $cmd . "\r\n");
        $response = $this->smtpRead($socket);

        if ($expectedCode && (int)substr($response, 0, 3) !== $expectedCode) {
            throw new \Exception("SMTP erro: {$response}");
        }

        return $response;
    }

    private function smtpRead($socket) {
        $data = '';
        while ($str = fgets($socket, 4096)) {
            $data .= $str;
            if (substr($str, 3, 1) === ' ') break;
        }
        return $data;
    }

    // ==========================================
    //  HELPERS
    // ==========================================

    private function buildImapString($host, $port, $security, $folder = 'INBOX') {
        $flags = '/imap';
        if ($security === 'ssl') {
            $flags .= '/ssl/novalidate-cert';
        } elseif ($security === 'tls') {
            $flags .= '/tls/novalidate-cert';
        } else {
            $flags .= '/notls';
        }
        return '{' . $host . ':' . $port . $flags . '}' . $folder;
    }

    private function decodeMimeHeader($string) {
        $decoded = imap_mime_header_decode($string);
        $result = '';
        foreach ($decoded as $part) {
            $charset = strtoupper($part->charset ?? 'default');
            $text = $part->text;
            if ($charset !== 'DEFAULT' && $charset !== 'UTF-8') {
                $text = @mb_convert_encoding($text, 'UTF-8', $charset) ?: $text;
            }
            $result .= $text;
        }
        return $result;
    }

    private function parseAddress($address) {
        $name = '';
        $email = $address;

        if (preg_match('/^(.+?)\s*<(.+?)>$/', $address, $m)) {
            $name = trim($m[1], ' "\'');
            $email = $m[2];
        } elseif (preg_match('/<(.+?)>/', $address, $m)) {
            $email = $m[1];
        }

        return ['name' => $name, 'email' => $email];
    }

    private function decodeBody($data, $encoding) {
        switch ($encoding) {
            case 3: return imap_base64($data);      // BASE64
            case 4: return imap_qprint($data);       // QUOTED-PRINTABLE
            case 1: return imap_8bit($data);         // 8BIT
            case 2: return imap_binary($data);       // BINARY
            default: return $data;                   // 7BIT ou outro
        }
    }

    private function getCharset($part) {
        if (!empty($part->parameters)) {
            foreach ($part->parameters as $param) {
                if (strtoupper($param->attribute) === 'CHARSET') {
                    return $param->value;
                }
            }
        }
        return 'UTF-8';
    }

    private function convertToUTF8($text, $charset) {
        $charset = strtoupper($charset);
        if ($charset === 'UTF-8' || $charset === 'UTF8') return $text;
        return @mb_convert_encoding($text, 'UTF-8', $charset) ?: $text;
    }

    private function getMimeType($part) {
        $types = ['TEXT', 'MULTIPART', 'MESSAGE', 'APPLICATION', 'AUDIO', 'IMAGE', 'VIDEO', 'MODEL', 'OTHER'];
        $type = $types[$part->type] ?? 'APPLICATION';
        $subtype = $part->subtype ?? 'OCTET-STREAM';
        return strtolower($type . '/' . $subtype);
    }

    /**
     * Encriptação simples da senha (AES-256)
     */
    private function encriptarSenha($senha) {
        $key = hash('sha256', 'helpdesk-email-key-2024', true);
        $iv = openssl_random_pseudo_bytes(16);
        $encrypted = openssl_encrypt($senha, 'AES-256-CBC', $key, 0, $iv);
        return base64_encode($iv . '::' . $encrypted);
    }

    private function decriptarSenha($senhaEncriptada) {
        $key = hash('sha256', 'helpdesk-email-key-2024', true);
        $data = base64_decode($senhaEncriptada);
        $parts = explode('::', $data, 2);
        if (count($parts) !== 2) throw new \Exception('Senha de e-mail corrompida');
        return openssl_decrypt($parts[1], 'AES-256-CBC', $key, 0, $parts[0]);
    }

    /**
     * Contar e-mails não lidos
     */
    public function contarNaoLidos($contaId, $usuarioId, $folder = 'INBOX') {
        $conta = $this->getConta($contaId, $usuarioId);
        if (!$conta) return 0;

        try {
            $imap = $this->imapConnect($conta, $folder);
            $status = imap_status($imap, $this->buildImapString($conta['imap_host'], $conta['imap_porta'], $conta['imap_seguranca'], $folder), SA_UNSEEN);
            imap_close($imap);
            return $status->unseen ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }
}
