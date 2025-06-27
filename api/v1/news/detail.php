<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        throw new Exception('Veritabanı bağlantısı kurulamadı');
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        exit();
    }

    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Geçersiz haber ID']);
        exit();
    }

    // Haber detayını getir
    $sql = "SELECT id, title, description, content, image_url, category, is_sponsored, author, publish_date, view_count 
            FROM news 
            WHERE id = ? AND is_active = 1";

    $stmt = $db->prepare($sql);
    $stmt->execute([$id]);

    $news = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$news) {
        http_response_code(404);
        echo json_encode(['error' => 'Haber bulunamadı']);
        exit();
    }

    // Görüntülenme sayısını artır
    $updateSql = "UPDATE news SET view_count = view_count + 1 WHERE id = ?";
    $updateStmt = $db->prepare($updateSql);
    $updateStmt->execute([$id]);

    // Boolean değerleri düzelt
    $news['is_sponsored'] = (bool)$news['is_sponsored'];
    $news['view_count'] = (int)$news['view_count'] + 1; // Güncellenmiş sayıyı göster
    $news['id'] = (int)$news['id'];

    echo json_encode($news);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Haber detayı yüklenirken hata oluştu: ' . $e->getMessage()]);
}
