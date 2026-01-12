<?php

$host = 'db5019378605.hosting-data.io'; 
$db   = 'dbs15162823';  
$user = 'dbu2244961';                 
$pass = 'YOUR_PASSWORD';         
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) { die("Connection Failed"); }

session_start();

// Helper Functions
function getSetting($key) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT v FROM settings WHERE k=?");
    $stmt->execute([$key]);
    return $stmt->fetchColumn() ?: '';
}

function saveSetting($key, $val) {
    global $pdo;
    $pdo->prepare("REPLACE INTO settings (k,v) VALUES (?,?)")->execute([$key, $val]);
}
?>
