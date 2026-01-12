<?php
$host = 'db5019378605.hosting-data.io ';
$db   = 'dbs15162823'; 
$user = 'dbu2244961';
$pass = 'kuqteg-ginbak-myKga7';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    try {
        $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db`");
        $pdo->exec("USE `$db`");
    } catch(PDOException $ex) { die("فشل الاتصال بقاعدة البيانات"); }
}

session_start();

function getSet($k) { global $pdo; try{$s=$pdo->prepare("SELECT v FROM settings WHERE k=?");$s->execute([$k]);return $s->fetchColumn();}catch(Exception $e){return '';} }
function saveSet($k,$v) { global $pdo; $pdo->prepare("REPLACE INTO settings (k,v) VALUES (?,?)")->execute([$k,$v]); }
function upload($f){ if($f['error']==0){ $n=uniqid().'.'.pathinfo($f['name'],PATHINFO_EXTENSION); move_uploaded_file($f['tmp_name'],'uploads/'.$n); return 'uploads/'.$n; } return null; }
?>
