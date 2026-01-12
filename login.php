<?php
require 'db.php';
if($_POST){
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username=?"); $stmt->execute([$_POST['user']]); $u=$stmt->fetch();
    if($u && password_verify($_POST['pass'],$u['password'])) { $_SESSION['uid']=$u['id']; header("Location: index.php"); exit; }
    else $err="بيانات خاطئة";
}
?>
<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="UTF-8"><title>دخول - دار الميار</title>
<link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700&display=swap" rel="stylesheet">
<style>
body{background:#020617;font-family:'Tajawal';display:flex;align-items:center;justify-content:center;height:100vh;margin:0;color:white}
.box{background:#1e293b;padding:50px;border-radius:20px;width:350px;text-align:center;box-shadow:0 20px 50px rgba(0,0,0,0.5);border:1px solid #334155}
input{width:100%;padding:15px;margin:10px 0;background:#0f172a;border:1px solid #334155;border-radius:10px;color:white;box-sizing:border-box;font-family:inherit}
button{width:100%;padding:15px;border:none;border-radius:10px;background:#3b82f6;color:white;font-weight:bold;cursor:pointer;margin-top:20px}
</style></head>
<body><div class="box"><img src="logo.png" width="80" style="border-radius:50%;margin-bottom:20px"><h2>دار الميار للمقاولات</h2>
<?php if(isset($err)) echo "<p style='color:red'>$err</p>"; ?><form method="POST"><input type="text" name="user" placeholder="المستخدم"><input type="password" name="pass" placeholder="كلمة المرور"><button>دخول</button></form></div></body></html>
