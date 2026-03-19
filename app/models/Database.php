<?php
/**
 * Model: Database
 * Classe singleton para conexão PDO com MySQL
 */

require_once __DIR__ . '/../../config/database.php';

class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
            ];
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            $this->pdo->exec("SET time_zone = '-03:00'");
        } catch (PDOException $e) {
            error_log("Erro de conexão: " . $e->getMessage());
            die("Erro ao conectar com o banco de dados.");
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->pdo;
    }

    /**
     * Executar query com parâmetros
     */
    public function query($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Buscar um registro
     */
    public function fetch($sql, $params = []) {
        return $this->query($sql, $params)->fetch();
    }

    /**
     * Buscar vários registros
     */
    public function fetchAll($sql, $params = []) {
        return $this->query($sql, $params)->fetchAll();
    }

    /**
     * Buscar valor único
     */
    public function fetchColumn($sql, $params = []) {
        return $this->query($sql, $params)->fetchColumn();
    }

    /**
     * Inserir registro
     */
    public function insert($table, $data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $this->query($sql, array_values($data));
        return $this->pdo->lastInsertId();
    }

    /**
     * Atualizar registro
     */
    public function update($table, $data, $where, $whereParams = []) {
        $set = implode(', ', array_map(function($col) { return "{$col} = ?"; }, array_keys($data)));
        $sql = "UPDATE {$table} SET {$set} WHERE {$where}";
        $params = array_merge(array_values($data), $whereParams);
        return $this->query($sql, $params)->rowCount();
    }

    /**
     * Deletar registro
     */
    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        return $this->query($sql, $params)->rowCount();
    }

    /**
     * Contar registros
     */
    public function count($table, $where = '1=1', $params = []) {
        return (int) $this->fetchColumn("SELECT COUNT(*) FROM {$table} WHERE {$where}", $params);
    }

    /**
     * Iniciar transação
     */
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }

    public function commit() {
        return $this->pdo->commit();
    }

    public function rollBack() {
        return $this->pdo->rollBack();
    }

    /**
     * Último ID inserido
     */
    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }

    // Prevenir clone e deserialização
    private function __clone() {}
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}
