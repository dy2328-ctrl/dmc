<?php
require 'db.php';
if(!isset($_SESSION['uid'])) { header("Location: login.php"); exit; }

// --- BACKEND LOGIC ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. إضافة عقار (مبنى/أرض)
    if (isset($_POST['add_property'])) {
        $pdo->prepare("INSERT INTO properties (name, type, address, manager_name, manager_phone) VALUES (?,?,?,?,?)")
            ->execute([$_POST['name'], $_POST['type'], $_POST['address'], $_POST['manager'], $_POST['phone']]);
        header("Location: ?p=properties"); exit;
    }

    // 2. إضافة وحدة
    if (isset($_POST['add_unit'])) {
        $pdo->prepare("INSERT INTO units (property_id, unit_name, unit_number, floor_number, yearly_price, status) VALUES (?,?,?,?,?,?)")
            ->execute([$_POST['pid'], $_POST['name'], $_POST['num'], $_POST['floor'], $_POST['price'], $_POST['status']]);
        header("Location: ?p=units"); exit;
    }

    // 3. إضافة مستأجر
    if (isset($_POST['add_tenant'])) {
        $pdo->prepare("INSERT INTO tenants (full_name, phone, id_number, email) VALUES (?,?,?,?)")
            ->execute([$_POST['name'], $_POST['phone'], $_POST['nid'], $_POST['email']]);
        header("Location: ?p=tenants"); exit;
    }

    // 4. إضافة عقد (FIXED)
    if (isset($_POST['add_contract'])) {
        $pdo->prepare("INSERT INTO contracts (tenant_id, unit_id, start_date, end_date, total_amount, payment_cycle) VALUES (?,?,?,?,?,?)")
            ->execute([$_POST['tid'], $_POST['uid'], $_POST['start'], $_POST['end'], $_POST['total'], $_POST['cycle']]);
        $pdo->prepare("UPDATE units SET status='rented' WHERE id=?")->execute([$_POST['uid']]);
        header("Location: ?p=contracts"); exit;
    }

    // 5. حفظ الإعدادات
    if (isset($_POST['save_settings'])) {
        foreach($_POST['set'] as $k => $v) saveSetting($k, $v);
        header("Location: ?p=settings&success=1"); exit;
    }
}

