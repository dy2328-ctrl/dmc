<?php
if(isset($_POST['add_maint'])){
    check_csrf();
    $pdo->prepare("INSERT INTO maintenance (property_id, unit_id, vendor_id, description, cost, request_date, status) VALUES (?,?,?,?,?,CURRENT_DATE,'pending')")
        ->execute([$_POST['pid'], $_POST['uid'], $_POST['vid'], $_POST['desc'], $_POST['cost']]);
    echo "<script>window.location='index.php?p=maintenance';</script>";
}
?>

<div class="card">
    <div style="display:flex; justify-content:space-between; margin-bottom:20px">
        <h3>🛠️ إدارة الصيانة</h3>
        <button onclick="document.getElementById('maintModal').style.display='flex'" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> طلب صيانة جديد
        </button>
    </div>
    
    <table>
        <thead><tr><th>الوحدة</th><th>وصف العطل</th><th>المقاول</th><th>التكلفة</th><th>الحالة</th></tr></thead>
        <tbody>
            <?php 
            $maint = $pdo->query("SELECT m.*, u.unit_name, v.name as vname FROM maintenance m LEFT JOIN units u ON m.unit_id=u.id LEFT JOIN vendors v ON m.vendor_id=v.id ORDER BY id DESC");
            if($maint->rowCount() == 0) echo "<tr><td colspan='5' style='text-align:center'>لا توجد طلبات صيانة</td></tr>";
            while($r=$maint->fetch()): ?>
            <tr>
                <td><?= $r['unit_name'] ?></td>
                <td><?= $r['description'] ?></td>
                <td><?= $r['vname'] ?: '-' ?></td>
                <td style="color:#ef4444"><?= number_format($r['cost']) ?></td>
                <td><span style="background:#222; padding:4px 10px; border-radius:5px"><?= $r['status'] ?></span></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<div id="maintModal" class="modal">
    <div class="modal-content">
        <div class="close-icon" onclick="document.getElementById('maintModal').style.display='none'"><i class="fa-solid fa-xmark"></i></div>
        
        <div class="modal-header">
            <div class="modal-title">تسجيل طلب صيانة</div>
        </div>
        
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="add_maint" value="1">
            
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px">
                <div>
                    <label class="inp-label">العقار</label>
                    <select name="pid" class="inp">
                        <option value="">-- اختر العقار --</option>
                        <?php $ps=$pdo->query("SELECT * FROM properties"); while($p=$ps->fetch()) echo "<option value='{$p['id']}'>{$p['name']}</option>"; ?>
                    </select>
                </div>
                <div>
                    <label class="inp-label">الوحدة</label>
                    <select name="uid" class="inp">
                        <option value="">-- اختر الوحدة --</option>
                        <?php $us=$pdo->query("SELECT * FROM units"); while($u=$us->fetch()) echo "<option value='{$u['id']}'>{$u['unit_name']}</option>"; ?>
                    </select>
                </div>
            </div>

            <label class="inp-label">وصف المشكلة</label>
            <input type="text" name="desc" class="inp" placeholder="مثال: تسريب مياه في الحمام..." required>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px">
                <div>
                    <label class="inp-label">المقاول (اختياري)</label>
                    <select name="vid" class="inp">
                        <option value="">-- اختر --</option>
                        <?php $vs=$pdo->query("SELECT * FROM vendors"); while($v=$vs->fetch()) echo "<option value='{$v['id']}'>{$v['name']}</option>"; ?>
                    </select>
                </div>
                <div>
                    <label class="inp-label">التكلفة التقديرية</label>
                    <input type="number" name="cost" class="inp" placeholder="0.00">
                </div>
            </div>

            <button class="btn btn-primary" style="width:100%; justify-content:center; margin-top:10px">
                <i class="fa-solid fa-check"></i> حفظ الطلب
            </button>
        </form>
    </div>
</div>
