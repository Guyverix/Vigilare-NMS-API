<?php
/*
declare(strict_types=1);
// https://gist.github.com/bradtraversy/a77931605ba9b7cf3326644e75530464
*/

class Database {
    private $host;
    private $user;
    private $pass;
    private $dbname;
    private $port;
    private $dbh;

    public $error;
    public $stmt;

    const PDO_ERROR_CODE = 0;
    const PDO_ERROR_INFO = 1;

    public function __construct($host, $user, $pass, $dbname, $port = null) {
        $this->host = $host;
        $this->user = $user;
        $this->pass = $pass;
        $this->dbname = $dbname;
        $this->port = $port ?? ini_get('mysqli.default_port');

        try {
            $dsn = "mysql:host=$this->host;dbname=$this->dbname";
            if ($this->port) {
                $dsn .= ";port=$this->port";
            }
            $this->dbh = new PDO($dsn, $this->user, $this->pass);
            $this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            $this->error = $e;
            ExternalLogger::error("Database connection failed: " . $e->getMessage());
        }
    }

    public function prepare($query) {
        try {
            $this->stmt = $this->dbh->prepare($query);
            return $this->stmt;
        } catch (PDOException $e) {
            $this->error = $e;
            ExternalLogger::error("Database prepare failed: " . $e->getMessage());
            return false;
        }
    }

    public function execute() {
        try {
            return $this->stmt->execute();
        } catch (PDOException $e) {
            ExternalLogger::error("Database execute failed: " . $e->getMessage());
            throw new DatabaseException($e->getMessage(), $e->getCode());
        }
    }

    public function resultset() {
        try {
            $this->execute();
            return $this->stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            ExternalLogger::error("Database resultset failed: " . $e->getMessage());
            return [];
        }
    }

    public function rowCount() {
        try {
            return $this->stmt->rowCount();
        } catch (PDOException $e) {
            ExternalLogger::error("Database rowCount failed: " . $e->getMessage());
            return 0;
        }
    }

    public function lastInsertId() {
        try {
            return $this->dbh->lastInsertId();
        } catch (PDOException $e) {
            ExternalLogger::error("Database lastInsertId failed: " . $e->getMessage());
            return null;
        }
    }

    public function errorInfo() {
        try {
            return $this->dbh->errorInfo();
        } catch (PDOException $e) {
            ExternalLogger::error("Database errorInfo failed: " . $e->getMessage());
            return [];
        }
    }

    public function errorCode() {
        try {
            return $this->dbh->errorCode();
        } catch (PDOException $e) {
            ExternalLogger::error("Database errorCode failed: " . $e->getMessage());
            return null;
        }
    }
}

class DatabaseException extends Exception {}
?>
