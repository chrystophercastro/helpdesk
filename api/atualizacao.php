<?php
/**
 * API: Atualização do Sistema / Migration Runner
 * Acesso restrito a admin
 */
session_start();
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/models/MigrationManager.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn() || $_SESSION['usuario_tipo'] !== 'admin') {
    jsonResponse(['erro' => 'Acesso restrito a administradores'], 403);
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    $manager = new MigrationManager();

    switch ($action) {
        /* ─── GET ─── */
        case 'overview':
            $overview = $manager->getOverview();
            $overview['system_info']    = $manager->getSystemInfo();
            $overview['health_checks']  = $manager->getHealthChecks();
            jsonResponse($overview);
            break;

        case 'historico':
            jsonResponse(['historico' => $manager->getHistory(200)]);
            break;

        case 'system_info':
            jsonResponse($manager->getSystemInfo());
            break;

        case 'health_checks':
            jsonResponse($manager->getHealthChecks());
            break;

        /* ─── POST ─── */
        case 'executar':
            $data = json_decode(file_get_contents('php://input'), true);
            $migration = $data['migration'] ?? '';
            if (!$migration) jsonResponse(['erro' => 'Migration não informada'], 400);
            $result = $manager->runMigration($migration);
            $result['migration'] = $migration;
            jsonResponse($result);
            break;

        case 'executar_todas':
            $results = $manager->runAllPending();
            jsonResponse([
                'resultados' => $results,
                'total'      => count($results),
                'sucesso'    => count(array_filter($results, fn($r) => $r['status'] === 'sucesso')),
                'erros'      => count(array_filter($results, fn($r) => $r['status'] === 'erro'))
            ]);
            break;

        case 'marcar_todas':
            $count = $manager->markAllAsExecuted();
            jsonResponse(['marcadas' => $count, 'mensagem' => "$count migration(s) marcadas como executadas"]);
            break;

        case 'resetar':
            $data = json_decode(file_get_contents('php://input'), true);
            $migration = $data['migration'] ?? '';
            if (!$migration) jsonResponse(['erro' => 'Migration não informada'], 400);
            $manager->resetMigration($migration);
            jsonResponse(['sucesso' => true, 'mensagem' => "Migration '{$migration}' resetada com sucesso"]);
            break;

        default:
            jsonResponse(['erro' => 'Ação inválida'], 400);
    }
} catch (Exception $e) {
    jsonResponse(['erro' => $e->getMessage()], 500);
}
