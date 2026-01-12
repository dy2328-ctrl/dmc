<?php
// db.php - الاتصال والحماية

// إعدادات قاعدة البيانات (عدلها حسب السيرفر الخاص بك)
$host = 'db5019378605.hosting-data.io ';
$db   = 'dbs15162823';
$user = 'dbu2244961';
$pass = 'kuqteg-ginbak-myKga7';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) { 
    die("خطأ في الاتصال بقاعدة البيانات. تأكد من تشغيل XAMPP أو السيرفر."); 
}

// بدء الجلسة
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- دوال الحماية ---

// 1. تنظيف المدخلات (XSS Protection)
function secure($data) {
    if (is_null($data)) return '';
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// 2. إنشاء رمز الحماية (CSRF Token)
function generate_csrf() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// 3. التحقق من الرمز (CSRF Check)
function check_csrf() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            die("⛔ تنبيه أمني: محاولة إرسال بيانات غير مصرح بها (CSRF Error).");
        }
    }
}
?>
