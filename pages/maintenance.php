<?php
// حفظ طلب صيانة جديد
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_maint'])) {
    $stmt = $pdo->prepare("INSERT INTO maintenance (property_id, unit_id, vendor_id, description, cost, request_date, status) VALUES (?,?,?,?,?, CURDATE(), 'pending')");
    // ملاحظة: نجلب property_id تلقائياً بناءً على الوحدة المختارة لضمان الدقة
    // أولاً نجلب معرف العقار من الوحدة
    $unitInfo = $pdo->query("SELECT property_id FROM units WHERE id=".$_POST['uid'])->fetch();
    $pid = $unitInfo ? $unitInfo['property_id'] : 0;
    
    $stmt->execute([$pid, $_POST['uid'], $_POST['vid'], $_POST['desc'], $_POST['cost']]);
    echo "<script>window.location='index.php?p=maintenance';</script>";
}
?>

<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px">
        <h3>🛠️ طلبات الصيانة</h3>
        <button onclick="openMaintModal()" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> تسجيل طلب صيانة
        </button>
    </div>
    
    <?php 
    // جلب طلبات الصيانة مع اسم الوحدة واسم المقاول
    $reqs = $pdo->query("SELECT m.*, u.unit_name, v.name as vname FROM maintenance m JOIN units u ON m.unit_id=u.id LEFT JOIN vendors v ON m.vendor_id=v.id ORDER BY m.id DESC");
    
    if($reqs->rowCount() == 0):
    ?>
        <div style="text-align:center; padding:40px; border:2px dashed #333; color:#777; border-radius:10px;">
            لا توجد طلبات صيانة مسجلة حالياً.
        </div>
    <?php else: ?>
        <table style="width:100%; border-collapse:collapse">
            <thead>
                <tr style="background:#222; text-align:right">
                    <th style="padding:10px">رقم الطلب</th>
                    <th style="padding:10px">الوحدة</th>
                    <th style="padding:10px">وصف العطل</th>
                    <th style="padding:10px">المقاول المكلف</th>
                    <th style="padding:10px">التكلفة</th>
                    <th style="padding:10px">الحالة</th>
                    <th style="padding:10px">التاريخ</th>
                </tr>
            </thead>
            <tbody>
                <?php while($r = $reqs->fetch()): ?>
                <tr style="border-bottom:1px solid #333">
                    <td style="padding:10px">#<?= $r['id'] ?></td>
                    <td style="padding:10px; font-weight:bold"><?= $r['unit_name'] ?></td>
                    <td style="padding:10px"><?= $r['description'] ?></td>
                    <td style="padding:10px; color:#aaa"><?= $r['vname'] ?: 'غير محدد' ?></td>
                    <td style="padding:10px; font-weight:bold"><?= number_format($r['cost']) ?></td>
                    <td style="padding:10px">
                        <?php if($r['status']=='pending'): ?>
                            <span class="badge" style="background:#f59e0b; color:black">قيد الانتظار</span>
                        <?php elseif($r['status']=='completed'): ?>
                            <span class="badge" style="background:#10b981">مكتمل</span>
                        <?php else: ?>
                            <span class="badge" style="background:#333"><?= $r['status'] ?></span>
                        <?php endif; ?>
                    </td>
                    <td style="padding:10px; font-size:12px"><?= $r['request_date'] ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div id="maintModal" class="modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:9999; justify-content:center; align-items:center;">
    <div class="modal-content" style="background:#1a1a1a; padding:25px; border-radius:15px; width:500px; position:relative;">
        
        <div style="display:flex; justify-content:space-between; margin-bottom:20px;">
            <h3 style="margin:0">تسجيل طلب صيانة</h3>
            <div style="cursor:pointer; font-size:20px;" onclick="document.getElementById('maintModal').style.display='none'">
                <i class="fa-solid fa-xmark"></i>
            </div>
        </div>
        
        <form method="POST">
            <input type="hidden" name="save_maint" value="1">
            
            <div style="margin-bottom:15px">
                <label class="inp-label">الوحدة المتضررة</label>
                <select name="uid" class="inp" required style="width:100%">
                    <option value="">-- اختر الوحدة --</option>
                    <?php 
                    $units = $pdo->query("SELECT * FROM units"); 
                    while($u = $units->fetch()) echo "<option value='{$u['id']}'>{$u['unit_name']} ({$u['type']})</option>"; 
                    ?>
                </select>
            </div>
            
            <div style="margin-bottom:15px">
                <label class="inp-label">المقاول (اختياري)</label>
                <select name="vid" class="inp" style="width:100%">
                    <option value="">-- اختر المقاول --</option>
                    <?php 
                    $vendors = $pdo->query("SELECT * FROM vendors"); 
                    while($v = $vendors->fetch()) echo "<option value='{$v['id']}'>{$v['name']} ({$v['service_type']})</option>"; 
                    ?>
                </select>
            </div>
            
            <div style="margin-bottom:15px">
                <label class="inp-label">وصف المشكلة</label>
                <textarea name="desc" class="inp" required style="width:100%; height:80px;"></textarea>
            </div>
            
            <div style="margin-bottom:20px">
                <label class="inp-label">التكلفة التقديرية (ريال)</label>
                <input type="number" name="cost" class="inp" placeholder="0.00" style="width:100%">
            </div>
            
            <button class="btn btn-primary" style="width:100%; justify-content:center; padding:12px">حفظ الطلب</button>
        </form>
    </div>
</div>

<script>
    function openMaintModal() {
        // هذه الدالة تضمن فتح النافذة
        var modal = document.getElementById('maintModal');
        if(modal) {
            modal.style.display = 'flex';
        } else {
            alert('خطأ: نافذة الصيانة غير موجودة في الصفحة');
        }
    }
</script>