$p = $_GET['p'] ?? 'dashboard';
$companyName = getSetting('company_name');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $companyName ?> - النظام الذكي</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        /* --- GEMINI DARK THEME CSS --- */
        :root {
            --bg: #0f172a; --sidebar: #1e293b; --card: #1e293b; 
            --text-main: #f8fafc; --text-sub: #94a3b8;
            --primary: #6366f1; --accent: #8b5cf6;
            --success: #10b981; --warning: #f59e0b; --danger: #ef4444;
            --grad-main: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            --glass: rgba(30, 41, 59, 0.7);
        }
        
        body { font-family: 'Tajawal'; background: var(--bg); color: var(--text-main); margin: 0; display: flex; height: 100vh; overflow: hidden; }
        ::-webkit-scrollbar { width: 8px; } ::-webkit-scrollbar-track { background: var(--bg); } ::-webkit-scrollbar-thumb { background: #334155; border-radius: 4px; }

        /* Sidebar */
        .sidebar { width: 280px; background: var(--sidebar); border-left: 1px solid #334155; display: flex; flex-direction: column; padding: 20px; z-index: 10; box-shadow: 4px 0 20px rgba(0,0,0,0.3); }
        .brand { display: flex; align-items: center; gap: 15px; margin-bottom: 40px; padding-bottom: 20px; border-bottom: 1px solid #334155; }
        .brand img { width: 45px; height: 45px; }
        .brand span { font-size: 18px; font-weight: 800; background: var(--grad-main); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        
        .nav-link { display: flex; align-items: center; gap: 12px; padding: 14px 18px; margin-bottom: 8px; border-radius: 12px; color: var(--text-sub); text-decoration: none; font-weight: 500; transition: 0.3s; }
        .nav-link:hover, .nav-link.active { background: var(--grad-main); color: white; box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3); transform: translateX(-5px); }
        .nav-link i { width: 20px; text-align: center; }

        /* Main Content */
        .main { flex: 1; padding: 30px; overflow-y: auto; position: relative; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .page-title { font-size: 24px; font-weight: 800; }
        .user-profile { display: flex; align-items: center; gap: 10px; background: var(--card); padding: 8px 15px; border-radius: 30px; border: 1px solid #334155; }

        /* Cards & Stats */
        .grid-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: var(--card); padding: 20px; border-radius: 16px; position: relative; overflow: hidden; border: 1px solid #334155; transition: 0.3s; }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px -5px rgba(0,0,0,0.3); border-color: var(--primary); }
        .stat-val { font-size: 32px; font-weight: 800; margin: 10px 0; }
        .stat-label { color: var(--text-sub); font-size: 14px; }
        .stat-icon { position: absolute; left: 15px; top: 15px; font-size: 40px; opacity: 0.1; color: white; }

        /* Tables */
        .table-container { background: var(--card); border-radius: 16px; overflow: hidden; border: 1px solid #334155; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #0f172a; padding: 15px; text-align: right; font-size: 13px; color: var(--text-sub); }
        td { padding: 15px; border-bottom: 1px solid #334155; vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: rgba(255,255,255,0.02); }

        /* Buttons & Badges */
        .btn { padding: 10px 20px; border-radius: 10px; border: none; cursor: pointer; font-weight: bold; font-family: inherit; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; transition: 0.2s; }
        .btn-primary { background: var(--grad-main); color: white; }
        .btn-primary:hover { opacity: 0.9; transform: scale(1.02); }
        .badge { padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: bold; }
        .bg-green { background: rgba(16, 185, 129, 0.2); color: #34d399; }
        .bg-red { background: rgba(239, 68, 68, 0.2); color: #f87171; }
        .bg-blue { background: rgba(99, 102, 241, 0.2); color: #818cf8; }

        /* Modals (The Cool Part) */
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); backdrop-filter: blur(5px); z-index: 1000; align-items: center; justify-content: center; }
        .modal-content { background: var(--card); width: 600px; border-radius: 20px; padding: 30px; border: 1px solid #475569; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); transform: scale(0.9); transition: 0.3s; opacity: 0; }
        .modal.active .modal-content { transform: scale(1); opacity: 1; }
        
        /* Form Inputs inside Modal */
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 8px; color: var(--text-sub); font-size: 13px; }
        .form-input { width: 100%; padding: 12px; background: #0f172a; border: 1px solid #334155; border-radius: 10px; color: white; outline: none; box-sizing: border-box; font-family: inherit; }
        .form-input:focus { border-color: var(--primary); }
        .full-width { grid-column: span 2; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="brand">
            <img src="<?= getSetting('logo') ?>" onerror="this.src='logo.png'">
            <span>دار الميار</span>
        </div>
        <a href="?p=dashboard" class="nav-link <?= $p=='dashboard'?'active':'' ?>"><i class="fa-solid fa-chart-pie"></i> لوحة التحكم</a>
        <a href="?p=properties" class="nav-link <?= $p=='properties'?'active':'' ?>"><i class="fa-solid fa-city"></i> العقارات</a>
        <a href="?p=units" class="nav-link <?= $p=='units'?'active':'' ?>"><i class="fa-solid fa-door-open"></i> الوحدات</a>
        <a href="?p=tenants" class="nav-link <?= $p=='tenants'?'active':'' ?>"><i class="fa-solid fa-users"></i> المستأجرين</a>
        <a href="?p=contracts" class="nav-link <?= $p=='contracts'?'active':'' ?>"><i class="fa-solid fa-file-signature"></i> العقود الإيجارية</a>
        <a href="?p=settings" class="nav-link <?= $p=='settings'?'active':'' ?>"><i class="fa-solid fa-gear"></i> الإعدادات</a>
        <div style="flex:1"></div>
        <a href="logout.php" class="nav-link" style="color:var(--danger)"><i class="fa-solid fa-right-from-bracket"></i> تسجيل الخروج</a>
    </div>

    <div class="main">
        <div class="top-bar">
            <div class="page-title">
                <?php 
                    if($p=='dashboard') echo 'لوحة التحكم الذكية';
                    elseif($p=='properties') echo 'إدارة العقارات';
                    elseif($p=='units') echo 'إدارة الوحدات';
                    elseif($p=='contracts') echo 'العقود الإيجارية';
                    elseif($p=='settings') echo 'الإعدادات العامة';
                    else echo 'النظام';
                ?>
            </div>
            <div class="user-profile">
                <i class="fa-solid fa-user-circle fa-lg"></i>
                <span>مدير النظام</span>
            </div>
        </div>

        <?php if($p == 'dashboard'): 
            $units_cnt = $pdo->query("SELECT count(*) FROM units")->fetchColumn();
            $rented_cnt = $pdo->query("SELECT count(*) FROM units WHERE status='rented'")->fetchColumn();
            $income = $pdo->query("SELECT SUM(total_amount) FROM contracts WHERE status='active'")->fetchColumn() ?: 0;
            $expiring = $pdo->query("SELECT count(*) FROM contracts WHERE end_date <= DATE_ADD(CURRENT_DATE, INTERVAL 30 DAY)")->fetchColumn();
        ?>
            <div class="grid-stats">
                <div class="stat-card">
                    <i class="fa-solid fa-wallet stat-icon" style="color:#10b981"></i>
                    <div class="stat-val"><?= number_format($income) ?></div>
                    <div class="stat-label">الإيرادات الحالية (<?= getSetting('currency_code') ?>)</div>
                </div>
                <div class="stat-card">
                    <i class="fa-solid fa-building stat-icon" style="color:#6366f1"></i>
                    <div class="stat-val"><?= $units_cnt ?></div>
                    <div class="stat-label">إجمالي الوحدات</div>
                </div>
                <div class="stat-card">
                    <i class="fa-solid fa-check-circle stat-icon" style="color:#f59e0b"></i>
                    <div class="stat-val"><?= $rented_cnt ?></div>
                    <div class="stat-label">وحدات مؤجرة</div>
                </div>
                <div class="stat-card" style="border-color: <?= $expiring>0 ? 'var(--danger)' : '#334155' ?>">
                    <i class="fa-solid fa-clock stat-icon" style="color:#ef4444"></i>
                    <div class="stat-val" style="color:<?= $expiring>0 ? 'var(--danger)' : 'white' ?>"><?= $expiring ?></div>
                    <div class="stat-label">عقود تنتهي قريباً</div>
                </div>
            </div>

            <div style="display:grid; grid-template-columns: 2fr 1fr; gap:20px;">
                <div class="table-container" style="padding:20px;">
                    <h3 style="margin-top:0">تنبيهات العقود</h3>
                    <table>
                        <thead><tr><th>رقم العقد</th><th>المستأجر</th><th>النهاية</th><th>الحالة</th></tr></thead>
                        <tbody>
                            <?php $q=$pdo->query("SELECT c.*, t.full_name FROM contracts c JOIN tenants t ON c.tenant_id=t.id WHERE c.end_date <= DATE_ADD(CURRENT_DATE, INTERVAL 30 DAY) LIMIT 5");
                            if($q->rowCount() == 0) echo "<tr><td colspan='4' style='text-align:center; padding:20px; color:#64748b'>لا توجد تنبيهات حالياً <i class='fa-solid fa-check'></i></td></tr>";
                            while($r=$q->fetch()): ?>
                            <tr>
                                <td>#<?= $r['id'] ?></td>
                                <td><?= $r['full_name'] ?></td>
                                <td style="color:var(--danger)"><?= $r['end_date'] ?></td>
                                <td><span class="badge bg-red">ينتهي قريباً</span></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <div class="stat-card">
                    <h3 style="margin-top:0; text-align:center">حالة الإشغال</h3>
                    <canvas id="occChart"></canvas>
                </div>
            </div>
            <script>
                new Chart(document.getElementById('occChart'), {
                    type: 'doughnut',
                    data: {
                        labels: ['مؤجر', 'شاغر'],
                        datasets: [{ data: [<?= $rented_cnt ?>, <?= $units_cnt - $rented_cnt ?>], backgroundColor: ['#6366f1', '#1e293b'], borderWidth: 0 }]
                    },
                    options: { plugins: { legend: { position: 'bottom', labels: { color: 'white' } } }, cutout: '70%' }
                });
            </script>
        <?php endif; ?>

        <?php if($p == 'properties'): ?>
            <button onclick="openModal('addPropModal')" class="btn btn-primary" style="margin-bottom:20px"><i class="fa-solid fa-plus"></i> إضافة عقار جديد</button>
            <div class="table-container">
                <table>
                    <thead><tr><th>الاسم</th><th>النوع</th><th>العنوان</th><th>المسؤول</th><th>الإجراءات</th></tr></thead>
                    <tbody>
                        <?php $q=$pdo->query("SELECT * FROM properties ORDER BY id DESC"); while($r=$q->fetch()): ?>
                        <tr>
                            <td><i class="fa-solid fa-building" style="color:var(--primary); margin-left:10px"></i> <b><?= $r['name'] ?></b></td>
                            <td><?= $r['type'] ?></td>
                            <td><?= $r['address'] ?></td>
                            <td><?= $r['manager_name'] ?> <br> <small style="color:var(--text-sub)"><?= $r['manager_phone'] ?></small></td>
                            <td><button class="btn" style="background:#334155; padding:5px 10px"><i class="fa-solid fa-pen"></i></button></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <?php if($p == 'units'): ?>
            <button onclick="openModal('addUnitModal')" class="btn btn-primary" style="margin-bottom:20px"><i class="fa-solid fa-plus"></i> إضافة وحدة جديدة</button>
            <div class="table-container">
                <table>
                    <thead><tr><th>رقم الوحدة</th><th>العقار</th><th>السعر السنوي</th><th>الحالة</th><th>إجراءات</th></tr></thead>
                    <tbody>
                        <?php $q=$pdo->query("SELECT u.*, p.name as pname FROM units u JOIN properties p ON u.property_id=p.id"); while($r=$q->fetch()): ?>
                        <tr>
                            <td><b><?= $r['unit_name'] ?></b> (<?= $r['unit_number'] ?>)</td>
                            <td><?= $r['pname'] ?></td>
                            <td><?= number_format($r['yearly_price']) ?></td>
                            <td>
                                <span class="badge <?= $r['status']=='rented'?'bg-red':'bg-green' ?>">
                                    <?= $r['status']=='rented'?'مؤجر':'متاح' ?>
                                </span>
                            </td>
                            <td><button class="btn" style="background:#334155; padding:5px 10px"><i class="fa-solid fa-pen"></i></button></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <?php if($p == 'contracts'): ?>
            <button onclick="openModal('addContractModal')" class="btn btn-primary" style="margin-bottom:20px"><i class="fa-solid fa-file-contract"></i> إنشاء عقد جديد</button>
            <div class="table-container">
                <table>
                    <thead><tr><th>رقم العقد</th><th>المستأجر</th><th>الوحدة</th><th>التاريخ</th><th>القيمة</th><th>الحالة</th></tr></thead>
                    <tbody>
                        <?php $q=$pdo->query("SELECT c.*, t.full_name, u.unit_name FROM contracts c JOIN tenants t ON c.tenant_id=t.id JOIN units u ON c.unit_id=u.id ORDER BY c.id DESC"); 
                        while($r=$q->fetch()): ?>
                        <tr>
                            <td>#<?= $r['id'] ?></td>
                            <td><?= $r['full_name'] ?></td>
                            <td><?= $r['unit_name'] ?></td>
                            <td><?= $r['start_date'] ?> <i class="fa-solid fa-arrow-left" style="font-size:10px; margin:0 5px"></i> <?= $r['end_date'] ?></td>
                            <td style="color:var(--success); font-weight:bold"><?= number_format($r['total_amount']) ?></td>
                            <td><span class="badge bg-blue">نشط</span></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <?php if($p == 'tenants'): ?>
            <button onclick="openModal('addTenantModal')" class="btn btn-primary" style="margin-bottom:20px"><i class="fa-solid fa-user-plus"></i> إضافة مستأجر</button>
            <div class="table-container">
                <table>
                    <thead><tr><th>الاسم</th><th>الهوية</th><th>رقم الجوال</th><th>عقود نشطة</th></tr></thead>
                    <tbody>
                        <?php $q=$pdo->query("SELECT * FROM tenants"); while($r=$q->fetch()): ?>
                        <tr>
                            <td><b><?= $r['full_name'] ?></b></td>
                            <td><?= $r['id_number'] ?></td>
                            <td><?= $r['phone'] ?></td>
                            <td><span class="badge bg-green">1 عقد</span></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <?php if($p == 'settings'): ?>
            <form method="POST" class="grid-stats" style="display:grid; grid-template-columns: 1fr 1fr; gap:20px">
                <input type="hidden" name="save_settings" value="1">
                
                <div class="stat-card">
                    <h3 style="margin-top:0; color:var(--primary)"><i class="fa-solid fa-building"></i> معلومات الشركة</h3>
                    <div class="form-group">
                        <label>اسم الشركة</label>
                        <input type="text" name="set[company_name]" value="<?= getSetting('company_name') ?>" class="form-input">
                    </div>
                    <div class="form-group">
                        <label>الهاتف</label>
                        <input type="text" name="set[company_phone]" value="<?= getSetting('company_phone') ?>" class="form-input">
                    </div>
                </div>

                <div class="stat-card">
                    <h3 style="margin-top:0; color:var(--success)"><i class="fa-solid fa-percent"></i> إعدادات الضريبة</h3>
                    <div class="form-group">
                        <label>الرقم الضريبي</label>
                        <input type="text" name="set[vat_number]" value="<?= getSetting('vat_number') ?>" class="form-input">
                    </div>
                    <div class="form-group">
                        <label>نسبة الضريبة (%)</label>
                        <input type="number" name="set[vat_percent]" value="<?= getSetting('vat_percent') ?>" class="form-input">
                    </div>
                </div>

                <div class="stat-card">
                    <h3 style="margin-top:0; color:var(--warning)"><i class="fa-solid fa-coins"></i> العملة</h3>
                    <div class="form-group">
                        <label>رمز العملة</label>
                        <input type="text" name="set[currency_code]" value="<?= getSetting('currency_code') ?>" class="form-input">
                    </div>
                </div>

                <div class="stat-card" style="display:flex; align-items:center; justify-content:center">
                    <button class="btn btn-primary" style="width:100%; justify-content:center; padding:15px">حفظ كافة الإعدادات</button>
                </div>
            </form>
        <?php endif; ?>

    </div>

    <div id="addPropModal" class="modal">
        <form method="POST" class="modal-content">
            <h3 style="margin-top:0">🏢 إضافة عقار جديد</h3>
            <input type="hidden" name="add_property" value="1">
            <div class="form-grid">
                <div class="form-group full-width"><label>اسم العقار</label><input type="text" name="name" class="form-input" required></div>
                <div class="form-group"><label>نوع العقار</label><select name="type" class="form-input"><option>عمارة سكنية</option><option>مجمع تجاري</option></select></div>
                <div class="form-group"><label>اسم المسؤول</label><input type="text" name="manager" class="form-input"></div>
                <div class="form-group"><label>هاتف المسؤول</label><input type="text" name="phone" class="form-input"></div>
                <div class="form-group full-width"><label>العنوان</label><input type="text" name="address" class="form-input"></div>
            </div>
            <div style="display:flex; gap:10px; margin-top:20px">
                <button class="btn btn-primary" style="flex:1">حفظ</button>
                <button type="button" onclick="closeModal('addPropModal')" class="btn" style="background:#334155; color:white">إلغاء</button>
            </div>
        </form>
    </div>

    <div id="addUnitModal" class="modal">
        <form method="POST" class="modal-content">
            <h3 style="margin-top:0">🏠 إضافة وحدة</h3>
            <input type="hidden" name="add_unit" value="1">
            <div class="form-grid">
                <div class="form-group full-width">
                    <label>تابع للعقار</label>
                    <select name="pid" class="form-input">
                        <?php $ps=$pdo->query("SELECT * FROM properties"); while($p=$ps->fetch()) echo "<option value='{$p['id']}'>{$p['name']}</option>"; ?>
                    </select>
                </div>
                <div class="form-group"><label>اسم الوحدة</label><input type="text" name="name" class="form-input" placeholder="شقة 101"></div>
                <div class="form-group"><label>رقم الوحدة</label><input type="text" name="num" class="form-input"></div>
                <div class="form-group"><label>الدور</label><input type="text" name="floor" class="form-input"></div>
                <div class="form-group"><label>السعر السنوي</label><input type="number" name="price" class="form-input"></div>
                <div class="form-group"><label>الحالة</label><select name="status" class="form-input"><option value="available">متاح</option><option value="maintenance">صيانة</option></select></div>
            </div>
            <div style="display:flex; gap:10px; margin-top:20px">
                <button class="btn btn-primary" style="flex:1">حفظ</button>
                <button type="button" onclick="closeModal('addUnitModal')" class="btn" style="background:#334155; color:white">إلغاء</button>
            </div>
        </form>
    </div>

    <div id="addContractModal" class="modal">
        <form method="POST" class="modal-content">
            <h3 style="margin-top:0">📝 عقد جديد</h3>
            <input type="hidden" name="add_contract" value="1">
            <div class="form-grid">
                <div class="form-group full-width">
                    <label>المستأجر</label>
                    <select name="tid" class="form-input">
                        <?php $ts=$pdo->query("SELECT * FROM tenants"); while($t=$ts->fetch()) echo "<option value='{$t['id']}'>{$t['full_name']}</option>"; ?>
                    </select>
                </div>
                <div class="form-group full-width">
                    <label>الوحدة (المتاحة فقط)</label>
                    <select name="uid" class="form-input">
                        <?php $us=$pdo->query("SELECT * FROM units WHERE status='available'"); while($u=$us->fetch()) echo "<option value='{$u['id']}'>{$u['unit_name']} ({$u['yearly_price']})</option>"; ?>
                    </select>
                </div>
                <div class="form-group"><label>تاريخ البدء</label><input type="date" name="start" class="form-input"></div>
                <div class="form-group"><label>تاريخ الانتهاء</label><input type="date" name="end" class="form-input"></div>
                <div class="form-group"><label>إجمالي العقد</label><input type="number" name="total" class="form-input"></div>
                <div class="form-group"><label>دورة السداد</label><select name="cycle" class="form-input"><option value="yearly">سنوي</option><option value="monthly">شهري</option></select></div>
            </div>
            <div style="display:flex; gap:10px; margin-top:20px">
                <button class="btn btn-primary" style="flex:1">إصدار العقد</button>
                <button type="button" onclick="closeModal('addContractModal')" class="btn" style="background:#334155; color:white">إلغاء</button>
            </div>
        </form>
    </div>

    <div id="addTenantModal" class="modal">
        <form method="POST" class="modal-content">
            <h3 style="margin-top:0">👤 مستأجر جديد</h3>
            <input type="hidden" name="add_tenant" value="1">
            <div class="form-grid">
                <div class="form-group full-width"><label>الاسم الكامل</label><input type="text" name="name" class="form-input"></div>
                <div class="form-group"><label>رقم الجوال</label><input type="text" name="phone" class="form-input"></div>
                <div class="form-group"><label>رقم الهوية</label><input type="text" name="nid" class="form-input"></div>
                <div class="form-group full-width"><label>البريد الإلكتروني</label><input type="email" name="email" class="form-input"></div>
            </div>
            <div style="display:flex; gap:10px; margin-top:20px">
                <button class="btn btn-primary" style="flex:1">حفظ</button>
                <button type="button" onclick="closeModal('addTenantModal')" class="btn" style="background:#334155; color:white">إلغاء</button>
            </div>
        </form>
    </div>

    <script>
        function openModal(id) {
            let m = document.getElementById(id);
            m.style.display = 'flex';
            setTimeout(() => m.classList.add('active'), 10);
        }
        function closeModal(id) {
            let m = document.getElementById(id);
            m.classList.remove('active');
            setTimeout(() => m.style.display = 'none', 300);
        }
        // إغلاق النافذة عند النقر خارجها
        window.onclick = function(e) {
            if(e.target.classList.contains('modal')) closeModal(e.target.id);
        }
    </script>
</body>
</html>
