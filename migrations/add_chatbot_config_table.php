<?php
require_once 'config/database.php';

$db = Database::getInstance();

$sql = "
CREATE TABLE IF NOT EXISTS chatbot_configuracoes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    chave VARCHAR(100) NOT NULL UNIQUE,
    valor TEXT,
    descricao VARCHAR(255),
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

try {
    $db->query($sql);
    echo "Tabela 'chatbot_configuracoes' criada com sucesso.\n";
} catch (Exception $e) {
    echo "Erro ao criar tabela: " . $e->getMessage() . "\n";
}

// Inserir configurações padrão se não existirem
$defaults = [
    'chatbot_ativo' => '0',
    'chatbot_nome' => 'Assistente Virtual',
    'chatbot_boas_vindas' => 'Olá! Como posso ajudar você hoje?',
    'chatbot_sem_resposta' => 'Desculpe, não entendi. Pode reformular?',
    'chatbot_modelo_ia' => 'gpt-3.5-turbo',
    'chatbot_max_tokens' => '150'
];

foreach ($defaults as $key => $value) {
    $check = $db->fetch("SELECT id FROM chatbot_configuracoes WHERE chave = ?", [$key]);
    if (!$check) {
        $db->insert("chatbot_configuracoes", ['chave' => $key, 'valor' => $value]);
    }
}