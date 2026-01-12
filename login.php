<?php
require 'db.php';
if($_POST) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email=?");
    $stmt->execute([$_POST['email']]);
    $u = $stmt->fetch();
    if($u && password_verify($_POST['pass'], $u['password'])) {
        $_SESSION['uid'] = $u['id']; header("Location: index.php");
    } else $err="بيانات خاطئة";
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تسجيل الدخول</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1c1c1e 0%, #2d2f31 100%); /* خلفية داكنة فخمة */
            height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Tajawal';
        }
        .login-box {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            padding: 40px; border-radius: 24px; width: 350px;
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
            border: 1px solid rgba(255, 255, 255, 0.18);
            color: white; text-align: center;
        }
        input {
            width: 100%; padding: 12px; margin: 10px 0; border-radius: 12px; border: none;
            background: rgba(255,255,255,0.1); color: white; outline: none;
        }
        button {
            width: 100%; padding: 12px; border-radius: 12px; border: none;
            background: linear-gradient(90deg, #4facfe 0%, #00f2fe 100%);
            color: #000; font-weight: bold; cursor: pointer; margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>Gemini Estate</h2>
        <p>نظام إدارة العقارات الذكي</p>
        <?php if(isset($err)) echo "<p style='color:#ff6b6b'>$err</p>"; ?>
        <form method="POST">
            <input type="email" name="email" placeholder="البريد الإلكتروني" required>
            <input type="password" name="pass" placeholder="كلمة المرور" required>
            <button>دخول آمن</button>
        </form>
    </div>
</body>
</html>
