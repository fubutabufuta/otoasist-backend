<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

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

    // Verification codes tablosunu oluştur
    $sql = "CREATE TABLE IF NOT EXISTS `verification_codes` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `code` varchar(6) NOT NULL,
        `type` enum('register', 'login', 'password_reset') NOT NULL,
        `expires_at` timestamp NOT NULL,
        `used_at` timestamp NULL DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `user_id` (`user_id`),
        KEY `code_type` (`code`, `type`),
        KEY `expires_at` (`expires_at`)
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci";

    $pdo->exec($sql);

    // Users tablosunu güncelle
    $alter_sqls = [
        "ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `last_login` timestamp NULL DEFAULT NULL AFTER `is_verified`",
        "ALTER TABLE `users` CHANGE `password_hash` `password` varchar(255) NOT NULL"
    ];

    foreach ($alter_sqls as $alter_sql) {
        try {
            $pdo->exec($alter_sql);
        } catch (Exception $e) {
            // Kolon zaten varsa hata vermeyecek
        }
    }

    echo json_encode([
        "status" => "success",
        "message" => "Veritabanı tabloları başarıyla oluşturuldu/güncellendi"
    ]);
} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Hata: " . $e->getMessage()
    ]);
}
