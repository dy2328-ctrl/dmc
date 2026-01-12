<?php
require 'db.php';
$company = getSet('company_name') ?: 'دار الميار للمقاولات';
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
    <title>تسجيل الدخول - <?= $company ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0; height: 100vh; font-family: 'Tajawal';
            background: #0f172a; background-image: radial-gradient(circle at top, #1e293b, #0f172a);
            display: flex; align-items: center; justify-content: center;
        }
        .login-card {
            background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(20px);
            width: 450px; padding: 60px 40px; border-radius: 30px;
            text-align: center; border: 1px solid rgba(255,255,255,0.05);
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);
        }
        .logo-box {
            width: 140px; height: 140px; margin: 0 auto 30px;
            background: white; border-radius: 50%; display: flex; align-items: center; justify-content: center;
            box-shadow: 0 0 40px rgba(99, 102, 241, 0.3);
        }
        h2 { color: white; margin: 0 0 10px; font-size: 28px; }
        p { color: #94a3b8; margin-bottom: 40px; font-size: 16px; }
        input {
            width: 100%; padding: 18px 20px; margin-bottom: 20px;
            background: #1e293b; border: 2px solid #334155; border-radius: 15px;
            color: white; font-size: 16px; outline: none; transition: 0.3s;
            box-sizing: border-box; font-family: inherit;
        }
        input:focus { border-color: #6366f1; background: #0f172a; }
        button {
            width: 100%; padding: 18px; border-radius: 15px; border: none;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white; font-size: 18px; font-weight: bold; cursor: pointer;
            transition: 0.3s;
        }
        button:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(99, 102, 241, 0.4); }
        .err { background: #fee2e2; color: #991b1b; padding: 15px; border-radius: 10px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="logo-box"><img src="logo.png" style="max-width:80%; max-height:80%"></div>
        <h2><?= $company ?></h2>
        <p>بوابة إدارة الأملاك والمقاولات</p>
        <?php if(isset($err)) echo "<div class='err'>$err</div>"; ?>
        <form method="POST">
            <input type="text" name="user" placeholder="اسم المستخدم" required>
            <input type="password" name="pass" placeholder="كلمة المرور" required>
            <button>تسجيل الدخول الآمن</button>
        </form>
    </div>
</body>
</html>
