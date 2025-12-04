<?php
/**
 * Database Connection Handler
 * 
 * @author Maitiú Ellis
 * @package Database
 * @description Manages PDO connection to SQLite database
 */

class Database {
    
    /**
     * @var PDO|null Database connection instance
     */
    private static ?PDO $connection = null;
    
    /**
     * @var string Path to SQLite database file
     */
    private static string $dbPath = __DIR__ . DIRECTORY_SEPARATOR . 'database.sqlite';
    
    /**
     * Get or create database connection
     * 
     * @return PDO Database connection object
     * @throws PDOException If connection fails
     */
    public static function connect(): PDO {
        if (self::$connection === null) {
            try {
                self::$connection = new PDO(
                    'sqlite:' . self::$dbPath,
                    null,
                    null,
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                );
            } catch (PDOException $e) {
                die('Database connection failed: ' . $e->getMessage());
            }
        }
        
        return self::$connection;
    }
    
    /**
     * Initialize database tables (create if not exist)
     * 
     * @return void
     */
    public static function initialize(): void {
        $pdo = self::connect();
        
        // Example table - modify as needed
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT UNIQUE NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }
    
    /**
     * Get database instance
     * 
     * @return PDO
     */
    public static function getInstance(): PDO {
        return self::connect();
    }
}
