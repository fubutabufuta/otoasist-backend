<?php
require_once 'config/database.php';
session_start();

// Include all CRUD functions
function isAdminLoggedIn()
{
    return isset($_SESSION['admin_token']) && !empty($_SESSION['admin_token']);
}

function authenticateAdmin($token)
{
    try {
        $database = new Database();
        $conn = $database->getConnection();

        $stmt = $conn->prepare("
            SELECT u.id, u.full_name, u.email, u.phone, u.role_id 
            FROM users u 
            WHERE u.phone = ? AND u.role_id = 3
        ");

        $stmt->execute([$token]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return false;
    }
}

// Handle POST actions
if ($_POST) {
    $action = $_POST['action'] ?? '';

    try {
        $database = new Database();
        $conn = $database->getConnection();

        switch ($action) {
            case 'login':
                $token = $_POST['token'] ?? '';

                if ($admin = authenticateAdmin($token)) {
                    $_SESSION['admin_token'] = $token;
                    $_SESSION['admin_user'] = $admin;
                    header('Location: admin_modern.php');
                    exit;
                } else {
                    $error = "Geçersiz admin token!";
                }
                break;

            // User CRUD
            case 'create_user':
                if (isAdminLoggedIn()) {
                    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("INSERT INTO users (full_name, email, phone, password, role_id) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$_POST['full_name'], $_POST['email'], $_POST['phone'], $password, $_POST['role_id']]);
                    $message = 'Kullanıcı başarıyla eklendi';
                }
                break;

            case 'update_user':
                if (isAdminLoggedIn()) {
                    if (!empty($_POST['password'])) {
                        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                        $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, password = ?, role_id = ? WHERE id = ?");
                        $stmt->execute([$_POST['full_name'], $_POST['email'], $_POST['phone'], $password, $_POST['role_id'], $_POST['user_id']]);
                    } else {
                        $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, role_id = ? WHERE id = ?");
                        $stmt->execute([$_POST['full_name'], $_POST['email'], $_POST['phone'], $_POST['role_id'], $_POST['user_id']]);
                    }
                    $message = 'Kullanıcı başarıyla güncellendi';
                }
                break;

            case 'delete_user':
                if (isAdminLoggedIn()) {
                    $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role_id != 3");
                    $stmt->execute([$_POST['user_id']]);
                    $message = 'Kullanıcı başarıyla silindi';
                }
                break;

            // Provider CRUD
            case 'update_provider':
                if (isAdminLoggedIn()) {
                    $stmt = $conn->prepare("UPDATE service_providers SET company_name = ?, description = ?, city = ?, services = ? WHERE id = ?");
                    $stmt->execute([$_POST['company_name'], $_POST['description'], $_POST['city'], $_POST['services'], $_POST['provider_id']]);
                    $message = 'Servis sağlayıcı güncellendi';
                }
                break;

            case 'delete_provider':
                if (isAdminLoggedIn()) {
                    $stmt = $conn->prepare("DELETE FROM service_providers WHERE id = ?");
                    $stmt->execute([$_POST['provider_id']]);
                    $message = 'Servis sağlayıcı silindi';
                }
                break;

            // Package CRUD
            case 'create_package':
                if (isAdminLoggedIn()) {
                    $stmt = $conn->prepare("INSERT INTO subscription_packages (name, description, price, duration_months, max_requests_per_month) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$_POST['name'], $_POST['description'], $_POST['price'], $_POST['duration_months'], $_POST['max_requests_per_month'] ?: null]);
                    $message = 'Paket başarıyla eklendi';
                }
                break;

            case 'update_package':
                if (isAdminLoggedIn()) {
                    $stmt = $conn->prepare("UPDATE subscription_packages SET name = ?, description = ?, price = ?, duration_months = ?, max_requests_per_month = ? WHERE id = ?");
                    $stmt->execute([$_POST['name'], $_POST['description'], $_POST['price'], $_POST['duration_months'], $_POST['max_requests_per_month'] ?: null, $_POST['package_id']]);
                    $message = 'Paket başarıyla güncellendi';
                }
                break;

            case 'delete_package':
                if (isAdminLoggedIn()) {
                    $stmt = $conn->prepare("DELETE FROM subscription_packages WHERE id = ?");
                    $stmt->execute([$_POST['package_id']]);
                    $message = 'Paket başarıyla silindi';
                }
                break;

            // News CRUD
            case 'create_news':
                if (isAdminLoggedIn()) {
                    $stmt = $conn->prepare("INSERT INTO news (title, content, excerpt, image_url, category, is_featured, is_sponsored, author) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $_POST['title'],
                        $_POST['content'],
                        $_POST['excerpt'],
                        $_POST['image_url'],
                        $_POST['category'],
                        isset($_POST['is_featured']) ? 1 : 0,
                        isset($_POST['is_sponsored']) ? 1 : 0,
                        $_SESSION['admin_user']['full_name']
                    ]);
                    $message = 'Haber başarıyla eklendi';
                }
                break;

            case 'update_news':
                if (isAdminLoggedIn()) {
                    $stmt = $conn->prepare("UPDATE news SET title = ?, content = ?, excerpt = ?, image_url = ?, category = ?, is_featured = ?, is_sponsored = ? WHERE id = ?");
                    $stmt->execute([
                        $_POST['title'],
                        $_POST['content'],
                        $_POST['excerpt'],
                        $_POST['image_url'],
                        $_POST['category'],
                        isset($_POST['is_featured']) ? 1 : 0,
                        isset($_POST['is_sponsored']) ? 1 : 0,
                        $_POST['news_id']
                    ]);
                    $message = 'Haber başarıyla güncellendi';
                }
                break;

            case 'delete_news':
                if (isAdminLoggedIn()) {
                    $stmt = $conn->prepare("DELETE FROM news WHERE id = ?");
                    $stmt->execute([$_POST['news_id']]);
                    $message = 'Haber başarıyla silindi';
                }
                break;

            // Slider CRUD
            case 'create_slider':
                if (isAdminLoggedIn()) {
                    $conn->exec("CREATE TABLE IF NOT EXISTS sliders (id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(255), description TEXT, image_url VARCHAR(500), link_url VARCHAR(500), sort_order INT DEFAULT 0, is_active TINYINT(1) DEFAULT 1, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
                    $stmt = $conn->prepare("INSERT INTO sliders (title, description, image_url, link_url, sort_order) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$_POST['title'], $_POST['description'], $_POST['image_url'], $_POST['link_url'], $_POST['sort_order']]);
                    $message = 'Slider başarıyla eklendi';
                }
                break;

            case 'update_slider':
                if (isAdminLoggedIn()) {
                    $stmt = $conn->prepare("UPDATE sliders SET title = ?, description = ?, image_url = ?, link_url = ?, sort_order = ?, is_active = ? WHERE id = ?");
                    $stmt->execute([$_POST['title'], $_POST['description'], $_POST['image_url'], $_POST['link_url'], $_POST['sort_order'], isset($_POST['is_active']) ? 1 : 0, $_POST['slider_id']]);
                    $message = 'Slider başarıyla güncellendi';
                }
                break;

            case 'delete_slider':
                if (isAdminLoggedIn()) {
                    $stmt = $conn->prepare("DELETE FROM sliders WHERE id = ?");
                    $stmt->execute([$_POST['slider_id']]);
                    $message = 'Slider başarıyla silindi';
                }
                break;

            // Quote CRUD
            case 'update_quote':
                if (isAdminLoggedIn()) {
                    $stmt = $conn->prepare("UPDATE quote_requests SET title = ?, description = ?, status = ? WHERE id = ?");
                    $stmt->execute([$_POST['title'], $_POST['description'], $_POST['status'], $_POST['quote_id']]);
                    $message = 'Teklif başarıyla güncellendi';
                }
                break;

            case 'delete_quote_request':
                if (isAdminLoggedIn()) {
                    $stmt = $conn->prepare("DELETE FROM quote_requests WHERE id = ?");
                    $stmt->execute([$_POST['quote_request_id']]);
                    $message = 'Teklif talebi başarıyla silindi';
                }
                break;

            // Profile Update
            case 'update_profile':
                if (isAdminLoggedIn()) {
                    $userId = $_SESSION['admin_user']['id'];
                    if (!empty($_POST['password'])) {
                        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                        $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, password = ? WHERE id = ?");
                        $stmt->execute([$_POST['full_name'], $_POST['email'], $_POST['phone'], $password, $userId]);
                    } else {
                        $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = ?");
                        $stmt->execute([$_POST['full_name'], $_POST['email'], $_POST['phone'], $userId]);
                    }

                    // Session'ı güncelle
                    $_SESSION['admin_user']['full_name'] = $_POST['full_name'];
                    $_SESSION['admin_user']['email'] = $_POST['email'];
                    $_SESSION['admin_user']['phone'] = $_POST['phone'];

                    $message = 'Profil başarıyla güncellendi';
                }
                break;

            // Settings
            case 'update_settings':
                if (isAdminLoggedIn()) {
                    // Ayarlar tablosunu oluştur
                    $conn->exec("CREATE TABLE IF NOT EXISTS app_settings (id INT AUTO_INCREMENT PRIMARY KEY, setting_key VARCHAR(255) UNIQUE, setting_value TEXT, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)");

                    $settings = ['app_name', 'app_description', 'contact_email', 'contact_phone', 'maintenance_mode'];

                    foreach ($settings as $key) {
                        if (isset($_POST[$key])) {
                            $value = $key === 'maintenance_mode' ? (isset($_POST[$key]) ? '1' : '0') : $_POST[$key];
                            $stmt = $conn->prepare("INSERT INTO app_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
                            $stmt->execute([$key, $value]);
                        }
                    }
                    $message = 'Ayarlar başarıyla güncellendi';
                }
                break;
        }
    } catch (Exception $e) {
        $error = 'Hata: ' . $e->getMessage();
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin_modern.php');
    exit;
}

// Get data
function getData($action)
{
    try {
        $database = new Database();
        $conn = $database->getConnection();

        switch ($action) {
            case 'users':
                $stmt = $conn->query("SELECT u.*, ur.name as role_name FROM users u LEFT JOIN user_roles ur ON u.role_id = ur.id ORDER BY u.id DESC LIMIT 50");
                return $stmt->fetchAll(PDO::FETCH_ASSOC);

            case 'providers':
                $stmt = $conn->query("SELECT sp.*, u.full_name, u.phone, u.email FROM service_providers sp LEFT JOIN users u ON sp.user_id = u.id ORDER BY sp.id DESC");
                return $stmt->fetchAll(PDO::FETCH_ASSOC);

            case 'packages':
                $stmt = $conn->query("SELECT *, (SELECT COUNT(*) FROM subscriptions WHERE package_id = subscription_packages.id) as subscriptions FROM subscription_packages ORDER BY price ASC");
                return $stmt->fetchAll(PDO::FETCH_ASSOC);

            case 'news':
                $stmt = $conn->query("SELECT *, CASE WHEN is_sponsored = 1 THEN 'Sponsor' ELSE 'Normal' END as news_type FROM news ORDER BY created_at DESC LIMIT 50");
                return $stmt->fetchAll(PDO::FETCH_ASSOC);

            case 'sliders':
                $conn->exec("CREATE TABLE IF NOT EXISTS sliders (id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(255), description TEXT, image_url VARCHAR(500), link_url VARCHAR(500), sort_order INT DEFAULT 0, is_active TINYINT(1) DEFAULT 1, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
                $stmt = $conn->query("SELECT * FROM sliders ORDER BY sort_order ASC");
                return $stmt->fetchAll(PDO::FETCH_ASSOC);

            case 'quotes':
                $stmt = $conn->query("SELECT qr.*, u.full_name as user_name, u.phone as user_phone, v.brand, v.model, COALESCE(qr.status, 'pending') as status FROM quote_requests qr JOIN users u ON qr.user_id = u.id LEFT JOIN vehicles v ON qr.vehicle_id = v.id ORDER BY qr.created_at DESC LIMIT 50");
                return $stmt->fetchAll(PDO::FETCH_ASSOC);

            default:
                return [];
        }
    } catch (Exception $e) {
        return [];
    }
}

function getStats()
{
    try {
        $database = new Database();
        $conn = $database->getConnection();

        $stats = [];
        $stmt = $conn->query("SELECT COUNT(*) as count FROM users WHERE role_id = 1");
        $stats['users'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        $stmt = $conn->query("SELECT COUNT(*) as count FROM service_providers");
        $stats['providers'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        $stmt = $conn->query("SELECT COUNT(*) as count FROM subscription_packages");
        $stats['packages'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        $stmt = $conn->query("SELECT COUNT(*) as count FROM quote_requests WHERE MONTH(created_at) = MONTH(NOW())");
        $stats['quotes'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        return $stats;
    } catch (Exception $e) {
        return ['users' => 0, 'providers' => 0, 'packages' => 0, 'quotes' => 0];
    }
}

function getSettings()
{
    try {
        $database = new Database();
        $conn = $database->getConnection();

        $conn->exec("CREATE TABLE IF NOT EXISTS app_settings (id INT AUTO_INCREMENT PRIMARY KEY, setting_key VARCHAR(255) UNIQUE, setting_value TEXT, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)");

        $stmt = $conn->query("SELECT setting_key, setting_value FROM app_settings");
        $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        // Default values
        $defaults = [
            'app_name' => 'Oto Asist',
            'app_description' => 'Profesyonel otomotiv hizmetleri platformu',
            'contact_email' => 'info@otoasist.com',
            'contact_phone' => '+90 555 123 45 67',
            'maintenance_mode' => '0'
        ];

        return array_merge($defaults, $settings);
    } catch (Exception $e) {
        return [
            'app_name' => 'Oto Asist',
            'app_description' => 'Profesyonel otomotiv hizmetleri platformu',
            'contact_email' => 'info@otoasist.com',
            'contact_phone' => '+90 555 123 45 67',
            'maintenance_mode' => '0'
        ];
    }
}

// Get edit data for forms
$editData = null;
if (isset($_GET['edit']) && isset($_GET['id'])) {
    $editType = $_GET['edit'];
    $editId = $_GET['id'];

    try {
        $database = new Database();
        $conn = $database->getConnection();

        switch ($editType) {
            case 'user':
                $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$editId]);
                $editData = $stmt->fetch(PDO::FETCH_ASSOC);
                break;
            case 'provider':
                $stmt = $conn->prepare("SELECT * FROM service_providers WHERE id = ?");
                $stmt->execute([$editId]);
                $editData = $stmt->fetch(PDO::FETCH_ASSOC);
                break;
            case 'package':
                $stmt = $conn->prepare("SELECT * FROM subscription_packages WHERE id = ?");
                $stmt->execute([$editId]);
                $editData = $stmt->fetch(PDO::FETCH_ASSOC);
                break;
            case 'news':
                $stmt = $conn->prepare("SELECT * FROM news WHERE id = ?");
                $stmt->execute([$editId]);
                $editData = $stmt->fetch(PDO::FETCH_ASSOC);
                break;
            case 'slider':
                $stmt = $conn->prepare("SELECT * FROM sliders WHERE id = ?");
                $stmt->execute([$editId]);
                $editData = $stmt->fetch(PDO::FETCH_ASSOC);
                break;
            case 'quote':
                $stmt = $conn->prepare("SELECT * FROM quote_requests WHERE id = ?");
                $stmt->execute([$editId]);
                $editData = $stmt->fetch(PDO::FETCH_ASSOC);
                break;
        }
    } catch (Exception $e) {
        $editData = null;
    }
}

// Get quote details for modal
$quoteDetails = null;
if (isset($_GET['view_quote']) && isset($_GET['id'])) {
    try {
        $database = new Database();
        $conn = $database->getConnection();

        $stmt = $conn->prepare("
            SELECT qr.*, u.full_name as user_name, u.phone as user_phone, u.email as user_email, 
                   v.brand, v.model, v.year, v.plate 
            FROM quote_requests qr 
            JOIN users u ON qr.user_id = u.id 
            LEFT JOIN vehicles v ON qr.vehicle_id = v.id 
            WHERE qr.id = ?
        ");
        $stmt->execute([$_GET['id']]);
        $quoteDetails = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $quoteDetails = null;
    }
}

$action = $_GET['action'] ?? 'dashboard';
$data = [];
$stats = [];
$settings = [];

if (isAdminLoggedIn()) {
    $stats = getStats();
    if ($action !== 'dashboard') {
        $data = getData($action);
    }
    if ($action === 'settings') {
        $settings = getSettings();
    }
}
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Oto Asist - AdminMart Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --sidebar-width: 250px;
            --primary-color: #6366f1;
            --secondary-color: #64748b;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --dark-color: #1e293b;
            --light-color: #f8fafc;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--light-color);
            margin: 0;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: linear-gradient(180deg, var(--dark-color) 0%, #2d3748 100%);
            color: white;
            overflow-y: auto;
            z-index: 1000;
            box-shadow: 4px 0 15px rgba(0, 0, 0, 0.1);
        }

        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid #374151;
            text-align: center;
        }

        .sidebar-header h3 {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .sidebar-menu {
            padding: 1rem 0;
        }

        .menu-item {
            display: block;
            padding: 0.75rem 1.5rem;
            color: #cbd5e1;
            text-decoration: none;
            transition: all 0.3s;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
        }

        .menu-item:hover,
        .menu-item.active {
            background: var(--primary-color);
            color: white;
            transform: translateX(5px);
        }

        .menu-item i {
            width: 20px;
            margin-right: 10px;
        }

        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
        }

        .topbar {
            background: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .content-area {
            padding: 2rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border-left: 4px solid var(--primary-color);
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
        }

        .stat-card.users {
            border-left-color: var(--primary-color);
        }

        .stat-card.providers {
            border-left-color: var(--success-color);
        }

        .stat-card.packages {
            border-left-color: var(--warning-color);
        }

        .stat-card.quotes {
            border-left-color: var(--danger-color);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .stat-card.users .stat-number {
            color: var(--primary-color);
        }

        .stat-card.providers .stat-number {
            color: var(--success-color);
        }

        .stat-card.packages .stat-number {
            color: var(--warning-color);
        }

        .stat-card.quotes .stat-number {
            color: var(--danger-color);
        }

        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border: none;
            margin-bottom: 2rem;
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary-color), #8b5cf6);
            color: white;
            border-radius: 12px 12px 0 0;
            padding: 1rem 1.5rem;
            border: none;
        }

        .card-body {
            padding: 1.5rem;
        }

        .btn-primary {
            background: var(--primary-color);
            border: none;
            border-radius: 8px;
            padding: 0.5rem 1rem;
            font-weight: 500;
        }

        .btn-danger {
            background: var(--danger-color);
            border: none;
            border-radius: 8px;
        }

        .btn-warning {
            background: var(--warning-color);
            border: none;
            border-radius: 8px;
        }

        .btn-success {
            background: var(--success-color);
            border: none;
            border-radius: 8px;
        }

        .form-control {
            border-radius: 8px;
            border: 2px solid #e5e7eb;
            padding: 0.75rem;
            transition: border-color 0.3s;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(99, 102, 241, 0.25);
        }

        .table {
            border-radius: 8px;
            overflow: hidden;
        }

        .table thead th {
            background: var(--dark-color);
            color: white;
            border: none;
            font-weight: 600;
            padding: 1rem;
        }

        .table tbody tr:hover {
            background: #f1f5f9;
        }

        .badge {
            padding: 0.5rem 0.75rem;
            border-radius: 6px;
            font-weight: 500;
        }

        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--primary-color), #8b5cf6);
        }

        .login-card {
            background: white;
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
            width: 100%;
            max-width: 400px;
        }

        .alert {
            border-radius: 8px;
            border: none;
            padding: 1rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s;
            }

            .main-content {
                margin-left: 0;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <?php if (!isAdminLoggedIn()): ?>
        <div class="login-container">
            <div class="login-card">
                <div class="text-center mb-4">
                    <h2><i class="fas fa-car text-primary"></i> Oto Asist</h2>
                    <p class="text-muted">AdminMart Panel Girişi</p>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="action" value="login">
                    <div class="mb-3">
                        <label class="form-label">Admin Token</label>
                        <input type="text" name="token" class="form-control" value="+905551234567" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-sign-in-alt me-2"></i>Giriş Yap
                    </button>
                </form>

                <div class="mt-4 p-3 bg-light rounded">
                    <small class="text-muted">
                        <strong>Demo Token:</strong> +905551234567<br>
                        <strong>Kullanıcı:</strong> Ahmet Yılmaz
                    </small>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h3><i class="fas fa-car"></i> Oto Asist</h3>
                <small>AdminMart Panel</small>
            </div>

            <div class="sidebar-menu">
                <a href="?action=dashboard" class="menu-item <?= $action === 'dashboard' ? 'active' : '' ?>">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="?action=users" class="menu-item <?= $action === 'users' ? 'active' : '' ?>">
                    <i class="fas fa-users"></i> Kullanıcılar
                </a>
                <a href="?action=providers" class="menu-item <?= $action === 'providers' ? 'active' : '' ?>">
                    <i class="fas fa-building"></i> Servis Sağlayıcılar
                </a>
                <a href="?action=packages" class="menu-item <?= $action === 'packages' ? 'active' : '' ?>">
                    <i class="fas fa-box"></i> Paketler
                </a>
                <a href="?action=news" class="menu-item <?= $action === 'news' ? 'active' : '' ?>">
                    <i class="fas fa-newspaper"></i> Haberler
                </a>
                <a href="?action=sliders" class="menu-item <?= $action === 'sliders' ? 'active' : '' ?>">
                    <i class="fas fa-images"></i> Sliderlar
                </a>
                <a href="?action=quotes" class="menu-item <?= $action === 'quotes' ? 'active' : '' ?>">
                    <i class="fas fa-comments"></i> Teklifler
                </a>
                <div style="border-top: 1px solid #374151; margin: 1rem 0;"></div>
                <a href="?action=profile" class="menu-item <?= $action === 'profile' ? 'active' : '' ?>">
                    <i class="fas fa-user-cog"></i> Profil
                </a>
                <a href="?action=settings" class="menu-item <?= $action === 'settings' ? 'active' : '' ?>">
                    <i class="fas fa-cogs"></i> Ayarlar
                </a>
                <a href="?logout=1" class="menu-item">
                    <i class="fas fa-sign-out-alt"></i> Çıkış
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="topbar">
                <h4 class="mb-0">
                    <?php
                    $titles = [
                        'dashboard' => 'Dashboard',
                        'users' => 'Kullanıcı Yönetimi',
                        'providers' => 'Servis Sağlayıcılar',
                        'packages' => 'Paket Yönetimi',
                        'news' => 'Haber Yönetimi',
                        'sliders' => 'Slider Yönetimi',
                        'quotes' => 'Teklif Yönetimi',
                        'profile' => 'Profil Ayarları',
                        'settings' => 'Sistem Ayarları'
                    ];
                    echo $titles[$action] ?? 'Yönetim Paneli';
                    ?>
                </h4>
                <div>
                    <span class="text-muted">Hoş geldiniz, <?= htmlspecialchars($_SESSION['admin_user']['full_name']) ?></span>
                </div>
            </div>

            <div class="content-area">
                <?php if (isset($message)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <?php if ($action === 'dashboard'): ?>
                    <!-- Dashboard Stats -->
                    <div class="stats-grid">
                        <div class="stat-card users">
                            <div class="stat-number"><?= $stats['users'] ?></div>
                            <div class="text-muted">Toplam Kullanıcı</div>
                        </div>
                        <div class="stat-card providers">
                            <div class="stat-number"><?= $stats['providers'] ?></div>
                            <div class="text-muted">Servis Sağlayıcı</div>
                        </div>
                        <div class="stat-card packages">
                            <div class="stat-number"><?= $stats['packages'] ?></div>
                            <div class="text-muted">Abonelik Paketi</div>
                        </div>
                        <div class="stat-card quotes">
                            <div class="stat-number"><?= $stats['quotes'] ?></div>
                            <div class="text-muted">Aylık Teklif</div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5><i class="fas fa-chart-line me-2"></i>Son Aktiviteler</h5>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted">✅ AdminMart temalı modern panel aktif</p>
                                    <p class="text-muted">✅ CRUD işlemleri çalışıyor</p>
                                    <p class="text-muted">✅ Demo veriler yüklendi</p>
                                    <p class="text-muted">✅ Edit özellikleri eklendi</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5><i class="fas fa-cogs me-2"></i>Sistem Bilgileri</h5>
                                </div>
                                <div class="card-body">
                                    <p><strong>Tema:</strong> AdminMart</p>
                                    <p><strong>Versiyon:</strong> 2.0</p>
                                    <p><strong>PHP:</strong> <?= PHP_VERSION ?></p>
                                    <p><strong>Durum:</strong> <span class="badge bg-success">Aktif</span></p>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- CRUD Sections -->

                    <?php if ($action === 'users'): ?>
                        <!-- User Management -->
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-users me-2"></i>Kullanıcı Yönetimi</h5>
                            </div>
                            <div class="card-body">
                                <!-- Add User Form -->
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <h6>Yeni Kullanıcı Ekle</h6>
                                        <form method="POST" class="row g-3">
                                            <input type="hidden" name="action" value="<?= $editData ? 'update_user' : 'create_user' ?>">
                                            <?php if ($editData): ?>
                                                <input type="hidden" name="user_id" value="<?= $editData['id'] ?>">
                                            <?php endif; ?>
                                            <div class="col-md-3">
                                                <input type="text" name="full_name" class="form-control" placeholder="Ad Soyad" value="<?= htmlspecialchars($editData['full_name'] ?? '') ?>" required>
                                            </div>
                                            <div class="col-md-3">
                                                <input type="email" name="email" class="form-control" placeholder="Email" value="<?= htmlspecialchars($editData['email'] ?? '') ?>">
                                            </div>
                                            <div class="col-md-2">
                                                <input type="text" name="phone" class="form-control" placeholder="Telefon" value="<?= htmlspecialchars($editData['phone'] ?? '') ?>" required>
                                            </div>
                                            <div class="col-md-2">
                                                <input type="password" name="password" class="form-control" placeholder="<?= $editData ? 'Yeni Şifre (boş bırakılabilir)' : 'Şifre' ?>" <?= !$editData ? 'required' : '' ?>>
                                            </div>
                                            <div class="col-md-1">
                                                <select name="role_id" class="form-control">
                                                    <option value="1" <?= ($editData['role_id'] ?? 1) == 1 ? 'selected' : '' ?>>User</option>
                                                    <option value="2" <?= ($editData['role_id'] ?? 1) == 2 ? 'selected' : '' ?>>Provider</option>
                                                    <option value="3" <?= ($editData['role_id'] ?? 1) == 3 ? 'selected' : '' ?>>Admin</option>
                                                </select>
                                            </div>
                                            <div class="col-md-1">
                                                <button type="submit" class="btn btn-primary">
                                                    <?= $editData ? 'Güncelle' : 'Ekle' ?>
                                                </button>
                                                <?php if ($editData): ?>
                                                    <a href="?action=users" class="btn btn-secondary">İptal</a>
                                                <?php endif; ?>
                                            </div>
                                        </form>
                                    </div>
                                </div>

                                <!-- Filter for Users -->
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <input type="text" id="filterUsers" class="form-control" placeholder="🔍 Kullanıcı ara...">
                                    </div>
                                    <div class="col-md-3">
                                        <select id="filterUserRole" class="form-control">
                                            <option value="">Tüm Roller</option>
                                            <?php
                                            $roles = array_unique(array_column($data, 'role_name'));
                                            sort($roles);
                                            foreach ($roles as $role):
                                                if (!empty($role)):
                                            ?>
                                                    <option value="<?= htmlspecialchars($role) ?>"><?= htmlspecialchars($role) ?></option>
                                            <?php
                                                endif;
                                            endforeach;
                                            ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-secondary" onclick="clearFilters('users')">
                                            <i class="fas fa-times"></i> Temizle
                                        </button>
                                    </div>
                                </div>

                                <!-- Users Table -->
                                <?php if (!empty($data)): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover" id="usersTable">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Ad Soyad</th>
                                                    <th>Telefon</th>
                                                    <th>Email</th>
                                                    <th>Rol</th>
                                                    <th>İşlemler</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($data as $user): ?>
                                                    <tr>
                                                        <td><?= $user['id'] ?></td>
                                                        <td><?= htmlspecialchars($user['full_name']) ?></td>
                                                        <td><?= htmlspecialchars($user['phone']) ?></td>
                                                        <td><?= htmlspecialchars($user['email']) ?></td>
                                                        <td><span class="badge bg-info"><?= $user['role_name'] ?? 'User' ?></span></td>
                                                        <td class="action-buttons">
                                                            <a href="?action=users&edit=user&id=<?= $user['id'] ?>" class="btn btn-sm btn-warning">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <?php if ($user['role_id'] != 3): ?>
                                                                <form method="POST" style="display: inline;">
                                                                    <input type="hidden" name="action" value="delete_user">
                                                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Emin misiniz?')">
                                                                        <i class="fas fa-trash"></i>
                                                                    </button>
                                                                </form>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info">Henüz kullanıcı bulunmuyor.</div>
                                <?php endif; ?>
                            </div>
                        </div>

                    <?php elseif ($action === 'packages'): ?>
                        <!-- Package Management -->
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-box me-2"></i>Paket Yönetimi</h5>
                            </div>
                            <div class="card-body">
                                <!-- Add Package Form -->
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <h6><?= $editData ? 'Paket Düzenle' : 'Yeni Paket Ekle' ?></h6>
                                        <form method="POST" class="row g-3">
                                            <input type="hidden" name="action" value="<?= $editData ? 'update_package' : 'create_package' ?>">
                                            <?php if ($editData): ?>
                                                <input type="hidden" name="package_id" value="<?= $editData['id'] ?>">
                                            <?php endif; ?>
                                            <div class="col-md-3">
                                                <input type="text" name="name" class="form-control" placeholder="Paket Adı" value="<?= htmlspecialchars($editData['name'] ?? '') ?>" required>
                                            </div>
                                            <div class="col-md-3">
                                                <input type="text" name="description" class="form-control" placeholder="Açıklama" value="<?= htmlspecialchars($editData['description'] ?? '') ?>">
                                            </div>
                                            <div class="col-md-2">
                                                <input type="number" name="price" class="form-control" placeholder="Fiyat" step="0.01" value="<?= $editData['price'] ?? '' ?>" required>
                                            </div>
                                            <div class="col-md-2">
                                                <input type="number" name="duration_months" class="form-control" placeholder="Süre (Ay)" value="<?= $editData['duration_months'] ?? '' ?>" required>
                                            </div>
                                            <div class="col-md-2">
                                                <button type="submit" class="btn btn-primary">
                                                    <?= $editData ? 'Güncelle' : 'Ekle' ?>
                                                </button>
                                                <?php if ($editData): ?>
                                                    <a href="?action=packages" class="btn btn-secondary">İptal</a>
                                                <?php endif; ?>
                                            </div>
                                        </form>
                                    </div>
                                </div>

                                <!-- Filter for Packages -->
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <input type="text" id="filterPackages" class="form-control" placeholder="🔍 Paket ara...">
                                    </div>
                                    <div class="col-md-3">
                                        <input type="number" id="filterMinPrice" class="form-control" placeholder="Min Fiyat" step="0.01">
                                    </div>
                                    <div class="col-md-3">
                                        <input type="number" id="filterMaxPrice" class="form-control" placeholder="Max Fiyat" step="0.01">
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-secondary" onclick="clearFilters('packages')">
                                            <i class="fas fa-times"></i> Temizle
                                        </button>
                                    </div>
                                </div>

                                <!-- Packages Table -->
                                <?php if (!empty($data)): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover" id="packagesTable">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Paket Adı</th>
                                                    <th>Fiyat</th>
                                                    <th>Süre</th>
                                                    <th>Abonelik</th>
                                                    <th>İşlemler</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($data as $package): ?>
                                                    <tr>
                                                        <td><?= $package['id'] ?></td>
                                                        <td><?= htmlspecialchars($package['name']) ?></td>
                                                        <td><span class="badge bg-success"><?= $package['price'] ?> ₺</span></td>
                                                        <td><?= $package['duration_months'] ?> ay</td>
                                                        <td><?= $package['subscriptions'] ?? 0 ?></td>
                                                        <td class="action-buttons">
                                                            <a href="?action=packages&edit=package&id=<?= $package['id'] ?>" class="btn btn-sm btn-warning">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <form method="POST" style="display: inline;">
                                                                <input type="hidden" name="action" value="delete_package">
                                                                <input type="hidden" name="package_id" value="<?= $package['id'] ?>">
                                                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Emin misiniz?')">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info">Henüz paket bulunmuyor.</div>
                                <?php endif; ?>
                            </div>
                        </div>

                    <?php elseif ($action === 'providers'): ?>
                        <!-- Providers Management -->
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-building me-2"></i>Servis Sağlayıcı Yönetimi</h5>
                            </div>
                            <div class="card-body">
                                <!-- Edit Provider Form -->
                                <?php if ($editData): ?>
                                    <div class="row mb-4">
                                        <div class="col-12">
                                            <h6>Servis Sağlayıcı Düzenle</h6>
                                            <form method="POST" class="row g-3">
                                                <input type="hidden" name="action" value="update_provider">
                                                <input type="hidden" name="provider_id" value="<?= $editData['id'] ?>">
                                                <div class="col-md-3">
                                                    <input type="text" name="company_name" class="form-control" placeholder="Şirket Adı" value="<?= htmlspecialchars($editData['company_name']) ?>" required>
                                                </div>
                                                <div class="col-md-3">
                                                    <input type="text" name="city" class="form-control" placeholder="Şehir" value="<?= htmlspecialchars($editData['city']) ?>">
                                                </div>
                                                <div class="col-md-3">
                                                    <input type="text" name="services" class="form-control" placeholder="Hizmetler" value="<?= htmlspecialchars($editData['services']) ?>">
                                                </div>
                                                <div class="col-md-3">
                                                    <textarea name="description" class="form-control" placeholder="Açıklama"><?= htmlspecialchars($editData['description']) ?></textarea>
                                                </div>
                                                <div class="col-12">
                                                    <button type="submit" class="btn btn-primary">Güncelle</button>
                                                    <a href="?action=providers" class="btn btn-secondary">İptal</a>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Filter for Providers -->
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <input type="text" id="filterProviders" class="form-control" placeholder="🔍 Servis sağlayıcı ara...">
                                    </div>
                                    <div class="col-md-3">
                                        <select id="filterProviderCity" class="form-control">
                                            <option value="">Tüm Şehirler</option>
                                            <?php
                                            $cities = array_unique(array_column($data, 'city'));
                                            sort($cities);
                                            foreach ($cities as $city):
                                                if (!empty($city)):
                                            ?>
                                                    <option value="<?= htmlspecialchars($city) ?>"><?= htmlspecialchars($city) ?></option>
                                            <?php
                                                endif;
                                            endforeach;
                                            ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-secondary" onclick="clearFilters('providers')">
                                            <i class="fas fa-times"></i> Temizle
                                        </button>
                                    </div>
                                </div>

                                <!-- Providers Table -->
                                <?php if (!empty($data)): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover" id="providersTable">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Şirket</th>
                                                    <th>İletişim</th>
                                                    <th>Telefon</th>
                                                    <th>Şehir</th>
                                                    <th>Hizmetler</th>
                                                    <th>İşlemler</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($data as $provider): ?>
                                                    <tr>
                                                        <td><?= $provider['id'] ?></td>
                                                        <td><?= htmlspecialchars($provider['company_name']) ?></td>
                                                        <td><?= htmlspecialchars($provider['full_name']) ?></td>
                                                        <td><?= htmlspecialchars($provider['phone']) ?></td>
                                                        <td><?= htmlspecialchars($provider['city']) ?></td>
                                                        <td><?= htmlspecialchars(substr($provider['services'], 0, 30)) ?>...</td>
                                                        <td class="action-buttons">
                                                            <a href="?action=providers&edit=provider&id=<?= $provider['id'] ?>" class="btn btn-sm btn-warning">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <form method="POST" style="display: inline;">
                                                                <input type="hidden" name="action" value="delete_provider">
                                                                <input type="hidden" name="provider_id" value="<?= $provider['id'] ?>">
                                                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Emin misiniz?')">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info">Henüz servis sağlayıcı bulunmuyor.</div>
                                <?php endif; ?>
                            </div>
                        </div>

                    <?php elseif ($action === 'news'): ?>
                        <!-- News Management -->
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-newspaper me-2"></i>Haber Yönetimi</h5>
                            </div>
                            <div class="card-body">
                                <!-- Add/Edit News Form -->
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <h6><?= $editData ? 'Haber Düzenle' : 'Yeni Haber Ekle' ?></h6>
                                        <form method="POST">
                                            <input type="hidden" name="action" value="<?= $editData ? 'update_news' : 'create_news' ?>">
                                            <?php if ($editData): ?>
                                                <input type="hidden" name="news_id" value="<?= $editData['id'] ?>">
                                            <?php endif; ?>
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <input type="text" name="title" class="form-control" placeholder="Başlık" value="<?= htmlspecialchars($editData['title'] ?? '') ?>" required>
                                                </div>
                                                <div class="col-md-3">
                                                    <select name="category" class="form-control">
                                                        <option value="general" <?= ($editData['category'] ?? '') == 'general' ? 'selected' : '' ?>>Genel</option>
                                                        <option value="teknoloji" <?= ($editData['category'] ?? '') == 'teknoloji' ? 'selected' : '' ?>>Teknoloji</option>
                                                        <option value="sigorta" <?= ($editData['category'] ?? '') == 'sigorta' ? 'selected' : '' ?>>Sigorta</option>
                                                        <option value="bakım" <?= ($editData['category'] ?? '') == 'bakım' ? 'selected' : '' ?>>Bakım</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-3">
                                                    <input type="url" name="image_url" class="form-control" placeholder="Resim URL" value="<?= htmlspecialchars($editData['image_url'] ?? '') ?>">
                                                </div>
                                                <div class="col-12">
                                                    <textarea name="excerpt" class="form-control" placeholder="Özet" rows="2"><?= htmlspecialchars($editData['excerpt'] ?? '') ?></textarea>
                                                </div>
                                                <div class="col-12">
                                                    <textarea name="content" class="form-control" placeholder="İçerik" rows="5" required><?= htmlspecialchars($editData['content'] ?? '') ?></textarea>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-check">
                                                        <input type="checkbox" name="is_featured" class="form-check-input" <?= ($editData['is_featured'] ?? 0) ? 'checked' : '' ?>>
                                                        <label class="form-check-label">Öne Çıkan</label>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-check">
                                                        <input type="checkbox" name="is_sponsored" class="form-check-input" <?= ($editData['is_sponsored'] ?? 0) ? 'checked' : '' ?>>
                                                        <label class="form-check-label">Sponsor Haber</label>
                                                    </div>
                                                </div>
                                                <div class="col-12">
                                                    <button type="submit" class="btn btn-primary"><?= $editData ? 'Güncelle' : 'Ekle' ?></button>
                                                    <?php if ($editData): ?>
                                                        <a href="?action=news" class="btn btn-secondary">İptal</a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>

                                <!-- Filter for News -->
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <input type="text" id="filterNews" class="form-control" placeholder="🔍 Haber ara...">
                                    </div>
                                    <div class="col-md-3">
                                        <select id="filterNewsCategory" class="form-control">
                                            <option value="">Tüm Kategoriler</option>
                                            <?php
                                            $categories = array_unique(array_column($data, 'category'));
                                            sort($categories);
                                            foreach ($categories as $category):
                                                if (!empty($category)):
                                            ?>
                                                    <option value="<?= htmlspecialchars($category) ?>"><?= htmlspecialchars($category) ?></option>
                                            <?php
                                                endif;
                                            endforeach;
                                            ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <select id="filterNewsType" class="form-control">
                                            <option value="">Tüm Tipler</option>
                                            <option value="Normal">Normal</option>
                                            <option value="Sponsor">Sponsor</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-secondary" onclick="clearFilters('news')">
                                            <i class="fas fa-times"></i> Temizle
                                        </button>
                                    </div>
                                </div>

                                <!-- News Table -->
                                <?php if (!empty($data)): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover" id="newsTable">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Başlık</th>
                                                    <th>Kategori</th>
                                                    <th>Tip</th>
                                                    <th>Yazar</th>
                                                    <th>İşlemler</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($data as $news): ?>
                                                    <tr>
                                                        <td><?= $news['id'] ?></td>
                                                        <td><?= htmlspecialchars(substr($news['title'], 0, 40)) ?>...</td>
                                                        <td><span class="badge bg-info"><?= $news['category'] ?></span></td>
                                                        <td><span class="badge bg-<?= $news['is_sponsored'] ? 'warning' : 'primary' ?>"><?= $news['news_type'] ?></span></td>
                                                        <td><?= htmlspecialchars($news['author']) ?></td>
                                                        <td class="action-buttons">
                                                            <a href="?action=news&edit=news&id=<?= $news['id'] ?>" class="btn btn-sm btn-warning">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <form method="POST" style="display: inline;">
                                                                <input type="hidden" name="action" value="delete_news">
                                                                <input type="hidden" name="news_id" value="<?= $news['id'] ?>">
                                                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Emin misiniz?')">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info">Henüz haber bulunmuyor.</div>
                                <?php endif; ?>
                            </div>
                        </div>

                    <?php elseif ($action === 'sliders'): ?>
                        <!-- Slider Management -->
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-images me-2"></i>Slider Yönetimi</h5>
                            </div>
                            <div class="card-body">
                                <!-- Add/Edit Slider Form -->
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <h6><?= $editData ? 'Slider Düzenle' : 'Yeni Slider Ekle' ?></h6>
                                        <form method="POST" class="row g-3">
                                            <input type="hidden" name="action" value="<?= $editData ? 'update_slider' : 'create_slider' ?>">
                                            <?php if ($editData): ?>
                                                <input type="hidden" name="slider_id" value="<?= $editData['id'] ?>">
                                            <?php endif; ?>
                                            <div class="col-md-4">
                                                <input type="text" name="title" class="form-control" placeholder="Başlık" value="<?= htmlspecialchars($editData['title'] ?? '') ?>" required>
                                            </div>
                                            <div class="col-md-4">
                                                <input type="url" name="image_url" class="form-control" placeholder="Resim URL" value="<?= htmlspecialchars($editData['image_url'] ?? '') ?>" required>
                                            </div>
                                            <div class="col-md-2">
                                                <input type="url" name="link_url" class="form-control" placeholder="Link URL" value="<?= htmlspecialchars($editData['link_url'] ?? '') ?>">
                                            </div>
                                            <div class="col-md-2">
                                                <input type="number" name="sort_order" class="form-control" placeholder="Sıra" value="<?= $editData['sort_order'] ?? 0 ?>">
                                            </div>
                                            <div class="col-12">
                                                <textarea name="description" class="form-control" placeholder="Açıklama" rows="2"><?= htmlspecialchars($editData['description'] ?? '') ?></textarea>
                                            </div>
                                            <?php if ($editData): ?>
                                                <div class="col-md-2">
                                                    <div class="form-check">
                                                        <input type="checkbox" name="is_active" class="form-check-input" <?= ($editData['is_active'] ?? 1) ? 'checked' : '' ?>>
                                                        <label class="form-check-label">Aktif</label>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                            <div class="col-12">
                                                <button type="submit" class="btn btn-primary"><?= $editData ? 'Güncelle' : 'Ekle' ?></button>
                                                <?php if ($editData): ?>
                                                    <a href="?action=sliders" class="btn btn-secondary">İptal</a>
                                                <?php endif; ?>
                                            </div>
                                        </form>
                                    </div>
                                </div>

                                <!-- Filter for Sliders -->
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <input type="text" id="filterSliders" class="form-control" placeholder="🔍 Slider ara...">
                                    </div>
                                    <div class="col-md-3">
                                        <select id="filterSliderStatus" class="form-control">
                                            <option value="">Tüm Durumlar</option>
                                            <option value="Aktif">Aktif</option>
                                            <option value="Pasif">Pasif</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-secondary" onclick="clearFilters('sliders')">
                                            <i class="fas fa-times"></i> Temizle
                                        </button>
                                    </div>
                                </div>

                                <!-- Sliders Table -->
                                <?php if (!empty($data)): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover" id="slidersTable">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Başlık</th>
                                                    <th>Açıklama</th>
                                                    <th>Sıra</th>
                                                    <th>Tıklama</th>
                                                    <th>Aktif</th>
                                                    <th>İşlemler</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($data as $slider): ?>
                                                    <tr>
                                                        <td><?= $slider['id'] ?></td>
                                                        <td><?= htmlspecialchars($slider['title']) ?></td>
                                                        <td><?= htmlspecialchars(substr($slider['description'], 0, 30)) ?>...</td>
                                                        <td><?= $slider['sort_order'] ?></td>
                                                        <td><span class="badge bg-primary"><?= $slider['click_count'] ?? 0 ?></span></td>
                                                        <td><span class="badge bg-<?= $slider['is_active'] ? 'success' : 'secondary' ?>"><?= $slider['is_active'] ? 'Aktif' : 'Pasif' ?></span></td>
                                                        <td class="action-buttons">
                                                            <a href="?action=sliders&edit=slider&id=<?= $slider['id'] ?>" class="btn btn-sm btn-warning">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <form method="POST" style="display: inline;">
                                                                <input type="hidden" name="action" value="delete_slider">
                                                                <input type="hidden" name="slider_id" value="<?= $slider['id'] ?>">
                                                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Emin misiniz?')">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info">Henüz slider bulunmuyor.</div>
                                <?php endif; ?>
                            </div>
                        </div>

                    <?php elseif ($action === 'quotes'): ?>
                        <!-- Quotes Management -->
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-comments me-2"></i>Teklif Yönetimi</h5>
                            </div>
                            <div class="card-body">
                                <!-- Edit Quote Form -->
                                <?php if ($editData): ?>
                                    <div class="row mb-4">
                                        <div class="col-12">
                                            <h6>Teklif Düzenle</h6>
                                            <form method="POST" class="row g-3">
                                                <input type="hidden" name="action" value="update_quote">
                                                <input type="hidden" name="quote_id" value="<?= $editData['id'] ?>">
                                                <div class="col-md-4">
                                                    <input type="text" name="title" class="form-control" placeholder="Başlık" value="<?= htmlspecialchars($editData['title']) ?>" required>
                                                </div>
                                                <div class="col-md-3">
                                                    <select name="status" class="form-control">
                                                        <option value="pending" <?= $editData['status'] == 'pending' ? 'selected' : '' ?>>Bekliyor</option>
                                                        <option value="approved" <?= $editData['status'] == 'approved' ? 'selected' : '' ?>>Onaylandı</option>
                                                        <option value="rejected" <?= $editData['status'] == 'rejected' ? 'selected' : '' ?>>Reddedildi</option>
                                                        <option value="completed" <?= $editData['status'] == 'completed' ? 'selected' : '' ?>>Tamamlandı</option>
                                                    </select>
                                                </div>
                                                <div class="col-12">
                                                    <textarea name="description" class="form-control" placeholder="Açıklama" rows="3"><?= htmlspecialchars($editData['description']) ?></textarea>
                                                </div>
                                                <div class="col-12">
                                                    <button type="submit" class="btn btn-primary">Güncelle</button>
                                                    <a href="?action=quotes" class="btn btn-secondary">İptal</a>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Filter for Quotes -->
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <input type="text" id="filterQuotes" class="form-control" placeholder="🔍 Teklif ara...">
                                    </div>
                                    <div class="col-md-3">
                                        <select id="filterQuoteStatus" class="form-control">
                                            <option value="">Tüm Durumlar</option>
                                            <option value="pending">Bekliyor</option>
                                            <option value="approved">Onaylandı</option>
                                            <option value="rejected">Reddedildi</option>
                                            <option value="completed">Tamamlandı</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-secondary" onclick="clearFilters('quotes')">
                                            <i class="fas fa-times"></i> Temizle
                                        </button>
                                    </div>
                                </div>

                                <!-- Quotes Table -->
                                <?php if (!empty($data)): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover" id="quotesTable">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Başlık</th>
                                                    <th>Kullanıcı</th>
                                                    <th>Telefon</th>
                                                    <th>Araç</th>
                                                    <th>Durum</th>
                                                    <th>İşlemler</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($data as $quote): ?>
                                                    <tr>
                                                        <td><?= $quote['id'] ?></td>
                                                        <td><?= htmlspecialchars($quote['title']) ?></td>
                                                        <td><?= htmlspecialchars($quote['user_name']) ?></td>
                                                        <td><?= htmlspecialchars($quote['user_phone']) ?></td>
                                                        <td><?= htmlspecialchars($quote['brand'] . ' ' . $quote['model']) ?></td>
                                                        <td><span class="badge bg-info"><?= $quote['status'] ?></span></td>
                                                        <td class="action-buttons">
                                                            <a href="?view_quote=1&id=<?= $quote['id'] ?>" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#quoteModal">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                            <a href="?action=quotes&edit=quote&id=<?= $quote['id'] ?>" class="btn btn-sm btn-warning">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <form method="POST" style="display: inline;">
                                                                <input type="hidden" name="action" value="delete_quote_request">
                                                                <input type="hidden" name="quote_request_id" value="<?= $quote['id'] ?>">
                                                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Emin misiniz?')">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info">Henüz teklif talebi bulunmuyor.</div>
                                <?php endif; ?>
                            </div>
                        </div>

                    <?php elseif ($action === 'profile'): ?>
                        <!-- Profile Management -->
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-user-cog me-2"></i>Profil Ayarları</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" class="row g-3">
                                    <input type="hidden" name="action" value="update_profile">
                                    <div class="col-md-6">
                                        <label class="form-label">Ad Soyad</label>
                                        <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($_SESSION['admin_user']['full_name']) ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Email</label>
                                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($_SESSION['admin_user']['email']) ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Telefon</label>
                                        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($_SESSION['admin_user']['phone']) ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Yeni Şifre (Boş bırakılabilir)</label>
                                        <input type="password" name="password" class="form-control" placeholder="Yeni şifre girmek için tıklayın">
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>Profili Güncelle
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                    <?php elseif ($action === 'settings'): ?>
                        <!-- Settings Management -->
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-cogs me-2"></i>Sistem Ayarları</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" class="row g-3">
                                    <input type="hidden" name="action" value="update_settings">
                                    <div class="col-md-6">
                                        <label class="form-label">Uygulama Adı</label>
                                        <input type="text" name="app_name" class="form-control" value="<?= htmlspecialchars($settings['app_name']) ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">İletişim Telefonu</label>
                                        <input type="text" name="contact_phone" class="form-control" value="<?= htmlspecialchars($settings['contact_phone']) ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">İletişim Email</label>
                                        <input type="email" name="contact_email" class="form-control" value="<?= htmlspecialchars($settings['contact_email']) ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check mt-4">
                                            <input type="checkbox" name="maintenance_mode" class="form-check-input" <?= $settings['maintenance_mode'] ? 'checked' : '' ?>>
                                            <label class="form-check-label">Bakım Modu</label>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Uygulama Açıklaması</label>
                                        <textarea name="app_description" class="form-control" rows="3"><?= htmlspecialchars($settings['app_description']) ?></textarea>
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>Ayarları Kaydet
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>Bu bölüm yakında eklenecek.
                        </div>
                    <?php endif; ?>

                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Quote Detail Modal -->
    <?php if ($quoteDetails): ?>
        <div class="modal fade" id="quoteModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Teklif Detayları</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Kullanıcı:</strong> <?= htmlspecialchars($quoteDetails['user_name']) ?><br>
                                <strong>Telefon:</strong> <?= htmlspecialchars($quoteDetails['user_phone']) ?><br>
                                <strong>Email:</strong> <?= htmlspecialchars($quoteDetails['user_email'] ?: 'Belirtilmemiş') ?><br>
                            </div>
                            <div class="col-md-6">
                                <strong>Araç:</strong> <?= htmlspecialchars($quoteDetails['brand'] . ' ' . $quoteDetails['model'] . ' (' . $quoteDetails['year'] . ')') ?><br>
                                <strong>Plaka:</strong> <?= htmlspecialchars($quoteDetails['plate'] ?: 'Belirtilmemiş') ?><br>
                                <strong>Durum:</strong> <span class="badge bg-info"><?= $quoteDetails['status'] ?></span><br>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-12">
                                <strong>Başlık:</strong> <?= htmlspecialchars($quoteDetails['title']) ?><br><br>
                                <strong>Açıklama:</strong><br>
                                <?= nl2br(htmlspecialchars($quoteDetails['description'])) ?>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                        <a href="?action=quotes&edit=quote&id=<?= $quoteDetails['id'] ?>" class="btn btn-primary">Düzenle</a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-hide alerts after 3 seconds
        setTimeout(function() {
            document.querySelectorAll('.alert').forEach(function(alert) {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            });
        }, 3000);

        // Edit functions
        function editItem(type, id) {
            window.location.href = `?action=${type}&edit=${type}&id=${id}`;
        }

        // Auto-show modal if quote details are loaded
        <?php if ($quoteDetails): ?>
            document.addEventListener('DOMContentLoaded', function() {
                var modal = new bootstrap.Modal(document.getElementById('quoteModal'));
                modal.show();
            });
        <?php endif; ?>

        // Filtering functions
        function setupFilters() {
            // Users filtering
            const userFilter = document.getElementById('filterUsers');
            const userRoleFilter = document.getElementById('filterUserRole');
            if (userFilter && userRoleFilter) {
                userFilter.addEventListener('input', filterUsers);
                userRoleFilter.addEventListener('change', filterUsers);
            }

            // Providers filtering
            const providerFilter = document.getElementById('filterProviders');
            const providerCityFilter = document.getElementById('filterProviderCity');
            if (providerFilter && providerCityFilter) {
                providerFilter.addEventListener('input', filterProviders);
                providerCityFilter.addEventListener('change', filterProviders);
            }

            // News filtering
            const newsFilter = document.getElementById('filterNews');
            const newsCategoryFilter = document.getElementById('filterNewsCategory');
            const newsTypeFilter = document.getElementById('filterNewsType');
            if (newsFilter && newsCategoryFilter && newsTypeFilter) {
                newsFilter.addEventListener('input', filterNews);
                newsCategoryFilter.addEventListener('change', filterNews);
                newsTypeFilter.addEventListener('change', filterNews);
            }

            // Sliders filtering
            const sliderFilter = document.getElementById('filterSliders');
            const sliderStatusFilter = document.getElementById('filterSliderStatus');
            if (sliderFilter && sliderStatusFilter) {
                sliderFilter.addEventListener('input', filterSliders);
                sliderStatusFilter.addEventListener('change', filterSliders);
            }

            // Quotes filtering
            const quoteFilter = document.getElementById('filterQuotes');
            const quoteStatusFilter = document.getElementById('filterQuoteStatus');
            if (quoteFilter && quoteStatusFilter) {
                quoteFilter.addEventListener('input', filterQuotes);
                quoteStatusFilter.addEventListener('change', filterQuotes);
            }

            // Packages filtering
            const packageFilter = document.getElementById('filterPackages');
            const minPriceFilter = document.getElementById('filterMinPrice');
            const maxPriceFilter = document.getElementById('filterMaxPrice');
            if (packageFilter && minPriceFilter && maxPriceFilter) {
                packageFilter.addEventListener('input', filterPackages);
                minPriceFilter.addEventListener('input', filterPackages);
                maxPriceFilter.addEventListener('input', filterPackages);
            }
        }

        function filterUsers() {
            const searchText = document.getElementById('filterUsers').value.toLowerCase();
            const selectedRole = document.getElementById('filterUserRole').value;
            const table = document.getElementById('usersTable');

            if (!table) return;

            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

            for (let row of rows) {
                const name = row.cells[1].textContent.toLowerCase();
                const phone = row.cells[2].textContent.toLowerCase();
                const email = row.cells[3].textContent.toLowerCase();
                const role = row.cells[4].textContent.trim();

                const matchesSearch = name.includes(searchText) || phone.includes(searchText) || email.includes(searchText);
                const matchesRole = !selectedRole || role.includes(selectedRole);

                row.style.display = matchesSearch && matchesRole ? '' : 'none';
            }
        }

        function filterProviders() {
            const searchText = document.getElementById('filterProviders').value.toLowerCase();
            const selectedCity = document.getElementById('filterProviderCity').value;
            const table = document.getElementById('providersTable');

            if (!table) return;

            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

            for (let row of rows) {
                const company = row.cells[1].textContent.toLowerCase();
                const contact = row.cells[2].textContent.toLowerCase();
                const phone = row.cells[3].textContent.toLowerCase();
                const city = row.cells[4].textContent.trim();
                const services = row.cells[5].textContent.toLowerCase();

                const matchesSearch = company.includes(searchText) || contact.includes(searchText) ||
                    phone.includes(searchText) || services.includes(searchText);
                const matchesCity = !selectedCity || city === selectedCity;

                row.style.display = matchesSearch && matchesCity ? '' : 'none';
            }
        }

        function filterNews() {
            const searchText = document.getElementById('filterNews').value.toLowerCase();
            const selectedCategory = document.getElementById('filterNewsCategory').value;
            const selectedType = document.getElementById('filterNewsType').value;
            const table = document.getElementById('newsTable');

            if (!table) return;

            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

            for (let row of rows) {
                const title = row.cells[1].textContent.toLowerCase();
                const category = row.cells[2].textContent.trim();
                const type = row.cells[3].textContent.trim();
                const author = row.cells[4].textContent.toLowerCase();

                const matchesSearch = title.includes(searchText) || author.includes(searchText);
                const matchesCategory = !selectedCategory || category.includes(selectedCategory);
                const matchesType = !selectedType || type.includes(selectedType);

                row.style.display = matchesSearch && matchesCategory && matchesType ? '' : 'none';
            }
        }

        function filterSliders() {
            const searchText = document.getElementById('filterSliders').value.toLowerCase();
            const selectedStatus = document.getElementById('filterSliderStatus').value;
            const table = document.getElementById('slidersTable');

            if (!table) return;

            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

            for (let row of rows) {
                const title = row.cells[1].textContent.toLowerCase();
                const description = row.cells[2].textContent.toLowerCase();
                const status = row.cells[5].textContent.trim();

                const matchesSearch = title.includes(searchText) || description.includes(searchText);
                const matchesStatus = !selectedStatus || status.includes(selectedStatus);

                row.style.display = matchesSearch && matchesStatus ? '' : 'none';
            }
        }

        function filterQuotes() {
            const searchText = document.getElementById('filterQuotes').value.toLowerCase();
            const selectedStatus = document.getElementById('filterQuoteStatus').value;
            const table = document.getElementById('quotesTable');

            if (!table) return;

            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

            for (let row of rows) {
                const title = row.cells[1].textContent.toLowerCase();
                const user = row.cells[2].textContent.toLowerCase();
                const phone = row.cells[3].textContent.toLowerCase();
                const vehicle = row.cells[4].textContent.toLowerCase();
                const status = row.cells[5].textContent.trim().toLowerCase();

                const matchesSearch = title.includes(searchText) || user.includes(searchText) ||
                    phone.includes(searchText) || vehicle.includes(searchText);
                const matchesStatus = !selectedStatus || status.includes(selectedStatus.toLowerCase());

                row.style.display = matchesSearch && matchesStatus ? '' : 'none';
            }
        }

        function filterPackages() {
            const searchText = document.getElementById('filterPackages').value.toLowerCase();
            const minPrice = parseFloat(document.getElementById('filterMinPrice').value) || 0;
            const maxPrice = parseFloat(document.getElementById('filterMaxPrice').value) || Infinity;
            const table = document.getElementById('packagesTable');

            if (!table) return;

            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

            for (let row of rows) {
                const name = row.cells[1].textContent.toLowerCase();
                const priceText = row.cells[2].textContent;
                const price = parseFloat(priceText.replace(/[^0-9.]/g, '')) || 0;

                const matchesSearch = name.includes(searchText);
                const matchesPrice = price >= minPrice && price <= maxPrice;

                row.style.display = matchesSearch && matchesPrice ? '' : 'none';
            }
        }

        function clearFilters(type) {
            switch (type) {
                case 'users':
                    document.getElementById('filterUsers').value = '';
                    document.getElementById('filterUserRole').value = '';
                    filterUsers();
                    break;
                case 'providers':
                    document.getElementById('filterProviders').value = '';
                    document.getElementById('filterProviderCity').value = '';
                    filterProviders();
                    break;
                case 'news':
                    document.getElementById('filterNews').value = '';
                    document.getElementById('filterNewsCategory').value = '';
                    document.getElementById('filterNewsType').value = '';
                    filterNews();
                    break;
                case 'sliders':
                    document.getElementById('filterSliders').value = '';
                    document.getElementById('filterSliderStatus').value = '';
                    filterSliders();
                    break;
                case 'quotes':
                    document.getElementById('filterQuotes').value = '';
                    document.getElementById('filterQuoteStatus').value = '';
                    filterQuotes();
                    break;
                case 'packages':
                    document.getElementById('filterPackages').value = '';
                    document.getElementById('filterMinPrice').value = '';
                    document.getElementById('filterMaxPrice').value = '';
                    filterPackages();
                    break;
            }
        }

        // Initialize filters when page loads
        document.addEventListener('DOMContentLoaded', function() {
            setupFilters();
        });
    </script>
</body>

</html>