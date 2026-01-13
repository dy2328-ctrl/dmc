<?php
// الحذف والإضافة كما هي في الكود السابق...
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

<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px">
        <h3>👷 المقاولين</h3>
        <button onclick="document.getElementById('vModal').style.display='flex'; document.getElementById('vForm').reset(); document.getElementById('v_vid').value='';" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> إضافة مقاول
        </button>
    </div>
    
    <table style="width:100%; border-collapse:collapse">
        <thead><tr style="background:#222; text-align:right"><th style="padding:10px">الاسم</th><th style="padding:10px">التخصص</th><th style="padding:10px">جوال</th><th style="padding:10px"></th></tr></thead>
        <tbody>
            <?php $vs=$pdo->query("SELECT * FROM vendors"); while($v=$vs->fetch()): ?>
            <tr style="border-bottom:1px solid #333">
                <td style="padding:10px"><?= $v['name'] ?></td>
                <td style="padding:10px"><?= $v['service_type'] ?></td>
                <td style="padding:10px"><?= $v['phone'] ?></td>
                <td style="padding:10px">
                    <form method="POST" onsubmit="return confirm('حذف؟')" style="display:inline">
                        <input type="hidden" name="delete_id" value="<?= $v['id'] ?>">
                        <button class="btn btn-danger btn-sm"><i class="fa-solid fa-trash"></i></button>
                    </form>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<div id="vModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:9999; justify-content:center; align-items:center;">
    <div style="background:#1a1a1a; padding:25px; border-radius:15px; width:400px;">
        <div style="display:flex; justify-content:space-between; margin-bottom:20px;">
            <h3>بيانات المقاول</h3>
            <span onclick="document.getElementById('vModal').style.display='none'" style="cursor:pointer; font-size:20px">&times;</span>
        </div>
        <form method="POST" id="vForm">
            <input type="hidden" name="save_vendor" value="1">
            <input type="hidden" name="vid" id="v_vid">
            <input type="text" name="name" placeholder="الاسم" class="inp" required style="width:100%; margin-bottom:10px">
            <input type="text" name="type" placeholder="التخصص" class="inp" required style="width:100%; margin-bottom:10px">
            <input type="text" name="phone" placeholder="الجوال" class="inp" required style="width:100%; margin-bottom:10px">
            <button class="btn btn-primary" style="width:100%">حفظ</button>
        </form>
    </div>
</div>
