<?php
$servername = "db5019378605.hosting-data.io "; // أو عنوان السيرفر
$username = "dbu2244961";
$password = "kuqteg-ginbak-myKga7";

// إنشاء الاتصال
$conn = new mysqli($servername, $username, $password);

// فحص الاتصال
if ($conn->connect_error) {
  die("فشل الاتصال: " . $conn->connect_error);
}
echo "تم الاتصال بنجاح!";
?>
