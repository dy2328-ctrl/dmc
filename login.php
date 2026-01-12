<?php
require 'db.php';
$name = getSet('company_name') ?: 'دار الميار للمقاولات';
$logo = getSet('logo') ?: 'logo.png';
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
    <title>دخول - <?= $name ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0; height: 100vh; font-family: 'Tajawal';
            background: #050505; 
            background-image: radial-gradient(circle at 50% 0%, #1e1b4b, #000);
            display: flex; align-items: center; justify-content: center; color: white;
        }
        .glass-box {
            background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            width: 420px; padding: 60px 40px; border-radius: 30px; text-align: center;
            box-shadow: 0 0 50px rgba(99, 102, 241, 0.15);
        }
        .logo-con {
            width: 120px; height: 120px; margin: 0 auto 30px; background: white;
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            border: 4px solid #6366f1; box-shadow: 0 0 30px rgba(99, 102, 241, 0.5);
        }
        input {
            width: 100%; padding: 18px; margin-bottom: 20px; border-radius: 15px;
            border: 2px solid #333; background: #0a0a0a; color: white; font-size: 16px;
            outline: none; transition: 0.3s; box-sizing: border-box; font-family: inherit;
        }
        input:focus { border-color: #6366f1; background: #111; box-shadow: 0 0 15px rgba(99, 102, 241, 0.2); }
        button {
            width: 100%; padding: 18px; border-radius: 15px; border: none;
            background: linear-gradient(135deg, #6366f1, #a855f7);
            color: white; font-weight: 800; font-size: 18px; cursor: pointer; transition: 0.3s;
        }
        button:hover { transform: scale(1.02); box-shadow: 0 0 30px rgba(99, 102, 241, 0.4); }
    </style>
</head>
<body>
    <div class="glass-box">
        <div class="logo-con"><img src="<?= $logo ?>" style="max-width:80%; max-height:80%"></div>
        <h2 style="margin:0 0 10px"><?= $name ?></h2>
        <p style="color:#888; margin-bottom:40px">لوحة تحكم الإدارة الذكية</p>
        <?php if(isset($err)) echo "<div style='color:#f87171; background:rgba(255,0,0,0.1); padding:10px; border-radius:10px; margin-bottom:20px'>$err</div>"; ?>
        <form method="POST">
            <input type="text" name="user" placeholder="اسم المستخدم" required>
            <input type="password" name="pass" placeholder="كلمة المرور" required>
            <button>تسجيل الدخول</button>
        </form>
    </div>
</body>
</html>
