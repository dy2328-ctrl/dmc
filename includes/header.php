<?php
// includes/header.php
if (session_status() === PHP_SESSION_NONE) session_start();
if(!isset($_SESSION['uid'])) { header("Location: login.php"); exit; }
$user_name = $_SESSION['user_name'] ?? 'المدير';
$p = $_GET['p'] ?? 'dashboard';

// جلب الشعار من الإعدادات أو استخدام الافتراضي
$stmt = $pdo->prepare("SELECT v FROM settings WHERE k='logo'");
$stmt->execute();
$db_logo = $stmt->fetchColumn();
$logo_src = $db_logo && file_exists($db_logo) ? $db_logo : 'logo.png';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>دار الميار - النظام المتكامل</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* GEMINI ULTIMATE DARK THEME (ثابت) */
        :root { --bg:#050505; --card:#111; --border:#222; --primary:#6366f1; --accent:#a855f7; --text:#fff; --muted:#9ca3af; --green:#10b981; --red:#ef4444; }
        body { font-family:'Tajawal'; background:var(--bg); color:var(--text); margin:0; display:flex; height:100vh; overflow:hidden; }
        
        /* Sidebar & Logo Fix */
        .sidebar { width:280px; background:#0a0a0a; border-left:1px solid var(--border); display:flex; flex-direction:column; padding:25px; z-index:10; }
        .logo-container { width:100%; text-align:center; margin-bottom:30px; padding-bottom:20px; border-bottom:1px solid var(--border); }
        .logo-img { max-width:120px; height:120px; object-fit:contain; filter:drop-shadow(0 0 10px rgba(99,102,241,0.3)); transition:0.3s; }
        .logo-img:hover { transform:scale(1.05); }
        
        /* Navigation */
        .nav-link { display:flex; align-items:center; gap:12px; padding:15px; margin-bottom:5px; border-radius:12px; color:var(--muted); text-decoration:none; font-weight:500; transition:0.3s; }
        .nav-link:hover, .nav-link.active { background:rgba(99,102,241,0.1); color:white; border-right:3px solid var(--primary); }
        .nav-link i { width:25px; text-align:center; color:var(--primary); font-size:18px; }

        /* Main Content & Layout */
        .main { flex:1; padding:30px 40px; overflow-y:auto; background-image:radial-gradient(circle at top left, #1e1b4b, transparent 40%); }
        .header { display:flex; justify-content:space-between; align-items:center; margin-bottom:30px; }
        
        /* Components */
        .card { background:rgba(20,20,20,0.7); backdrop-filter:blur(10px); border:1px solid var(--border); border-radius:24px; padding:30px; margin-bottom:30px; box-shadow:0 10px 30px rgba(0,0,0,0.2); }
        .btn { padding:12px 24px; background:linear-gradient(135deg, var(--primary), var(--accent)); color:white; border:none; border-radius:12px; cursor:pointer; font-weight:bold; display:inline-flex; align-items:center; gap:8px; text-decoration:none; font-size:14px; transition:0.3s; }
        .btn:hover { box-shadow:0 0 20px rgba(99,102,241,0.4); transform:translateY(-2px); }
        .btn-green { background:linear-gradient(135deg, #10b981, #059669); }
        .btn-red { background:linear-gradient(135deg, #ef4444, #b91c1c); }
        
        /* Tables */
        table { width:100%; border-collapse:separate; border-spacing:0 10px; }
        th { text-align:right; padding:15px; color:var(--muted); font-size:13px; text-transform:uppercase; letter-spacing:1px; }
        td { background:#161616; padding:18px; border-top:1px solid var(--border); border-bottom:1px solid var(--border); vertical-align:middle; }
        td:first-child { border-right:1px solid var(--border); border-radius:0 15px 15px 0; }
        td:last-child { border-left:1px solid var(--border); border-radius:15px 0 0 15px; }
        
        /* Forms */
        .inp { width:100%; padding:15px; background:#050505; border:1px solid #333; border-radius:12px; color:white; outline:none; margin-bottom:15px; font-family:inherit; transition:0.3s; }
        .inp:focus { border-color:var(--primary); box-shadow:0 0 0 4px rgba(99,102,241,0.1); }
        
        /* Modal */
        .modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.85); z-index:1000; backdrop-filter:blur(5px); justify-content:center; align-items:center; }
        .modal-content { background:#111; width:500px; padding:40px; border-radius:30px; border:1px solid #333; position:relative; animation:slideUp 0.3s ease; }
        @keyframes slideUp { from{transform:translateY(20px);opacity:0} to{transform:translateY(0);opacity:1} }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="logo-container">
        <img src="<?= $logo_src ?>" class="logo-img" alt="Logo">
        <h4 style="margin:15px 0 0; letter-spacing:1px">دار الميار</h4>
    </div>
    
    <div style="flex:1; overflow-y:auto; padding-left:10px;">
        <a href="index.php?p=dashboard" class="nav-link <?= $p=='dashboard'?'active':'' ?>"><i class="fa-solid fa-chart-pie"></i> الرئيسية</a>
        <a href="index.php?p=properties" class="nav-link <?= $p=='properties'?'active':'' ?>"><i class="fa-solid fa-city"></i> العقارات</a>
        <a href="index.php?p=units" class="nav-link <?= $p=='units'?'active':'' ?>"><i class="fa-solid fa-door-open"></i> الوحدات</a>
        <a href="index.php?p=contracts" class="nav-link <?= $p=='contracts'?'active':'' ?>"><i class="fa-solid fa-file-contract"></i> العقود</a>
        <a href="index.php?p=tenants" class="nav-link <?= $p=='tenants'?'active':'' ?>"><i class="fa-solid fa-users"></i> المستأجرين</a>
        <a href="index.php?p=alerts" class="nav-link <?= $p=='alerts'?'active':'' ?>"><i class="fa-solid fa-bell"></i> التنبيهات</a>
        <a href="index.php?p=maintenance" class="nav-link <?= $p=='maintenance'?'active':'' ?>"><i class="fa-solid fa-screwdriver-wrench"></i> الصيانة</a>
        <a href="index.php?p=vendors" class="nav-link <?= $p=='vendors'?'active':'' ?>"><i class="fa-solid fa-helmet-safety"></i> المقاولين</a>
        <a href="index.php?p=settings" class="nav-link <?= $p=='settings'?'active':'' ?>"><i class="fa-solid fa-gear"></i> الإعدادات</a>
    </div>

    <a href="logout.php" class="nav-link" style="color:#ef4444; margin-top:10px"><i class="fa-solid fa-right-from-bracket"></i> تسجيل خروج</a>
</div>

<div class="main">
    <div class="header">
        <div>
            <h2 style="margin:0; font-weight:800">نظام الإدارة الذكي</h2>
            <div style="color:var(--muted); font-size:14px">أهلاً بك، <?= $user_name ?></div>
        </div>
        <button class="btn" style="background:#222; border:1px solid #333">
            <i class="fa-regular fa-calendar"></i> <?= date('Y-m-d') ?>
        </button>
    </div>
