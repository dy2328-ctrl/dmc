<?php
$host = 'db5019378605.hosting-data.io ';
$db   = 'dbs15162823'; 
$user = 'dbu2244961';
$pass = 'kuqteg-ginbak-myKga7';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    // محاولة الاتصال وإنشاء القاعدة إذا لم توجد
    try {
        $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db`");
        $pdo->exec("USE `$db`");
    } catch(PDOException $ex) { die("خطأ اتصال"); }
}

session_start();

function getSet($k) { 
    global $pdo; 
    $stmt=$pdo->prepare("SELECT v FROM settings WHERE k=?");
    $stmt->execute([$k]);
    return $stmt->fetchColumn();
}
function saveSet($k,$v) { 
    global $pdo; 
    $pdo->prepare("REPLACE INTO settings (k,v) VALUES (?,?)")->execute([$k,$v]); 
}
?>
