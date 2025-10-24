<?php
/**
 * Database Connection Configuration
 * Hospital Management System
 */

class Database {
    private $host = 'localhost';
    private $db_name = 'hospital_management';
    private $username = 'root';  // Change this to your database username
    private $password = '';      // Change this to your database password
    private $conn;

    public function connect() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            echo "Connection Error: " . $e->getMessage();
        }
        
        return $this->conn;
    }
}

// Function to get database connection
function getDBConnection() {
    $database = new Database();
    return $database->connect();
}

// Function to execute query and return results
function executeQuery($sql, $params = []) {
    try {
        $conn = getDBConnection();
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        return ['error' => $e->getMessage()];
    }
}

// Function to execute query and return single result
function executeSingleQuery($sql, $params = []) {
    try {
        $conn = getDBConnection();
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    } catch(PDOException $e) {
        return ['error' => $e->getMessage()];
    }
}

// Function to execute insert/update/delete queries
function executeModifyQuery($sql, $params = []) {
    try {
        $conn = getDBConnection();
        $stmt = $conn->prepare($sql);
        $result = $stmt->execute($params);
        return [
            'success' => $result,
            'lastInsertId' => $conn->lastInsertId(),
            'rowCount' => $stmt->rowCount()
        ];
    } catch(PDOException $e) {
        return ['error' => $e->getMessage()];
    }
}
?>