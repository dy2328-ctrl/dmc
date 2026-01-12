<?php
require 'db.php';

// === معالجة البيانات (Backend) ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // إضافة وحدة (مع صورة)
    if (isset($_POST['add_unit'])) {
        $photo = '';
        if(!empty($_FILES['photo']['tmp_name'])) {
            $data = file_get_contents($_FILES['photo']['tmp_name']);
            $photo = 'data:image/jpeg;base64,' . base64_encode($data);
        }
        $pdo->prepare("INSERT INTO units (property_id, unit_name, unit_number, floor_number, yearly_price, meter_number, photo_url) VALUES (?,?,?,?,?,?,?)")
            ->execute([$_POST['pid'], $_POST['name'], $_POST['num'], $_POST['floor'], $_POST['price'], $_POST['meter'], $photo]);
        header("Location: ?p=units");
    }

    // إضافة مستأجر (بيانات كاملة)
    if (isset($_POST['add_tenant'])) {
        $pdo->prepare("INSERT INTO tenants (full_name, phone, id_number, cr_number, activity_type, email) VALUES (?,?,?,?,?,?)")
            ->execute([$_POST['name'], $_POST['phone'], $_POST['nid'], $_POST['cr'], $_POST['activity'], $_POST['email']]);
        header("Location: ?p=tenants");
    }

    // إضافة عقد
    if (isset($_POST['add_contract'])) {
        $pdo->prepare("INSERT INTO contracts (tenant_id, unit_id, start_date, end_date, total_amount, payment_cycle) VALUES (?,?,?,?,?,?)")
            ->execute([$_POST['tid'], $_POST['uid'], $_POST['start'], $_POST['end'], $_POST['total'], $_POST['cycle']]);
        $pdo->prepare("UPDATE units SET status='rented' WHERE id=?")->execute([$_POST['uid']]);
        header("Location: ?p=contracts");
    }
}

// بيانات الرسم البياني
$rented = $pdo->query("SELECT count(*) FROM units WHERE status='rented'")->fetchColumn();
$avail = $pdo->query("SELECT count(*) FROM units WHERE status='available'")->fetchColumn();
$revenue = $pdo->query("SELECT SUM(total_amount) FROM contracts WHERE status='active'")->fetchColumn() ?: 0;

