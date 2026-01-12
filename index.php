<?php
require 'db.php';

// === معالجة الطلبات (Backend) ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf(); // تفعيل الحماية

    // 1. إضافة وحدة
    if (isset($_POST['add_unit'])) {
        $photo = ''; // يمكنك إضافة كود رفع الصور هنا
        $pdo->prepare("INSERT INTO units (unit_name, unit_number, floor_number, yearly_price, meter_number, photo_url) VALUES (?,?,?,?,?,?)")
            ->execute([$_POST['name'], $_POST['num'], $_POST['floor'], $_POST['price'], $_POST['meter'], $photo]);
        header("Location: ?p=units"); exit;
    }

    // 2. إضافة مستأجر
    if (isset($_POST['add_tenant'])) {
        $pdo->prepare("INSERT INTO tenants (full_name, phone, id_number, email) VALUES (?,?,?,?)")
            ->execute([$_POST['name'], $_POST['phone'], $_POST['nid'], $_POST['email']]);
        header("Location: ?p=tenants"); exit;
    }

    // 3. إضافة عقد
    if (isset($_POST['add_contract'])) {
        $pdo->prepare("INSERT INTO contracts (tenant_id, unit_id, start_date, end_date, total_amount, payment_cycle) VALUES (?,?,?,?,?,?)")
            ->execute([$_POST['tid'], $_POST['uid'], $_POST['start'], $_POST['end'], $_POST['total'], $_POST['cycle']]);
        $pdo->prepare("UPDATE units SET status='rented' WHERE id=?")->execute([$_POST['uid']]);
        header("Location: ?p=contracts"); exit;
    }

    // 4. تسجيل دفعة (وإصدار فاتورة)
    if (isset($_POST['add_payment'])) {
        $uuid = uniqid('INV-'); // رقم فاتورة فريد
        $pdo->prepare("INSERT INTO payments (contract_id, amount, payment_date, payment_method, note, uuid) VALUES (?,?,?,?,?,?)")
            ->execute([$_POST['cid'], $_POST['amount'], $_POST['date'], $_POST['method'], $_POST['note'], $uuid]);
        header("Location: invoice.php?uuid=" . $uuid); exit; // توجيه للطباعة فوراً
    }

    // 5. طلب صيانة
    if (isset($_POST['add_ticket'])) {
        $pdo->prepare("INSERT INTO maintenance_tickets (unit_id, description) VALUES (?,?)")
            ->execute([$_POST['uid'], $_POST['desc']]);
        header("Location: ?p=maintenance"); exit;
    }

    // 6. تحديث صيانة
    if (isset($_POST['update_ticket'])) {
        $pdo->prepare("UPDATE maintenance_tickets SET status=?, cost=? WHERE id=?")
            ->execute([$_POST['status'], $_POST['cost'], $_POST['tid']]);
        header("Location: ?p=maintenance"); exit;
    }
}

