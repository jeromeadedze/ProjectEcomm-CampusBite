<?php

require_once __DIR__ . "/db_cred.php";

/**
 * Base Database Class
 * Connects via PDO and executes queries with prepared statements.
 */
class Database
{
    // One connection per request, shared by every model, so a transaction covers all of them
    private static $sharedConn = null;
    private $conn;
    private $host = DB_HOST;
    private $dbname = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;

    public function __construct()
    {
        if (self::$sharedConn) {
            $this->conn = self::$sharedConn;
            return;
        }
        try {
            $this->conn = new PDO(
                "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$sharedConn = $this->conn;
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }

    public function fetchAll($sql, $params = [])
    {
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die("<pre style='color:#b00;background:#fff;padding:16px;border:1px solid #ccc;font-family:monospace;font-size:13px;'>Database query failed:\n" . htmlspecialchars($e->getMessage()) . "\n\nQuery: " . htmlspecialchars($sql) . "</pre>");
        }
    }

    public function fetchOne($sql, $params = [])
    {
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die("<pre style='color:#b00;background:#fff;padding:16px;border:1px solid #ccc;font-family:monospace;font-size:13px;'>Database query failed:\n" . htmlspecialchars($e->getMessage()) . "\n\nQuery: " . htmlspecialchars($sql) . "</pre>");
        }
    }

    public function execute($sql, $params = [])
    {
        try {
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            die("<pre style='color:#b00;background:#fff;padding:16px;border:1px solid #ccc;font-family:monospace;font-size:13px;'>Database query failed:\n" . htmlspecialchars($e->getMessage()) . "\n\nQuery: " . htmlspecialchars($sql) . "</pre>");
        }
    }

    // Runs a write and returns how many rows it changed (0 when a conditional UPDATE matched nothing)
    public function executeCount($sql, $params = [])
    {
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            die("<pre style='color:#b00;background:#fff;padding:16px;border:1px solid #ccc;font-family:monospace;font-size:13px;'>Database query failed:\n" . htmlspecialchars($e->getMessage()) . "\n\nQuery: " . htmlspecialchars($sql) . "</pre>");
        }
    }

    public function getLastInsertId()
    {
        return $this->conn->lastInsertId();
    }

    public function beginTransaction()
    {
        return $this->conn->beginTransaction();
    }

    public function commit()
    {
        return $this->conn->commit();
    }

    public function rollBack()
    {
        return $this->conn->inTransaction() ? $this->conn->rollBack() : false;
    }

    public function getConnection()
    {
        return $this->conn;
    }
}
?>
