<?php
// index.php
require 'config.php';
require 'SmartSystem.php';

if(!isset($_SESSION['uid'])) { header("Location: login.php"); exit; }

$p = $_GET['p'] ?? 'dashboard';
$allowed = ['dashboard', 'properties', 'units', 'tenants', 'contracts', 'contract_view', 'tenant_view', 'maintenance', 'settings'];

// تضمين الهيدر
include 'includes/header.php';

// تحميل الصفحة المطلوبة
if(in_array($p, $allowed) && file_exists("pages/$p.php")) {
    include "pages/$p.php";
} else {
    echo "<div class='p-5 text-center'><h1>404</h1><p>الصفحة غير موجودة</p></div>";
}

// تضمين الفوتر
include 'includes/footer.php';
ob_end_flush();
?>
