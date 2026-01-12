<?php
require 'config.php';
require 'SmartSystem.php'; // استدعاء العقل الذكي

if(!isset($_SESSION['uid'])) { header("Location: login.php"); exit; }

// تحديد الصفحة المطلوبة
$page = $_GET['p'] ?? 'dashboard';
$allowed_pages = ['dashboard', 'properties', 'units', 'tenants', 'contracts', 'maintenance', 'settings', 'ai_assistant'];

// جلب بيانات المستخدم
$stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
$stmt->execute([$_SESSION['uid']]);
$user = $stmt->fetch();

// تحميل الهيدر
include 'includes/header.php'; 

// تحميل الصفحة المطلوبة ديناميكياً
if(in_array($page, $allowed_pages) && file_exists("pages/$page.php")) {
    include "pages/$page.php";
} else {
    echo "<div class='alert alert-danger'>الصفحة غير موجودة</div>";
}

// تحميل الفوتر
include 'includes/footer.php';
?>
