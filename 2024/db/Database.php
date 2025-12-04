<?php
/**
 * Database Connection Handler
 * 
 * @author Maitiú Ellis
 * @package Database
 * @description Manages PDO connection to SQLite database and initializes schema
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
     * Initialize database tables (create if not exist) and seed simple data
     * 
     * @return void
     */
    public static function initialize(): void {
        $pdo = self::connect();

        // Wrap in transaction
        $pdo->beginTransaction();
        try {
            // Users
            $pdo->exec("CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT UNIQUE NOT NULL,
                password TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )");

            // Hotels - simple list of hotels with rooms and price per night
            $pdo->exec("CREATE TABLE IF NOT EXISTS hotels (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                total_rooms INTEGER NOT NULL DEFAULT 0,
                price_per_night REAL NOT NULL DEFAULT 0
            )");

            // Hotel bookings
            $pdo->exec("CREATE TABLE IF NOT EXISTS hotel_bookings (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT NOT NULL,
                hotel_id INTEGER NOT NULL,
                rooms INTEGER NOT NULL,
                check_in DATE NOT NULL,
                check_out DATE NOT NULL,
                total_price REAL NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (hotel_id) REFERENCES hotels(id)
            )");

            // Ticket types
            $pdo->exec("CREATE TABLE IF NOT EXISTS ticket_types (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                price REAL NOT NULL
            )");

            // Ticket purchases
            $pdo->exec("CREATE TABLE IF NOT EXISTS ticket_purchases (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT NOT NULL,
                ticket_type_id INTEGER NOT NULL,
                date DATE NOT NULL,
                quantity INTEGER NOT NULL,
                total_price REAL NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (ticket_type_id) REFERENCES ticket_types(id)
            )");

            // Seed hotels if none exist
            $stmt = $pdo->query("SELECT COUNT(*) as c FROM hotels");
            $count = (int)$stmt->fetchColumn();
            if ($count === 0) {
                $insert = $pdo->prepare("INSERT INTO hotels (name, total_rooms, price_per_night) VALUES (?, ?, ?)");
                $insert->execute(['Seaside Hotel', 10, 120.00]);
                $insert->execute(['City Centre Inn', 5, 85.00]);
            }

            // Seed ticket types if none exist
            $stmt = $pdo->query("SELECT COUNT(*) as c FROM ticket_types");
            $count = (int)$stmt->fetchColumn();
            if ($count === 0) {
                $insert = $pdo->prepare("INSERT INTO ticket_types (name, price) VALUES (?, ?)");
                $insert->execute(['Child', 8.00]);
                $insert->execute(['Adult', 15.00]);
                $insert->execute(['OAP', 10.00]);
            }

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
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
