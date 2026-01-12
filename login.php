<?php
require 'db.php';
$name = getSet('company_name') ?: 'دار الميار للمقاولات';
if($_POST){
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username=?");
    $stmt->execute([$_POST['user']]); $u = $stmt->fetch();
    if($u && password_verify($_POST['pass'], $u['password'])){ $_SESSION['uid'] = $u['id']; header("Location: index.php"); exit; } 
    else $err="بيانات الدخول غير صحيحة";
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title><?= $name ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;500;800&display=swap" rel="stylesheet">
    <style>
        body { margin:0; height:100vh; font-family:'Tajawal'; background:#000; overflow:hidden; display:flex; align-items:center; justify-content:center; }
        body::before { content:''; position:absolute; width:150%; height:150%; background:radial-gradient(circle at 50% -20%, #4f46e5, transparent 60%); animation:pulse 10s infinite alternate; }
        @keyframes pulse { 0% { opacity:0.5; } 100% { opacity:0.8; } }
        .box { position:relative; width:400px; padding:60px 40px; background:rgba(20,20,20,0.7); backdrop-filter:blur(30px); border:1px solid rgba(255,255,255,0.1); border-radius:30px; text-align:center; box-shadow:0 20px 80px rgba(0,0,0,0.8); }
        .logo { width:100px; height:100px; margin:0 auto 30px; background:white; border-radius:50%; display:flex; align-items:center; justify-content:center; box-shadow:0 0 40px rgba(99,102,241,0.5); }
        input { width:100%; padding:18px; margin-bottom:20px; background:#111; border:1px solid #333; border-radius:15px; color:white; outline:none; text-align:center; transition:0.3s; font-family:inherit; box-sizing:border-box; font-size:16px; }
        input:focus { border-color:#6366f1; background:#1a1a1a; }
        button { width:100%; padding:18px; background:linear-gradient(135deg,#6366f1,#8b5cf6); color:white; border:none; border-radius:15px; font-weight:800; cursor:pointer; font-size:18px; transition:0.3s; }
        button:hover { transform:scale(1.02); box-shadow:0 0 30px rgba(99,102,241,0.6); }
    </style>
</head>
<body>
    <div class="box">
        <div class="logo"><img src="logo.png" style="max-width:70%"></div>
        <h2 style="color:white; margin:0 0 10px"><?= $name ?></h2>
        <p style="color:#888; margin-bottom:40px">منصة إدارة الأملاك الذكية</p>
        <?php if(isset($err)) echo "<p style='color:#f87171'>$err</p>"; ?>
        <form method="POST">
            <input type="text" name="user" placeholder="اسم المستخدم" required>
            <input type="password" name="pass" placeholder="كلمة المرور" required>
            <button>دخول</button>
        </form>
    </div>
</body>
</html>
