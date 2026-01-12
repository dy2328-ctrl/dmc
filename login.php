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
    <title>دخول - <?= $name ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    <style>
        body { margin:0; height:100vh; font-family:'Tajawal'; background:#f3f4f6; display:flex; align-items:center; justify-content:center; }
        .box { background:white; width:400px; padding:40px; border-radius:20px; text-align:center; box-shadow:0 10px 30px rgba(0,0,0,0.05); }
        .logo { width:100px; margin-bottom:20px; }
        input { width:100%; padding:15px; margin-bottom:15px; border:1px solid #e5e7eb; border-radius:10px; box-sizing:border-box; outline:none; font-family:inherit; }
        button { width:100%; padding:15px; background:#6366f1; color:white; border:none; border-radius:10px; font-weight:bold; cursor:pointer; font-size:16px; }
    </style>
</head>
<body>
    <div class="box">
        <img src="logo.png" class="logo">
        <h2 style="margin:0 0 20px"><?= $name ?></h2>
        <?php if(isset($err)) echo "<p style='color:red'>$err</p>"; ?>
        <form method="POST">
            <input type="text" name="user" placeholder="اسم المستخدم" required>
            <input type="password" name="pass" placeholder="كلمة المرور" required>
            <button>تسجيل الدخول</button>
        </form>
    </div>
</body>
</html>
