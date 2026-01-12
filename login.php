<?php
require 'config.php';
if($_POST){
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username=?");
    $stmt->execute([$_POST['user']]); $u = $stmt->fetch();
    if($u && password_verify($_POST['pass'], $u['password'])){ 
        $_SESSION['uid'] = $u['id']; 
        header("Location: index.php"); exit; 
    } 
    $err="خطأ في البيانات";
}
?>
<!DOCTYPE html>
<html dir="rtl">
<head>
    <title>تسجيل الدخول</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>body{background:#1e293b;height:100vh;display:flex;align-items:center;justify-content:center}</style>
</head>
<body>
    <div class="card p-5" style="width:400px">
        <h3 class="text-center mb-4">دار الميار</h3>
        <?php if(isset($err)) echo "<div class='alert alert-danger'>$err</div>"; ?>
        <form method="POST">
            <input type="text" name="user" class="form-control mb-3" placeholder="اسم المستخدم" required>
            <input type="password" name="pass" class="form-control mb-3" placeholder="كلمة المرور" required>
            <button class="btn btn-primary w-100">دخول</button>
        </form>
    </div>
</body>
</html>
