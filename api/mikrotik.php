<?php
/**
 * API: MikroTik
 * Endpoints para gestão de dispositivos MikroTik RouterOS
 */
session_start();
require_once __DIR__ . '/../config/app.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    jsonResponse(['error' => 'Não autenticado'], 401);
}

if (!in_array($_SESSION['usuario_tipo'], ['admin', 'tecnico'])) {
    jsonResponse(['error' => 'Sem permissão'], 403);
}

require_once __DIR__ . '/../app/models/Mikrotik.php';
$mk = new Mikrotik();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Para requests JSON (Content-Type: application/json), extrair action do body
$_jsonBody = null;
if (empty($action) && $method === 'POST') {
    $_rawBody = file_get_contents('php://input');
    $_jsonBody = json_decode($_rawBody, true);
    if ($_jsonBody && isset($_jsonBody['action'])) {
        $action = $_jsonBody['action'];
    }
}

// ==========================================
//  SSE STREAMING — IA MikroTik Assistant
// ==========================================
if ($action === 'ia_stream') {
    $input = $_jsonBody ?: json_decode(file_get_contents('php://input'), true) ?: [];

    $userMsg = trim($input['mensagem'] ?? '');
    $deviceId = $input['device_id'] ?? null;
    $modelo = $input['modelo'] ?? '';

    if (empty($userMsg)) {
        header('Content-Type: application/json');
        jsonResponse(['error' => 'Mensagem vazia'], 400);
    }

    // Coletar contexto do dispositivo se disponível
    $deviceContext = '';
    if ($deviceId) {
        try {
            $overview = $mk->getOverview($deviceId);
            $device = $mk->getDispositivo($deviceId);
            $natRules = $mk->getNatRules($deviceId);
            $fwRules = $mk->getFirewallRules($deviceId);
            $dhcpLeases = $mk->getDHCPLeases($deviceId);
            $routes = $mk->getRoutes($deviceId);
            $queues = $mk->getQueues($deviceId);
            $ips = $mk->getIPAddresses($deviceId);
            $interfaces = $mk->getInterfaces($deviceId);

            $deviceContext = "\n\n=== DADOS DO DISPOSITIVO MIKROTIK ===\n";
            $deviceContext .= "Nome: " . ($device['nome'] ?? 'N/A') . "\n";
            $deviceContext .= "Host: " . ($device['host'] ?? 'N/A') . ":" . ($device['porta'] ?? '8728') . "\n";
            $deviceContext .= "Identity: " . ($overview['identity'] ?? 'N/A') . "\n";
            $deviceContext .= "Board: " . ($overview['board_name'] ?? 'N/A') . "\n";
            $deviceContext .= "RouterOS: " . ($overview['version'] ?? 'N/A') . "\n";
            $deviceContext .= "Uptime: " . ($overview['uptime'] ?? 'N/A') . "\n";
            $deviceContext .= "CPU: " . ($overview['cpu_load'] ?? '?') . "% (" . ($overview['cpu_count'] ?? '?') . " cores)\n";
            $deviceContext .= "RAM: " . round(($overview['used_memory'] ?? 0) / 1048576, 1) . " MB / " . round(($overview['total_memory'] ?? 0) / 1048576, 1) . " MB (" . ($overview['memory_pct'] ?? '?') . "%)\n";
            $deviceContext .= "HDD: " . round(($overview['used_hdd'] ?? 0) / 1048576, 1) . " MB / " . round(($overview['total_hdd'] ?? 0) / 1048576, 1) . " MB (" . ($overview['hdd_pct'] ?? '?') . "%)\n";
            $deviceContext .= "Interfaces UP/Total: " . ($overview['interfaces_up'] ?? '?') . "/" . ($overview['interfaces_total'] ?? '?') . "\n";
            $deviceContext .= "DHCP Leases: " . ($overview['dhcp_leases'] ?? '?') . "\n";
            $deviceContext .= "Regras NAT: " . ($overview['nat_rules'] ?? '?') . "\n";
            $deviceContext .= "Regras Firewall: " . ($overview['firewall_rules'] ?? '?') . "\n";
            $deviceContext .= "Simple Queues: " . ($overview['queues'] ?? '?') . "\n";

            // IPs
            if (!empty($ips)) {
                $deviceContext .= "\n--- Endereços IP ---\n";
                foreach ($ips as $ip) {
                    $deviceContext .= "  " . ($ip['address'] ?? '') . " em " . ($ip['interface'] ?? '') . " (" . ($ip['disabled'] === 'true' ? 'desabilitado' : 'ativo') . ")\n";
                }
            }

            // Interfaces (resumo)
            if (!empty($interfaces)) {
                $deviceContext .= "\n--- Interfaces ---\n";
                foreach (array_slice($interfaces, 0, 20) as $iface) {
                    $status = ($iface['disabled'] ?? '') === 'true' ? 'DISABLED' : (($iface['running'] ?? '') === 'true' ? 'RUNNING' : 'DOWN');
                    $deviceContext .= "  " . ($iface['name'] ?? '?') . " [" . ($iface['type'] ?? '?') . "] MAC:" . ($iface['mac-address'] ?? '-') . " $status\n";
                }
            }

            // NAT (resumo)
            if (!empty($natRules)) {
                $deviceContext .= "\n--- Regras NAT (primeiras 30) ---\n";
                foreach (array_slice($natRules, 0, 30) as $r) {
                    $st = ($r['disabled'] ?? '') === 'true' ? ' [DISABLED]' : '';
                    $deviceContext .= "  chain=" . ($r['chain'] ?? '') . " action=" . ($r['action'] ?? '') . " proto=" . ($r['protocol'] ?? 'any') . " dst-port=" . ($r['dst-port'] ?? '*') . " to=" . ($r['to-addresses'] ?? '') . ":" . ($r['to-ports'] ?? '') . $st . " " . ($r['comment'] ?? '') . "\n";
                }
            }

            // Firewall (resumo)
            if (!empty($fwRules)) {
                $deviceContext .= "\n--- Firewall Filter (primeiras 20) ---\n";
                foreach (array_slice($fwRules, 0, 20) as $r) {
                    $st = ($r['disabled'] ?? '') === 'true' ? ' [DISABLED]' : '';
                    $deviceContext .= "  chain=" . ($r['chain'] ?? '') . " action=" . ($r['action'] ?? '') . " proto=" . ($r['protocol'] ?? 'any') . " dst-port=" . ($r['dst-port'] ?? '*') . $st . " " . ($r['comment'] ?? '') . "\n";
                }
            }

            // DHCP (resumo)
            if (!empty($dhcpLeases)) {
                $deviceContext .= "\n--- DHCP Leases (primeiras 30) ---\n";
                foreach (array_slice($dhcpLeases, 0, 30) as $l) {
                    $deviceContext .= "  " . ($l['address'] ?? '') . " MAC:" . ($l['mac-address'] ?? '') . " host=" . ($l['host-name'] ?? '') . " " . ($l['status'] ?? '') . " " . (($l['dynamic'] ?? '') === 'true' ? 'dynamic' : 'static') . "\n";
                }
            }

            // Routes (resumo)
            if (!empty($routes)) {
                $deviceContext .= "\n--- Rotas (primeiras 15) ---\n";
                foreach (array_slice($routes, 0, 15) as $r) {
                    $deviceContext .= "  dst=" . ($r['dst-address'] ?? '') . " gw=" . ($r['gateway'] ?? '') . " dist=" . ($r['distance'] ?? '') . " " . (($r['active'] ?? '') === 'true' ? 'ACTIVE' : '') . "\n";
                }
            }

            // Queues (resumo)
            if (!empty($queues)) {
                $deviceContext .= "\n--- Simple Queues ---\n";
                foreach (array_slice($queues, 0, 15) as $q) {
                    $deviceContext .= "  " . ($q['name'] ?? '') . " target=" . ($q['target'] ?? '') . " max=" . ($q['max-limit'] ?? '') . "\n";
                }
            }

        } catch (Exception $e) {
            $deviceContext = "\n[Erro ao coletar dados do dispositivo: " . $e->getMessage() . "]\n";
        }
    }

    $systemPrompt = "Você é um especialista em MikroTik RouterOS integrado ao sistema Oracle X. "
        . "Você tem acesso completo aos dados do roteador do usuário e deve analisar a configuração, "
        . "sugerir melhorias de segurança, performance e boas práticas. "
        . "Responda em português brasileiro. "
        . "Quando sugerir comandos, use a sintaxe do RouterOS CLI. "
        . "Seja objetivo mas detalhado quando necessário. "
        . "Se o usuário pedir para fazer algo, explique o que deve ser feito e forneça os comandos RouterOS. "
        . "Analise regras NAT, firewall, DHCP, rotas, queues, interfaces e dê recomendações práticas. "
        . "Avise sobre problemas de segurança que identificar (portas abertas, regras permissivas, falta de firewall, etc). "
        . "Formate com Markdown para melhor leitura."
        . $deviceContext;

    $messages = [
        ['role' => 'system', 'content' => $systemPrompt],
        ['role' => 'user', 'content' => $userMsg],
    ];

    // Incluir histórico se enviado
    if (!empty($input['historico']) && is_array($input['historico'])) {
        $hist = [];
        $hist[] = ['role' => 'system', 'content' => $systemPrompt];
        foreach (array_slice($input['historico'], -10) as $h) {
            if (in_array($h['role'] ?? '', ['user', 'assistant'])) {
                $hist[] = ['role' => $h['role'], 'content' => $h['content']];
            }
        }
        $hist[] = ['role' => 'user', 'content' => $userMsg];
        $messages = $hist;
    }

    // SSE headers
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    header('Connection: keep-alive');
    header('X-Accel-Buffering: no');
    session_write_close();
    set_time_limit(0);
    ini_set('max_execution_time', '0');
    @ini_set('output_buffering', 'off');
    @ini_set('zlib.output_compression', false);
    if (function_exists('apache_setenv')) @apache_setenv('no-gzip', '1');
    while (ob_get_level()) ob_end_flush();

    require_once __DIR__ . '/../app/models/IA.php';
    $ia = new IA();

    $fullContent = '';
    $lastHeartbeat = microtime(true);

    // Helper para flush seguro
    $sseFlush = function() {
        if (ob_get_level() > 0) @ob_flush();
        @flush();
    };

    // Evento inicial
    echo "data: " . json_encode(['status' => 'connected', 'model' => $modelo ?: 'default']) . "\n\n";
    $sseFlush();

    // Usar modelo configurado centralmente para rede/MikroTik
    $modeloRede = $modelo ?: $ia->getConfig('modelo_rede', null);

    try {
        $ia->chatStream($messages, function($chunk) use (&$fullContent, &$lastHeartbeat, $sseFlush) {
            // Heartbeat durante carregamento do modelo
            if (!empty($chunk['heartbeat'])) {
                $now = microtime(true);
                if ($now - $lastHeartbeat >= 2) {
                    echo "data: " . json_encode(['loading' => true, 'status' => 'loading_model']) . "\n\n";
                    $sseFlush();
                    $lastHeartbeat = $now;
                }
                return;
            }
            if (isset($chunk['message']['content'])) {
                $content = $chunk['message']['content'];
                $fullContent .= $content;
                echo "data: " . json_encode(['content' => $content, 'done' => false]) . "\n\n";
                $sseFlush();
            }
            if (!empty($chunk['done'])) {
                // Tokens info
                $tokens = ($chunk['prompt_eval_count'] ?? 0) + ($chunk['eval_count'] ?? 0);
                echo "data: " . json_encode(['done' => true, 'full_content' => $fullContent, 'tokens' => $tokens]) . "\n\n";
                $sseFlush();
            }
        }, $modeloRede);

        // Fallback done event (caso o callback done não tenha disparado)
        if ($fullContent && !str_contains($fullContent, '"done":true')) {
            echo "data: " . json_encode(['done' => true, 'full_content' => $fullContent]) . "\n\n";
            $sseFlush();
        }

    } catch (Exception $e) {
        echo "data: " . json_encode(['error' => $e->getMessage(), 'done' => true]) . "\n\n";
        $sseFlush();
    }

    exit;
}

