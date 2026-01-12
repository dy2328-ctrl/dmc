<?php
require 'db.php';
if($_POST){
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username=?");
    $stmt->execute([$_POST['user']]);
    $u = $stmt->fetch();
    if($u && password_verify($_POST['pass'], $u['password'])){
        $_SESSION['uid'] = $u['id']; header("Location: index.php"); exit;
    } else $err="بيانات خاطئة";
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>دخول - دار الميار</title>
    <style>
        body{background:#0f172a; color:white; font-family:'Segoe UI',tahoma; display:flex; align-items:center; justify-content:center; height:100vh; margin:0}
        .box{background:#1e293b; padding:40px; border-radius:20px; text-align:center; width:350px; box-shadow:0 10px 30px rgba(0,0,0,0.5)}
        input{width:100%; padding:12px; margin:10px 0; border-radius:8px; border:1px solid #334155; background:#0f172a; color:white; box-sizing:border-box}
        button{width:100%; padding:12px; border-radius:8px; border:none; background:#6366f1; color:white; font-weight:bold; cursor:pointer; margin-top:20px}
    </style>
</head>
<body>
    <div class="box">
        <img src="logo.png" width="100" style="margin-bottom:20px">
        <h2>دار الميار للمقاولات</h2>
        <?php if(isset($err)) echo "<p style='color:red'>$err</p>"; ?>
        <form method="POST">
            <input type="text" name="user" placeholder="اسم المستخدم (admin)" required>
            <input type="password" name="pass" placeholder="كلمة المرور" required>
            <button>تسجيل الدخول</button>
        </form>
    </div>
</body>
</html>
