<?php
require 'db.php';
if($_POST) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email=?");
    $stmt->execute([$_POST['email']]);
    $u = $stmt->fetch();
    if($u && password_verify($_POST['pass'], $u['password'])) {
        $_SESSION['uid'] = $u['id']; header("Location: index.php"); exit;
    } else $err = "بيانات الدخول غير صحيحة";
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تسجيل الدخول - Gemini Estate</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;500;800&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0; height: 100vh; font-family: 'Tajawal';
            background: radial-gradient(circle at top right, #1e1b4b, #0f172a);
            display: flex; align-items: center; justify-content: center; overflow: hidden;
        }
        .glass-card {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 50px; border-radius: 24px; width: 380px; text-align: center;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }
        .logo { width: 100px; margin-bottom: 20px; filter: drop-shadow(0 0 10px rgba(99, 102, 241, 0.5)); }
        input {
            width: 100%; padding: 15px; margin: 10px 0; border-radius: 12px; border: 1px solid #334155;
            background: #0f172a; color: white; outline: none; font-family: inherit; transition: 0.3s;
            box-sizing: border-box;
        }
        input:focus { border-color: #6366f1; box-shadow: 0 0 15px rgba(99, 102, 241, 0.3); }
        button {
            width: 100%; padding: 15px; border-radius: 12px; border: none; margin-top: 20px;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white; font-weight: bold; cursor: pointer; font-size: 16px; transition: 0.3s;
        }
        button:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(99, 102, 241, 0.4); }
        h2 { color: white; margin: 0 0 10px 0; }
        p { color: #94a3b8; font-size: 14px; margin-bottom: 30px; }
        .err { color: #ef4444; background: rgba(239, 68, 68, 0.1); padding: 10px; border-radius: 8px; margin-bottom: 15px; font-size: 13px; }
    </style>
</head>
<body>
    <div class="glass-card">
        <img src="logo.png" class="logo" onerror="this.style.display='none'">
        <h2>مرحباً بك مجدداً</h2>
        <p>نظام إدارة الأملاك والمقاولات الذكي</p>
        <?php if(isset($err)) echo "<div class='err'>$err</div>"; ?>
        <form method="POST">
            <input type="email" name="email" placeholder="البريد الإلكتروني" required>
            <input type="password" name="pass" placeholder="كلمة المرور" required>
            <button>تسجيل الدخول</button>
        </form>
    </div>
</body>
</html>
