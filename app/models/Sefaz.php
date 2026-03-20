<?php
/**
 * Model: SEFAZ
 * Integração com Manifesto do Destinatário (MDF-e / NF-e)
 * 
 * Webservices utilizados:
 * - NFeDistribuicaoDFe: Consulta DF-e destinados ao CNPJ (Ambiente Nacional)
 * - RecepcaoEvento: Registro de eventos de manifesto (AN)
 * - NFeConsultaProtocolo: Consulta situação de uma NF-e pela chave (per-UF)
 * - NFeStatusServico: Verifica disponibilidade do serviço (per-UF)
 */
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Financeiro.php';

class Sefaz {
    private $db;
    private $financeiro;
    private $config;
    private $endpoints;

    // Namespaces SEFAZ
    const NS_NFE = 'http://www.portalfiscal.inf.br/nfe';
    const NS_DIST = 'http://www.portalfiscal.inf.br/nfe/wsdl/NFeDistribuicaoDFe';
    const NS_EVENTO = 'http://www.portalfiscal.inf.br/nfe/wsdl/NFeRecepcaoEvento4';

    public function __construct() {
        $this->db = Database::getInstance();
        $this->financeiro = new Financeiro();
        $this->endpoints = $this->loadEndpoints();
        $this->config = $this->getConfig();
    }

    /**
     * Carregar endpoints do arquivo de configuração
     */
    private function loadEndpoints() {
        $file = __DIR__ . '/../../config/sefaz_endpoints.php';
        if (!file_exists($file)) {
            throw new Exception('Arquivo de endpoints SEFAZ não encontrado: config/sefaz_endpoints.php');
        }
        return require $file;
    }

    /**
     * Obter URL do webservice por serviço
     * @param string $servico Ex: NFeDistribuicaoDFe, RecepcaoEvento, NFeConsulta, NFeStatusServico
     * @param bool $usarAN Forçar uso do Ambiente Nacional (para DistribuicaoDFe e Manifesto)
     */
    public function getEndpointUrl($servico, $usarAN = false) {
        $ambiente = $this->config['ambiente'] ?? 'homologacao';
        $uf = strtoupper($this->config['uf'] ?? 'GO');

        // Serviços nacionais SEMPRE via AN
        $servicosAN = ['NFeDistribuicaoDFe'];
        if (in_array($servico, $servicosAN) || $usarAN) {
            return $this->endpoints['AN'][$ambiente][$servico] ?? null;
        }

        // Serviços per-UF: resolver autorizadora
        $autorizadora = $this->endpoints['UF_AUTORIZADORA'][$uf] ?? 'SVRS';
        $ufEndpoints = $this->endpoints['UF'][$autorizadora][$ambiente] ?? [];

        if (isset($ufEndpoints[$servico])) {
            return $ufEndpoints[$servico];
        }

        // Fallback para AN
        return $this->endpoints['AN'][$ambiente][$servico] ?? null;
    }

    /**
     * Obter SoapAction para um serviço
     */
    private function getSoapAction($servico) {
        return $this->endpoints['SOAP'][$servico]['action'] ?? null;
    }

    /**
     * Obter configuração SEFAZ
     */
    public function getConfig() {
        $cfg = $this->db->fetch("SELECT * FROM sefaz_config WHERE ativo = 1 LIMIT 1");
        if (!$cfg) {
            // Tentar carregar do configuracoes
            $cnpj = $this->db->fetchColumn("SELECT valor FROM configuracoes WHERE chave = 'financeiro_empresa_cnpj'");
            $razao = $this->db->fetchColumn("SELECT valor FROM configuracoes WHERE chave = 'financeiro_empresa_razao'");
            $uf = $this->db->fetchColumn("SELECT valor FROM configuracoes WHERE chave = 'financeiro_empresa_uf'") ?: 'BA';
            $ambiente = $this->db->fetchColumn("SELECT valor FROM configuracoes WHERE chave = 'financeiro_sefaz_ambiente'") ?: 'homologacao';
            return [
                'cnpj_empresa' => $cnpj,
                'razao_social' => $razao,
                'uf' => $uf,
                'ambiente' => $ambiente,
                'ultimo_nsu' => '0',
                'max_nsu' => '0',
                'certificado_pfx' => null,
                'certificado_senha' => null
            ];
        }
        return $cfg;
    }

    /**
     * Salvar configuração SEFAZ
     */
    public function salvarConfig($dados) {
        $existe = $this->db->fetch("SELECT id FROM sefaz_config WHERE ativo = 1 LIMIT 1");
        if ($existe) {
            $this->db->update('sefaz_config', $dados, 'id = ?', [$existe['id']]);
            return $existe['id'];
        }
        return $this->db->insert('sefaz_config', $dados);
    }

    /**
     * Upload certificado digital A1 (.pfx)
     */
    public function uploadCertificado($pfxContent, $senha) {
        // Validar se o certificado é válido
        $certs = [];
        if (!openssl_pkcs12_read($pfxContent, $certs, $senha)) {
            throw new Exception('Certificado inválido ou senha incorreta');
        }

        // Extrair validade
        $certInfo = openssl_x509_parse($certs['cert']);
        $validade = date('Y-m-d', $certInfo['validTo_time_t'] ?? 0);

        $this->salvarConfig([
            'certificado_pfx' => $pfxContent,
            'certificado_senha' => $senha,
            'certificado_validade' => $validade
        ]);

        return [
            'validade' => $validade,
            'subject' => $certInfo['subject']['CN'] ?? 'N/A'
        ];
    }

