<?php
class Database
{
    // SiteGround Production Database Settings
    private $host = "localhost";
    private $db_name = "SITEGROUND_DB_NAME"; // SiteGround'dan alacağın DB adı
    private $username = "SITEGROUND_USERNAME"; // SiteGround DB kullanıcı adı
    private $password = "SITEGROUND_PASSWORD"; // SiteGround DB şifresi
    private $conn;

    public function getConnection()
    {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password,
                array(
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                )
            );
        } catch (PDOException $exception) {
            error_log("Database connection error: " . $exception->getMessage());

            // Production'da detaylı hata gösterme
            if (defined('DEVELOPMENT') && DEVELOPMENT) {
                echo "Connection error: " . $exception->getMessage();
            } else {
                echo json_encode(array("message" => "Database connection failed"));
            }
        }

        return $this->conn;
    }

    // Test connection
    public function testConnection()
    {
        $conn = $this->getConnection();
        if ($conn) {
            try {
                $stmt = $conn->query("SELECT 1");
                return true;
            } catch (PDOException $e) {
                return false;
            }
        }
        return false;
    }
}
