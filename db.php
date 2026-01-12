<?php
$host = 'db5019378605.hosting-data.io';
$db   = 'dbs15162823';
$user = 'dbu2244961';
$pass = 'YOUR_PASSWORD'; // ⚠️ ضع كلمة المرور هنا

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) { die("خطأ في الاتصال بقاعدة البيانات"); }

session_start();
?>
