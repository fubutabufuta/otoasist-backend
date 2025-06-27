<?php
class Database
{
    // Database credentials
    private $host = 'localhost';
    private $db_name = 'otoasist';
    private $username = 'root';
    private $password = '';
    private $conn;

    // Get database connection
    public function getConnection()
    {
        $this->conn = null;

        try {
            // Try MySQL first
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4";
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            error_log("MySQL connection successful to database: " . $this->db_name);

            // Test the connection with a simple query
            $stmt = $this->conn->query("SELECT 1");
            if ($stmt) {
                error_log("Database connection test successful");

                // Create basic tables if they don't exist (for MySQL)
                $this->createBasicTablesMySQL();

                return $this->conn;
            }
        } catch (PDOException $e) {
            error_log("MySQL connection failed: " . $e->getMessage());

            // Try SQLite as fallback
            try {
                $sqliteDb = __DIR__ . '/../otoasist.db';
                $this->conn = new PDO("sqlite:" . $sqliteDb);
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

                error_log("SQLite connection successful: " . $sqliteDb);

                // Create basic tables if they don't exist
                $this->createBasicTables();

                return $this->conn;
            } catch (PDOException $sqliteException) {
                error_log("SQLite connection also failed: " . $sqliteException->getMessage());
                throw new Exception("No database available: MySQL error: " . $e->getMessage() . ", SQLite error: " . $sqliteException->getMessage());
            }
        }

        return $this->conn;
    }

    private function createBasicTablesMySQL()
    {
        try {
            // Create quote_requests table for MySQL
            $this->conn->exec("
                CREATE TABLE IF NOT EXISTS quote_requests (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    vehicle_id INT NOT NULL,
                    service_type_id INT NOT NULL,
                    title VARCHAR(255) NOT NULL,
                    description TEXT,
                    user_notes TEXT,
                    share_phone TINYINT(1) DEFAULT 0,
                    status VARCHAR(50) DEFAULT 'pending',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            // Create service_types table for MySQL
            $this->conn->exec("
                CREATE TABLE IF NOT EXISTS service_types (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(100) NOT NULL,
                    description TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            // Insert basic service types
            $this->conn->exec("
                INSERT IGNORE INTO service_types (id, name, description) VALUES 
                (1, 'Servis', 'Genel araç servisi'),
                (2, 'Sigorta', 'Araç sigortası'),
                (3, 'Kasko', 'Kasko sigortası'),
                (4, 'Lastik', 'Lastik değişimi'),
                (5, 'Yağ Değişimi', 'Motor yağı değişimi')
            ");

            // Create vehicles table for MySQL
            $this->conn->exec("
                CREATE TABLE IF NOT EXISTS vehicles (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    brand VARCHAR(100),
                    model VARCHAR(100),
                    year INT,
                    plate VARCHAR(20),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            // Insert demo vehicle
            $this->conn->exec("
                INSERT IGNORE INTO vehicles (id, user_id, brand, model, year, plate) VALUES 
                (1, 1, 'Demo', 'Car', 2023, '34ABC123')
            ");

            error_log("Basic MySQL tables created successfully");
        } catch (PDOException $e) {
            error_log("Error creating basic MySQL tables: " . $e->getMessage());
        }
    }

    private function createBasicTables()
    {
        try {
            // Create quote_requests table
            $this->conn->exec("
                CREATE TABLE IF NOT EXISTS quote_requests (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id INTEGER NOT NULL,
                    vehicle_id INTEGER NOT NULL,
                    service_type_id INTEGER NOT NULL,
                    title TEXT NOT NULL,
                    description TEXT,
                    user_notes TEXT,
                    share_phone INTEGER DEFAULT 0,
                    status TEXT DEFAULT 'pending',
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ");

            // Create service_types table
            $this->conn->exec("
                CREATE TABLE IF NOT EXISTS service_types (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name TEXT NOT NULL,
                    description TEXT,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ");

            // Insert basic service types
            $this->conn->exec("
                INSERT OR IGNORE INTO service_types (id, name, description) VALUES 
                (1, 'Servis', 'Genel araç servisi'),
                (2, 'Sigorta', 'Araç sigortası'),
                (3, 'Kasko', 'Kasko sigortası'),
                (4, 'Lastik', 'Lastik değişimi'),
                (5, 'Yağ Değişimi', 'Motor yağı değişimi')
            ");

            // Create vehicles table
            $this->conn->exec("
                CREATE TABLE IF NOT EXISTS vehicles (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id INTEGER NOT NULL,
                    brand TEXT,
                    model TEXT,
                    year INTEGER,
                    plate TEXT,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ");

            // Insert demo vehicle
            $this->conn->exec("
                INSERT OR IGNORE INTO vehicles (id, user_id, brand, model, year, plate) VALUES 
                (1, 1, 'Demo', 'Car', 2023, '34ABC123')
            ");

            error_log("Basic tables created successfully");
        } catch (PDOException $e) {
            error_log("Error creating basic tables: " . $e->getMessage());
        }
    }
}
