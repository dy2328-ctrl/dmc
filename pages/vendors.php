<?php
// معالجة الحذف
if (isset($_POST['delete_id'])) {
    $pdo->prepare("DELETE FROM vendors WHERE id=?")->execute([$_POST['delete_id']]);
    echo "<script>window.location='index.php?p=vendors';</script>";
}

// معالجة الحفظ (جديد / تعديل)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_vendor'])) {
    if (!empty($_POST['vid'])) {
        $stmt = $pdo->prepare("UPDATE vendors SET name=?, service_type=?, phone=? WHERE id=?");
        $stmt->execute([$_POST['name'], $_POST['type'], $_POST['phone'], $_POST['vid']]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO vendors (name, service_type, phone) VALUES (?,?,?)");
        $stmt->execute([$_POST['name'], $_POST['type'], $_POST['phone']]);
    }
    echo "<script>window.location='index.php?p=vendors';</script>";
}
?>

<style>
    /* تنسيق خاص للنافذة لضمان ظهورها بشكل جميل */
    .custom-modal {
        display: none; 
        position: fixed; 
        top: 0; left: 0; 
        width: 100%; height: 100%; 
        background: rgba(0,0,0,0.85); 
        z-index: 10000; 
        justify-content: center; 
        align-items: center;
        backdrop-filter: blur(5px); /* تأثير ضبابي جميل للخلفية */
    }
    .custom-modal-content {
        background: #1f1f1f; 
        padding: 30px; 
        border-radius: 15px; 
        width: 450px; 
        box-shadow: 0 10px 30px rgba(0,0,0,0.5); 
        border: 1px solid #333;
        animation: fadeIn 0.3s ease;
    }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }
</style>

<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px">
        <h3>👷 إدارة المقاولين</h3>
        <button onclick="openVendorModal()" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> إضافة مقاول
        </button>
    </div>
    
    <table style="width:100%; border-collapse:collapse">
        <thead>
            <tr style="background:#222; text-align:right">
                <th style="padding:15px">الاسم</th>
                <th style="padding:15px">التخصص</th>
                <th style="padding:15px">الجوال</th>
                <th style="padding:15px">إجراءات</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $vendors = $pdo->query("SELECT * FROM vendors ORDER BY id DESC");
            while($v = $vendors->fetch()): 
            ?>
            <tr style="border-bottom:1px solid #333">
                <td style="padding:15px; font-weight:bold"><?= $v['name'] ?></td>
                <td style="padding:15px"><span class="badge" style="background:#374151"><?= $v['service_type'] ?></span></td>
                <td style="padding:15px"><?= $v['phone'] ?></td>
                <td style="padding:15px; display:flex; gap:10px">
                    <button onclick='editVendor(<?= json_encode($v) ?>)' class="btn btn-dark btn-sm"><i class="fa-solid fa-pen"></i></button>
                    <form method="POST" onsubmit="return confirm('هل أنت متأكد من الحذف؟')" style="margin:0">
                        <input type="hidden" name="delete_id" value="<?= $v['id'] ?>">
                        <button class="btn btn-danger btn-sm"><i class="fa-solid fa-trash"></i></button>
                    </form>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<div id="vendorOverlay" class="custom-modal">
    <div class="custom-modal-content">
        <div style="display:flex; justify-content:space-between; margin-bottom:20px;">
            <h3 id="vTitle" style="margin:0; color:#fff">إضافة مقاول</h3>
            <div onclick="document.getElementById('vendorOverlay').style.display='none'" style="cursor:pointer; font-size:20px; color:#aaa; transition:0.3s hover:color:#fff">
                <i class="fa-solid fa-xmark"></i>
            </div>
        </div>
        
        <form method="POST">
            <input type="hidden" name="save_vendor" value="1">
            <input type="hidden" name="vid" id="v_id">
            
            <div style="margin-bottom:15px">
                <label class="inp-label">اسم المقاول / الشركة</label>
                <input type="text" name="name" id="v_name" class="inp" placeholder="الاسم هنا..." required style="width:100%">
            </div>
            
            <div style="margin-bottom:15px">
                <label class="inp-label">التخصص</label>
                <input type="text" name="type" id="v_type" class="inp" placeholder="مثال: سباكة" required style="width:100%">
            </div>
            
            <div style="margin-bottom:25px">
                <label class="inp-label">رقم الجوال</label>
                <input type="text" name="phone" id="v_phone" class="inp" placeholder="05xxxxxxxx" required style="width:100%">
            </div>
            
            <button class="btn btn-primary" style="width:100%; justify-content:center; padding:12px">حفظ البيانات</button>
        </form>
    </div>
</div>

<script>
    function openVendorModal() {
        document.getElementById('vendorOverlay').style.display = 'flex';
        document.getElementById('vTitle').innerText = 'إضافة مقاول جديد';
        document.getElementById('v_id').value = '';
        document.getElementById('v_name').value = '';
        document.getElementById('v_type').value = '';
        document.getElementById('v_phone').value = '';
    }
    
    function editVendor(data) {
        document.getElementById('vendorOverlay').style.display = 'flex';
        document.getElementById('vTitle').innerText = 'تعديل بيانات المقاول';
        document.getElementById('v_id').value = data.id;
        document.getElementById('v_name').value = data.name;
        document.getElementById('v_type').value = data.service_type;
        document.getElementById('v_phone').value = data.phone;
    }
</script>