$p = $_GET['p'] ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نظام إدارة العقارات المتكامل</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #2563eb; --dark: #1e293b; --bg: #f3f4f6; --success: #16a34a; --warning: #d97706; --danger: #dc2626; }
        body { font-family: 'Tajawal'; background: var(--bg); margin: 0; display: flex; height: 100vh; overflow: hidden; }
        
        /* Sidebar */
        .sidebar { width: 260px; background: var(--dark); color: white; display: flex; flex-direction: column; padding: 20px; }
        .brand { font-size: 20px; font-weight: 800; margin-bottom: 40px; color: #60a5fa; display: flex; align-items: center; gap: 10px; }
        .nav-item { display: flex; align-items: center; gap: 12px; padding: 12px; color: #cbd5e1; text-decoration: none; border-radius: 8px; margin-bottom: 5px; transition: 0.3s; }
        .nav-item:hover, .nav-item.active { background: var(--primary); color: white; }

        /* Main Content */
        .main { flex: 1; padding: 30px; overflow-y: auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .btn { background: var(--primary); color: white; padding: 10px 20px; border-radius: 8px; border: none; cursor: pointer; text-decoration: none; font-weight: bold; }
        
        /* Components */
        .card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 30px; }
        table { width: 100%; border-collapse: separate; border-spacing: 0 8px; }
        th { text-align: right; padding: 15px; color: #64748b; font-size: 13px; }
        td { background: white; padding: 15px; border-top: 1px solid #f1f5f9; border-bottom: 1px solid #f1f5f9; }
        td:first-child { border-radius: 0 8px 8px 0; border-right: 1px solid #f1f5f9; }
        td:last-child { border-radius: 8px 0 0 8px; border-left: 1px solid #f1f5f9; }
        
        /* Badges */
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: bold; }
        .bg-green { background: #dcfce7; color: #166534; }
        .bg-red { background: #fee2e2; color: #991b1b; }
        .bg-yellow { background: #fef3c7; color: #92400e; }

        /* Modal */
        .modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); backdrop-filter: blur(2px); z-index:100; justify-content:center; align-items:center; }
        .modal-content { background:white; padding:30px; border-radius:16px; width:450px; max-width:90%; }
        .inp, select, textarea { width:100%; padding:12px; margin:8px 0 16px; border:1px solid #e2e8f0; border-radius:8px; box-sizing:border-box; font-family:inherit; }

        @media (max-width: 768px) { body { flex-direction: column; } .sidebar { width: 100%; height: auto; flex-direction: row; overflow-x: auto; padding: 10px; } .brand { display:none; } }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="brand"><i class="fa-solid fa-building-columns"></i> إدارة الأملاك</div>
        <a href="?p=dashboard" class="nav-item <?= $p=='dashboard'?'active':'' ?>"><i class="fa-solid fa-chart-line"></i> الرئيسية</a>
        <a href="?p=units" class="nav-item <?= $p=='units'?'active':'' ?>"><i class="fa-solid fa-door-open"></i> الوحدات</a>
        <a href="?p=contracts" class="nav-item <?= $p=='contracts'?'active':'' ?>"><i class="fa-solid fa-file-contract"></i> العقود والمالية</a>
        <a href="?p=tenants" class="nav-item <?= $p=='tenants'?'active':'' ?>"><i class="fa-solid fa-users"></i> المستأجرين</a>
        <a href="?p=maintenance" class="nav-item <?= $p=='maintenance'?'active':'' ?>"><i class="fa-solid fa-screwdriver-wrench"></i> الصيانة</a>
        <a href="?p=reports" class="nav-item <?= $p=='reports'?'active':'' ?>"><i class="fa-solid fa-print"></i> التقارير</a>
    </div>

    <div class="main">

        <?php if($p == 'dashboard'): ?>
        <?php 
            $revenue = $pdo->query("SELECT SUM(amount) FROM payments")->fetchColumn() ?: 0;
            $tickets = $pdo->query("SELECT count(*) FROM maintenance_tickets WHERE status='pending'")->fetchColumn();
            $rented = $pdo->query("SELECT count(*) FROM units WHERE status='rented'")->fetchColumn();
            $total_units = $pdo->query("SELECT count(*) FROM units")->fetchColumn() ?: 1;
        ?>
        <div class="header"><h2>لوحة القيادة</h2></div>
        <div class="stats-grid">
            <div class="card">
                <div style="color:#64748b">الإيرادات المحصلة</div>
                <div style="font-size:24px; font-weight:bold; color:var(--success)"><?= number_format($revenue) ?> ريال</div>
            </div>
            <div class="card">
                <div style="color:#64748b">طلبات صيانة معلقة</div>
                <div style="font-size:24px; font-weight:bold; color:var(--warning)"><?= $tickets ?> طلبات</div>
            </div>
            <div class="card">
                <div style="color:#64748b">نسبة الإشغال</div>
                <div style="font-size:24px; font-weight:bold; color:var(--primary)"><?= round(($rented/$total_units)*100) ?>%</div>
            </div>
        </div>
        <div class="card">
            <h3>أحدث العمليات المالية</h3>
            <table>
                <thead><tr><th>الفاتورة</th><th>التاريخ</th><th>المبلغ</th><th>البيان</th></tr></thead>
                <tbody>
                    <?php $q=$pdo->query("SELECT * FROM payments ORDER BY id DESC LIMIT 5"); while($r=$q->fetch()): ?>
                    <tr>
                        <td>#<?= $r['uuid'] ?></td>
                        <td><?= $r['payment_date'] ?></td>
                        <td style="color:green; font-weight:bold"><?= number_format($r['amount']) ?></td>
                        <td><?= secure($r['note']) ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <?php if($p == 'units'): ?>
        <div class="header"><h2>الوحدات</h2><button onclick="openModal('addUnitModal')" class="btn">إضافة وحدة</button></div>
        <div class="card" style="background:none; box-shadow:none; padding:0">
            <table>
                <thead><tr><th>الوحدة</th><th>رقم العداد</th><th>السعر</th><th>الحالة</th></tr></thead>
                <tbody>
                    <?php $q=$pdo->query("SELECT * FROM units"); while($r=$q->fetch()): ?>
                    <tr>
                        <td><b><?= secure($r['unit_name']) ?></b><br><small><?= secure($r['unit_number']) ?></small></td>
                        <td><?= secure($r['meter_number']) ?></td>
                        <td><?= number_format($r['yearly_price']) ?></td>
                        <td><span class="badge <?= $r['status']=='rented'?'bg-red':'bg-green' ?>"><?= $r['status'] ?></span></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <?php if($p == 'contracts'): ?>
        <div class="header"><h2>العقود</h2><button onclick="openModal('addContractModal')" class="btn">عقد جديد</button></div>
        <?php 
        $q = $pdo->query("SELECT c.*, t.full_name, u.unit_name FROM contracts c JOIN tenants t ON c.tenant_id=t.id JOIN units u ON c.unit_id=u.id ORDER BY c.id DESC");
        while($r = $q->fetch()): 
            $paid = $pdo->prepare("SELECT SUM(amount) FROM payments WHERE contract_id=?"); $paid->execute([$r['id']]); $paid = $paid->fetchColumn() ?: 0;
            $remain = $r['total_amount'] - $paid;
        ?>
        <div class="card">
            <div style="display:flex; justify-content:space-between; align-items:center">
                <div>
                    <h3><?= secure($r['full_name']) ?> <small style="color:#64748b">(<?= secure($r['unit_name']) ?>)</small></h3>
                    <div style="font-size:13px; color:#64748b">ينتهي في: <?= $r['end_date'] ?></div>
                </div>
                <div style="text-align:left">
                    <div style="font-weight:bold; color:var(--primary)"><?= number_format($r['total_amount']) ?> ريال</div>
                    <div style="font-size:12px; color:<?= $remain>0?'red':'green' ?>">المتبقي: <?= number_format($remain) ?></div>
                </div>
            </div>
            <hr style="border:0; border-top:1px solid #eee; margin:15px 0">
            <button onclick="openPaymentModal(<?= $r['id'] ?>, '<?= secure($r['full_name']) ?>')" class="btn" style="background:var(--dark); font-size:12px">تسجيل دفعة</button>
        </div>
        <?php endwhile; ?>
        <?php endif; ?>

        <?php if($p == 'maintenance'): ?>
        <div class="header"><h2>الصيانة</h2><button onclick="openModal('addTicketModal')" class="btn">طلب صيانة</button></div>
        <div class="card">
            <table>
                <thead><tr><th>الوحدة</th><th>المشكلة</th><th>الحالة</th><th>التكلفة</th><th>تحكم</th></tr></thead>
                <tbody>
                    <?php $q=$pdo->query("SELECT m.*, u.unit_name FROM maintenance_tickets m JOIN units u ON m.unit_id=u.id ORDER BY m.id DESC"); while($r=$q->fetch()): ?>
                    <tr>
                        <td><?= secure($r['unit_name']) ?></td>
                        <td><?= secure($r['description']) ?></td>
                        <td><span class="badge <?= $r['status']=='completed'?'bg-green':'bg-yellow' ?>"><?= $r['status'] ?></span></td>
                        <td><?= $r['cost'] ?></td>
                        <td>
                            <?php if($r['status']!='completed'): ?>
                            <button onclick="editTicket(<?= $r['id'] ?>)" class="btn" style="padding:5px 10px; font-size:11px">تحديث</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <?php if($p == 'reports'): ?>
        <div class="header"><h2>التقارير وسجل الفواتير</h2></div>
        <div class="card">
            <table>
                <thead><tr><th>رقم السند</th><th>العقد</th><th>التاريخ</th><th>المبلغ</th><th>طباعة</th></tr></thead>
                <tbody>
                    <?php $q=$pdo->query("SELECT p.*, c.id as cid FROM payments p JOIN contracts c ON p.contract_id=c.id ORDER BY p.id DESC LIMIT 50"); while($r=$q->fetch()): ?>
                    <tr>
                        <td><?= $r['uuid'] ?></td>
                        <td>#<?= $r['cid'] ?></td>
                        <td><?= $r['payment_date'] ?></td>
                        <td style="color:green"><?= number_format($r['amount']) ?></td>
                        <td><a href="invoice.php?uuid=<?= $r['uuid'] ?>" target="_blank" class="btn" style="background:#475569; padding:5px 10px"><i class="fa fa-print"></i></a></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <?php if($p == 'tenants'): ?>
        <div class="header"><h2>المستأجرين</h2><button onclick="openModal('addTenantModal')" class="btn">مستأجر جديد</button></div>
        <div class="card">
            <table>
                <thead><tr><th>الاسم</th><th>الجوال</th><th>الهوية</th></tr></thead>
                <tbody>
                    <?php $q=$pdo->query("SELECT * FROM tenants"); while($r=$q->fetch()): ?>
                    <tr>
                        <td><?= secure($r['full_name']) ?></td>
                        <td><?= secure($r['phone']) ?></td>
                        <td><?= secure($r['id_number']) ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

    </div>

    <div id="addUnitModal" class="modal"><div class="modal-content">
        <h3>🏠 وحدة جديدة</h3>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= generate_csrf() ?>">
            <input type="hidden" name="add_unit" value="1">
            <input type="text" name="name" class="inp" placeholder="اسم الوحدة" required>
            <div style="display:flex; gap:10px">
                <input type="text" name="num" class="inp" placeholder="رقم الوحدة">
                <input type="text" name="floor" class="inp" placeholder="الدور">
            </div>
            <input type="text" name="meter" class="inp" placeholder="رقم العداد">
            <input type="number" name="price" class="inp" placeholder="السعر السنوي" required>
            <button class="btn" style="width:100%">حفظ</button>
            <div onclick="document.getElementById('addUnitModal').style.display='none'" style="text-align:center; margin-top:10px; cursor:pointer; color:red">إلغاء</div>
        </form>
    </div></div>

    <div id="addTenantModal" class="modal"><div class="modal-content">
        <h3>👤 مستأجر جديد</h3>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= generate_csrf() ?>">
            <input type="hidden" name="add_tenant" value="1">
            <input type="text" name="name" class="inp" placeholder="الاسم الرباعي" required>
            <input type="text" name="phone" class="inp" placeholder="رقم الجوال" required>
            <input type="text" name="nid" class="inp" placeholder="رقم الهوية">
            <input type="email" name="email" class="inp" placeholder="البريد الإلكتروني">
            <button class="btn" style="width:100%">حفظ</button>
            <div onclick="document.getElementById('addTenantModal').style.display='none'" style="text-align:center; margin-top:10px; cursor:pointer; color:red">إلغاء</div>
        </form>
    </div></div>

    <div id="addContractModal" class="modal"><div class="modal-content">
        <h3>📝 عقد جديد</h3>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= generate_csrf() ?>">
            <input type="hidden" name="add_contract" value="1">
            <select name="tid" required>
                <option value="">اختر المستأجر...</option>
                <?php $t=$pdo->query("SELECT * FROM tenants"); while($r=$t->fetch()) echo "<option value='{$r['id']}'>{$r['full_name']}</option>"; ?>
            </select>
            <select name="uid" required>
                <option value="">اختر الوحدة (المتاحة فقط)...</option>
                <?php $u=$pdo->query("SELECT * FROM units WHERE status='available'"); while($r=$u->fetch()) echo "<option value='{$r['id']}'>{$r['unit_name']} - {$r['yearly_price']} ريال</option>"; ?>
            </select>
            <div style="display:flex; gap:10px">
                <input type="date" name="start" class="inp" required>
                <input type="date" name="end" class="inp" required>
            </div>
            <input type="number" name="total" class="inp" placeholder="قيمة العقد الإجمالية" required>
            <button class="btn" style="width:100%">إنشاء العقد</button>
            <div onclick="document.getElementById('addContractModal').style.display='none'" style="text-align:center; margin-top:10px; cursor:pointer; color:red">إلغاء</div>
        </form>
    </div></div>

    <div id="paymentModal" class="modal"><div class="modal-content">
        <h3>💰 تسجيل دفعة</h3>
        <p id="payContractName" style="color:#64748b"></p>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= generate_csrf() ?>">
            <input type="hidden" name="add_payment" value="1">
            <input type="hidden" name="cid" id="payContractId">
            <input type="number" name="amount" class="inp" placeholder="المبلغ المستلم" required>
            <input type="date" name="date" class="inp" value="<?= date('Y-m-d') ?>">
            <select name="method" required>
                <option value="cash">نقدي (Cash)</option>
                <option value="transfer">تحويل بنكي</option>
            </select>
            <input type="text" name="note" class="inp" placeholder="ملاحظات">
            <button class="btn" style="width:100%">حفظ وطباعة السند</button>
            <div onclick="document.getElementById('paymentModal').style.display='none'" style="text-align:center; margin-top:10px; cursor:pointer; color:red">إلغاء</div>
        </form>
    </div></div>

    <div id="addTicketModal" class="modal"><div class="modal-content">
        <h3>🔧 طلب صيانة</h3>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= generate_csrf() ?>">
            <input type="hidden" name="add_ticket" value="1">
            <select name="uid" required>
                <?php $u=$pdo->query("SELECT * FROM units"); while($r=$u->fetch()) echo "<option value='{$r['id']}'>{$r['unit_name']}</option>"; ?>
            </select>
            <textarea name="desc" class="inp" rows="3" placeholder="وصف العطل..." required></textarea>
            <button class="btn" style="width:100%">فتح تذكرة</button>
            <div onclick="document.getElementById('addTicketModal').style.display='none'" style="text-align:center; margin-top:10px; cursor:pointer; color:red">إلغاء</div>
        </form>
    </div></div>

    <div id="updateTicketModal" class="modal"><div class="modal-content">
        <h3>تحديث حالة الطلب</h3>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= generate_csrf() ?>">
            <input type="hidden" name="update_ticket" value="1">
            <input type="hidden" name="tid" id="updateTid">
            <select name="status">
                <option value="in_progress">جاري التنفيذ</option>
                <option value="completed">مكتمل</option>
            </select>
            <input type="number" name="cost" class="inp" placeholder="التكلفة النهائية">
            <button class="btn" style="width:100%">حفظ التحديث</button>
            <div onclick="document.getElementById('updateTicketModal').style.display='none'" style="text-align:center; margin-top:10px; cursor:pointer; color:red">إلغاء</div>
        </form>
    </div></div>

    <script>
        function openModal(id) { document.getElementById(id).style.display = 'flex'; }
        
        function openPaymentModal(id, name) {
            document.getElementById('payContractId').value = id;
            document.getElementById('payContractName').innerText = 'للعقد: ' + name;
            openModal('paymentModal');
        }

        function editTicket(id) {
            document.getElementById('updateTid').value = id;
            openModal('updateTicketModal');
        }
    </script>
</body>
</html>
