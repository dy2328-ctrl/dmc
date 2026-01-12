<?php
// install.php - Dar Al-Mayar Enterprise Setup
require 'db.php';

$sql = "
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE, password VARCHAR(255),
    full_name VARCHAR(100), phone VARCHAR(20), role VARCHAR(20) DEFAULT 'staff',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS settings (k VARCHAR(50) PRIMARY KEY, v LONGTEXT);

CREATE TABLE IF NOT EXISTS properties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255), type VARCHAR(100), address TEXT, 
    manager_name VARCHAR(100), manager_phone VARCHAR(50), photo VARCHAR(255)
);

CREATE TABLE IF NOT EXISTS units (
    id INT AUTO_INCREMENT PRIMARY KEY,
    property_id INT, unit_name VARCHAR(100), 
    type VARCHAR(50) DEFAULT 'apartment',
    yearly_price DECIMAL(15,2), elec_meter VARCHAR(50), water_meter VARCHAR(50),
    status VARCHAR(20) DEFAULT 'available', notes TEXT, photo VARCHAR(255),
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS tenants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255), phone VARCHAR(20), id_number VARCHAR(50), 
    id_type VARCHAR(20), email VARCHAR(100), address TEXT,
    id_photo VARCHAR(255), personal_photo VARCHAR(255)
);

CREATE TABLE IF NOT EXISTS contracts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT, unit_id INT, start_date DATE, end_date DATE,
    total_amount DECIMAL(15,2), payment_cycle VARCHAR(20),
    signature_img LONGTEXT, status VARCHAR(20) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id),
    FOREIGN KEY (unit_id) REFERENCES units(id)
);
";

try {
    $pdo->exec($sql);
    
    // إنشاء مجلد للصور
    if (!file_exists('uploads')) { mkdir('uploads', 0777, true); }

    // إنشاء الأدمن
    $chk = $pdo->query("SELECT count(*) FROM users WHERE username='admin'")->fetchColumn();
    if($chk == 0){
        $pdo->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?,?,?,?)")
            ->execute(['admin', password_hash('123456', PASSWORD_DEFAULT), 'المدير العام', 'admin']);
    }

    // الإعدادات الافتراضية
    $defaults = ['company_name'=>'دار الميار للمقاولات', 'vat_no'=>'3000000000', 'logo'=>'logo.png'];
    foreach($defaults as $k=>$v) $pdo->prepare("INSERT IGNORE INTO settings (k,v) VALUES (?,?)")->execute([$k,$v]);

    echo "<div style='font-family:tahoma; text-align:center; padding:50px; background:#0f172a; color:#4ade80;'>
            <h1>✅ تم تثبيت نظام دار الميار (نسخة المؤسسات)</h1>
            <p>تم تحديث الجداول وتفعيل الميزات الذكية.</p>
            <a href='index.php' style='color:white; font-size:20px; text-decoration:underline'>الدخول للنظام</a>
          </div>";

} catch (PDOException $e) { die("Error: " . $e->getMessage()); }
?>
