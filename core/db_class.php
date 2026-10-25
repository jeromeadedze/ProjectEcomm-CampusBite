<?php

require_once __DIR__ . "/db_cred.php";

/**
 * Base Database Class
 * Connects via PDO and executes queries with prepared statements.
 */
class Database
{
    private $conn;
    private $host = DB_HOST;
    private $dbname = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;

    public function __construct()
    {
        try {
            $this->conn = new PDO(
                "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }

    public function fetchAll($sql, $params = [])
    {
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function fetchOne($sql, $params = [])
    {
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function execute($sql, $params = [])
    {
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($params);
    }

    public function getLastInsertId()
    {
        return $this->conn->lastInsertId();
    }

    public function getConnection()
    {
        return $this->conn;
    }
}
?>
