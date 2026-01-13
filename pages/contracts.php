<?php
// معالجة إضافة عقد جديد
if (isset($_POST['add_contract'])) {
    check_csrf();
    try {
        $pdo->beginTransaction();
        
        // 1. إدراج العقد
        $sql = "INSERT INTO contracts (tenant_id, unit_id, start_date, end_date, total_amount, remaining_amount, payment_cycle, status) VALUES (?,?,?,?,?,?,?, 'active')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$_POST['tid'], $_POST['uid'], $_POST['start'], $_POST['end'], $_POST['total'], $_POST['total'], $_POST['cycle']]);
        $cid = $pdo->lastInsertId();

        // 2. تحديث حالة الوحدة
        $pdo->prepare("UPDATE units SET status='rented' WHERE id=?")->execute([$_POST['uid']]);

        // 3. توليد الدفعات
        $start = new DateTime($_POST['start']);
        $end = new DateTime($_POST['end']);
        $amount = $_POST['total'];
        $cycle = $_POST['cycle'];
        
        // تقسيم المبلغ
        $div = ($cycle=='monthly') ? 12 : ($cycle=='quarterly' ? 4 : ($cycle=='biannual' ? 2 : 1));
        $installment_amount = $amount / $div; // هذا تقريبي، يمكن تحسينه للدقة
        
        // تحديد الفاصل الزمني
        $intervalStr = ($cycle=='monthly') ? 'P1M' : ($cycle=='quarterly' ? 'P3M' : ($cycle=='biannual' ? 'P6M' : 'P1Y'));
        
        $curr = clone $start;
        $count = 1;
        
        while($curr < $end) {
            $pdo->prepare("INSERT INTO payments (contract_id, title, amount, due_date, status) VALUES (?, ?, ?, ?, 'pending')")
                ->execute([$cid, "دفعة #$count", $installment_amount, $curr->format('Y-m-d')]);
            $curr->add(new DateInterval($intervalStr));
            $count++;
            // حماية من الحلقة اللانهائية في حال الخطأ
            if($count > 50) break;
        }

        $pdo->commit();
        echo "<script>window.location='index.php?p=contract_view&id=$cid';</script>";
        
    } catch(Exception $e) {
        $pdo->rollBack();
        echo "<script>alert('خطأ أثناء إنشاء العقد: " . $e->getMessage() . "');</script>";
    }
}
?>

<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px">
        <h3>📄 إدارة العقود</h3>
        <button onclick="document.getElementById('contractModal').style.display='flex'" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> إنشاء عقد جديد
        </button>
    </div>
    
    <table>
        <thead><tr><th>رقم العقد</th><th>المستأجر</th><th>الوحدة</th><th>القيمة</th><th>الحالة</th><th>عرض</th></tr></thead>
        <tbody>
            <?php 
            $conts = $pdo->query("SELECT c.*, t.name as tname, u.unit_name FROM contracts c JOIN tenants t ON c.tenant_id=t.id JOIN units u ON c.unit_id=u.id ORDER BY id DESC");
            while($r = $conts->fetch()): ?>
            <tr>
                <td>#<?= $r['id'] ?></td>
                <td style="font-weight:bold"><?= $r['tname'] ?></td>
                <td><?= $r['unit_name'] ?></td>
                <td><?= number_format($r['total_amount']) ?></td>
                <td><span class="badge" style="background:rgba(16,185,129,0.2); color:#6ee7b7">نشط</span></td>
                <td><a href="index.php?p=contract_view&id=<?= $r['id'] ?>" class="btn btn-dark" style="padding:5px 15px; font-size:12px">تفاصيل</a></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<div id="contractModal" class="modal">
    <div class="modal-content">
        <div class="close-icon" onclick="document.getElementById('contractModal').style.display='none'"><i class="fa-solid fa-xmark"></i></div>
        <div class="modal-header"><div class="modal-title">إنشاء عقد إيجار جديد</div></div>
        
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="add_contract" value="1">
            
            <div class="inp-grid">
                <div>
                    <label class="inp-label">المستأجر</label>
                    <select name="tid" class="inp" required>
                        <option value="">-- اختر --</option>
                        <?php $ts=$pdo->query("SELECT * FROM tenants"); while($t=$ts->fetch()) echo "<option value='{$t['id']}'>{$t['name']}</option>"; ?>
                    </select>
                </div>
                <div>
                    <label class="inp-label">الوحدة (الشاغرة فقط)</label>
                    <select name="uid" class="inp" required>
                        <option value="">-- اختر --</option>
                        <?php $us=$pdo->query("SELECT * FROM units WHERE status='available'"); while($u=$us->fetch()) echo "<option value='{$u['id']}'>{$u['unit_name']}</option>"; ?>
                    </select>
                </div>
            </div>

            <div class="inp-grid">
                <div><label class="inp-label">تاريخ البداية</label><input type="date" name="start" class="inp" required></div>
                <div><label class="inp-label">تاريخ النهاية</label><input type="date" name="end" class="inp" required></div>
            </div>

            <div class="inp-grid">
                <div><label class="inp-label">إجمالي قيمة العقد</label><input type="number" name="total" class="inp" placeholder="0.00" required></div>
                <div>
                    <label class="inp-label">دورة السداد</label>
                    <select name="cycle" class="inp">
                        <option value="monthly">شهري (12 دفعة)</option>
                        <option value="quarterly">ربع سنوي (4 دفعات)</option>
                        <option value="biannual">نصف سنوي (دفعتين)</option>
                        <option value="yearly">سنوي (دفعة واحدة)</option>
                    </select>
                </div>
            </div>

            <button class="btn btn-primary" style="width:100%; justify-content:center; margin-top:10px">
                <i class="fa-solid fa-file-signature"></i> حفظ وإنشاء الدفعات
            </button>
        </form>
    </div>
</div>
