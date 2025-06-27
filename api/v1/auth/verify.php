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
if (empty($data->phone) || empty($data->verification_code)) {
    http_response_code(400);
    echo json_encode(array("message" => "Telefon numarası ve doğrulama kodu gereklidir"));
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
    $user_query = "SELECT id, phone, full_name, email, is_verified FROM users WHERE phone = :phone";
    $user_stmt = $db->prepare($user_query);
    $user_stmt->bindParam(":phone", $data->phone);
    $user_stmt->execute();

    if ($user_stmt->rowCount() == 0) {
        http_response_code(404);
        echo json_encode(array("message" => "Kullanıcı bulunamadı"));
        exit();
    }

    $user = $user_stmt->fetch(PDO::FETCH_ASSOC);

    // Doğrulama kodunu kontrol et
    $code_query = "SELECT id FROM verification_codes 
                   WHERE user_id = :user_id AND code = :code AND type = 'register' 
                   AND expires_at > NOW() AND used_at IS NULL";
    $code_stmt = $db->prepare($code_query);
    $code_stmt->bindParam(":user_id", $user['id']);
    $code_stmt->bindParam(":code", $data->verification_code);
    $code_stmt->execute();

    if ($code_stmt->rowCount() == 0) {
        http_response_code(401);
        echo json_encode(array("message" => "Geçersiz veya süresi dolmuş doğrulama kodu"));
        exit();
    }

    $verification = $code_stmt->fetch(PDO::FETCH_ASSOC);

    // Kullanıcıyı doğrulanmış olarak işaretle
    $verify_query = "UPDATE users SET is_verified = 1, updated_at = NOW() WHERE id = :id";
    $verify_stmt = $db->prepare($verify_query);
    $verify_stmt->bindParam(":id", $user['id']);
    $verify_stmt->execute();

    // Doğrulama kodunu kullanılmış olarak işaretle
    $used_query = "UPDATE verification_codes SET used_at = NOW() WHERE id = :id";
    $used_stmt = $db->prepare($used_query);
    $used_stmt->bindParam(":id", $verification['id']);
    $used_stmt->execute();

    // Token oluştur
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

    http_response_code(200);
    echo json_encode(array(
        "message" => "Doğrulama başarılı",
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
