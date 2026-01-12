<?php
// install.php - Gemini AI-Master
require 'db.php';

$sql = "
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE, password VARCHAR(255),
    full_name VARCHAR(100), phone VARCHAR(20), role ENUM('admin','staff') DEFAULT 'staff',
    photo VARCHAR(255), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS settings (k VARCHAR(50) PRIMARY KEY, v LONGTEXT);

CREATE TABLE IF NOT EXISTS properties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255), type VARCHAR(100), address TEXT, 
    manager_name VARCHAR(100), manager_phone VARCHAR(50), photo VARCHAR(255)
);

CREATE TABLE IF NOT EXISTS units (
    id INT AUTO_INCREMENT PRIMARY KEY,
    property_id INT, unit_name VARCHAR(100), type VARCHAR(50),
    yearly_price DECIMAL(15,2), elec_meter_no VARCHAR(50), water_meter_no VARCHAR(50),
    status VARCHAR(50) DEFAULT 'available', notes TEXT, photo VARCHAR(255),
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS vendors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100), service_type VARCHAR(50), phone VARCHAR(20), email VARCHAR(100)
);

CREATE TABLE IF NOT EXISTS maintenance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    property_id INT, unit_id INT, vendor_id INT,
    description TEXT, cost DECIMAL(15,2), status ENUM('pending','completed','paid') DEFAULT 'pending',
    request_date DATE,
    FOREIGN KEY (property_id) REFERENCES properties(id)
);

CREATE TABLE IF NOT EXISTS tenants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255), phone VARCHAR(20), id_number VARCHAR(50), 
    id_type VARCHAR(50), cr_number VARCHAR(50), email VARCHAR(100), address TEXT,
    id_photo VARCHAR(255), personal_photo VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS contracts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT, unit_id INT, start_date DATE, end_date DATE,
    total_amount DECIMAL(15,2), payment_cycle VARCHAR(50),
    signature_img LONGTEXT, status VARCHAR(20) DEFAULT 'active',
    notes TEXT, services_cost DECIMAL(15,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id),
    FOREIGN KEY (unit_id) REFERENCES units(id)
);

CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contract_id INT, title VARCHAR(100), 
    amount DECIMAL(15,2), paid_amount DECIMAL(15,2) DEFAULT 0, 
    due_date DATE, status VARCHAR(20) DEFAULT 'pending', 
    paid_date DATE,
    FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE CASCADE
);

-- الجديد: جدول العمليات المالية (سندات القبض)
CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payment_id INT,
    amount_paid DECIMAL(15,2),
    payment_method VARCHAR(50), -- كاش، تحويل، شيك
    transaction_date DATE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE
);
";

try {
    $pdo->exec($sql);
    $defaults = ['company_name'=>'دار الميار للمقاولات', 'logo'=>'logo.png'];
    foreach($defaults as $k=>$v) $pdo->prepare("INSERT IGNORE INTO settings (k,v) VALUES (?,?)")->execute([$k,$v]);
    
    // Admin setup
    $chk = $pdo->query("SELECT count(*) FROM users WHERE username='admin'")->fetchColumn();
    if($chk == 0){
        $pdo->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?,?,?,?)")
            ->execute(['admin', password_hash('123456', PASSWORD_DEFAULT), 'المدير العام', 'admin']);
    }

    echo "<div style='background:#000; color:#4ade80; padding:40px; text-align:center; font-family:tahoma;'>
            <h1>✅ تم الترقية إلى Gemini AI-Master</h1>
            <p>تم تفعيل المحرك المالي الذكي والجداول التحليلية.</p>
            <a href='index.php' style='color:white; font-size:20px'>الدخول للنظام</a>
          </div>";

} catch (PDOException $e) { die("Error: " . $e->getMessage()); }
?>
