<?php
/**
 * API: Proxmox
 * Endpoints para gestão de servidores Proxmox VE
 */
session_start();
require_once __DIR__ . '/../config/app.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    jsonResponse(['error' => 'Não autenticado'], 401);
}

// Apenas admin e técnicos podem acessar
if (!in_array($_SESSION['usuario_tipo'], ['admin', 'tecnico'])) {
    jsonResponse(['error' => 'Sem permissão'], 403);
}

require_once __DIR__ . '/../app/models/Proxmox.php';
$proxmox = new Proxmox();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    if ($method === 'GET') {
        switch ($action) {
            // ====== SERVIDORES ======
            case 'listar_servidores':
                $servidores = $proxmox->listarServidores();
                // Não retornar senhas
                foreach ($servidores as &$s) {
                    unset($s['senha'], $s['token_secret']);
                }
                jsonResponse(['success' => true, 'data' => $servidores]);
                break;

            case 'get_servidor':
                $id = (int)($_GET['id'] ?? 0);
                if (!$id) jsonResponse(['error' => 'ID obrigatório'], 400);
                $server = $proxmox->getServidor($id);
                if (!$server) jsonResponse(['error' => 'Servidor não encontrado'], 404);
                // Não retornar dados sensíveis
                unset($server['senha'], $server['senha_decrypted'], $server['token_secret'], $server['token_secret_decrypted']);
                jsonResponse(['success' => true, 'data' => $server]);
                break;

            case 'testar_conexao':
                $id = (int)($_GET['id'] ?? 0);
                if (!$id) jsonResponse(['error' => 'ID obrigatório'], 400);
                $resultado = $proxmox->testarConexao($id);
                jsonResponse(['success' => true, 'data' => $resultado]);
                break;

            // ====== OVERVIEW ======
            case 'overview':
                $serverId = (int)($_GET['server_id'] ?? 0);
                if (!$serverId) jsonResponse(['error' => 'server_id obrigatório'], 400);
                $overview = $proxmox->getServerOverview($serverId);
                jsonResponse(['success' => true, 'data' => $overview]);
                break;

            // ====== NODES ======
            case 'nodes':
                $serverId = (int)($_GET['server_id'] ?? 0);
                if (!$serverId) jsonResponse(['error' => 'server_id obrigatório'], 400);
                $nodes = $proxmox->getNodes($serverId);
                jsonResponse(['success' => true, 'data' => $nodes]);
                break;

            case 'node_status':
                $serverId = (int)($_GET['server_id'] ?? 0);
                $node = $_GET['node'] ?? '';
                if (!$serverId || !$node) jsonResponse(['error' => 'server_id e node obrigatórios'], 400);
                $status = $proxmox->getNodeStatus($serverId, $node);
                jsonResponse(['success' => true, 'data' => $status]);
                break;

            // ====== VMs ======
            case 'vms':
                $serverId = (int)($_GET['server_id'] ?? 0);
                $node = $_GET['node'] ?? '';
                if (!$serverId || !$node) jsonResponse(['error' => 'server_id e node obrigatórios'], 400);
                $vms = $proxmox->getVMs($serverId, $node);
                jsonResponse(['success' => true, 'data' => $vms]);
                break;

            case 'vm_status':
                $serverId = (int)($_GET['server_id'] ?? 0);
                $node = $_GET['node'] ?? '';
                $vmid = (int)($_GET['vmid'] ?? 0);
                if (!$serverId || !$node || !$vmid) jsonResponse(['error' => 'Parâmetros incompletos'], 400);
                $status = $proxmox->getVMStatus($serverId, $node, $vmid);
                $config = $proxmox->getVMConfig($serverId, $node, $vmid);
                jsonResponse(['success' => true, 'data' => ['status' => $status, 'config' => $config]]);
                break;

            // ====== CONTAINERS ======
            case 'containers':
                $serverId = (int)($_GET['server_id'] ?? 0);
                $node = $_GET['node'] ?? '';
                if (!$serverId || !$node) jsonResponse(['error' => 'server_id e node obrigatórios'], 400);
                $cts = $proxmox->getContainers($serverId, $node);
                jsonResponse(['success' => true, 'data' => $cts]);
                break;

            case 'container_status':
                $serverId = (int)($_GET['server_id'] ?? 0);
                $node = $_GET['node'] ?? '';
                $vmid = (int)($_GET['vmid'] ?? 0);
                if (!$serverId || !$node || !$vmid) jsonResponse(['error' => 'Parâmetros incompletos'], 400);
                $status = $proxmox->getContainerStatus($serverId, $node, $vmid);
                $config = $proxmox->getContainerConfig($serverId, $node, $vmid);
                jsonResponse(['success' => true, 'data' => ['status' => $status, 'config' => $config]]);
                break;

            // ====== SNAPSHOTS ======
            case 'snapshots':
                $serverId = (int)($_GET['server_id'] ?? 0);
                $node = $_GET['node'] ?? '';
                $vmid = (int)($_GET['vmid'] ?? 0);
                $type = $_GET['type'] ?? 'qemu';
                if (!$serverId || !$node || !$vmid) jsonResponse(['error' => 'Parâmetros incompletos'], 400);
                $snaps = $proxmox->getSnapshots($serverId, $node, $vmid, $type);
                jsonResponse(['success' => true, 'data' => $snaps]);
                break;

            // ====== STORAGE ======
            case 'storage':
                $serverId = (int)($_GET['server_id'] ?? 0);
                $node = $_GET['node'] ?? '';
                if (!$serverId || !$node) jsonResponse(['error' => 'server_id e node obrigatórios'], 400);
                $storage = $proxmox->getStorage($serverId, $node);
                jsonResponse(['success' => true, 'data' => $storage]);
                break;

            // ====== TASKS ======
            case 'tasks':
                $serverId = (int)($_GET['server_id'] ?? 0);
                $node = $_GET['node'] ?? '';
                if (!$serverId || !$node) jsonResponse(['error' => 'server_id e node obrigatórios'], 400);
                $tasks = $proxmox->getTasks($serverId, $node, 30);
                jsonResponse(['success' => true, 'data' => $tasks]);
                break;

            // ====== NETWORK ======
            case 'networks':
                $serverId = (int)($_GET['server_id'] ?? 0);
                $node = $_GET['node'] ?? '';
                if (!$serverId || !$node) jsonResponse(['error' => 'server_id e node obrigatórios'], 400);
                $networks = $proxmox->getNodeNetworks($serverId, $node);
                jsonResponse(['success' => true, 'data' => $networks]);
                break;

            // ====== RESOURCES ======
            case 'resources':
                $serverId = (int)($_GET['server_id'] ?? 0);
                $type = $_GET['type'] ?? null;
                if (!$serverId) jsonResponse(['error' => 'server_id obrigatório'], 400);
                $resources = $proxmox->getClusterResources($serverId, $type);
                jsonResponse(['success' => true, 'data' => $resources]);
                break;

            // ====== LOGS ======
            case 'logs':
                $serverId = $_GET['server_id'] ?? null;
                $logs = $proxmox->listarLogs($serverId ? (int)$serverId : null, 100);
                jsonResponse(['success' => true, 'data' => $logs]);
                break;

            // ====== NEXT VMID ======
            case 'next_vmid':
                $serverId = (int)($_GET['server_id'] ?? 0);
                if (!$serverId) jsonResponse(['error' => 'server_id obrigatório'], 400);
                $nextId = $proxmox->getNextVMID($serverId);
                jsonResponse(['success' => true, 'data' => $nextId]);
                break;

            // ====== TEMPLATES ======
            case 'templates':
                $serverId = (int)($_GET['server_id'] ?? 0);
                $node = $_GET['node'] ?? '';
                $storage = $_GET['storage'] ?? 'local';
                if (!$serverId || !$node) jsonResponse(['error' => 'server_id e node obrigatórios'], 400);
                $templates = $proxmox->getTemplates($serverId, $node, $storage);
                jsonResponse(['success' => true, 'data' => $templates]);
                break;

            default:
                jsonResponse(['error' => 'Ação GET não reconhecida: ' . $action], 400);
        }
    }

    elseif ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) $input = $_POST;
        $action = $input['action'] ?? $action;

        switch ($action) {
            // ====== SERVIDORES CRUD ======
            case 'criar_servidor':
                $required = ['nome', 'host', 'usuario'];
                foreach ($required as $field) {
                    if (empty($input[$field])) jsonResponse(['error' => "Campo '{$field}' obrigatório"], 400);
                }
                $input['criado_por'] = $_SESSION['usuario_id'];
                $id = $proxmox->criarServidor($input);
                $proxmox->registrarLog($id, $_SESSION['usuario_id'], 'criar_servidor', [
                    'detalhes' => 'Servidor criado: ' . $input['nome'],
                    'resultado' => 'sucesso'
                ]);
                jsonResponse(['success' => true, 'id' => $id, 'message' => 'Servidor cadastrado']);
                break;

            case 'atualizar_servidor':
                $id = (int)($input['id'] ?? 0);
                if (!$id) jsonResponse(['error' => 'ID obrigatório'], 400);
                $proxmox->atualizarServidor($id, $input);
                $proxmox->registrarLog($id, $_SESSION['usuario_id'], 'atualizar_servidor', [
                    'detalhes' => 'Servidor atualizado: ' . ($input['nome'] ?? ''),
                    'resultado' => 'sucesso'
                ]);
                jsonResponse(['success' => true, 'message' => 'Servidor atualizado']);
                break;

            case 'excluir_servidor':
                $id = (int)($input['id'] ?? 0);
                if (!$id) jsonResponse(['error' => 'ID obrigatório'], 400);
                $proxmox->excluirServidor($id);
                jsonResponse(['success' => true, 'message' => 'Servidor excluído']);
                break;

            // ====== VM ACTIONS ======
            case 'vm_start':
            case 'vm_stop':
            case 'vm_shutdown':
            case 'vm_reset':
            case 'vm_reboot':
            case 'vm_suspend':
            case 'vm_resume':
                $serverId = (int)($input['server_id'] ?? 0);
                $node = $input['node'] ?? '';
                $vmid = (int)($input['vmid'] ?? 0);
                $vmNome = $input['vm_nome'] ?? '';
                if (!$serverId || !$node || !$vmid) jsonResponse(['error' => 'Parâmetros incompletos'], 400);

                $actionMap = [
                    'vm_start' => 'startVM',
                    'vm_stop' => 'stopVM',
                    'vm_shutdown' => 'shutdownVM',
                    'vm_reset' => 'resetVM',
                    'vm_reboot' => 'rebootVM',
                    'vm_suspend' => 'suspendVM',
                    'vm_resume' => 'resumeVM'
                ];

                $methodName = $actionMap[$action];
                $result = $proxmox->$methodName($serverId, $node, $vmid);

                $proxmox->registrarLog($serverId, $_SESSION['usuario_id'], $action, [
                    'vmid' => $vmid,
                    'vm_nome' => $vmNome,
                    'node' => $node,
                    'detalhes' => "Ação {$action} em VM {$vmid}",
                    'resultado' => 'sucesso'
                ]);

                $labels = [
                    'vm_start' => 'iniciada', 'vm_stop' => 'parada',
                    'vm_shutdown' => 'desligamento solicitado', 'vm_reset' => 'resetada',
                    'vm_reboot' => 'reiniciada', 'vm_suspend' => 'suspensa', 'vm_resume' => 'resumida'
                ];

                jsonResponse(['success' => true, 'message' => "VM {$vmid} {$labels[$action]}", 'data' => $result]);
                break;

            // ====== CONTAINER ACTIONS ======
            case 'ct_start':
            case 'ct_stop':
            case 'ct_shutdown':
            case 'ct_reboot':
                $serverId = (int)($input['server_id'] ?? 0);
                $node = $input['node'] ?? '';
                $vmid = (int)($input['vmid'] ?? 0);
                $vmNome = $input['vm_nome'] ?? '';
                if (!$serverId || !$node || !$vmid) jsonResponse(['error' => 'Parâmetros incompletos'], 400);

                $ctActionMap = [
                    'ct_start' => 'startContainer',
                    'ct_stop' => 'stopContainer',
                    'ct_shutdown' => 'shutdownContainer',
                    'ct_reboot' => 'rebootContainer'
                ];

                $methodName = $ctActionMap[$action];
                $result = $proxmox->$methodName($serverId, $node, $vmid);

                $proxmox->registrarLog($serverId, $_SESSION['usuario_id'], $action, [
                    'vmid' => $vmid, 'vm_nome' => $vmNome, 'node' => $node,
                    'detalhes' => "Ação {$action} em Container {$vmid}",
                    'resultado' => 'sucesso'
                ]);

                jsonResponse(['success' => true, 'message' => "Container {$vmid} ação executada", 'data' => $result]);
                break;

            // ====== SNAPSHOTS ======
            case 'criar_snapshot':
                $serverId = (int)($input['server_id'] ?? 0);
                $node = $input['node'] ?? '';
                $vmid = (int)($input['vmid'] ?? 0);
                $type = $input['type'] ?? 'qemu';
                $snapname = $input['snapname'] ?? '';
                $description = $input['description'] ?? '';
                if (!$serverId || !$node || !$vmid) jsonResponse(['error' => 'Parâmetros incompletos'], 400);

                $result = $proxmox->createSnapshot($serverId, $node, $vmid, $type, $snapname, $description);

                $proxmox->registrarLog($serverId, $_SESSION['usuario_id'], 'criar_snapshot', [
                    'vmid' => $vmid, 'node' => $node,
                    'detalhes' => "Snapshot '{$snapname}' criado para {$type} {$vmid}",
                    'resultado' => 'sucesso'
                ]);

                jsonResponse(['success' => true, 'message' => 'Snapshot criado', 'data' => $result]);
                break;

            case 'excluir_snapshot':
                $serverId = (int)($input['server_id'] ?? 0);
                $node = $input['node'] ?? '';
                $vmid = (int)($input['vmid'] ?? 0);
                $snapname = $input['snapname'] ?? '';
                $type = $input['type'] ?? 'qemu';
                if (!$serverId || !$node || !$vmid || !$snapname) jsonResponse(['error' => 'Parâmetros incompletos'], 400);

                $result = $proxmox->deleteSnapshot($serverId, $node, $vmid, $snapname, $type);

                $proxmox->registrarLog($serverId, $_SESSION['usuario_id'], 'excluir_snapshot', [
                    'vmid' => $vmid, 'node' => $node,
                    'detalhes' => "Snapshot '{$snapname}' excluído",
                    'resultado' => 'sucesso'
                ]);

                jsonResponse(['success' => true, 'message' => 'Snapshot excluído', 'data' => $result]);
                break;

            case 'rollback_snapshot':
                $serverId = (int)($input['server_id'] ?? 0);
                $node = $input['node'] ?? '';
                $vmid = (int)($input['vmid'] ?? 0);
                $snapname = $input['snapname'] ?? '';
                $type = $input['type'] ?? 'qemu';
                if (!$serverId || !$node || !$vmid || !$snapname) jsonResponse(['error' => 'Parâmetros incompletos'], 400);

                $result = $proxmox->rollbackSnapshot($serverId, $node, $vmid, $snapname, $type);

                $proxmox->registrarLog($serverId, $_SESSION['usuario_id'], 'rollback_snapshot', [
                    'vmid' => $vmid, 'node' => $node,
                    'detalhes' => "Rollback para snapshot '{$snapname}'",
                    'resultado' => 'sucesso'
                ]);

                jsonResponse(['success' => true, 'message' => 'Rollback executado', 'data' => $result]);
                break;

            // ====== BACKUP ======
            case 'backup_vm':
                $serverId = (int)($input['server_id'] ?? 0);
                $node = $input['node'] ?? '';
                $vmid = (int)($input['vmid'] ?? 0);
                $type = $input['type'] ?? 'qemu';
                $storage = $input['storage'] ?? 'local';
                $mode = $input['mode'] ?? 'snapshot';
                $compress = $input['compress'] ?? 'zstd';
                if (!$serverId || !$node || !$vmid) jsonResponse(['error' => 'Parâmetros incompletos'], 400);

                $result = $proxmox->backupVM($serverId, $node, $vmid, $type, $storage, $mode, $compress);

                $proxmox->registrarLog($serverId, $_SESSION['usuario_id'], 'backup_vm', [
                    'vmid' => $vmid, 'node' => $node,
                    'detalhes' => "Backup de {$type} {$vmid} no storage '{$storage}'",
                    'resultado' => 'sucesso'
                ]);

                jsonResponse(['success' => true, 'message' => 'Backup iniciado', 'data' => $result]);
                break;

            // ====== UPDATE CONFIG ======
            case 'update_vm_config':
                $serverId = (int)($input['server_id'] ?? 0);
                $node = $input['node'] ?? '';
                $vmid = (int)($input['vmid'] ?? 0);
                $type = $input['type'] ?? 'qemu';
                $config = $input['config'] ?? [];
                if (!$serverId || !$node || !$vmid || empty($config)) jsonResponse(['error' => 'Parâmetros incompletos'], 400);

                $result = $proxmox->updateVMConfig($serverId, $node, $vmid, $config, $type);

                $proxmox->registrarLog($serverId, $_SESSION['usuario_id'], 'update_config', [
                    'vmid' => $vmid, 'node' => $node,
                    'detalhes' => "Config atualizada: " . json_encode($config),
                    'resultado' => 'sucesso'
                ]);

                jsonResponse(['success' => true, 'message' => 'Configuração atualizada', 'data' => $result]);
                break;

            // ====== CLONE ======
            case 'clone_vm':
                $serverId = (int)($input['server_id'] ?? 0);
                $node = $input['node'] ?? '';
                $vmid = (int)($input['vmid'] ?? 0);
                $newid = (int)($input['newid'] ?? 0);
                $name = $input['name'] ?? '';
                $type = $input['type'] ?? 'qemu';
                if (!$serverId || !$node || !$vmid || !$newid) jsonResponse(['error' => 'Parâmetros incompletos'], 400);

                $result = $proxmox->cloneVM($serverId, $node, $vmid, $newid, $name, true, $type);

                $proxmox->registrarLog($serverId, $_SESSION['usuario_id'], 'clone_vm', [
                    'vmid' => $vmid, 'node' => $node,
                    'detalhes' => "VM {$vmid} clonada para {$newid}" . ($name ? " ({$name})" : ''),
                    'resultado' => 'sucesso'
                ]);

                jsonResponse(['success' => true, 'message' => 'Clone iniciado', 'data' => $result]);
                break;

            // ====== DELETE VM ======
            case 'delete_vm':
                if ($_SESSION['usuario_tipo'] !== 'admin') {
                    jsonResponse(['error' => 'Apenas administradores podem excluir VMs'], 403);
                }
                $serverId = (int)($input['server_id'] ?? 0);
                $node = $input['node'] ?? '';
                $vmid = (int)($input['vmid'] ?? 0);
                $type = $input['type'] ?? 'qemu';
                if (!$serverId || !$node || !$vmid) jsonResponse(['error' => 'Parâmetros incompletos'], 400);

                $result = $proxmox->deleteVM($serverId, $node, $vmid, $type);

                $proxmox->registrarLog($serverId, $_SESSION['usuario_id'], 'delete_vm', [
                    'vmid' => $vmid, 'node' => $node,
                    'detalhes' => "VM {$vmid} excluída ({$type})",
                    'resultado' => 'sucesso'
                ]);

                jsonResponse(['success' => true, 'message' => 'VM excluída', 'data' => $result]);
                break;

            // ====== CREATE VM ======
            case 'create_vm':
                if ($_SESSION['usuario_tipo'] !== 'admin') {
                    jsonResponse(['error' => 'Apenas administradores podem criar VMs'], 403);
                }
                $serverId = (int)($input['server_id'] ?? 0);
                $node = $input['node'] ?? '';
                $vmid = (int)($input['vmid'] ?? 0);
                $config = $input['config'] ?? [];
                if (!$serverId || !$node || !$vmid || empty($config)) jsonResponse(['error' => 'Parâmetros incompletos'], 400);

                $result = $proxmox->createVM($serverId, $node, $vmid, $config);

                $proxmox->registrarLog($serverId, $_SESSION['usuario_id'], 'create_vm', [
                    'vmid' => $vmid, 'node' => $node,
                    'vm_nome' => $config['name'] ?? '',
                    'detalhes' => "VM {$vmid} criada: " . ($config['name'] ?? '') . " ({$config['cores']}CPU/{$config['memory']}MB RAM)",
                    'resultado' => 'sucesso'
                ]);

                jsonResponse(['success' => true, 'message' => "VM {$vmid} criada com sucesso", 'data' => $result]);
                break;

            // ====== CREATE CONTAINER ======
            case 'create_ct':
                if ($_SESSION['usuario_tipo'] !== 'admin') {
                    jsonResponse(['error' => 'Apenas administradores podem criar containers'], 403);
                }
                $serverId = (int)($input['server_id'] ?? 0);
                $node = $input['node'] ?? '';
                $vmid = (int)($input['vmid'] ?? 0);
                $config = $input['config'] ?? [];
                if (!$serverId || !$node || !$vmid || empty($config)) jsonResponse(['error' => 'Parâmetros incompletos'], 400);

                $result = $proxmox->createContainer($serverId, $node, $vmid, $config);

                $proxmox->registrarLog($serverId, $_SESSION['usuario_id'], 'create_ct', [
                    'vmid' => $vmid, 'node' => $node,
                    'vm_nome' => $config['hostname'] ?? '',
                    'detalhes' => "Container {$vmid} criado: " . ($config['hostname'] ?? ''),
                    'resultado' => 'sucesso'
                ]);

                jsonResponse(['success' => true, 'message' => "Container {$vmid} criado com sucesso", 'data' => $result]);
                break;

            // ====== MIGRATE ======
            case 'migrate_vm':
                $serverId = (int)($input['server_id'] ?? 0);
                $node = $input['node'] ?? '';
                $vmid = (int)($input['vmid'] ?? 0);
                $targetNode = $input['target_node'] ?? '';
                $online = $input['online'] ?? true;
                $type = $input['type'] ?? 'qemu';
                if (!$serverId || !$node || !$vmid || !$targetNode) jsonResponse(['error' => 'Parâmetros incompletos'], 400);

                $result = $proxmox->migrateVM($serverId, $node, $vmid, $targetNode, $online, $type);

                $proxmox->registrarLog($serverId, $_SESSION['usuario_id'], 'migrate_vm', [
                    'vmid' => $vmid, 'node' => $node,
                    'detalhes' => "{$type} {$vmid} migrada de {$node} para {$targetNode}",
                    'resultado' => 'sucesso'
                ]);

                jsonResponse(['success' => true, 'message' => "Migração iniciada para {$targetNode}", 'data' => $result]);
                break;

            // ====== RESIZE DISK ======
            case 'resize_disk':
                $serverId = (int)($input['server_id'] ?? 0);
                $node = $input['node'] ?? '';
                $vmid = (int)($input['vmid'] ?? 0);
                $disk = $input['disk'] ?? 'scsi0';
                $size = $input['size'] ?? '';
                $type = $input['type'] ?? 'qemu';
                if (!$serverId || !$node || !$vmid || !$size) jsonResponse(['error' => 'Parâmetros incompletos'], 400);

                $result = $proxmox->resizeDisk($serverId, $node, $vmid, $disk, $size, $type);

                $proxmox->registrarLog($serverId, $_SESSION['usuario_id'], 'resize_disk', [
                    'vmid' => $vmid, 'node' => $node,
                    'detalhes' => "Disco {$disk} redimensionado para {$size} em {$type} {$vmid}",
                    'resultado' => 'sucesso'
                ]);

                jsonResponse(['success' => true, 'message' => 'Disco redimensionado', 'data' => $result]);
                break;

            // ====== ADD DISK ======
            case 'add_disk':
                $serverId = (int)($input['server_id'] ?? 0);
                $node = $input['node'] ?? '';
                $vmid = (int)($input['vmid'] ?? 0);
                $type = $input['type'] ?? 'qemu';
                $diskConfig = $input['disk_config'] ?? [];
                if (!$serverId || !$node || !$vmid || empty($diskConfig)) jsonResponse(['error' => 'Parâmetros incompletos'], 400);

                $result = $proxmox->updateVMConfig($serverId, $node, $vmid, $diskConfig, $type);

                $proxmox->registrarLog($serverId, $_SESSION['usuario_id'], 'add_disk', [
                    'vmid' => $vmid, 'node' => $node,
                    'detalhes' => "Disco adicionado em {$type} {$vmid}: " . json_encode($diskConfig),
                    'resultado' => 'sucesso'
                ]);

                jsonResponse(['success' => true, 'message' => 'Disco adicionado', 'data' => $result]);
                break;

            // ====== ADD/EDIT NETWORK ======
            case 'update_network':
                $serverId = (int)($input['server_id'] ?? 0);
                $node = $input['node'] ?? '';
                $vmid = (int)($input['vmid'] ?? 0);
                $type = $input['type'] ?? 'qemu';
                $netConfig = $input['net_config'] ?? [];
                if (!$serverId || !$node || !$vmid || empty($netConfig)) jsonResponse(['error' => 'Parâmetros incompletos'], 400);

                $result = $proxmox->updateVMConfig($serverId, $node, $vmid, $netConfig, $type);

                $proxmox->registrarLog($serverId, $_SESSION['usuario_id'], 'update_network', [
                    'vmid' => $vmid, 'node' => $node,
                    'detalhes' => "Rede atualizada em {$type} {$vmid}: " . json_encode($netConfig),
                    'resultado' => 'sucesso'
                ]);

                jsonResponse(['success' => true, 'message' => 'Rede atualizada', 'data' => $result]);
                break;

            // ====== ISO LIST ======
            case 'list_isos':
                $serverId = (int)($input['server_id'] ?? 0);
                $node = $input['node'] ?? '';
                $storage = $input['storage'] ?? 'local';
                if (!$serverId || !$node) jsonResponse(['error' => 'Parâmetros incompletos'], 400);

                $result = $proxmox->getStorageContent($serverId, $node, $storage);
                // Filter ISOs only
                $isos = array_filter($result, fn($item) => ($item['content'] ?? '') === 'iso');
                jsonResponse(['success' => true, 'data' => array_values($isos)]);
                break;

            // ====== CONSOLE ======
            case 'console':
                $serverId = (int)($input['server_id'] ?? 0);
                $node = $input['node'] ?? '';
                $vmid = (int)($input['vmid'] ?? 0);
                $type = $input['type'] ?? 'qemu';
                if (!$serverId || !$node || !$vmid) jsonResponse(['error' => 'Parâmetros incompletos'], 400);

                $consoleUrl = $proxmox->getConsoleUrl($serverId, $node, $vmid, $type);
                jsonResponse(['success' => true, 'data' => ['url' => $consoleUrl]]);
                break;

            default:
                jsonResponse(['error' => 'Ação POST não reconhecida: ' . $action], 400);
        }
    }

    else {
        jsonResponse(['error' => 'Método não suportado'], 405);
    }

} catch (Exception $e) {
    // Log de erro
    try {
        $serverId = (int)($input['server_id'] ?? $_GET['server_id'] ?? 0);
        if ($serverId) {
            $proxmox->registrarLog($serverId, $_SESSION['usuario_id'], $action ?? 'erro', [
                'detalhes' => $e->getMessage(),
                'resultado' => 'erro'
            ]);
        }
    } catch (Exception $ignored) {}

    jsonResponse(['error' => $e->getMessage()], 500);
}