$p = $_GET['p'] ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>دار الميار - النظام الذكي</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root { --primary: #4f46e5; --bg: #f8fafc; --text: #1e293b; --grad: linear-gradient(135deg, #4f46e5 0%, #8b5cf6 100%); }
        body { font-family: 'Tajawal'; background: var(--bg); margin: 0; display: flex; height: 100vh; overflow: hidden; }
        
        /* القائمة الجانبية */
        .sidebar { width: 260px; background: white; border-left: 1px solid #e2e8f0; display: flex; flex-direction: column; padding: 20px; z-index: 10; }
        .brand { font-size: 20px; font-weight: 800; color: var(--primary); margin-bottom: 30px; display: flex; align-items: center; gap: 10px; }
        .nav-item { display: flex; align-items: center; gap: 12px; padding: 12px; color: #64748b; text-decoration: none; border-radius: 10px; margin-bottom: 5px; transition: 0.3s; font-weight: 500; }
        .nav-item:hover, .nav-item.active { background: var(--grad); color: white; box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3); }

        /* المحتوى */
        .main { flex: 1; padding: 30px; overflow-y: auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .btn-main { background: var(--grad); color: white; border: none; padding: 10px 20px; border-radius: 10px; cursor: pointer; text-decoration: none; font-weight: bold; display: flex; align-items: center; gap: 8px; }

        /* الكروت */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .card { background: white; padding: 25px; border-radius: 20px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .val { font-size: 28px; font-weight: 800; margin: 10px 0; }
        
        /* الجداول */
        table { width: 100%; border-collapse: collapse; }
        th { text-align: right; padding: 15px; color: #64748b; font-size: 14px; background: #f8fafc; }
        td { padding: 15px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        .badge { padding: 5px 10px; border-radius: 15px; font-size: 12px; font-weight: bold; }
        .bg-green { background: #dcfce7; color: #166534; }
        .bg-red { background: #fee2e2; color: #991b1b; }

        /* Modal */
        .modal { display: none; position: fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); backdrop-filter: blur(4px); justify-content:center; align-items:center; z-index: 1000; }
        .modal-content { background: white; padding: 30px; border-radius: 20px; width: 550px; max-width: 95%; box-shadow: 0 20px 50px rgba(0,0,0,0.2); }
        .inp { width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 15px; box-sizing: border-box; font-family: inherit; }
        
        /* Mobile */
        @media (max-width: 768px) { body { flex-direction: column; } .sidebar { width: 100%; height: auto; flex-direction: row; overflow-x: auto; order: 2; padding: 10px; } .main { padding: 15px; order: 1; } .brand { display:none; } .nav-item span { display:none; } }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="brand"><i class="fa-solid fa-building"></i> دار الميار</div>
        <a href="?p=dashboard" class="nav-item <?= $p=='dashboard'?'active':'' ?>"><i class="fa-solid fa-chart-pie"></i> <span>الرئيسية</span></a>
        <a href="?p=units" class="nav-item <?= $p=='units'?'active':'' ?>"><i class="fa-solid fa-door-open"></i> <span>الوحدات</span></a>
        <a href="?p=tenants" class="nav-item <?= $p=='tenants'?'active':'' ?>"><i class="fa-solid fa-users"></i> <span>المستأجرين</span></a>
        <a href="?p=contracts" class="nav-item <?= $p=='contracts'?'active':'' ?>"><i class="fa-solid fa-file-contract"></i> <span>العقود</span></a>
    </div>

    <div class="main">

        <?php if($p == 'dashboard'): ?>
        <div class="header">
            <h2>لوحة التحكم الذكية</h2>
            <button onclick="openModal('addContractModal')" class="btn-main"><i class="fa-solid fa-plus"></i> عقد جديد</button>
        </div>

        <div class="stats-grid">
            <div class="card">
                <div style="color:#64748b">الإيرادات الحالية</div>
                <div class="val"><?= number_format($revenue) ?> <small>ريال</small></div>
                <i class="fa-solid fa-wallet" style="color:#10b981; font-size:24px"></i>
            </div>
            <div class="card" style="grid-row: span 2; display:flex; flex-direction:column; align-items:center">
                <h4 style="margin:0 0 20px 0">نسبة الإشغال</h4>
                <div style="width:200px; height:200px">
                    <canvas id="occupancyChart"></canvas>
                </div>
                <div style="margin-top:15px; font-weight:bold; color:var(--primary)">
                    <?= ($rented + $avail) > 0 ? round(($rented / ($rented + $avail)) * 100) : 0 ?>% مؤجر
                </div>
            </div>
            <div class="card">
                <div style="color:#64748b">عدد الوحدات</div>
                <div class="val"><?= $rented + $avail ?></div>
            </div>
        </div>
        
        <script>
            // تفعيل الرسم البياني
            new Chart(document.getElementById('occupancyChart'), {
                type: 'doughnut',
                data: {
                    labels: ['مؤجر', 'شاغر'],
                    datasets: [{ data: [<?= $rented ?>, <?= $avail ?>], backgroundColor: ['#4f46e5', '#e2e8f0'], borderWidth: 0 }]
                },
                options: { cutout: '75%', plugins: { legend: { display: false } } }
            });
        </script>
        <?php endif; ?>

        <?php if($p == 'units'): ?>
        <div class="header"><h2>إدارة الوحدات</h2><button onclick="openModal('addUnitModal')" class="btn-main">إضافة وحدة</button></div>
        <div class="card" style="padding:0; overflow:hidden">
            <table>
                <thead><tr><th>صورة</th><th>الوحدة</th><th>العداد</th><th>السعر</th><th>الحالة</th></tr></thead>
                <tbody>
                    <?php $q = $pdo->query("SELECT * FROM units"); while($r = $q->fetch()): ?>
                    <tr>
                        <td>
                            <?php if($r['photo_url']): ?>
                                <img src="<?= $r['photo_url'] ?>" style="width:40px; height:40px; border-radius:8px; object-fit:cover;">
                            <?php else: ?><i class="fa-solid fa-image" style="color:#ccc; font-size:20px"></i><?php endif; ?>
                        </td>
                        <td><?= $r['unit_name'] ?> (<?= $r['unit_number'] ?>)</td>
                        <td><?= $r['meter_number'] ?></td>
                        <td><?= number_format($r['yearly_price']) ?></td>
                        <td><span class="badge <?= $r['status']=='rented'?'bg-red':'bg-green' ?>"><?= $r['status']=='rented'?'مؤجر':'متاح' ?></span></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <?php if($p == 'tenants'): ?>
        <div class="header"><h2>المستأجرين</h2><button onclick="openModal('addTenantModal')" class="btn-main">مستأجر جديد</button></div>
        <div class="card" style="padding:0; overflow:hidden">
            <table>
                <thead><tr><th>الاسم</th><th>النشاط</th><th>الهوية / السجل</th><th>تواصل</th></tr></thead>
                <tbody>
                    <?php $q = $pdo->query("SELECT * FROM tenants"); while($r = $q->fetch()): ?>
                    <tr>
                        <td><b><?= $r['full_name'] ?></b></td>
                        <td><?= $r['activity_type'] ?></td>
                        <td><?= $r['id_number'] ?: $r['cr_number'] ?></td>
                        <td>
                            <a href="https://wa.me/966<?= substr($r['phone'],1) ?>" target="_blank" style="color:#25D366; text-decoration:none; font-weight:bold; background:#ecfdf5; padding:5px 10px; border-radius:8px">
                                <i class="fa-brands fa-whatsapp"></i> واتساب
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <?php if($p == 'contracts'): ?>
        <div class="header"><h2>العقود</h2><button onclick="openModal('addContractModal')" class="btn-main">عقد جديد</button></div>
        <div class="card" style="padding:0; overflow:hidden">
            <table>
                <thead><tr><th>رقم العقد</th><th>المستأجر</th><th>الوحدة</th><th>النهاية</th><th>القيمة</th></tr></thead>
                <tbody>
                    <?php $q = $pdo->query("SELECT c.*, t.full_name, u.unit_name FROM contracts c JOIN tenants t ON c.tenant_id=t.id JOIN units u ON c.unit_id=u.id"); while($r = $q->fetch()): ?>
                    <tr>
                        <td>#<?= $r['id'] ?></td>
                        <td><?= $r['full_name'] ?></td>
                        <td><?= $r['unit_name'] ?></td>
                        <td><?= $r['end_date'] ?></td>
                        <td><?= number_format($r['total_amount']) ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

    </div>

    <div id="addUnitModal" class="modal"><div class="modal-content">
        <h3>🏠 وحدة جديدة</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="add_unit" value="1">
            <input type="text" name="pid" value="1" hidden>
            <div style="display:flex; gap:10px">
                <input type="text" name="name" class="inp" placeholder="اسم الوحدة" required>
                <input type="text" name="num" class="inp" placeholder="رقم الوحدة">
            </div>
            <div style="display:flex; gap:10px">
                <input type="text" name="floor" class="inp" placeholder="الدور">
                <input type="text" name="meter" class="inp" placeholder="رقم العداد">
            </div>
            <input type="number" name="price" class="inp" placeholder="السعر السنوي" required>
            <label style="display:block; margin-bottom:5px; font-weight:bold">صورة الوحدة:</label>
            <input type="file" name="photo" class="inp" accept="image/*">
            <button class="btn-main" style="width:100%; justify-content:center">حفظ</button>
            <button type="button" onclick="closeModal('addUnitModal')" style="width:100%; border:none; background:none; color:red; margin-top:10px; cursor:pointer">إلغاء</button>
        </form>
    </div></div>

    <div id="addTenantModal" class="modal"><div class="modal-content">
        <h3>👤 مستأجر جديد</h3>
        <form method="POST">
            <input type="hidden" name="add_tenant" value="1">
            <input type="text" name="name" class="inp" placeholder="الاسم الكامل" required>
            <input type="text" name="phone" class="inp" placeholder="الجوال (05xxxxxxxx)" required>
            <div style="display:flex; gap:10px">
                <input type="text" name="nid" class="inp" placeholder="رقم الهوية">
                <input type="text" name="cr" class="inp" placeholder="السجل التجاري">
            </div>
            <input type="text" name="activity" class="inp" placeholder="نوع النشاط (مطعم، مكتب...)">
            <input type="email" name="email" class="inp" placeholder="البريد الإلكتروني">
            <button class="btn-main" style="width:100%; justify-content:center">حفظ</button>
            <button type="button" onclick="closeModal('addTenantModal')" style="width:100%; border:none; background:none; color:red; margin-top:10px; cursor:pointer">إلغاء</button>
        </form>
    </div></div>

    <div id="addContractModal" class="modal"><div class="modal-content">
        <h3>📝 عقد جديد</h3>
        <form method="POST">
            <input type="hidden" name="add_contract" value="1">
            <label>المستأجر</label>
            <select name="tid" class="inp"><?php $q=$pdo->query("SELECT * FROM tenants"); while($r=$q->fetch()) echo "<option value='{$r['id']}'>{$r['full_name']}</option>"; ?></select>
            <label>الوحدة</label>
            <select name="uid" class="inp"><?php $q=$pdo->query("SELECT * FROM units WHERE status='available'"); while($r=$q->fetch()) echo "<option value='{$r['id']}'>{$r['unit_name']}</option>"; ?></select>
            <div style="display:flex; gap:10px">
                <div style="flex:1"><label>البداية</label><input type="date" name="start" class="inp" required></div>
                <div style="flex:1"><label>النهاية</label><input type="date" name="end" class="inp" required></div>
            </div>
            <input type="number" name="total" class="inp" placeholder="القيمة الإجمالية" required>
            <select name="cycle" class="inp"><option value="yearly">سنوي</option><option value="monthly">شهري</option></select>
            <button class="btn-main" style="width:100%; justify-content:center">إصدار</button>
            <button type="button" onclick="closeModal('addContractModal')" style="width:100%; border:none; background:none; color:red; margin-top:10px; cursor:pointer">إلغاء</button>
        </form>
    </div></div>

    <script>
        function openModal(id) { document.getElementById(id).style.display = 'flex'; }
        function closeModal(id) { document.getElementById(id).style.display = 'none'; }
    </script>
</body>
</html>