try {
    // ==========================================
    //  GET
    // ==========================================
    if ($method === 'GET') {
        switch ($action) {

            case 'listar_dispositivos':
                $data = $mk->listarDispositivos();
                jsonResponse(['success' => true, 'data' => $data]);
                break;

            case 'get_dispositivo':
                if (empty($_GET['id'])) jsonResponse(['error' => 'ID obrigatório'], 400);
                $device = $mk->getDispositivo($_GET['id']);
                if (!$device) jsonResponse(['error' => 'Dispositivo não encontrado'], 404);
                unset($device['senha']);
                jsonResponse(['success' => true, 'data' => $device]);
                break;

            case 'overview':
                if (empty($_GET['device_id'])) jsonResponse(['error' => 'ID obrigatório'], 400);
                $data = $mk->getOverview($_GET['device_id']);
                jsonResponse(['success' => true, 'data' => $data]);
                break;

            case 'interfaces':
                if (empty($_GET['device_id'])) jsonResponse(['error' => 'ID obrigatório'], 400);
                $data = $mk->getInterfaces($_GET['device_id']);
                jsonResponse(['success' => true, 'data' => $data]);
                break;

            case 'ip_addresses':
                if (empty($_GET['device_id'])) jsonResponse(['error' => 'ID obrigatório'], 400);
                $data = $mk->getIPAddresses($_GET['device_id']);
                jsonResponse(['success' => true, 'data' => $data]);
                break;

            case 'nat_rules':
                if (empty($_GET['device_id'])) jsonResponse(['error' => 'ID obrigatório'], 400);
                $data = $mk->getNatRules($_GET['device_id']);
                jsonResponse(['success' => true, 'data' => $data]);
                break;

            case 'firewall_rules':
                if (empty($_GET['device_id'])) jsonResponse(['error' => 'ID obrigatório'], 400);
                $data = $mk->getFirewallRules($_GET['device_id']);
                jsonResponse(['success' => true, 'data' => $data]);
                break;

            case 'dhcp_leases':
                if (empty($_GET['device_id'])) jsonResponse(['error' => 'ID obrigatório'], 400);
                $data = $mk->getDHCPLeases($_GET['device_id']);
                jsonResponse(['success' => true, 'data' => $data]);
                break;

            case 'dhcp_servers':
                if (empty($_GET['device_id'])) jsonResponse(['error' => 'ID obrigatório'], 400);
                $data = $mk->getDHCPServers($_GET['device_id']);
                jsonResponse(['success' => true, 'data' => $data]);
                break;

            case 'routes':
                if (empty($_GET['device_id'])) jsonResponse(['error' => 'ID obrigatório'], 400);
                $data = $mk->getRoutes($_GET['device_id']);
                jsonResponse(['success' => true, 'data' => $data]);
                break;

            case 'queues':
                if (empty($_GET['device_id'])) jsonResponse(['error' => 'ID obrigatório'], 400);
                $data = $mk->getQueues($_GET['device_id']);
                jsonResponse(['success' => true, 'data' => $data]);
                break;

            case 'arp':
                if (empty($_GET['device_id'])) jsonResponse(['error' => 'ID obrigatório'], 400);
                $data = $mk->getArpList($_GET['device_id']);
                jsonResponse(['success' => true, 'data' => $data]);
                break;

            case 'dns':
                if (empty($_GET['device_id'])) jsonResponse(['error' => 'ID obrigatório'], 400);
                $settings = $mk->getDnsSettings($_GET['device_id']);
                $statics = $mk->getDnsStatic($_GET['device_id']);
                jsonResponse(['success' => true, 'data' => ['settings' => $settings, 'static' => $statics]]);
                break;

            case 'logs':
                if (empty($_GET['device_id'])) jsonResponse(['error' => 'ID obrigatório'], 400);
                $limit = (int)($_GET['limit'] ?? 100);
                $data = $mk->getLogs($_GET['device_id'], $limit);
                jsonResponse(['success' => true, 'data' => $data]);
                break;

            case 'users':
                if (empty($_GET['device_id'])) jsonResponse(['error' => 'ID obrigatório'], 400);
                $data = $mk->getUsers($_GET['device_id']);
                jsonResponse(['success' => true, 'data' => $data]);
                break;

            case 'active_connections':
                if (empty($_GET['device_id'])) jsonResponse(['error' => 'ID obrigatório'], 400);
                $data = $mk->getActiveConnections($_GET['device_id']);
                jsonResponse(['success' => true, 'data' => ['count' => count($data)]]);
                break;

            default:
                jsonResponse(['error' => 'Ação GET não reconhecida: ' . $action], 400);
        }
    }

    // ==========================================
    //  POST
    // ==========================================
    elseif ($method === 'POST') {
        $input = $_jsonBody ?: json_decode(file_get_contents('php://input'), true);
        if (!$input) $input = $_POST;
        $action = $input['action'] ?? $action;

        switch ($action) {

            // ===== DISPOSITIVOS =====
            case 'criar_dispositivo':
                $required = ['nome', 'host', 'usuario', 'senha'];
                foreach ($required as $f) {
                    if (empty($input[$f])) jsonResponse(['error' => "Campo '{$f}' é obrigatório"], 400);
                }
                $id = $mk->criarDispositivo($input);
                jsonResponse(['success' => true, 'id' => $id, 'message' => 'Dispositivo cadastrado com sucesso!']);
                break;

            case 'atualizar_dispositivo':
                if (empty($input['id'])) jsonResponse(['error' => 'ID obrigatório'], 400);
                $required = ['nome', 'host', 'usuario'];
                foreach ($required as $f) {
                    if (empty($input[$f])) jsonResponse(['error' => "Campo '{$f}' é obrigatório"], 400);
                }
                $mk->atualizarDispositivo($input['id'], $input);
                jsonResponse(['success' => true, 'message' => 'Dispositivo atualizado!']);
                break;

            case 'excluir_dispositivo':
                if (empty($input['id'])) jsonResponse(['error' => 'ID obrigatório'], 400);
                $mk->excluirDispositivo($input['id']);
                jsonResponse(['success' => true, 'message' => 'Dispositivo excluído!']);
                break;

            case 'testar_conexao':
                if (empty($input['host']) || empty($input['usuario'])) {
                    jsonResponse(['error' => 'Host e usuário são obrigatórios'], 400);
                }
                $result = $mk->testarConexao($input);
                jsonResponse(['success' => true, 'data' => $result]);
                break;

            // ===== INTERFACES =====
            case 'enable_interface':
                if (empty($input['device_id']) || !isset($input['interface_id'])) jsonResponse(['error' => 'Parâmetros inválidos'], 400);
                $mk->enableInterface($input['device_id'], $input['interface_id']);
                jsonResponse(['success' => true, 'message' => 'Interface habilitada!']);
                break;

            case 'disable_interface':
                if (empty($input['device_id']) || !isset($input['interface_id'])) jsonResponse(['error' => 'Parâmetros inválidos'], 400);
                $mk->disableInterface($input['device_id'], $input['interface_id']);
                jsonResponse(['success' => true, 'message' => 'Interface desabilitada!']);
                break;

            // ===== NAT =====
            case 'add_nat':
                if (empty($input['device_id']) || empty($input['chain']) || empty($input['action_type'])) {
                    jsonResponse(['error' => 'Parâmetros obrigatórios: chain e action'], 400);
                }
                $input['action'] = $input['action_type'];
                $mk->addNatRule($input['device_id'], $input);
                jsonResponse(['success' => true, 'message' => 'Regra NAT adicionada!']);
                break;

            case 'update_nat':
                if (empty($input['device_id']) || empty($input['rule_id'])) jsonResponse(['error' => 'Parâmetros inválidos'], 400);
                if (isset($input['action_type'])) $input['action'] = $input['action_type'];
                $mk->updateNatRule($input['device_id'], $input['rule_id'], $input);
                jsonResponse(['success' => true, 'message' => 'Regra NAT atualizada!']);
                break;

            case 'remove_nat':
                if (empty($input['device_id']) || empty($input['rule_id'])) jsonResponse(['error' => 'Parâmetros inválidos'], 400);
                $mk->removeNatRule($input['device_id'], $input['rule_id']);
                jsonResponse(['success' => true, 'message' => 'Regra NAT removida!']);
                break;

            case 'enable_nat':
                if (empty($input['device_id']) || empty($input['rule_id'])) jsonResponse(['error' => 'Parâmetros inválidos'], 400);
                $mk->enableNatRule($input['device_id'], $input['rule_id']);
                jsonResponse(['success' => true, 'message' => 'Regra NAT habilitada!']);
                break;

            case 'disable_nat':
                if (empty($input['device_id']) || empty($input['rule_id'])) jsonResponse(['error' => 'Parâmetros inválidos'], 400);
                $mk->disableNatRule($input['device_id'], $input['rule_id']);
                jsonResponse(['success' => true, 'message' => 'Regra NAT desabilitada!']);
                break;

            // ===== FIREWALL =====
            case 'add_firewall':
                if (empty($input['device_id']) || empty($input['chain']) || empty($input['action_type'])) {
                    jsonResponse(['error' => 'Parâmetros obrigatórios: chain e action'], 400);
                }
                $input['action'] = $input['action_type'];
                $mk->addFirewallRule($input['device_id'], $input);
                jsonResponse(['success' => true, 'message' => 'Regra de firewall adicionada!']);
                break;

            case 'update_firewall':
                if (empty($input['device_id']) || empty($input['rule_id'])) jsonResponse(['error' => 'Parâmetros inválidos'], 400);
                if (isset($input['action_type'])) $input['action'] = $input['action_type'];
                $mk->updateFirewallRule($input['device_id'], $input['rule_id'], $input);
                jsonResponse(['success' => true, 'message' => 'Regra de firewall atualizada!']);
                break;

            case 'remove_firewall':
                if (empty($input['device_id']) || empty($input['rule_id'])) jsonResponse(['error' => 'Parâmetros inválidos'], 400);
                $mk->removeFirewallRule($input['device_id'], $input['rule_id']);
                jsonResponse(['success' => true, 'message' => 'Regra de firewall removida!']);
                break;

            case 'enable_firewall':
                if (empty($input['device_id']) || empty($input['rule_id'])) jsonResponse(['error' => 'Parâmetros inválidos'], 400);
                $mk->enableFirewallRule($input['device_id'], $input['rule_id']);
                jsonResponse(['success' => true, 'message' => 'Regra habilitada!']);
                break;

            case 'disable_firewall':
                if (empty($input['device_id']) || empty($input['rule_id'])) jsonResponse(['error' => 'Parâmetros inválidos'], 400);
                $mk->disableFirewallRule($input['device_id'], $input['rule_id']);
                jsonResponse(['success' => true, 'message' => 'Regra desabilitada!']);
                break;

            // ===== DHCP =====
            case 'add_dhcp_lease':
                if (empty($input['device_id']) || empty($input['address']) || empty($input['mac-address'])) {
                    jsonResponse(['error' => 'IP e MAC são obrigatórios'], 400);
                }
                $mk->addDHCPLease($input['device_id'], $input);
                jsonResponse(['success' => true, 'message' => 'Lease DHCP adicionada!']);
                break;

            case 'remove_dhcp_lease':
                if (empty($input['device_id']) || empty($input['lease_id'])) jsonResponse(['error' => 'Parâmetros inválidos'], 400);
                $mk->removeDHCPLease($input['device_id'], $input['lease_id']);
                jsonResponse(['success' => true, 'message' => 'Lease removida!']);
                break;

            case 'make_dhcp_static':
                if (empty($input['device_id']) || empty($input['lease_id'])) jsonResponse(['error' => 'Parâmetros inválidos'], 400);
                $mk->makeDHCPStatic($input['device_id'], $input['lease_id']);
                jsonResponse(['success' => true, 'message' => 'Lease convertida para estática!']);
                break;

            // ===== QUEUES =====
            case 'add_queue':
                if (empty($input['device_id']) || empty($input['name']) || empty($input['target'])) {
                    jsonResponse(['error' => 'Nome e target são obrigatórios'], 400);
                }
                $mk->addQueue($input['device_id'], $input);
                jsonResponse(['success' => true, 'message' => 'Queue adicionada!']);
                break;

            case 'update_queue':
                if (empty($input['device_id']) || empty($input['queue_id'])) jsonResponse(['error' => 'Parâmetros inválidos'], 400);
                $mk->updateQueue($input['device_id'], $input['queue_id'], $input);
                jsonResponse(['success' => true, 'message' => 'Queue atualizada!']);
                break;

            case 'remove_queue':
                if (empty($input['device_id']) || empty($input['queue_id'])) jsonResponse(['error' => 'Parâmetros inválidos'], 400);
                $mk->removeQueue($input['device_id'], $input['queue_id']);
                jsonResponse(['success' => true, 'message' => 'Queue removida!']);
                break;

            case 'enable_queue':
                if (empty($input['device_id']) || empty($input['queue_id'])) jsonResponse(['error' => 'Parâmetros inválidos'], 400);
                $mk->enableQueue($input['device_id'], $input['queue_id']);
                jsonResponse(['success' => true, 'message' => 'Queue habilitada!']);
                break;

            case 'disable_queue':
                if (empty($input['device_id']) || empty($input['queue_id'])) jsonResponse(['error' => 'Parâmetros inválidos'], 400);
                $mk->disableQueue($input['device_id'], $input['queue_id']);
                jsonResponse(['success' => true, 'message' => 'Queue desabilitada!']);
                break;

            // ===== ROUTES =====
            case 'add_route':
                if (empty($input['device_id']) || empty($input['dst-address']) || empty($input['gateway'])) {
                    jsonResponse(['error' => 'Destino e gateway são obrigatórios'], 400);
                }
                $mk->addRoute($input['device_id'], $input);
                jsonResponse(['success' => true, 'message' => 'Rota adicionada!']);
                break;

            case 'remove_route':
                if (empty($input['device_id']) || empty($input['route_id'])) jsonResponse(['error' => 'Parâmetros inválidos'], 400);
                $mk->removeRoute($input['device_id'], $input['route_id']);
                jsonResponse(['success' => true, 'message' => 'Rota removida!']);
                break;

            // ===== DNS =====
            case 'flush_dns':
                if (empty($input['device_id'])) jsonResponse(['error' => 'ID obrigatório'], 400);
                $mk->flushDnsCache($input['device_id']);
                jsonResponse(['success' => true, 'message' => 'Cache DNS limpo!']);
                break;

            // ===== TOOLS =====
            case 'ping':
                if (empty($input['device_id']) || empty($input['address'])) {
                    jsonResponse(['error' => 'Endereço é obrigatório'], 400);
                }
                $count = (int)($input['count'] ?? 4);
                $data = $mk->ping($input['device_id'], $input['address'], $count);
                jsonResponse(['success' => true, 'data' => $data]);
                break;

            case 'reboot':
                if (empty($input['device_id'])) jsonResponse(['error' => 'ID obrigatório'], 400);
                $mk->reboot($input['device_id']);
                jsonResponse(['success' => true, 'message' => 'Dispositivo reiniciando...']);
                break;

            default:
                jsonResponse(['error' => 'Ação POST não reconhecida: ' . $action], 400);
        }
    }
    else {
        jsonResponse(['error' => 'Método não suportado'], 405);
    }

} catch (Exception $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}
