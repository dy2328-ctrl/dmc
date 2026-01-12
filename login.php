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
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700;800&display=swap" rel="stylesheet">
    <style>
        body { margin:0; height:100vh; font-family:'Tajawal'; background:#050505; background-image:radial-gradient(circle at 50% 0%, #1e1b4b, #000); display:flex; align-items:center; justify-content:center; color:white; }
        .glass-box { background:rgba(255,255,255,0.03); backdrop-filter:blur(20px); border:1px solid rgba(255,255,255,0.1); width:400px; padding:50px; border-radius:30px; text-align:center; box-shadow:0 0 50px rgba(99,102,241,0.15); }
        input { width:100%; padding:18px; margin-bottom:20px; border-radius:15px; border:2px solid #333; background:#0a0a0a; color:white; outline:none; box-sizing:border-box; font-family:inherit; transition:0.3s; }
        input:focus { border-color:#6366f1; box-shadow:0 0 15px rgba(99,102,241,0.2); }
        button { width:100%; padding:18px; border-radius:15px; border:none; background:linear-gradient(135deg,#6366f1,#a855f7); color:white; font-weight:800; cursor:pointer; font-size:16px; transition:0.3s; }
        button:hover { transform:scale(1.02); box-shadow:0 0 30px rgba(99,102,241,0.4); }
    </style>
</head>
<body>
    <div class="glass-box">
        <h2 style="margin:0 0 30px; font-size:28px"><?= $name ?></h2>
        <?php if(isset($err)) echo "<p style='color:#f87171; background:rgba(255,0,0,0.1); padding:10px; border-radius:10px'>$err</p>"; ?>
        <form method="POST">
            <input type="text" name="user" placeholder="اسم المستخدم" required>
            <input type="password" name="pass" placeholder="كلمة المرور" required>
            <button>دخول آمن</button>
        </form>
    </div>
</body>
</html>
