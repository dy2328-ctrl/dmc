<?php
// config.php - إعدادات النظام والاتصال الآمن
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_strict_mode', 1);
    session_start();
}

// إعدادات قاعدة البيانات
define('DB_HOST', 'db5019378605.hosting-data.io'); // عدل البيانات هنا
define('DB_NAME', 'dbs15162823');
define('DB_USER', 'dbu2244961');
define('DB_PASS', 'kuqteg-ginbak-myKga7');

// مفاتيح الربط الخارجي (سنحتاجها للذكاء الاصطناعي والواتساب)
define('OPENAI_API_KEY', 'sk-...'); // ضع مفتاح OpenAI هنا
define('WHATSAPP_API_URL', 'https://api.ultramsg.com/...'); // رابط بوابة الواتساب

try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("خطأ في الاتصال بقاعدة البيانات. يرجى مراجعة ملف config.php");
}

// دوال الحماية الأساسية
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function check_csrf() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            die("تم رفض الطلب: رمز الحماية غير صالح (CSRF Error)");
        }
    }
}
?>
