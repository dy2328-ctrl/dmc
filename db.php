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
    // محاولة الإنشاء التلقائي
    try {
        $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
        $pdo->exec("USE `$db`");
    } catch(PDOException $e2) { die("خطأ الاتصال: " . $e2->getMessage()); }
}
session_start();

function getSet($k) { global $pdo; $s=$pdo->prepare("SELECT v FROM settings WHERE k=?");$s->execute([$k]);return $s->fetchColumn(); }
function saveSet($k,$v) { global $pdo; $pdo->prepare("REPLACE INTO settings (k,v) VALUES (?,?)")->execute([$k,$v]); }
?>