    /**
     * Verificar se integração está pronta
     */
    public function isReady() {
        if (!$this->config) return false;
        if (empty($this->config['cnpj_empresa'])) return false;
        if (empty($this->config['certificado_pfx'])) return false;
        return true;
    }

    // ================================================================
    // DISTRIBUIÇÃO DF-e (Consulta notas destinadas ao CNPJ)
    // ================================================================

    /**
     * Consultar DF-e distribuídos no Ambiente Nacional
     * Retorna notas fiscais destinadas ao CNPJ da empresa
     */
    public function consultarDFe($nsu = null) {
        if (!$this->isReady()) {
            throw new Exception('SEFAZ não configurada. Configure o certificado digital e CNPJ.');
        }

        $cnpj = preg_replace('/[^0-9]/', '', $this->config['cnpj_empresa']);
        $ultimoNSU = $nsu ?? $this->config['ultimo_nsu'] ?? '0';
        $ultimoNSU = str_pad($ultimoNSU, 15, '0', STR_PAD_LEFT);

        // Montar XML de consulta
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soap12:Envelope xmlns:soap12="http://www.w3.org/2003/05/soap-envelope">'
            . '<soap12:Body>'
            . '<nfeDistDFeInteresse xmlns="' . self::NS_DIST . '">'
            . '<nfeDadosMsg>'
            . '<distDFeInt xmlns="' . self::NS_NFE . '" versao="1.01">'
            . '<tpAmb>' . ($this->config['ambiente'] === 'producao' ? '1' : '2') . '</tpAmb>'
            . '<cUFAutor>' . $this->getCodigoUF($this->config['uf']) . '</cUFAutor>'
            . '<CNPJ>' . $cnpj . '</CNPJ>'
            . '<distNSU><ultNSU>' . $ultimoNSU . '</ultNSU></distNSU>'
            . '</distDFeInt>'
            . '</nfeDadosMsg>'
            . '</nfeDistDFeInteresse>'
            . '</soap12:Body>'
            . '</soap12:Envelope>';

        $url = $this->getEndpointUrl('NFeDistribuicaoDFe');
        if (!$url) {
            throw new Exception('URL do serviço NFeDistribuicaoDFe não configurada para UF=' . ($this->config['uf'] ?? '?') . ' ambiente=' . ($this->config['ambiente'] ?? '?'));
        }
        $response = $this->soapRequest($url, $xml, 'NFeDistribuicaoDFe', 'dist_dfe');

        if (!$response) {
            throw new Exception('Sem resposta do SEFAZ');
        }

        return $this->processarRespostaDFe($response);
    }

    /**
     * Processar resposta da distribuição DF-e
     */
    private function processarRespostaDFe($xmlResponse) {
        $resultados = ['notas' => [], 'ultNSU' => '0', 'maxNSU' => '0', 'cStat' => '', 'xMotivo' => ''];

        try {
            // Suprimir warnings do SimpleXMLElement (XML inválido não deve gerar saída HTML)
            libxml_use_internal_errors(true);
            $xml = @new SimpleXMLElement($xmlResponse);
            libxml_clear_errors();
            $xml->registerXPathNamespace('nfe', self::NS_NFE);

            $retDist = $xml->xpath('//nfe:retDistDFeInt');
            if (empty($retDist)) return $resultados;

            $ret = $retDist[0];
            $resultados['cStat'] = (string)($ret->cStat ?? '');
            $resultados['xMotivo'] = (string)($ret->xMotivo ?? '');
            $resultados['ultNSU'] = (string)($ret->ultNSU ?? '0');
            $resultados['maxNSU'] = (string)($ret->maxNSU ?? '0');

            // Processar documentos (lote)
            $docs = $ret->xpath('.//nfe:docZip');
            foreach ($docs as $doc) {
                $nsu = (string)$doc['NSU'];
                $schema = (string)($doc['schema'] ?? '');
                $content = base64_decode((string)$doc);
                $content = @gzinflate($content);

                if ($content === false) {
                    // Tentar gzuncompress (outro formato de compressão possível)
                    $content = @gzuncompress(base64_decode((string)$doc));
                }
                if ($content === false) {
                    // Tentar gzdecode (formato gzip com header)
                    $content = @gzdecode(base64_decode((string)$doc));
                }

                if (!$content) continue;

                $nota = $this->parseDFe($content, $nsu, $schema);
                if ($nota) {
                    $resultados['notas'][] = $nota;
                }
            }

            // Atualizar NSU
            if ($resultados['ultNSU'] !== '0') {
                $this->db->query(
                    "UPDATE sefaz_config SET ultimo_nsu = ?, max_nsu = ?, ultima_consulta = NOW() WHERE ativo = 1",
                    [$resultados['ultNSU'], $resultados['maxNSU']]
                );
            }
        } catch (Exception $e) {
            $this->logSefaz('dist_dfe', null, null, 'parse_error', null, null, null, false, $e->getMessage());
        }

        return $resultados;
    }

