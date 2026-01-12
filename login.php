<?php
require 'db.php';
if($_POST){
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username=?");
    $stmt->execute([$_POST['user']]);
    $u = $stmt->fetch();
    if($u && password_verify($_POST['pass'], $u['password'])){
        $_SESSION['uid'] = $u['id']; header("Location: index.php"); exit;
    } else $err="خطأ في البيانات";
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
        .box { background: rgba(30, 41, 59, 0.6); backdrop-filter: blur(15px); padding: 60px; border-radius: 30px; width: 450px; text-align: center; border: 1px solid rgba(255,255,255,0.1); box-shadow: 0 30px 60px rgba(0,0,0,0.6); }
        input { width: 100%; padding: 18px; margin: 12px 0; border-radius: 15px; border: 2px solid #334155; background: #0f172a; color: white; outline: none; box-sizing: border-box; font-size: 16px; transition: 0.3s; }
        input:focus { border-color: #6366f1; }
        button { width: 100%; padding: 18px; border-radius: 15px; border: none; background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; font-weight: bold; cursor: pointer; margin-top: 30px; font-size: 18px; }
    </style>
</head>
<body>
    <div class="box">
        <img src="logo.png" width="100" style="margin-bottom:20px; border-radius:50%; background:white; padding:5px">
        <h2 style="margin:0 0 10px 0; font-size:28px">دار الميار للمقاولات</h2>
        <p style="color:#94a3b8; margin-bottom:40px">لوحة التحكم الإدارية</p>
        <?php if(isset($err)) echo "<div style='color:#f87171; background:rgba(248,113,113,0.1); padding:10px; border-radius:10px; margin-bottom:20px'>$err</div>"; ?>
        <form method="POST">
            <input type="text" name="user" placeholder="اسم المستخدم" required>
            <input type="password" name="pass" placeholder="كلمة المرور" required>
            <button>تسجيل الدخول</button>
        </form>
    </div>
</body>
</html>
