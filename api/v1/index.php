<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

echo json_encode([
    "message" => "OtoAsist API v1",
    "version" => "1.0.0",
    "status" => "active",
    "endpoints" => [
        "auth" => "/api/v1/auth/",
        "vehicles" => "/api/v1/vehicles/",
        "reminders" => "/api/v1/reminders/",
        "campaigns" => "/api/v1/campaigns/"
    ]
]);
