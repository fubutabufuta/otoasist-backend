<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../test_db.php';

// Veritabanı bağlantısını test et
try {
    $host = "127.0.0.1";
    $db_name = "otoasist";
    $username = "root";
    $password = "";

    $pdo = new PDO(
        "mysql:host=" . $host . ";dbname=" . $db_name . ";charset=utf8mb4",
        $username,
        $password
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Basit bir sorgu çalıştır
    $stmt = $pdo->query("SELECT COUNT(*) as user_count FROM users");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => "success",
        "message" => "Veritabanı bağlantısı başarılı",
        "user_count" => $result['user_count'],
        "server_info" => $pdo->getAttribute(PDO::ATTR_SERVER_INFO)
    ]);
} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Veritabanı bağlantı hatası: " . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Genel hata: " . $e->getMessage()
    ]);
}
