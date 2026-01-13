<?php
// معالجة البيانات (PHP)
if (isset($_POST['delete_id'])) {
    $pdo->prepare("DELETE FROM vendors WHERE id=?")->execute([$_POST['delete_id']]);
    echo "<script>window.location='index.php?p=vendors';</script>";
}
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_vendor'])) {
    if(!empty($_POST['vid'])){
        $pdo->prepare("UPDATE vendors SET name=?, service_type=?, phone=? WHERE id=?")->execute([$_POST['name'], $_POST['type'], $_POST['phone'], $_POST['vid']]);
    } else {
        $pdo->prepare("INSERT INTO vendors (name, service_type, phone) VALUES (?,?,?)")->execute([$_POST['name'], $_POST['type'], $_POST['phone']]);
    }
    echo "<script>window.location='index.php?p=vendors';</script>";
}
?>

<style>
    /* خلفية معتمة تظهر خلف اللوحة */
    .backdrop-blur {
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0, 0, 0, 0.6);
        backdrop-filter: blur(5px); /* تأثير الزجاج */
        z-index: 99998;
        display: none;
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    
    /* اللوحة المنزلقة */
    .slide-panel {
        position: fixed;
        top: 0;
        left: -500px; /* مخفية خارج الشاشة من اليسار */
        width: 400px;
        height: 100%;
        background: #1a1a1a;
        z-index: 99999;
        box-shadow: 10px 0 30px rgba(0,0,0,0.5);
        transition: left 0.4s cubic-bezier(0.4, 0, 0.2, 1); /* حركة انزلاق سينمائية */
        padding: 30px;
        border-right: 1px solid #333;
        display: flex;
        flex-direction: column;
    }

    /* كلاس التفعيل */
    .slide-active { left: 0 !important; }
    .backdrop-active { display: block !important; opacity: 1 !important; }

    /* تحسين شكل الحقول */
    .modern-inp {
        background: #2a2a2a; border: 1px solid #444; color: white;
        padding: 15px; border-radius: 8px; width: 100%; margin-bottom: 20px;
        transition: 0.3s;
    }
    .modern-inp:focus { border-color: #6366f1; outline: none; background: #333; }
</style>

<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px">
        <h3>👷 إدارة المقاولين</h3>
        <button onclick="openSlide()" class="btn btn-primary" style="border-radius:30px; padding:10px 25px; box-shadow: 0 4px 15px rgba(99, 102, 241, 0.4);">
            <i class="fa-solid fa-plus"></i> إضافة مقاول (Slide)
        </button>
    </div>
    
    <table style="width:100%; border-collapse:collapse">
        <thead>
            <tr style="background:#222; text-align:right">
                <th style="padding:15px">الاسم</th>
                <th style="padding:15px">التخصص</th>
                <th style="padding:15px">الجوال</th>
                <th style="padding:15px">خيارات</th>
            </tr>
        </thead>
        <tbody>
            <?php $vs=$pdo->query("SELECT * FROM vendors ORDER BY id DESC"); while($v=$vs->fetch()): ?>
            <tr style="border-bottom:1px solid #333">
                <td style="padding:15px; font-weight:bold"><?= $v['name'] ?></td>
                <td style="padding:15px"><span class="badge" style="background:#374151; padding:5px 10px; border-radius:20px"><?= $v['service_type'] ?></span></td>
                <td style="padding:15px; font-family:monospace"><?= $v['phone'] ?></td>
                <td style="padding:15px">
                    <button onclick='editSlide(<?= json_encode($v) ?>)' class="btn btn-dark btn-sm" style="border-radius:50%; width:35px; height:35px; padding:0"><i class="fa-solid fa-pen"></i></button>
                    <form method="POST" style="display:inline" onsubmit="return confirm('حذف؟')">
                        <input type="hidden" name="delete_id" value="<?= $v['id'] ?>">
                        <button class="btn btn-danger btn-sm" style="border-radius:50%; width:35px; height:35px; padding:0"><i class="fa-solid fa-trash"></i></button>
                    </form>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<div id="venBackdrop" class="backdrop-blur" onclick="closeSlide()"></div>

<div id="venSlide" class="slide-panel">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:40px;">
        <h2 id="slideTitle" style="margin:0; font-weight:300; letter-spacing:1px; color:white">إضافة مقاول</h2>
        <button onclick="closeSlide()" style="background:none; border:none; color:#666; font-size:24px; cursor:pointer; transition:0.3s hover:color:white"><i class="fa-solid fa-arrow-left"></i></button>
    </div>

    <form method="POST" style="flex-grow:1">
        <input type="hidden" name="save_vendor" value="1">
        <input type="hidden" name="vid" id="v_vid">
        
        <label style="color:#888; margin-bottom:5px; display:block">اسم الشركة / المقاول</label>
        <input type="text" name="name" id="v_name" class="modern-inp" required placeholder="أدخل الاسم...">

        <label style="color:#888; margin-bottom:5px; display:block">التخصص</label>
        <input type="text" name="type" id="v_type" class="modern-inp" required placeholder="مثال: أعمال كهرباء">

        <label style="color:#888; margin-bottom:5px; display:block">رقم التواصل</label>
        <input type="text" name="phone" id="v_phone" class="modern-inp" required placeholder="05xxxxxxxx">

        <button class="btn btn-primary" style="width:100%; padding:15px; font-size:16px; margin-top:20px; border-radius:10px">
            <i class="fa-solid fa-save"></i> حفظ البيانات
        </button>
    </form>
    
    <div style="text-align:center; color:#444; font-size:12px; margin-top:20px;">
        System Secured &copy; 2024
    </div>
</div>

<script>
    function openSlide() {
        document.getElementById('venBackdrop').classList.add('backdrop-active');
        document.getElementById('venSlide').classList.add('slide-active');
        // Reset form
        document.getElementById('slideTitle').innerText = 'إضافة مقاول جديد';
        document.getElementById('v_vid').value = '';
        document.getElementById('v_name').value = '';
        document.getElementById('v_type').value = '';
        document.getElementById('v_phone').value = '';
    }

    function editSlide(data) {
        openSlide(); // Open first
        document.getElementById('slideTitle').innerText = 'تعديل: ' + data.name;
        document.getElementById('v_vid').value = data.id;
        document.getElementById('v_name').value = data.name;
        document.getElementById('v_type').value = data.service_type;
        document.getElementById('v_phone').value = data.phone;
    }

    function closeSlide() {
        document.getElementById('venBackdrop').classList.remove('backdrop-active');
        document.getElementById('venSlide').classList.remove('slide-active');
    }
</script>
