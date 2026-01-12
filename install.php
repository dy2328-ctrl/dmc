<?php
// install.php - Dar Al-Mayar Enterprise Fixer
require 'db.php';

$sql = "
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE, password VARCHAR(255),
    full_name VARCHAR(100), phone VARCHAR(20), role ENUM('admin','staff') DEFAULT 'staff',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS settings (k VARCHAR(50) PRIMARY KEY, v LONGTEXT);

CREATE TABLE IF NOT EXISTS properties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255), type VARCHAR(100), address TEXT, 
    manager_name VARCHAR(100), manager_phone VARCHAR(50),
    photo VARCHAR(255)
);

CREATE TABLE IF NOT EXISTS units (
    id INT AUTO_INCREMENT PRIMARY KEY,
    property_id INT, unit_name VARCHAR(100), type VARCHAR(50),
    yearly_price DECIMAL(15,2), elec_meter_no VARCHAR(50), water_meter_no VARCHAR(50),
    status VARCHAR(50) DEFAULT 'available', notes TEXT, photo VARCHAR(255),
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS tenants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255), phone VARCHAR(20), id_number VARCHAR(50), 
    id_type VARCHAR(50), email VARCHAR(100), address TEXT,
    id_photo VARCHAR(255), personal_photo VARCHAR(255)
);

CREATE TABLE IF NOT EXISTS contracts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT, unit_id INT, start_date DATE, end_date DATE,
    total_amount DECIMAL(15,2), payment_cycle VARCHAR(50),
    signature_img LONGTEXT, status VARCHAR(20) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
";

try {
    $pdo->exec($sql);
    
    // تصحيح اسم الشركة إجبارياً
    $pdo->prepare("REPLACE INTO settings (k,v) VALUES (?,?)")->execute(['company_name', 'دار الميار للمقاولات']);
    $pdo->prepare("REPLACE INTO settings (k,v) VALUES (?,?)")->execute(['vat_no', '3000000000']);

    // إنشاء الأدمن
    $chk = $pdo->query("SELECT count(*) FROM users WHERE username='admin'")->fetchColumn();
    if($chk == 0){
        $pdo->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?,?,?,?)")
            ->execute(['admin', password_hash('123456', PASSWORD_DEFAULT), 'المدير العام', 'admin']);
    }

    echo "<div style='background:#0f172a; color:#4ade80; padding:40px; text-align:center; font-family:sans-serif;'>
            <h1>✅ تم تحديث النظام بنجاح</h1>
            <p>تم تغيير الاسم إلى (دار الميار للمقاولات)</p>
            <p>تم إصلاح نوافذ العقود والوحدات</p>
            <a href='index.php' style='color:white; font-size:20px; text-decoration:underline'>الدخول للنظام</a>
          </div>";

} catch (PDOException $e) { die("Error: " . $e->getMessage()); }
?>
