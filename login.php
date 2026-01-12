<?php
require 'db.php';
if($_POST){
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username=?");
    $stmt->execute([$_POST['user']]);
    $u = $stmt->fetch();
    if($u && password_verify($_POST['pass'], $u['password'])){
        $_SESSION['uid'] = $u['id']; header("Location: index.php"); exit;
    } else $err="بيانات الدخول غير صحيحة";
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>دخول - دار الميار</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;500;700;800&display=swap" rel="stylesheet">
    <style>
        body { background: #020617; color: white; height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Tajawal'; margin: 0; }
        .box { background: rgba(30, 41, 59, 0.5); backdrop-filter: blur(10px); padding: 50px; border-radius: 20px; width: 380px; text-align: center; border: 1px solid rgba(255,255,255,0.1); box-shadow: 0 20px 50px rgba(0,0,0,0.5); }
        input { width: 100%; padding: 15px; margin: 10px 0; border-radius: 10px; border: 1px solid #334155; background: #0f172a; color: white; outline: none; box-sizing: border-box; }
        button { width: 100%; padding: 15px; border-radius: 10px; border: none; background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; font-weight: bold; cursor: pointer; margin-top: 20px; font-size: 16px; }
        button:hover { transform: scale(1.02); }
    </style>
</head>
<body>
    <div class="box">
        <img src="logo.png" width="80" style="margin-bottom:20px; border-radius:50%; background:white; padding:5px">
        <h2 style="margin:0 0 10px 0">دار الميار للمقاولات</h2>
        <p style="color:#94a3b8; margin-bottom:30px">تسجيل الدخول للنظام الذكي</p>
        <?php if(isset($err)) echo "<p style='color:#f87171'>$err</p>"; ?>
        <form method="POST">
            <input type="text" name="user" placeholder="اسم المستخدم (admin)" required>
            <input type="password" name="pass" placeholder="كلمة المرور" required>
            <button>دخول</button>
        </form>
    </div>
</body>
</html>
