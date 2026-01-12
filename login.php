<?php
require 'db.php';
if($_POST){
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username=?");
    $stmt->execute([$_POST['user']]);
    $u = $stmt->fetch();
    if($u && password_verify($_POST['pass'], $u['password'])){
        $_SESSION['uid'] = $u['id']; header("Location: index.php"); exit;
    } else $err="اسم المستخدم أو كلمة المرور غير صحيحة";
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تسجيل الدخول - دار الميار</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;500;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            background: #020617; background-image: radial-gradient(at 0% 0%, #1e1b4b 0px, transparent 50%), radial-gradient(at 100% 100%, #312e81 0px, transparent 50%);
            height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Tajawal'; margin: 0; color: white;
        }
        .login-box {
            background: rgba(30, 41, 59, 0.6); backdrop-filter: blur(20px);
            padding: 50px; border-radius: 24px; width: 400px; text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.1); box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);
        }
        .logo-container {
            width: 100px; height: 100px; margin: 0 auto 20px; background: white; border-radius: 50%; display: flex; align-items: center; justify-content: center;
            box-shadow: 0 0 30px rgba(99, 102, 241, 0.5);
        }
        input {
            width: 100%; padding: 15px; margin: 10px 0; border-radius: 12px; border: 1px solid #334155;
            background: #0f172a; color: white; outline: none; font-size: 16px; font-family: inherit; box-sizing: border-box; transition: 0.3s;
        }
        input:focus { border-color: #6366f1; box-shadow: 0 0 15px rgba(99, 102, 241, 0.2); }
        button {
            width: 100%; padding: 15px; border-radius: 12px; border: none; margin-top: 20px;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white; font-weight: bold; cursor: pointer; font-size: 18px; transition: 0.3s;
        }
        button:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(99, 102, 241, 0.4); }
    </style>
</head>
<body>
    <div class="login-box">
        <div class="logo-container"><img src="logo.png" width="70"></div>
        <h2 style="margin:0 0 10px 0">دار الميار للمقاولات</h2>
        <p style="color:#94a3b8; margin-bottom:30px">تسجيل الدخول للنظام</p>
        <?php if(isset($err)) echo "<div style='color:#f87171; background:rgba(239,68,68,0.1); padding:10px; border-radius:8px; margin-bottom:15px'>$err</div>"; ?>
        <form method="POST">
            <input type="text" name="user" placeholder="اسم المستخدم (admin)" required>
            <input type="password" name="pass" placeholder="كلمة المرور" required>
            <button>دخول آمن</button>
        </form>
    </div>
</body>
</html>
