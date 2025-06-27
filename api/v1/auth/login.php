<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once __DIR__ . '/../../../config/database.php';

// Sadece POST isteklerine izin ver
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(array("message" => "Method not allowed"));
    exit();
}

// JSON verisini al
$data = json_decode(file_get_contents("php://input"));

// Gerekli alanları kontrol et
if (empty($data->phone) || empty($data->password)) {
    http_response_code(400);
    echo json_encode(array("message" => "Telefon numarası ve şifre gereklidir"));
    exit();
}

try {
    // Veritabanı bağlantısı
    $database = new Database();
    $db = $database->getConnection();

    if ($db === null) {
        http_response_code(500);
        echo json_encode(array("message" => "Veritabanı bağlantı hatası"));
        exit();
    }

    // Kullanıcıyı bul
    $query = "SELECT id, phone, password, full_name, email, is_verified FROM users WHERE phone = :phone";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":phone", $data->phone);
    $stmt->execute();

    if ($stmt->rowCount() == 0) {
        http_response_code(401);
        echo json_encode(array("message" => "Geçersiz telefon numarası veya şifre"));
        exit();
    }

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Şifreyi kontrol et
    if (!password_verify($data->password, $user['password'])) {
        http_response_code(401);
        echo json_encode(array("message" => "Geçersiz telefon numarası veya şifre"));
        exit();
    }

    // Kullanıcı doğrulanmış mı kontrol et
    if ($user['is_verified'] == 0) {
        http_response_code(403);
        echo json_encode(array(
            "message" => "Hesabınız henüz doğrulanmamış",
            "verification_required" => true
        ));
        exit();
    }

    // Token oluştur (basit JWT benzeri)
    $access_token = base64_encode(json_encode([
        'user_id' => $user['id'],
        'phone' => $user['phone'],
        'exp' => time() + (24 * 60 * 60) // 24 saat
    ]));

    $refresh_token = base64_encode(json_encode([
        'user_id' => $user['id'],
        'type' => 'refresh',
        'exp' => time() + (7 * 24 * 60 * 60) // 7 gün
    ]));

    // Son giriş zamanını güncelle
    $update_query = "UPDATE users SET last_login = NOW() WHERE id = :id";
    $update_stmt = $db->prepare($update_query);
    $update_stmt->bindParam(":id", $user['id']);
    $update_stmt->execute();

    http_response_code(200);
    echo json_encode(array(
        "message" => "Giriş başarılı",
        "access_token" => $access_token,
        "refresh_token" => $refresh_token,
        "user" => array(
            "id" => $user['id'],
            "phone" => $user['phone'],
            "full_name" => $user['full_name'],
            "email" => $user['email']
        )
    ));
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array("message" => "Sunucu hatası: " . $e->getMessage()));
}
