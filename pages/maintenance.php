<?php
// 1. كود الحفظ (طلب جديد)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_maint'])) {
    
    // جلب معرف العقار تلقائياً
    $u = $pdo->query("SELECT property_id FROM units WHERE id=".$_POST['uid'])->fetch();
    $pid = $u ? $u['property_id'] : 0;
    
    $stmt = $pdo->prepare("INSERT INTO maintenance (property_id, unit_id, vendor_id, description, cost, request_date, status) VALUES (?,?,?,?,?, CURDATE(), 'pending')");
    $stmt->execute([$pid, $_POST['uid'], $_POST['vid'], $_POST['desc'], $_POST['cost']]);
    
    echo "<script>window.location='index.php?p=maintenance';</script>";
}
?>

<style>
    .modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:9999; justify-content:center; align-items:center; }
    .modal-content { background:#1a1a1a; padding:25px; border-radius:15px; width:500px; max-width:90%; border:1px solid #444; }
    .close-icon { float:left; cursor:pointer; font-size:20px; color:#aaa; }
</style>

<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px">
        <h3>🛠️ طلبات الصيانة</h3>
        <button onclick="openMaintModal()" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> تسجيل طلب جديد
        </button>
    </div>
    
    <?php 
    $reqs = $pdo->query("SELECT m.*, u.unit_name, v.name as vname FROM maintenance m JOIN units u ON m.unit_id=u.id LEFT JOIN vendors v ON m.vendor_id=v.id ORDER BY m.id DESC");
    if($reqs->rowCount() == 0):
    ?>
        <div style="text-align:center; padding:40px; color:#666">
            <p>لا توجد طلبات صيانة حالياً</p>
        </div>
    <?php else: ?>
        <table>
            <thead><tr><th>الوحدة</th><th>المشكلة</th><th>المقاول</th><th>التكلفة</th><th>الحالة</th></tr></thead>
            <tbody>
                <?php while($r = $reqs->fetch()): ?>
                <tr>
                    <td style="font-weight:bold"><?= $r['unit_name'] ?></td>
                    <td><?= $r['description'] ?></td>
                    <td><?= $r['vname'] ?: '-' ?></td>
                    <td><?= number_format($r['cost']) ?></td>
                    <td><span class="badge" style="background:<?= $r['status']=='pending'?'#f59e0b':'#10b981' ?>"><?= $r['status'] ?></span></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div id="maintModal" class="modal">
    <div class="modal-content">
        <div class="close-icon" onclick="document.getElementById('maintModal').style.display='none'"><i class="fa-solid fa-xmark"></i></div>
        <div class="modal-header"><div class="modal-title">تسجيل طلب صيانة</div></div>
        
        <form method="POST">
            <input type="hidden" name="save_maint" value="1">
            
            <div class="inp-group">
                <label class="inp-label">الوحدة المتضررة</label>
                <select name="uid" class="inp" required>
                    <option value="">-- اختر الوحدة --</option>
                    <?php $us=$pdo->query("SELECT * FROM units"); while($u=$us->fetch()) echo "<option value='{$u['id']}'>{$u['unit_name']}</option>"; ?>
                </select>
            </div>
            
            <div class="inp-group">
                <label class="inp-label">المقاول (اختياري)</label>
                <select name="vid" class="inp">
                    <option value="0">-- اختر المقاول --</option>
                    <?php $vs=$pdo->query("SELECT * FROM vendors"); while($v=$vs->fetch()) echo "<option value='{$v['id']}'>{$v['name']}</option>"; ?>
                </select>
            </div>

            <div class="inp-group">
                <label class="inp-label">وصف المشكلة</label>
                <textarea name="desc" class="inp" style="height:80px" required></textarea>
            </div>

            <div class="inp-group">
                <label class="inp-label">التكلفة التقديرية</label>
                <input type="number" name="cost" class="inp">
            </div>

            <button class="btn btn-primary" style="width:100%; justify-content:center; margin-top:15px">
                <i class="fa-solid fa-check"></i> حفظ الطلب
            </button>
        </form>
    </div>
</div>

<script>
    function openMaintModal() {
        document.getElementById('maintModal').style.display='flex';
    }
</script>
