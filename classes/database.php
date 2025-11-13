<?php
// FILE: classes/Database.php

class Database {
    private $conn;
    private static $instance = null;

    public function __construct() {
        // Pastikan file config hanya di-load sekali
        if (!defined('DB_HOST')) {
            require_once __DIR__ . '/../config/config.php';
        }

        $this->connect();
    }

    /**
     * Buat koneksi ke database
     */
    private function connect() {
        try {
            // Buat koneksi MySQLi dengan error reporting
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            
            $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

            // Set charset agar support UTF-8 (emoji & multibahasa)
            if (!$this->conn->set_charset("utf8mb4")) {
                throw new Exception("Gagal set charset UTF8MB4: " . $this->conn->error);
            }

            // Set timeout yang lebih panjang untuk menghindari "MySQL server has gone away"
            $this->conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 10);
            $this->conn->options(MYSQLI_OPT_READ_TIMEOUT, 30);
            
        } catch (mysqli_sql_exception $e) {
            die("Koneksi Database Gagal: " . $e->getMessage() . "<br>Pastikan MySQL/XAMPP sudah berjalan!");
        }
    }

    /**
     * Reconnect jika koneksi terputus
     */
    private function reconnectIfNeeded() {
        if (!$this->conn || !$this->conn->ping()) {
            $this->connect();
        }
    }

    /**
     * Get raw connection object
     */
    public function getConnection() {
        $this->reconnectIfNeeded();
        return $this->conn;
    }

    /**
     * Execute query langsung (tanpa prepared statement)
     * Gunakan hanya untuk query yang AMAN (tidak ada user input)
     * 
     * @param string $sql
     * @return mysqli_result|bool
     */
    public function query($sql) {
        $this->reconnectIfNeeded();
        
        try {
            $result = $this->conn->query($sql);
            
            if (!$result) {
                throw new Exception("Query Error: " . $this->conn->error . " | SQL: " . $sql);
            }
            
            return $result;
        } catch (mysqli_sql_exception $e) {
            throw new Exception("Query Error: " . $e->getMessage() . " | SQL: " . $sql);
        }
    }

    /**
     * Prepare statement untuk query dengan parameter
     * 
     * @param string $sql
     * @return mysqli_stmt
     */
    public function prepare($sql) {
        $this->reconnectIfNeeded();
        
        try {
            $stmt = $this->conn->prepare($sql);
            
            if (!$stmt) {
                throw new Exception("Prepare Error: " . $this->conn->error . " | SQL: " . $sql);
            }
            
            return $stmt;
        } catch (mysqli_sql_exception $e) {
            throw new Exception("Prepare Error: " . $e->getMessage() . " | SQL: " . $sql);
        }
    }

    /**
     * Execute prepared statement dengan parameter
     * Untuk INSERT, UPDATE, DELETE
     * 
     * @param string $sql
     * @param array $params
     * @return bool
     */
    public function execute($sql, $params = []) {
        $this->reconnectIfNeeded();
        
        if (empty($params)) {
            return $this->conn->query($sql);
        }

        try {
            $stmt = $this->prepare($sql);
            $types = $this->detectParamTypes($params);
            
            if (!$stmt->bind_param($types, ...$params)) {
                throw new Exception("Bind Param Error: " . $stmt->error);
            }
            
            $result = $stmt->execute();
            
            if (!$result) {
                throw new Exception("Execute Error: " . $stmt->error . " | SQL: " . $sql);
            }
            
            $stmt->close();
            return $result;
        } catch (mysqli_sql_exception $e) {
            if (isset($stmt)) $stmt->close();
            throw new Exception("Execute Error: " . $e->getMessage() . " | SQL: " . $sql);
        }
    }

    /**
     * Fetch single row
     * 
     * @param string $sql
     * @param array $params
     * @return array|null
     */
    public function fetch($sql, $params = []) {
        $this->reconnectIfNeeded();
        
        try {
            if (empty($params)) {
                $result = $this->conn->query($sql);
                return $result ? $result->fetch_assoc() : null;
            }

            $stmt = $this->prepare($sql);
            $types = $this->detectParamTypes($params);
            
            if (!$stmt->bind_param($types, ...$params)) {
                throw new Exception("Bind Param Error: " . $stmt->error);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            $data = $result ? $result->fetch_assoc() : null;
            $stmt->close();
            
            return $data;
        } catch (mysqli_sql_exception $e) {
            if (isset($stmt)) $stmt->close();
            throw new Exception("Fetch Error: " . $e->getMessage() . " | SQL: " . $sql);
        }
    }

    /**
     * Fetch all rows
     * 
     * @param string $sql
     * @param array $params
     * @return array
     */
    public function fetchAll($sql, $params = []) {
        $this->reconnectIfNeeded();
        
        try {
            if (empty($params)) {
                $result = $this->conn->query($sql);
                return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
            }

            $stmt = $this->prepare($sql);
            $types = $this->detectParamTypes($params);
            
            if (!$stmt->bind_param($types, ...$params)) {
                throw new Exception("Bind Param Error: " . $stmt->error);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            $data = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
            $stmt->close();
            
            return $data;
        } catch (mysqli_sql_exception $e) {
            if (isset($stmt)) $stmt->close();
            throw new Exception("FetchAll Error: " . $e->getMessage() . " | SQL: " . $sql);
        }
    }

    /**
     * Insert data dan return inserted ID
     * 
     * @param string $table
     * @param array $data (associative array: column => value)
     * @return int Last Insert ID
     */
    public function insert($table, $data) {
        $this->reconnectIfNeeded();
        
        $columns = array_keys($data);
        $values = array_values($data);
        
        $columnStr = implode(', ', $columns);
        $placeholders = implode(', ', array_fill(0, count($values), '?'));
        
        $sql = "INSERT INTO $table ($columnStr) VALUES ($placeholders)";
        
        try {
            $stmt = $this->prepare($sql);
            $types = $this->detectParamTypes($values);
            
            if (!$stmt->bind_param($types, ...$values)) {
                throw new Exception("Bind Param Error: " . $stmt->error);
            }
            
            if (!$stmt->execute()) {
                throw new Exception("Insert Error: " . $stmt->error . " | SQL: " . $sql);
            }
            
            $insertId = $stmt->insert_id;
            $stmt->close();
            
            return $insertId;
        } catch (mysqli_sql_exception $e) {
            if (isset($stmt)) $stmt->close();
            throw new Exception("Insert Error: " . $e->getMessage() . " | SQL: " . $sql);
        }
    }

    /**
     * Update data
     * 
     * @param string $table
     * @param array $data (associative array: column => value)
     * @param string $where (e.g., "id = ?")
     * @param array $whereParams
     * @return bool
     */
    public function update($table, $data, $where, $whereParams = []) {
        $this->reconnectIfNeeded();
        
        $setParts = [];
        $values = [];
        
        foreach ($data as $column => $value) {
            $setParts[] = "$column = ?";
            $values[] = $value;
        }
        
        $setStr = implode(', ', $setParts);
        $sql = "UPDATE $table SET $setStr WHERE $where";
        
        // Gabungkan values dengan whereParams
        $allParams = array_merge($values, $whereParams);
        
        try {
            $stmt = $this->prepare($sql);
            $types = $this->detectParamTypes($allParams);
            
            if (!$stmt->bind_param($types, ...$allParams)) {
                throw new Exception("Bind Param Error: " . $stmt->error);
            }
            
            $result = $stmt->execute();
            $stmt->close();
            
            return $result;
        } catch (mysqli_sql_exception $e) {
            if (isset($stmt)) $stmt->close();
            throw new Exception("Update Error: " . $e->getMessage() . " | SQL: " . $sql);
        }
    }

    /**
     * Delete data
     * 
     * @param string $table
     * @param string $where (e.g., "id = ?")
     * @param array $params
     * @return bool
     */
    public function delete($table, $where, $params = []) {
        $this->reconnectIfNeeded();
        
        $sql = "DELETE FROM $table WHERE $where";
        
        try {
            if (empty($params)) {
                return $this->conn->query($sql);
            }
            
            $stmt = $this->prepare($sql);
            $types = $this->detectParamTypes($params);
            
            if (!$stmt->bind_param($types, ...$params)) {
                throw new Exception("Bind Param Error: " . $stmt->error);
            }
            
            $result = $stmt->execute();
            $stmt->close();
            
            return $result;
        } catch (mysqli_sql_exception $e) {
            if (isset($stmt)) $stmt->close();
            throw new Exception("Delete Error: " . $e->getMessage() . " | SQL: " . $sql);
        }
    }

    /**
     * Count rows
     * 
     * @param string $table
     * @param string $where (optional)
     * @param array $params
     * @return int
     */
    public function count($table, $where = '', $params = []) {
        $sql = "SELECT COUNT(*) as total FROM $table";
        
        if (!empty($where)) {
            $sql .= " WHERE $where";
        }
        
        $result = $this->fetch($sql, $params);
        return $result ? (int)$result['total'] : 0;
    }

    /**
     * Check if record exists
     * 
     * @param string $table
     * @param string $where
     * @param array $params
     * @return bool
     */
    public function exists($table, $where, $params = []) {
        return $this->count($table, $where, $params) > 0;
    }

    /**
     * Begin transaction
     */
    public function beginTransaction() {
        $this->reconnectIfNeeded();
        return $this->conn->begin_transaction();
    }

    /**
     * Commit transaction
     */
    public function commit() {
        return $this->conn->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollback() {
        return $this->conn->rollback();
    }

    /**
     * Escape string untuk mencegah SQL injection
     * 
     * @param string $string
     * @return string
     */
    public function escape($string) {
        $this->reconnectIfNeeded();
        return $this->conn->real_escape_string($string);
    }

    /**
     * Get last inserted ID
     * 
     * @return int
     */
    public function lastInsertId() {
        return $this->conn->insert_id;
    }

    /**
     * Get affected rows dari query terakhir
     * 
     * @return int
     */
    public function affectedRows() {
        return $this->conn->affected_rows;
    }

    /**
     * Get error message
     * 
     * @return string
     */
    public function error() {
        return $this->conn->error;
    }

    /**
     * Close database connection
     */
    public function close() {
        if ($this->conn && $this->conn->ping()) {
            $this->conn->close();
            $this->conn = null;
        }
    }

    /**
     * Auto-detect parameter types untuk bind_param
     * i = integer, d = double, s = string, b = blob
     * 
     * @param array $params
     * @return string
     */
    private function detectParamTypes($params) {
        $types = '';
        
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_float($param)) {
                $types .= 'd';
            } elseif (is_string($param)) {
                $types .= 's';
            } else {
                $types .= 's'; // Default to string for safety
            }
        }
        
        return $types;
    }

    /**
     * Destructor - auto close connection
     */
    public function __destruct() {
        // Jangan close di destructor karena bisa menyebabkan masalah
        // Connection akan di-close otomatis saat script selesai
    }
}