    /**
     * Parse de um DF-e individual (resumo ou NF-e completa)
     */
    private function parseDFe($xmlContent, $nsu, $schema) {
        try {
            libxml_use_internal_errors(true);
            $xml = @new SimpleXMLElement($xmlContent);
            libxml_clear_errors();
            $xml->registerXPathNamespace('nfe', self::NS_NFE);

            // ResNFe (resumo)
            if (strpos($schema, 'resNFe') !== false || $xml->getName() === 'resNFe') {
                return [
                    'tipo_doc' => 'resumo',
                    'nsu' => $nsu,
                    'chave_acesso' => (string)($xml->chNFe ?? ''),
                    'cnpj_emitente' => (string)($xml->CNPJ ?? ''),
                    'nome_emitente' => (string)($xml->xNome ?? ''),
                    'data_emissao' => (string)($xml->dhEmi ?? ''),
                    'valor_total' => (float)($xml->vNF ?? 0),
                    'tipo_nf' => (string)($xml->tpNF ?? '1'),
                    'sit_nfe' => (string)($xml->cSitNFe ?? '1'),
                    'xml_content' => $xmlContent
                ];
            }

            // NF-e completa (procNFe)
            if ($xml->getName() === 'nfeProc' || $xml->getName() === 'procNFe') {
                $ide = $xml->NFe->infNFe->ide ?? null;
                $emit = $xml->NFe->infNFe->emit ?? null;
                $total = $xml->NFe->infNFe->total->ICMSTot ?? null;

                return [
                    'tipo_doc' => 'nfe_completa',
                    'nsu' => $nsu,
                    'chave_acesso' => preg_replace('/[^0-9]/', '', (string)($xml->NFe->infNFe['Id'] ?? '')),
                    'numero' => (string)($ide->nNF ?? ''),
                    'serie' => (string)($ide->serie ?? ''),
                    'data_emissao' => (string)($ide->dhEmi ?? ''),
                    'cnpj_emitente' => (string)($emit->CNPJ ?? ''),
                    'nome_emitente' => (string)($emit->xNome ?? ''),
                    'fantasia_emitente' => (string)($emit->xFant ?? ''),
                    'uf_emitente' => (string)($emit->enderEmit->UF ?? ''),
                    'valor_produtos' => (float)($total->vProd ?? 0),
                    'valor_frete' => (float)($total->vFrete ?? 0),
                    'valor_desconto' => (float)($total->vDesc ?? 0),
                    'valor_ipi' => (float)($total->vIPI ?? 0),
                    'valor_icms' => (float)($total->vICMS ?? 0),
                    'valor_pis' => (float)($total->vPIS ?? 0),
                    'valor_cofins' => (float)($total->vCOFINS ?? 0),
                    'valor_total' => (float)($total->vNF ?? 0),
                    'xml_content' => $xmlContent,
                    'protocolo' => (string)($xml->protNFe->infProt->nProt ?? '')
                ];
            }

            // Evento de cancelamento
            if (strpos($schema, 'resEvento') !== false || $xml->getName() === 'resEvento') {
                return [
                    'tipo_doc' => 'evento',
                    'nsu' => $nsu,
                    'chave_acesso' => (string)($xml->chNFe ?? ''),
                    'tipo_evento' => (string)($xml->tpEvento ?? ''),
                    'descricao_evento' => (string)($xml->xEvento ?? ''),
                    'xml_content' => $xmlContent
                ];
            }
        } catch (Exception $e) {
            // Ignorar docs que não conseguimos parsear
        }
        return null;
    }

    /**
     * Importar NF-e para o sistema (criar registro + fornecedor se não existir)
     */
    public function importarNFe($dados, $xmlContent = null) {
        $chave = $dados['chave_acesso'] ?? '';
        if (!$chave) throw new Exception('Chave de acesso obrigatória');

        // Verificar se já existe
        $existe = $this->financeiro->getNotaPorChave($chave);
        if ($existe) return $existe['id'];

        // Criar/atualizar fornecedor
        $fornecedorId = null;
        if (!empty($dados['cnpj_emitente'])) {
            $forn = $this->financeiro->getFornecedorPorCNPJ($dados['cnpj_emitente']);
            if ($forn) {
                $fornecedorId = $forn['id'];
            } else {
                $fornecedorId = $this->financeiro->criarFornecedor([
                    'cnpj' => $dados['cnpj_emitente'],
                    'cnpj_cpf' => $dados['cnpj_emitente'],
                    'razao_social' => $dados['nome_emitente'] ?? $dados['cnpj_emitente'],
                    'nome_fantasia' => $dados['fantasia_emitente'] ?? null,
                    'tipo' => 'pj',
                ]);
            }
        }

        $dataEmissao = $dados['data_emissao'] ?? date('Y-m-d');
        if (strlen($dataEmissao) > 10) {
            $dataEmissao = substr($dataEmissao, 0, 10);
        }

        $notaDados = [
            'tipo' => 'nfe',
            'natureza' => 'entrada',
            'numero' => $dados['numero'] ?? null,
            'serie' => $dados['serie'] ?? null,
            'chave_acesso' => $chave,
            'protocolo_autorizacao' => $dados['protocolo'] ?? null,
            'data_emissao' => $dataEmissao,
            'emitente_cnpj' => $dados['cnpj_emitente'] ?? null,
            'emitente_razao' => $dados['nome_emitente'] ?? null,
            'emitente_nome_fantasia' => $dados['fantasia_emitente'] ?? null,
            'emitente_uf' => $dados['uf_emitente'] ?? null,
            'fornecedor_id' => $fornecedorId,
            'valor_produtos' => $dados['valor_produtos'] ?? 0,
            'valor_frete' => $dados['valor_frete'] ?? 0,
            'valor_desconto' => $dados['valor_desconto'] ?? 0,
            'valor_ipi' => $dados['valor_ipi'] ?? 0,
            'valor_icms' => $dados['valor_icms'] ?? 0,
            'valor_pis' => $dados['valor_pis'] ?? 0,
            'valor_cofins' => $dados['valor_cofins'] ?? 0,
            'valor_total' => $dados['valor_total'] ?? 0,
            'xml_conteudo' => $xmlContent,
            'manifesto_status' => 'pendente',
            'status' => 'pendente',
            'criado_por' => $_SESSION['usuario_id'] ?? null
        ];

        $id = $this->financeiro->criarNota($notaDados);

        $this->financeiro->registrarHistorico('nota_fiscal', $id, 'importou_sefaz', null, null, 'Importada via SEFAZ DFe');

        return $id;
    }

