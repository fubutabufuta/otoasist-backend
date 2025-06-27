<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../../config/database.php';

function addClickCountColumn($pdo)
{
    try {
        // Check if click_count column exists
        $sql = "SHOW COLUMNS FROM sliders LIKE 'click_count'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();

        if ($stmt->rowCount() == 0) {
            // Add the click_count column
            $sql = "ALTER TABLE sliders ADD COLUMN click_count INT DEFAULT 0";
            $pdo->exec($sql);
        }
    } catch (PDOException $e) {
        // Ignore errors - column might already exist
    }
}

try {
    // Initialize database connection
    $database = new Database();
    $pdo = $database->getConnection();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['slider_id']) || !is_numeric($input['slider_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid slider_id']);
        exit;
    }

    $slider_id = (int)$input['slider_id'];

    // Add click_count column if it doesn't exist
    addClickCountColumn($pdo);

    // Increment click count
    $sql = "UPDATE sliders SET click_count = COALESCE(click_count, 0) + 1 WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$slider_id]);

    if ($stmt->rowCount() > 0) {
        // Get updated click count
        $sql = "SELECT click_count FROM sliders WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$slider_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'slider_id' => $slider_id,
            'click_count' => $result['click_count']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Slider not found'
        ]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
