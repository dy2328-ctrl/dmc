<?php
require 'db.php';
$name = getSet('company_name');
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
        body { margin:0; height:100vh; font-family:'Tajawal'; background:#000; display:flex; align-items:center; justify-content:center; overflow:hidden; }
        body::before { content:''; position:absolute; width:120%; height:120%; background:radial-gradient(circle, #1e1b4b, #000 70%); z-index:-1; }
        .box { width:400px; padding:50px; background:rgba(20,20,20,0.7); backdrop-filter:blur(20px); border-radius:30px; text-align:center; border:1px solid #222; box-shadow:0 20px 50px rgba(0,0,0,0.5); }
        input { width:100%; padding:15px; margin-bottom:15px; background:#111; border:1px solid #333; border-radius:12px; color:#fff; text-align:center; outline:none; box-sizing:border-box; font-family:inherit; }
        input:focus { border-color:#6366f1; }
        button { width:100%; padding:15px; background:linear-gradient(135deg, #6366f1, #8b5cf6); border:none; border-radius:12px; color:#fff; font-weight:bold; cursor:pointer; }
    </style>
</head>
<body>
    <div class="box">
        <img src="logo.png" style="width:80px; margin-bottom:20px; border-radius:50%; background:#fff; padding:5px">
        <h2 style="color:#fff; margin:0 0 30px"><?= $name ?></h2>
        <?php if(isset($err)) echo "<p style='color:#ef4444'>$err</p>"; ?>
        <form method="POST"><input type="text" name="user" placeholder="اسم المستخدم"><input type="password" name="pass" placeholder="كلمة المرور"><button>دخول</button></form>
    </div>
</body>
</html>
