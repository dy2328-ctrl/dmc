<?php checkLogin(); ?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نظام إدارة العقارات</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap');
        body { background-color: #f8fafc; font-family: 'Tajawal', sans-serif; overflow-x: hidden; }
        .sidebar { min-height: 100vh; width: 260px; background: #1e293b; color: #fff; position: fixed; top: 0; right: 0; z-index: 1000; transition: all 0.3s; }
        .main-content { margin-right: 260px; padding: 20px; transition: all 0.3s; }
        .sidebar-brand { padding: 20px; font-size: 1.2rem; font-weight: bold; border-bottom: 1px solid #334155; text-align: center; }
        .nav-link { color: #cbd5e1; padding: 12px 20px; display: flex; align-items: center; transition: 0.2s; }
        .nav-link:hover, .nav-link.active { background: #4f46e5; color: #fff; border-radius: 8px; margin: 0 10px; }
        .nav-link i { margin-left: 10px; width: 20px; text-align: center; }
        .card-stat { border: none; border-radius: 12px; box-shadow: 0 2px 15px rgba(0,0,0,0.03); background: #fff; padding: 20px; display: flex; align-items: center; justify-content: space-between; }
        .icon-box { width: 50px; height: 50px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
        .btn-purple { background-color: #4f46e5; color: white; }
        .btn-purple:hover { background-color: #4338ca; color: white; }
        @media (max-width: 768px) {
            .sidebar { right: -260px; }
            .sidebar.active { right: 0; }
            .main-content { margin-right: 0; }
        }
    </style>
</head>
<body>

<div class="sidebar d-flex flex-column">
    <div class="sidebar-brand">
        <i class="fa-solid fa-building"></i> دار الميار
    </div>
    <ul class="nav flex-column mt-4">
        <li class="nav-item mb-2"><a href="index.php" class="nav-link"><i class="fa-solid fa-chart-pie"></i> لوحة التحكم</a></li>
        <li class="nav-item mb-2"><a href="index.php" class="nav-link"><i class="fa-solid fa-city"></i> العقارات</a></li>
        <li class="nav-item mb-2"><a href="#" class="nav-link"><i class="fa-solid fa-users"></i> المستأجرين</a></li>
        <li class="nav-item mb-2"><a href="#" class="nav-link"><i class="fa-solid fa-file-contract"></i> العقود</a></li>
        <li class="nav-item mt-auto"><a href="logout.php" class="nav-link text-danger"><i class="fa-solid fa-right-from-bracket"></i> تسجيل الخروج</a></li>
    </ul>
</div>

<div class="main-content">
    <nav class="navbar navbar-light bg-white rounded shadow-sm mb-4 px-3">
        <button class="btn d-md-none" id="toggleSidebar"><i class="fa-solid fa-bars"></i></button>
        <span class="navbar-brand mb-0 h1 me-auto">أهلاً، <?php echo $_SESSION['user_name']; ?></span>
    </nav>