    // ================================================================
    // MANIFESTO DO DESTINATÁRIO
    // ================================================================

    /**
     * Registrar evento de manifesto
     * @param string $chaveAcesso Chave de 44 dígitos
     * @param string $tipoEvento ciencia|confirmada|desconhecida|nao_realizada
     * @param string|null $justificativa Obrigatória para desconhecida e nao_realizada
     */
    public function manifestar($chaveAcesso, $tipoEvento, $justificativa = null) {
        if (!$this->isReady()) {
            throw new Exception('SEFAZ não configurada');
        }

        $eventos = [
            'ciencia'         => ['codigo' => '210210', 'descricao' => 'Ciencia da Operacao'],
            'confirmada'      => ['codigo' => '210200', 'descricao' => 'Confirmacao da Operacao'],
            'desconhecida'    => ['codigo' => '210220', 'descricao' => 'Desconhecimento da Operacao'],
            'nao_realizada'   => ['codigo' => '210240', 'descricao' => 'Operacao nao Realizada'],
        ];

        if (!isset($eventos[$tipoEvento])) {
            throw new Exception('Tipo de evento inválido');
        }

        if (in_array($tipoEvento, ['desconhecida', 'nao_realizada']) && empty($justificativa)) {
            throw new Exception('Justificativa obrigatória para este tipo de manifesto');
        }

        $ev = $eventos[$tipoEvento];
        $cnpj = preg_replace('/[^0-9]/', '', $this->config['cnpj_empresa']);
        $sequencia = '1';
        $dataEvento = date('Y-m-d\TH:i:sP');
        $idEvento = 'ID' . $ev['codigo'] . $chaveAcesso . str_pad($sequencia, 2, '0', STR_PAD_LEFT);
        $tpAmb = $this->config['ambiente'] === 'producao' ? '1' : '2';
        $orgao = '91'; // Ambiente Nacional

        // Montar XML do evento
        $detEvento = '<descEvento>' . $ev['descricao'] . '</descEvento>';
        if ($justificativa) {
            $detEvento .= '<xJust>' . htmlspecialchars($justificativa) . '</xJust>';
        }

        $xmlEvento = '<envEvento xmlns="' . self::NS_NFE . '" versao="1.00">'
            . '<idLote>' . str_pad(rand(1, 999999999999999), 15, '0', STR_PAD_LEFT) . '</idLote>'
            . '<evento xmlns="' . self::NS_NFE . '" versao="1.00">'
            . '<infEvento Id="' . $idEvento . '">'
            . '<cOrgao>' . $orgao . '</cOrgao>'
            . '<tpAmb>' . $tpAmb . '</tpAmb>'
            . '<CNPJ>' . $cnpj . '</CNPJ>'
            . '<chNFe>' . $chaveAcesso . '</chNFe>'
            . '<dhEvento>' . $dataEvento . '</dhEvento>'
            . '<tpEvento>' . $ev['codigo'] . '</tpEvento>'
            . '<nSeqEvento>' . $sequencia . '</nSeqEvento>'
            . '<verEvento>1.00</verEvento>'
            . '<detEvento versao="1.00">'
            . $detEvento
            . '</detEvento>'
            . '</infEvento>'
            . '</evento>'
            . '</envEvento>';

        // Assinar XML com certificado
        $xmlAssinado = $this->assinarXML($xmlEvento, 'infEvento');

        // SOAP Envelope
        $soapXml = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soap12:Envelope xmlns:soap12="http://www.w3.org/2003/05/soap-envelope">'
            . '<soap12:Body>'
            . '<nfeRecepcaoEvento xmlns="' . self::NS_EVENTO . '">'
            . '<nfeDadosMsg>' . $xmlAssinado . '</nfeDadosMsg>'
            . '</nfeRecepcaoEvento>'
            . '</soap12:Body>'
            . '</soap12:Envelope>';

        $url = $this->getEndpointUrl('RecepcaoEvento', true); // Manifesto usa AN
        if (!$url) {
            throw new Exception('URL do serviço RecepcaoEvento não configurada');
        }
        $response = $this->soapRequest($url, $soapXml, 'RecepcaoEvento', 'manifesto', $chaveAcesso, $tipoEvento);

        // Processar resposta
        $resultado = $this->processarRespostaManifesto($response, $chaveAcesso, $tipoEvento);

        // Atualizar nota no sistema
        if ($resultado['sucesso']) {
            $nota = $this->financeiro->getNotaPorChave($chaveAcesso);
            if ($nota) {
                $this->financeiro->atualizarNota($nota['id'], [
                    'manifesto_status' => $tipoEvento,
                    'manifesto_data' => date('Y-m-d H:i:s'),
                    'manifesto_protocolo' => $resultado['protocolo'] ?? null,
                    'manifesto_justificativa' => $justificativa
                ]);
                $this->financeiro->registrarHistorico(
                    'nota_fiscal', $nota['id'], 'manifestou',
                    'manifesto_status', null, $tipoEvento
                );
            }
        }

        return $resultado;
    }

