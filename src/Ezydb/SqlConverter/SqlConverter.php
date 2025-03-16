<?php
namespace Ezydb\SqlConverter;

class SQLConverter {
    private $vendor;

    public function __construct($vendor) {
        $this->vendor = strtolower($vendor);
    }

    public function convertCreateTable($sql) {
        // Basic parsing (this will need improvement for complex cases)
        $sql = trim($sql);
        if (stripos($sql, 'CREATE TABLE') !== 0) {
            throw new Exception("Invalid CREATE TABLE statement");
        }

        // Extract table name
        preg_match('/CREATE TABLE ([`"\w]+)/i', $sql, $matches);
        $tableName = $matches[1] ?? null;

        if (!$tableName) {
            throw new Exception("Table name not found");
        }

        // Adjust SQL for different vendors
        switch ($this->vendor) {
            case 'postgresql':
                return $this->convertForPostgreSQL($sql);
            case 'mysql':
                return $this->convertForMySQL($sql);
            case 'sqlite':
                return $this->convertForSQLite($sql);
            default:
                throw new Exception("Unsupported vendor: {$this->vendor}");
        }
    }

    private function convertForPostgreSQL($sql) {
        // Replace AUTO_INCREMENT with SERIAL
        $sql = preg_replace('/\bINTEGER\s+AUTO_INCREMENT\b/i', 'SERIAL', $sql);
        return $sql;
    }

    private function convertForMySQL($sql) {
        // Ensure AUTO_INCREMENT columns exist and add ENGINE=InnoDB
        $sql = preg_replace('/\bGENERATED ALWAYS AS IDENTITY\b/i', 'AUTO_INCREMENT', $sql);
        if (stripos($sql, 'ENGINE=') === false) {
            $sql .= ' ENGINE=InnoDB;';
        }
        return $sql;
    }

    private function convertForSQLite($sql) {
        // SQLite uses AUTOINCREMENT instead of AUTO_INCREMENT
        $sql = preg_replace('/\bAUTO_INCREMENT\b/i', 'AUTOINCREMENT', $sql);
        return $sql;
    }
}