<?php
// includes/header.php
if (session_status() === PHP_SESSION_NONE) session_start();
if(!isset($_SESSION['uid'])) { header("Location: login.php"); exit; }
$user_name = $_SESSION['user_name'] ?? 'المدير العام';
$p = $_GET['p'] ?? 'dashboard';

// جلب الشعار
$stmt = $pdo->prepare("SELECT v FROM settings WHERE k='logo'"); $stmt->execute();
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
        /* GEMINI ULTIMATE DARK THEME - CORE */
        :root { --bg:#050505; --card:#0f0f0f; --border:#222; --primary:#6366f1; --accent:#a855f7; --text:#fff; --muted:#64748b; --success:#10b981; --danger:#ef4444; --glass:rgba(20,20,20,0.6); }
        * { box-sizing:border-box; outline:none; }
        body { font-family:'Tajawal'; background:var(--bg); color:var(--text); margin:0; display:flex; height:100vh; overflow:hidden; }
        ::-webkit-scrollbar { width:6px; } ::-webkit-scrollbar-thumb { background:#333; border-radius:10px; }

        /* Sidebar & Logo Styles */
        .sidebar { width:280px; background:#080808; border-left:1px solid var(--border); display:flex; flex-direction:column; padding:25px; z-index:20; box-shadow:5px 0 40px rgba(0,0,0,0.5); }
        .logo-wrapper {
            width: 100px; height: 100px; margin: 0 auto 20px;
            border-radius: 50%;
            background: radial-gradient(circle at center, #1e1e2e, #000);
            border: 2px solid var(--border);
            box-shadow: 0 0 25px rgba(99,102,241,0.15);
            display: flex; align-items: center; justify-content: center;
            overflow: hidden; padding: 10px; transition: 0.3s;
        }
        .logo-wrapper:hover { border-color: var(--primary); box-shadow: 0 0 35px rgba(99,102,241,0.4); transform: scale(1.05); }
        .logo-img { max-width: 100%; max-height: 100%; object-fit: contain; }
        
        .nav-link { display:flex; align-items:center; gap:15px; padding:16px; margin-bottom:6px; border-radius:14px; color:var(--muted); text-decoration:none; font-weight:500; transition:0.3s; border:1px solid transparent; }
        .nav-link:hover, .nav-link.active { background:rgba(99,102,241,0.08); color:white; border-color:rgba(99,102,241,0.2); box-shadow:0 4px 15px rgba(0,0,0,0.2); }
        .nav-link i { width:20px; text-align:center; font-size:18px; color:var(--primary); }

        /* Main Area */
        .main { flex:1; padding:40px; overflow-y:auto; background:radial-gradient(circle at 10% 10%, #11101f, transparent 30%); position:relative; }
        .header { display:flex; justify-content:space-between; align-items:center; margin-bottom:40px; padding-bottom:20px; border-bottom:1px solid var(--border); }
        
        /* Cards */
        .card { background:var(--card); border:1px solid var(--border); border-radius:24px; padding:30px; margin-bottom:30px; position:relative; overflow:hidden; }
        .card::before { content:''; position:absolute; top:0; left:0; width:100%; height:4px; background:linear-gradient(90deg, var(--primary), var(--accent)); opacity:0; transition:0.3s; }
        .card:hover::before { opacity:1; }

        /* Better Modals */
        .modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.9); z-index:2000; backdrop-filter:blur(8px); justify-content:center; align-items:center; padding:20px; }
        .modal-content { background:#111; width:100%; max-width:650px; padding:40px; border-radius:30px; border:1px solid #333; box-shadow:0 20px 80px rgba(0,0,0,0.8); animation:modalPop 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
        @keyframes modalPop { 0% { opacity:0; transform:scale(0.8) translateY(20px); } 100% { opacity:1; transform:scale(1) translateY(0); } }
        
        .modal-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:30px; border-bottom:1px solid #222; padding-bottom:15px; }
        .modal-title { font-size:24px; font-weight:800; background:linear-gradient(to right, #fff, #aaa); -webkit-background-clip:text; -webkit-text-fill-color:transparent; }
        
        /* Form Elements */
        .inp-group { margin-bottom:20px; }
        .inp-label { display:block; margin-bottom:10px; color:#aaa; font-size:14px; font-weight:bold; }
        .inp { width:100%; padding:18px; background:#080808; border:1px solid #2a2a2a; border-radius:16px; color:white; font-family:'Tajawal'; font-size:16px; transition:0.3s; }
        .inp:focus { border-color:var(--primary); box-shadow:0 0 0 4px rgba(99,102,241,0.15); background:#0f0f0f; }
        .inp-grid { display:grid; grid-template-columns:1fr 1fr; gap:20px; }
        
        .btn { padding:18px; border:none; border-radius:16px; font-weight:bold; cursor:pointer; font-size:16px; transition:0.3s; display:inline-flex; align-items:center; justify-content:center; gap:10px; }
        .btn-primary { background:linear-gradient(135deg, var(--primary), var(--accent)); color:white; box-shadow:0 10px 20px rgba(99,102,241,0.3); }
        .btn-primary:hover { transform:translateY(-3px); box-shadow:0 15px 30px rgba(99,102,241,0.4); }

        /* Tables */
        table { width:100%; border-collapse:separate; border-spacing:0 8px; }
        th { text-align:right; color:#666; font-size:13px; padding:10px 20px; }
        td { background:#141414; padding:20px; border:1px solid #222; border-left:none; border-right:none; transition:0.2s; }
        td:first-child { border-radius:0 15px 15px 0; border-right:1px solid #222; }
        td:last-child { border-radius:15px 0 0 15px; border-left:1px solid #222; }
        tr:hover td { background:#1a1a1a; transform:scale(1.005); }
    </style>
</head>
<body>

<div class="sidebar">
    <div style="text-align:center; margin-bottom:30px">
        <div class="logo-wrapper">
            <img src="<?= $logo_src ?>" class="logo-img" alt="Logo">
        </div>
        <h4 style="margin:10px 0 5px; font-weight:800; letter-spacing:1px">دار الميار</h4>
        <span style="font-size:12px; color:var(--primary); background:rgba(99,102,241,0.1); padding:4px 10px; border-radius:20px">Ultimate System</span>
    </div>
    
    <div style="flex:1; overflow-y:auto; padding-left:5px">
        <a href="index.php?p=dashboard" class="nav-link <?= $p=='dashboard'?'active':'' ?>"><i class="fa-solid fa-layer-group"></i> لوحة القيادة</a>
        <a href="index.php?p=properties" class="nav-link <?= $p=='properties'?'active':'' ?>"><i class="fa-solid fa-city"></i> العقارات</a>
        <a href="index.php?p=units" class="nav-link <?= $p=='units'?'active':'' ?>"><i class="fa-solid fa-door-open"></i> الوحدات</a>
        <a href="index.php?p=tenants" class="nav-link <?= $p=='tenants'?'active':'' ?>"><i class="fa-solid fa-users"></i> المستأجرين</a>
        <a href="index.php?p=contracts" class="nav-link <?= $p=='contracts'?'active':'' ?>"><i class="fa-solid fa-file-contract"></i> العقود</a>
        <a href="index.php?p=alerts" class="nav-link <?= $p=='alerts'?'active':'' ?>"><i class="fa-solid fa-bell"></i> التنبيهات</a>
        <a href="index.php?p=maintenance" class="nav-link <?= $p=='maintenance'?'active':'' ?>"><i class="fa-solid fa-screwdriver-wrench"></i> الصيانة</a>
        <a href="index.php?p=vendors" class="nav-link <?= $p=='vendors'?'active':'' ?>"><i class="fa-solid fa-helmet-safety"></i> المقاولين</a>
        <a href="index.php?p=settings" class="nav-link <?= $p=='settings'?'active':'' ?>"><i class="fa-solid fa-gear"></i> الإعدادات</a>
    </div>

    <a href="logout.php" class="nav-link" style="color:#ef4444; margin-top:10px"><i class="fa-solid fa-power-off"></i> تسجيل خروج</a>
</div>

<div class="main">
    <div class="header">
        <div>
            <h1 style="margin:0; font-size:28px; font-weight:800">أهلاً بك، <?= $user_name ?> 👋</h1>
            <div style="color:var(--muted); font-size:15px; margin-top:5px">نظرة عامة على أداء محفظتك العقارية اليوم</div>
        </div>
        <div style="display:flex; gap:15px">
            <button class="btn" style="background:#111; border:1px solid #333; font-size:14px">
                <i class="fa-regular fa-calendar"></i> <?= date('d M Y') ?>
            </button>
            <div style="width:50px; height:50px; background:linear-gradient(135deg, #333, #111); border-radius:50%; display:flex; align-items:center; justify-content:center; border:1px solid #333">
                <i class="fa-solid fa-user"></i>
            </div>
        </div>
    </div>
