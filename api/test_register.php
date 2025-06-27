<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// Test verileri
$test_data = [
    'phone' => '5551234567',
    'password' => '123456',
    'full_name' => 'Test User',
    'email' => 'test@example.com'
];

// Register API'sine POST isteği gönder
$url = 'http://127.0.0.1:8000/v1/auth/register';
$options = [
    'http' => [
        'header' => "Content-type: application/json\r\n",
        'method' => 'POST',
        'content' => json_encode($test_data)
    ]
];

$context = stream_context_create($options);
$result = file_get_contents($url, false, $context);

if ($result === FALSE) {
    echo json_encode(['error' => 'API isteği başarısız']);
} else {
    echo "Register API Response:\n";
    echo $result;
}
