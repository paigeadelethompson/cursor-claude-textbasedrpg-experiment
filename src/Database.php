<?php

namespace Game;

use PDO;

/**
 * Class Database
 * Singleton database connection handler
 * 
 * @package Game
 */
class Database {
    /** @var Database|null Singleton instance */
    private static $instance = null;

    /** @var PDO Database connection */
    private $connection;

    /** @var array Database configuration */
    private const CONFIG = [
        'host' => 'localhost',
        'dbname' => 'game',
        'charset' => 'utf8mb4'
    ];

    /**
     * Private constructor to prevent direct instantiation
     * 
     * @throws \Exception If database connection fails
     */
    private function __construct() {
        try {
            $this->connection = new PDO(
                sprintf(
                    "mysql:host=%s;dbname=%s;charset=%s",
                    self::CONFIG['host'],
                    self::CONFIG['dbname'],
                    self::CONFIG['charset']
                ),
                getenv('DB_USER'),
                getenv('DB_PASS'),
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (\PDOException $e) {
            throw new \Exception("Database connection failed: " . $e->getMessage());
        }
    }

    /**
     * Get singleton instance
     *
     * @return Database Database instance
     */
    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get database connection
     *
     * @return PDO Active database connection
     */
    public function getConnection(): PDO {
        return $this->connection;
    }

    /**
     * Begin a transaction
     *
     * @return bool Success status
     */
    public function beginTransaction(): bool {
        return $this->connection->beginTransaction();
    }

    /**
     * Commit a transaction
     *
     * @return bool Success status
     */
    public function commit(): bool {
        return $this->connection->commit();
    }

    /**
     * Rollback a transaction
     *
     * @return bool Success status
     */
    public function rollback(): bool {
        return $this->connection->rollBack();
    }

    /**
     * Prevent cloning of singleton
     */
    private function __clone() {}

    /**
     * Prevent unserialization of singleton
     * 
     * @throws \Exception Always
     */
    public function __wakeup() {
        throw new \Exception("Cannot unserialize singleton");
    }
} 