    private function processarRespostaManifesto($xmlResponse, $chaveAcesso, $tipoEvento) {
        $resultado = ['sucesso' => false, 'cStat' => '', 'xMotivo' => '', 'protocolo' => null];

        try {
            libxml_use_internal_errors(true);
            $xml = @new SimpleXMLElement($xmlResponse);
            libxml_clear_errors();
            $xml->registerXPathNamespace('nfe', self::NS_NFE);

            $retEvento = $xml->xpath('//nfe:retEvento/nfe:infEvento');
            if (!empty($retEvento)) {
                $inf = $retEvento[0];
                $resultado['cStat'] = (string)($inf->cStat ?? '');
                $resultado['xMotivo'] = (string)($inf->xMotivo ?? '');
                $resultado['protocolo'] = (string)($inf->nProt ?? '');

                // 135 = Evento registrado, 136 = Evento já vinculado
                if (in_array($resultado['cStat'], ['135', '136'])) {
                    $resultado['sucesso'] = true;
                }
            }
        } catch (Exception $e) {
            $resultado['xMotivo'] = 'Erro ao processar resposta: ' . $e->getMessage();
        }

        return $resultado;
    }

    // ================================================================
    // SOAP / ASSINATURA
    // ================================================================

    /**
     * Fazer request SOAP para SEFAZ
     * @param string $url URL do webservice
     * @param string $xmlBody Envelope SOAP completo
     * @param string $servico Nome do serviço (para resolver SoapAction)
     * @param string $logTipo Tipo para log
     * @param string|null $chave Chave de acesso (log)
     * @param string|null $evento Evento (log)
     */
    private function soapRequest($url, $xmlBody, $servico, $logTipo, $chave = null, $evento = null) {
        if (!$this->config['certificado_pfx']) {
            throw new Exception('Certificado digital não configurado. Faça upload do certificado A1 (.pfx) na aba SEFAZ Config.');
        }

        // Extrair cert do PFX
        $certs = [];
        if (!@openssl_pkcs12_read($this->config['certificado_pfx'], $certs, $this->config['certificado_senha'])) {
            throw new Exception('Erro ao ler certificado digital. Verifique se a senha está correta.');
        }

        // Criar arquivos temporários para cert
        $certFile = tempnam(sys_get_temp_dir(), 'sefaz_cert_');
        $keyFile = tempnam(sys_get_temp_dir(), 'sefaz_key_');
        file_put_contents($certFile, $certs['cert']);
        file_put_contents($keyFile, $certs['pkey']);

        try {
            $ch = curl_init();

            // Content-Type com SoapAction (obrigatório para SOAP 1.2 SEFAZ)
            $soapAction = $this->getSoapAction($servico);
            $contentType = 'application/soap+xml; charset=utf-8';
            if ($soapAction) {
                $contentType .= '; action="' . $soapAction . '"';
            }

            $curlOpts = [
                CURLOPT_URL => $url,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $xmlBody,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 60,
                CURLOPT_CONNECTTIMEOUT => 30,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: ' . $contentType,
                    'Content-Length: ' . strlen($xmlBody),
                ],
                CURLOPT_SSLCERT => $certFile,
                CURLOPT_SSLKEY => $keyFile,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2,
            ];

            // Resolver CA bundle para XAMPP/Windows
            $caBundle = $this->findCaBundle();
            if ($caBundle) {
                $curlOpts[CURLOPT_CAINFO] = $caBundle;
            }

            curl_setopt_array($ch, $curlOpts);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            $curlErrno = curl_errno($ch);
            curl_close($ch);

            // Log detalhado
            $sucesso = $httpCode >= 200 && $httpCode < 300 && !empty($response);
            $this->logSefaz($logTipo, null, $chave, $evento, $xmlBody, $response, $httpCode, $sucesso, $curlError ?: null);

            if ($curlErrno) {
                $msgErro = $this->traduzirErroCurl($curlErrno, $curlError, $url);
                throw new Exception($msgErro);
            }

            if ($httpCode >= 400) {
                throw new Exception("SEFAZ retornou HTTP {$httpCode}. URL: {$url}");
            }

            return $response;
        } finally {
            @unlink($certFile);
            @unlink($keyFile);
        }
    }

    /**
     * Encontrar CA bundle para cURL (compatível XAMPP Windows)
     */
    private function findCaBundle() {
        // 1. Verificar se já configurado no php.ini
        $iniCaInfo = ini_get('curl.cainfo');
        if ($iniCaInfo && file_exists($iniCaInfo)) {
            return $iniCaInfo;
        }

        // 2. Buscar em locais conhecidos
        $paths = [
            __DIR__ . '/../../config/cacert.pem',            // projeto local
            'C:/xampp/php/extras/ssl/cacert.pem',            // XAMPP PHP
            'C:/xampp/apache/bin/curl-ca-bundle.crt',        // XAMPP Apache
            'C:/xampp/php/cacert.pem',                       // XAMPP raiz
            '/etc/ssl/certs/ca-certificates.crt',            // Debian/Ubuntu
            '/etc/pki/tls/certs/ca-bundle.crt',              // CentOS/RHEL
            '/usr/share/ssl/certs/ca-bundle.crt',            // Older
        ];

        foreach ($paths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        return null; // Deixa cURL usar o default do sistema
    }

    /**
     * Traduzir erros cURL para mensagens amigáveis em PT-BR
     */
    private function traduzirErroCurl($errno, $error, $url) {
        $host = parse_url($url, PHP_URL_HOST);

        switch ($errno) {
            case CURLE_SSL_CERTPROBLEM: // 58
                return "Erro no certificado digital do cliente. Verifique se o certificado A1 (.pfx) está válido e a senha correta.";
            case CURLE_SSL_CACERT: // 60
            case CURLE_SSL_CACERT_BADFILE: // 77
                return "Erro de verificação SSL. O certificado da SEFAZ não pôde ser verificado. "
                     . "Baixe o cacert.pem em https://curl.se/ca/cacert.pem e coloque em config/cacert.pem. "
                     . "Detalhes: {$error}";
            case CURLE_COULDNT_RESOLVE_HOST: // 6
                return "Não foi possível resolver o host '{$host}'. Verifique a conexão com a internet e o DNS.";
            case CURLE_COULDNT_CONNECT: // 7
                return "Não foi possível conectar ao servidor SEFAZ ({$host}). Verifique se o firewall permite conexões HTTPS de saída.";
            case CURLE_OPERATION_TIMEDOUT: // 28
                return "Timeout na conexão com SEFAZ ({$host}). O servidor demorou para responder. Tente novamente.";
            case CURLE_SSL_CONNECT_ERROR: // 35
                return "Erro na negociação SSL/TLS com SEFAZ ({$host}). Verifique se o PHP suporta TLS 1.2+. Detalhes: {$error}";
            case CURLE_PEER_FAILED_VERIFICATION: // 51
                return "A verificação do servidor SEFAZ falhou ({$host}). O certificado do servidor pode ter expirado.";
            default:
                return "Erro na comunicação com SEFAZ ({$host}): [{$errno}] {$error}";
        }
    }

    /**
     * Testar conexão com SEFAZ (sem certificado necessário)
     * Tenta resolver DNS e conectar ao endpoint StatusServico
     */
    public function testarConexao() {
        $ambiente = $this->config['ambiente'] ?? 'homologacao';
        $uf = strtoupper($this->config['uf'] ?? 'GO');
        $resultado = [
            'ambiente' => $ambiente,
            'uf' => $uf,
            'testes' => [],
            'ok' => true
        ];

        // Testar endpoints AN
        $urlDist = $this->getEndpointUrl('NFeDistribuicaoDFe');
        $resultado['testes']['NFeDistribuicaoDFe'] = $this->pingUrl($urlDist);

        // Testar endpoint RecepcaoEvento (AN)
        $urlEvento = $this->getEndpointUrl('RecepcaoEvento', true);
        $resultado['testes']['RecepcaoEvento_AN'] = $this->pingUrl($urlEvento);

        // Testar endpoint per-UF StatusServico (vai falhar sem certificado — esperado)
        $urlStatus = $this->getEndpointUrl('NFeStatusServico');
        $testeStatus = $this->pingUrl($urlStatus);
        if (!$testeStatus['ok'] && !empty($this->config['certificado_pfx'])) {
            // Se tem certificado, tenta com ele
            $testeStatus = $this->pingUrlComCert($urlStatus);
        } elseif (!$testeStatus['ok']) {
            $testeStatus['erro'] = ($testeStatus['erro'] ?? '') . ' (Serviços per-UF exigem certificado digital — faça upload do .pfx)';
        }
        $resultado['testes']['NFeStatusServico_UF'] = $testeStatus;

        // Verificar certificado
        $resultado['testes']['certificado'] = [
            'url' => null,
            'ok' => !empty($this->config['certificado_pfx']),
            'tempo' => 0,
            'erro' => empty($this->config['certificado_pfx']) ? 'Certificado digital não configurado' : null,
        ];

        // Verificar CA bundle
        $caBundle = $this->findCaBundle();
        $resultado['testes']['ca_bundle'] = [
            'url' => $caBundle,
            'ok' => $caBundle !== null,
            'tempo' => 0,
            'erro' => $caBundle ? null : 'CA bundle não encontrado. Baixe de https://curl.se/ca/cacert.pem e salve em config/cacert.pem',
        ];

        // Verificar se algum teste falhou
        foreach ($resultado['testes'] as $teste) {
            if (!$teste['ok']) {
                $resultado['ok'] = false;
                break;
            }
        }

        return $resultado;
    }

    /**
     * Ping básico em uma URL (DNS resolve + TCP connect)
     */
    private function pingUrl($url) {
        if (!$url) {
            return ['url' => null, 'ok' => false, 'tempo' => 0, 'erro' => 'URL não configurada'];
        }

        $inicio = microtime(true);
        $ch = curl_init();
        $opts = [
            CURLOPT_URL => $url,
            CURLOPT_NOBODY => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2,
        ];
        $caBundle = $this->findCaBundle();
        if ($caBundle) $opts[CURLOPT_CAINFO] = $caBundle;
        curl_setopt_array($ch, $opts);
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        $curlErrno = curl_errno($ch);
        curl_close($ch);
        $tempo = round((microtime(true) - $inicio) * 1000);

        // HTTP 200, 405, 500 = servidor respondeu (SEFAZ retorna 405 para GET, 500 para request inválido)
        $ok = ($curlErrno === 0) || in_array($httpCode, [200, 405, 500, 301, 302]);

        return [
            'url' => $url,
            'ok' => $ok,
            'http_code' => $httpCode,
            'tempo' => $tempo . 'ms',
            'erro' => $curlErrno ? "[{$curlErrno}] {$curlError}" : null,
        ];
    }

    /**
     * Ping com certificado digital (para serviços per-UF que exigem mTLS)
     */
    private function pingUrlComCert($url) {
        if (!$url || empty($this->config['certificado_pfx'])) {
            return ['url' => $url, 'ok' => false, 'tempo' => 0, 'erro' => 'Certificado não configurado'];
        }

        $certs = [];
        if (!@openssl_pkcs12_read($this->config['certificado_pfx'], $certs, $this->config['certificado_senha'])) {
            return ['url' => $url, 'ok' => false, 'tempo' => 0, 'erro' => 'Erro ao ler certificado'];
        }

        $certFile = tempnam(sys_get_temp_dir(), 'sefaz_ping_cert_');
        $keyFile = tempnam(sys_get_temp_dir(), 'sefaz_ping_key_');
        file_put_contents($certFile, $certs['cert']);
        file_put_contents($keyFile, $certs['pkey']);

        $inicio = microtime(true);
        try {
            $ch = curl_init();
            $opts = [
                CURLOPT_URL => $url,
                CURLOPT_NOBODY => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_SSLCERT => $certFile,
                CURLOPT_SSLKEY => $keyFile,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2,
            ];
            $caBundle = $this->findCaBundle();
            if ($caBundle) $opts[CURLOPT_CAINFO] = $caBundle;
            curl_setopt_array($ch, $opts);
            curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            $curlErrno = curl_errno($ch);
            curl_close($ch);
            $tempo = round((microtime(true) - $inicio) * 1000);

            $ok = ($curlErrno === 0) || in_array($httpCode, [200, 403, 405, 500]);

            return [
                'url' => $url,
                'ok' => $ok,
                'http_code' => $httpCode,
                'tempo' => $tempo . 'ms',
                'erro' => $curlErrno ? "[{$curlErrno}] {$curlError}" : null,
            ];
        } finally {
            @unlink($certFile);
            @unlink($keyFile);
        }
    }

    /**
     * Assinar XML com certificado A1
     */
    private function assinarXML($xml, $tagParaAssinar) {
        if (!$this->config['certificado_pfx']) {
            return $xml; // Retorna sem assinatura se não tem cert
        }

        $certs = [];
        if (!openssl_pkcs12_read($this->config['certificado_pfx'], $certs, $this->config['certificado_senha'])) {
            throw new Exception('Erro ao ler certificado para assinatura');
        }

        // Criar DOMDocument para assinatura XMLDSig
        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = false;
        $doc->loadXML($xml);

        // Encontrar o nó a assinar
        $nodes = $doc->getElementsByTagName($tagParaAssinar);
        if ($nodes->length === 0) return $xml;

        $node = $nodes->item(0);
        $id = $node->getAttribute('Id');

        // Canonizar
        $canonical = $node->C14N(false, false, null, null);
        $digestValue = base64_encode(hash('sha1', $canonical, true));

        // Criar SignedInfo
        $signedInfoXml = '<SignedInfo xmlns="http://www.w3.org/2000/09/xmldsig#">'
            . '<CanonicalizationMethod Algorithm="http://www.w3.org/TR/2001/REC-xml-c14n-20010315" />'
            . '<SignatureMethod Algorithm="http://www.w3.org/2000/09/xmldsig#rsa-sha1" />'
            . '<Reference URI="#' . $id . '">'
            . '<Transforms>'
            . '<Transform Algorithm="http://www.w3.org/2000/09/xmldsig#enveloped-signature" />'
            . '<Transform Algorithm="http://www.w3.org/TR/2001/REC-xml-c14n-20010315" />'
            . '</Transforms>'
            . '<DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1" />'
            . '<DigestValue>' . $digestValue . '</DigestValue>'
            . '</Reference>'
            . '</SignedInfo>';

        // Canonizar SignedInfo e assinar
        $signedInfoDoc = new DOMDocument('1.0', 'UTF-8');
        $signedInfoDoc->loadXML($signedInfoXml);
        $signedInfoCanonical = $signedInfoDoc->documentElement->C14N(false, false, null, null);

        $privateKey = openssl_pkey_get_private($certs['pkey']);
        openssl_sign($signedInfoCanonical, $signature, $privateKey, OPENSSL_ALGO_SHA1);
        $signatureValue = base64_encode($signature);

        // X509 cert
        $x509 = preg_replace('/-----[A-Z ]+-----/', '', $certs['cert']);
        $x509 = str_replace(["\r", "\n"], '', trim($x509));

        // Montar Signature completa
        $signatureXml = '<Signature xmlns="http://www.w3.org/2000/09/xmldsig#">'
            . $signedInfoXml
            . '<SignatureValue>' . $signatureValue . '</SignatureValue>'
            . '<KeyInfo><X509Data><X509Certificate>' . $x509 . '</X509Certificate></X509Data></KeyInfo>'
            . '</Signature>';

        // Inserir assinatura no XML antes do fechamento do nó pai do nó assinado
        $parentNode = $node->parentNode;
        $signatureDoc = new DOMDocument();
        $signatureDoc->loadXML($signatureXml);
        $signatureNode = $doc->importNode($signatureDoc->documentElement, true);
        $parentNode->insertBefore($signatureNode, $node->nextSibling);

        return $doc->saveXML($doc->documentElement);
    }

    /**
     * Log de operações SEFAZ
     */
    private function logSefaz($tipo, $nsu, $chave, $evento, $request, $response, $httpCode, $sucesso, $mensagem = null) {
        try {
            $this->db->insert('sefaz_log', [
                'tipo' => $tipo,
                'nsu' => $nsu,
                'chave_acesso' => $chave,
                'evento' => $evento,
                'request_xml' => $request ? substr($request, 0, 65000) : null,
                'response_xml' => $response ? substr($response, 0, 65000) : null,
                'http_code' => $httpCode,
                'sucesso' => $sucesso ? 1 : 0,
                'mensagem' => $mensagem
            ]);
        } catch (Exception $e) {
            // Não falhar por causa do log
        }
    }

    /**
     * Obter logs SEFAZ
     */
    public function getLogs($limite = 50) {
        return $this->db->fetchAll(
            "SELECT id, tipo, nsu, chave_acesso, evento, http_code, sucesso, mensagem, criado_em
             FROM sefaz_log ORDER BY id DESC LIMIT ?",
            [$limite]
        );
    }

    /**
     * Código UF para SEFAZ
     */
    private function getCodigoUF($uf) {
        $codigos = [
            'AC' => '12', 'AL' => '27', 'AP' => '16', 'AM' => '13', 'BA' => '29',
            'CE' => '23', 'DF' => '53', 'ES' => '32', 'GO' => '52', 'MA' => '21',
            'MT' => '51', 'MS' => '50', 'MG' => '31', 'PA' => '15', 'PB' => '25',
            'PR' => '41', 'PE' => '26', 'PI' => '22', 'RJ' => '33', 'RN' => '24',
            'RS' => '43', 'RO' => '11', 'RR' => '14', 'SC' => '42', 'SP' => '35',
            'SE' => '28', 'TO' => '17'
        ];
        return $codigos[strtoupper($uf)] ?? '29';
    }

    /**
     * Importar XML manualmente (upload)
     */
    public function importarXML($xmlContent) {
        libxml_use_internal_errors(true);
        $xml = @new SimpleXMLElement($xmlContent);
        $errors = libxml_get_errors();
        libxml_clear_errors();
        if ($errors) {
            throw new Exception('XML inválido: erro ao processar o arquivo');
        }
        $xml->registerXPathNamespace('nfe', self::NS_NFE);

        $infNFe = $xml->xpath('//nfe:infNFe');
        if (empty($infNFe)) {
            throw new Exception('XML inválido: não contém infNFe');
        }

        $inf = $infNFe[0];
        $ide = $inf->ide;
        $emit = $inf->emit;
        $total = $inf->total->ICMSTot;
        $prot = $xml->xpath('//nfe:protNFe/nfe:infProt/nfe:nProt');

        $chave = preg_replace('/[^0-9]/', '', (string)($inf['Id'] ?? ''));

        $dados = [
            'chave_acesso' => $chave,
            'numero' => (string)($ide->nNF ?? ''),
            'serie' => (string)($ide->serie ?? ''),
            'data_emissao' => (string)($ide->dhEmi ?? ''),
            'cnpj_emitente' => (string)($emit->CNPJ ?? ''),
            'nome_emitente' => (string)($emit->xNome ?? ''),
            'fantasia_emitente' => (string)($emit->xFant ?? ''),
            'uf_emitente' => (string)($emit->enderEmit->UF ?? ''),
            'valor_produtos' => (float)($total->vProd ?? 0),
            'valor_frete' => (float)($total->vFrete ?? 0),
            'valor_desconto' => (float)($total->vDesc ?? 0),
            'valor_ipi' => (float)($total->vIPI ?? 0),
            'valor_icms' => (float)($total->vICMS ?? 0),
            'valor_pis' => (float)($total->vPIS ?? 0),
            'valor_cofins' => (float)($total->vCOFINS ?? 0),
            'valor_total' => (float)($total->vNF ?? 0),
            'protocolo' => !empty($prot) ? (string)$prot[0] : null,
        ];

        return $this->importarNFe($dados, $xmlContent);
    }
}
