<?php
// includes/header.php
if(!isset($_SESSION['uid'])) { header("Location: login.php"); exit; }
$p = $_GET['p'] ?? 'dashboard';
$user_name = $_SESSION['user_name'] ?? 'المستخدم'; // تأكد من حفظ الاسم في الجلسة عند الدخول
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>دار الميار - النظام الذكي</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* GEMINI ULTIMATE DARK THEME */
        :root { --bg:#050505; --card:#111; --border:#222; --primary:#6366f1; --accent:#a855f7; --text:#fff; --muted:#9ca3af; --green:#10b981; --red:#ef4444; }
        body { font-family:'Tajawal'; background:var(--bg); color:var(--text); margin:0; display:flex; height:100vh; overflow:hidden; }
        
        /* Sidebar */
        .sidebar { width:280px; background:#0a0a0a; border-left:1px solid var(--border); display:flex; flex-direction:column; padding:25px; box-shadow:5px 0 50px rgba(0,0,0,0.5); z-index:10; }
        .logo-box { width:70px; height:70px; margin:0 auto 20px; border-radius:50%; background:white; display:flex; align-items:center; justify-content:center; box-shadow:0 0 30px rgba(99,102,241,0.3); }
        .nav-link { display:flex; align-items:center; gap:12px; padding:15px; margin-bottom:5px; border-radius:12px; color:var(--muted); text-decoration:none; font-weight:500; transition:0.3s; }
        .nav-link:hover, .nav-link.active { background:rgba(99,102,241,0.1); color:white; border-right:3px solid var(--primary); }
        .nav-link i { width:25px; text-align:center; color:var(--primary); }

        /* Main Content */
        .main { flex:1; padding:40px; overflow-y:auto; background-image:radial-gradient(circle at top left, #1e1b4b, transparent 40%); }
        .header { display:flex; justify-content:space-between; align-items:center; margin-bottom:40px; }
        
        /* Cards */
        .card { background:rgba(20,20,20,0.7); backdrop-filter:blur(10px); border:1px solid var(--border); border-radius:24px; padding:30px; margin-bottom:30px; }
        .stats-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:25px; margin-bottom:30px; }
        .stat-card { background:#0f0f0f; padding:25px; border-radius:20px; border:1px solid var(--border); position:relative; overflow:hidden; display:flex; flex-direction:column; justify-content:center; align-items:center; text-align:center; }
        .stat-val { font-size:28px; font-weight:800; margin:5px 0; }
        .stat-label { color:var(--muted); font-size:14px; }
        
        /* Special Colors */
        .card-green { background:linear-gradient(135deg, #10b981, #059669); color:white; border:none; box-shadow:0 10px 30px rgba(16,185,129,0.3); }
        .card-purple { background:linear-gradient(135deg, #6366f1, #4f46e5); color:white; border:none; box-shadow:0 10px 30px rgba(99,102,241,0.3); }

        /* Tables & Forms */
        .search-box { background:#111; border:1px solid #333; padding:10px 20px; border-radius:20px; color:white; width:300px; outline:none; font-family:inherit; }
        .search-box:focus { border-color:var(--primary); }
        table { width:100%; border-collapse:separate; border-spacing:0 10px; }
        th { text-align:right; padding:15px; color:var(--muted); font-size:14px; }
        td { background:#161616; padding:18px; border-top:1px solid var(--border); border-bottom:1px solid var(--border); }
        td:first-child { border-right:1px solid var(--border); border-radius:0 15px 15px 0; }
        td:last-child { border-left:1px solid var(--border); border-radius:15px 0 0 15px; }

        .btn { padding:12px 24px; background:linear-gradient(135deg, var(--primary), var(--accent)); color:white; border:none; border-radius:12px; cursor:pointer; font-weight:bold; display:inline-flex; align-items:center; gap:8px; text-decoration:none; font-size:14px; }
        .btn-green { background:linear-gradient(135deg, #10b981, #059669); }
        .badge { padding:5px 10px; border-radius:15px; font-size:11px; font-weight:bold; }
        .badge.bg-success { background:rgba(16,185,129,0.1); color:#34d399; }
        .badge.bg-secondary { background:rgba(156, 163, 175, 0.1); color:#9ca3af; }
        .inp { width:100%; padding:15px; background:#050505; border:1px solid #333; border-radius:12px; color:white; outline:none; margin-bottom:15px; box-sizing:border-box; font-family:inherit; }
        .inp:focus { border-color:var(--primary); }

        /* Modals */
        .modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.9); z-index:1000; justify-content:center; align-items:center; }
        .modal-dialog { width:100%; max-width:600px; padding:20px; }
        .modal-content { background:#111; padding:40px; border-radius:30px; border:1px solid #333; position:relative; }
        .btn-close { position:absolute; left:30px; top:30px; color:#ef4444; cursor:pointer; background:none; border:none; font-size:24px; }
    </style>
</head>
<body>

<div class="sidebar">
    <div style="text-align:center; margin-bottom:40px">
        <div class="logo-box"><i class="fa-solid fa-building fa-2x" style="color:#6366f1"></i></div>
        <h3 style="margin:10px 0 0">دار الميار</h3>
    </div>
    <a href="index.php?p=dashboard" class="nav-link <?= $p=='dashboard'?'active':'' ?>"><i class="fa-solid fa-chart-pie"></i> لوحة القيادة</a>
    <a href="index.php?p=properties" class="nav-link <?= $p=='properties'?'active':'' ?>"><i class="fa-solid fa-city"></i> العقارات</a>
    <a href="index.php?p=units" class="nav-link <?= $p=='units'?'active':'' ?>"><i class="fa-solid fa-door-open"></i> الوحدات</a>
    <div style="height:1px; background:#222; margin:15px 0"></div>
    <a href="index.php?p=contracts" class="nav-link <?= $p=='contracts'?'active':'' ?>"><i class="fa-solid fa-file-contract"></i> العقود</a>
    <a href="index.php?p=tenants" class="nav-link <?= $p=='tenants'?'active':'' ?>"><i class="fa-solid fa-users"></i> المستأجرين</a>
    <a href="logout.php" class="nav-link" style="margin-top:auto; color:#ef4444"><i class="fa-solid fa-power-off"></i> خروج</a>
</div>

<div class="main">
    <div class="header">
        <div>
            <div style="font-size:24px; font-weight:800; color:white">أهلاً، <?= $user_name ?></div>
            <div style="color:#666; font-size:14px">نظام إدارة الأملاك الذكي</div>
        </div>
        <div style="display:flex; gap:20px; align-items:center">
            <input type="text" id="tableSearch" onkeyup="searchTable()" class="search-box" placeholder="بحث سريع...">
        </div>
    </div>
