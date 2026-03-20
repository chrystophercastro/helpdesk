<?php
/**
 * Migration: Atualizar tabela fornecedores para schema completo
 * A tabela foi criada antes com schema simples (nome, cnpj, contato_nome, etc.)
 * O código espera o schema completo da migration (razao_social, nome_fantasia, cpf, etc.)
 */
require_once __DIR__ . '/../config/app.php';
$db = Database::getInstance()->getConnection();

$alteracoes = [];

// Renomear 'nome' → 'razao_social' 
$alteracoes[] = "ALTER TABLE fornecedores CHANGE COLUMN nome razao_social VARCHAR(200) NOT NULL";

// Adicionar colunas que faltam
$alteracoes[] = "ALTER TABLE fornecedores ADD COLUMN nome_fantasia VARCHAR(200) DEFAULT NULL AFTER razao_social";
$alteracoes[] = "ALTER TABLE fornecedores ADD COLUMN cnpj_cpf VARCHAR(20) DEFAULT NULL AFTER cnpj";
$alteracoes[] = "ALTER TABLE fornecedores ADD COLUMN cpf VARCHAR(14) DEFAULT NULL AFTER cnpj_cpf";
$alteracoes[] = "ALTER TABLE fornecedores ADD COLUMN tipo ENUM('pj','pf') NOT NULL DEFAULT 'pj' AFTER cpf";
$alteracoes[] = "ALTER TABLE fornecedores ADD COLUMN inscricao_estadual VARCHAR(30) DEFAULT NULL AFTER tipo";
$alteracoes[] = "ALTER TABLE fornecedores ADD COLUMN inscricao_municipal VARCHAR(30) DEFAULT NULL AFTER inscricao_estadual";
$alteracoes[] = "ALTER TABLE fornecedores ADD COLUMN email VARCHAR(200) DEFAULT NULL AFTER inscricao_municipal";
$alteracoes[] = "ALTER TABLE fornecedores ADD COLUMN telefone VARCHAR(20) DEFAULT NULL AFTER email";

// Colunas de endereço detalhado
$alteracoes[] = "ALTER TABLE fornecedores ADD COLUMN cep VARCHAR(10) DEFAULT NULL AFTER contato_nome";
$alteracoes[] = "ALTER TABLE fornecedores ADD COLUMN logradouro VARCHAR(200) DEFAULT NULL AFTER cep";
$alteracoes[] = "ALTER TABLE fornecedores ADD COLUMN numero VARCHAR(20) DEFAULT NULL AFTER logradouro";
$alteracoes[] = "ALTER TABLE fornecedores ADD COLUMN complemento VARCHAR(100) DEFAULT NULL AFTER numero";
$alteracoes[] = "ALTER TABLE fornecedores ADD COLUMN bairro VARCHAR(100) DEFAULT NULL AFTER complemento";
$alteracoes[] = "ALTER TABLE fornecedores ADD COLUMN cidade VARCHAR(100) DEFAULT NULL AFTER bairro";
$alteracoes[] = "ALTER TABLE fornecedores ADD COLUMN uf CHAR(2) DEFAULT NULL AFTER cidade";

// Colunas bancárias
$alteracoes[] = "ALTER TABLE fornecedores ADD COLUMN banco VARCHAR(100) DEFAULT NULL AFTER uf";
$alteracoes[] = "ALTER TABLE fornecedores ADD COLUMN agencia VARCHAR(20) DEFAULT NULL AFTER banco";
$alteracoes[] = "ALTER TABLE fornecedores ADD COLUMN conta VARCHAR(30) DEFAULT NULL AFTER agencia";
$alteracoes[] = "ALTER TABLE fornecedores ADD COLUMN pix VARCHAR(100) DEFAULT NULL AFTER conta";

// Coluna contato (alias para contato_nome que já existe)
$alteracoes[] = "ALTER TABLE fornecedores ADD COLUMN contato VARCHAR(100) DEFAULT NULL AFTER contato_nome";

// Status (substituir 'ativo' por enum 'status')
$alteracoes[] = "ALTER TABLE fornecedores ADD COLUMN status ENUM('ativo','inativo') NOT NULL DEFAULT 'ativo' AFTER pix";

$ok = 0;
$skip = 0;
$errs = 0;

foreach ($alteracoes as $sql) {
    try {
        $db->exec($sql);
        echo "OK: $sql\n";
        $ok++;
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "SKIP (já existe): $sql\n";
            $skip++;
        } else {
            echo "ERRO: $sql\n  -> " . $e->getMessage() . "\n";
            $errs++;
        }
    }
}

// Migrar dados existentes: copiar contato_email → email, contato_telefone → telefone (se vazios)
try {
    $db->exec("UPDATE fornecedores SET email = contato_email WHERE email IS NULL AND contato_email IS NOT NULL AND contato_email != ''");
    $db->exec("UPDATE fornecedores SET telefone = contato_telefone WHERE telefone IS NULL AND contato_telefone IS NOT NULL AND contato_telefone != ''");
    $db->exec("UPDATE fornecedores SET contato = contato_nome WHERE contato IS NULL AND contato_nome IS NOT NULL AND contato_nome != ''");
    // Setar status baseado no ativo
    $db->exec("UPDATE fornecedores SET status = 'inativo' WHERE ativo = 0");
    $db->exec("UPDATE fornecedores SET status = 'ativo' WHERE ativo = 1 OR ativo IS NULL");
    // Copiar cnpj → cnpj_cpf se vazio
    $db->exec("UPDATE fornecedores SET cnpj_cpf = cnpj WHERE cnpj_cpf IS NULL AND cnpj IS NOT NULL");
    echo "\nDados migrados com sucesso!\n";
} catch (PDOException $e) {
    echo "Aviso migração dados: " . $e->getMessage() . "\n";
}

echo "\n=== Resultado: $ok OK, $skip já existiam, $errs erros ===\n";

// Verificar resultado final
echo "\n=== COLUNAS FINAIS ===\n";
$stmt = $db->query("DESCRIBE fornecedores");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . " | " . $row['Type'] . " | " . ($row['Null'] === 'YES' ? 'NULL' : 'NOT NULL') . "\n";
}
