<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>نظام دار الميار الذكي</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Tajawal'; background: #f4f6f9; }
        .sidebar { min-height: 100vh; width: 260px; background: #1e293b; color: #fff; position: fixed; right: 0; top: 0; padding-top: 20px; }
        .main-content { margin-right: 260px; padding: 20px; }
        .nav-link { color: #cbd5e1; padding: 12px 20px; display: flex; align-items: center; }
        .nav-link:hover, .nav-link.active { background: #4f46e5; color: #fff; border-radius: 8px 0 0 8px; }
        .nav-link i { margin-left: 10px; width: 25px; }
        .card { border: none; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); margin-bottom: 20px; }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="text-center mb-4">
        <h4><i class="fa-solid fa-building-user"></i> دار الميار</h4>
    </div>
    <ul class="nav flex-column">
        <li class="nav-item"><a href="index.php?p=dashboard" class="nav-link <?= $p=='dashboard'?'active':'' ?>"><i class="fa-solid fa-chart-pie"></i> الرئيسية</a></li>
        <li class="nav-item"><a href="index.php?p=properties" class="nav-link <?= $p=='properties'?'active':'' ?>"><i class="fa-solid fa-city"></i> العقارات</a></li>
        <li class="nav-item"><a href="index.php?p=units" class="nav-link <?= $p=='units'?'active':'' ?>"><i class="fa-solid fa-door-open"></i> الوحدات</a></li>
        <li class="nav-item"><a href="index.php?p=tenants" class="nav-link <?= $p=='tenants'?'active':'' ?>"><i class="fa-solid fa-users"></i> المستأجرين</a></li>
        <li class="nav-item"><a href="index.php?p=contracts" class="nav-link <?= $p=='contracts'?'active':'' ?>"><i class="fa-solid fa-file-contract"></i> العقود</a></li>
        <li class="nav-item"><a href="logout.php" class="nav-link text-danger"><i class="fa-solid fa-right-from-bracket"></i> خروج</a></li>
    </ul>
</div>

<div class="main-content">
    <nav class="navbar bg-white rounded shadow-sm mb-4 px-3">
        <span class="navbar-brand">أهلاً بك في النظام الذكي</span>
    </nav>
