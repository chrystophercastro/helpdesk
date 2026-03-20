<?php
/**
 * Migration: Adicionar configurações do Portal customizável
 */
require_once __DIR__ . '/../config/app.php';

$db = Database::getInstance();

$portalConfigs = [
    ['portal_titulo', '', 'Título do Portal (vazio = usa nome da empresa)'],
    ['portal_subtitulo', 'Portal de Suporte — Selecione o departamento para abrir ou consultar sua demanda', 'Subtítulo do Portal'],
    ['portal_icone', 'fas fa-headset', 'Ícone do Portal (Font Awesome)'],
    ['portal_logo', '', 'Logo do Portal (URL da imagem)'],
    ['portal_cor_header_1', '#1E1B4B', 'Cor do gradiente do header (início)'],
    ['portal_cor_header_2', '#4338CA', 'Cor do gradiente do header (meio)'],
    ['portal_cor_header_3', '#6366F1', 'Cor do gradiente do header (fim)'],
    ['portal_cor_primaria', '#4F46E5', 'Cor primária do portal'],
    ['portal_cor_primaria_dark', '#3730A3', 'Cor primária escura do portal'],
    ['portal_mostrar_mural', '1', 'Exibir seção Mural no portal (0/1)'],
    ['portal_mostrar_consulta', '1', 'Exibir seção Consultar Chamado no portal (0/1)'],
    ['portal_mostrar_depts', '1', 'Exibir cards de departamentos no portal (0/1)'],
    ['portal_posts_quantidade', '5', 'Quantidade de posts exibidos no mural do portal'],
    ['portal_footer_texto', '', 'Texto personalizado no rodapé do portal'],
    ['portal_css_custom', '', 'CSS personalizado para o portal'],
    ['portal_banner_url', '', 'URL da imagem de banner do mural'],
    ['portal_mural_titulo', 'Mural da Empresa', 'Título da seção Mural'],
    ['portal_consulta_titulo', 'Consultar Chamado', 'Título da seção Consultar Chamado'],
];

$inserted = 0;
$skipped = 0;

foreach ($portalConfigs as $cfg) {
    $exists = $db->fetch("SELECT id FROM configuracoes WHERE chave = ?", [$cfg[0]]);
    if (!$exists) {
        $db->insert('configuracoes', [
            'chave' => $cfg[0],
            'valor' => $cfg[1],
            'descricao' => $cfg[2]
        ]);
        $inserted++;
        echo "  ✓ Inserido: {$cfg[0]}\n";
    } else {
        // Atualizar descrição se não tiver
        $db->query("UPDATE configuracoes SET descricao = ? WHERE chave = ? AND (descricao IS NULL OR descricao = '')", [$cfg[2], $cfg[0]]);
        $skipped++;
        echo "  - Já existe: {$cfg[0]}\n";
    }
}

echo "\n=== Migração concluída ===\n";
echo "Inseridos: {$inserted} | Já existentes: {$skipped}\n";
