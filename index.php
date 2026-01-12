<?php
// index.php - الملف الرئيسي المحدث
require 'config.php';
require 'SmartSystem.php';

if(!isset($_SESSION['uid'])) { header("Location: login.php"); exit; }

// قائمة الصفحات المسموح بها (تأكد من وجود الملفات في مجلد pages)
$p = $_GET['p'] ?? 'dashboard';
$allowed = [
    'dashboard', 
    'properties', 
    'units', 
    'tenants', 
    'tenant_view',
    'contracts', 
    'contract_view', 
    'maintenance', 
    'vendors', 
    'alerts', 
    'settings'
];

include 'includes/header.php';

// التحقق من وجود الصفحة
if(in_array($p, $allowed) && file_exists("pages/$p.php")) {
    include "pages/$p.php";
} else {
    // صفحة 404 مخصصة بنفس التصميم
    echo "<div class='card text-center' style='padding:50px'>
            <h1 style='color:#ef4444; font-size:50px'>404</h1>
            <h3>الصفحة غير موجودة</h3>
            <p style='color:#888'>عذراً، الملف pages/$p.php غير موجود في المجلد.</p>
            <a href='index.php' class='btn'>العودة للرئيسية</a>
          </div>";
}

include 'includes/footer.php';
?>
