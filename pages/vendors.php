<?php
// pages/vendors.php

// 1. الحذف
if (isset($_POST['delete_id'])) {
    $pdo->prepare("DELETE FROM vendors WHERE id=?")->execute([$_POST['delete_id']]);
    echo "<script>window.location='index.php?p=vendors';</script>";
    exit;
}

// 2. الحفظ
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_vendor'])) {
    if (!empty($_POST['vid'])) {
        $stmt = $pdo->prepare("UPDATE vendors SET name=?, service_type=?, phone=? WHERE id=?");
        $stmt->execute([$_POST['name'], $_POST['type'], $_POST['phone'], $_POST['vid']]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO vendors (name, service_type, phone) VALUES (?,?,?)");
        $stmt->execute([$_POST['name'], $_POST['type'], $_POST['phone']]);
    }
    echo "<script>window.location='index.php?p=vendors';</script>";
    exit;
}

// تحديد وضع العرض (قائمة أم نموذج)
$action = $_GET['action'] ?? 'list';
$edit_data = [];
if ($action == 'form' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM vendors WHERE id=?");
    $stmt->execute([$_GET['id']]);
    $edit_data = $stmt->fetch();
}
?>

<div class="card">
    <?php if ($action == 'list'): ?>
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px">
            <h3>👷 إدارة المقاولين</h3>
            <a href="index.php?p=vendors&action=form" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i> إضافة مقاول جديد
            </a>
        </div>

        <table style="width:100%; border-collapse:collapse">
            <thead>
                <tr style="background:#222; text-align:right">
                    <th style="padding:10px">الاسم</th>
                    <th style="padding:10px">التخصص</th>
                    <th style="padding:10px">الجوال</th>
                    <th style="padding:10px">إجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $vendors = $pdo->query("SELECT * FROM vendors ORDER BY id DESC");
                if($vendors->rowCount() == 0): 
                    echo "<tr><td colspan='4' style='text-align:center; padding:20px'>لا توجد بيانات.</td></tr>";
                else:
                    while($v = $vendors->fetch()): 
                ?>
                <tr style="border-bottom:1px solid #333">
                    <td style="padding:10px"><?= $v['name'] ?></td>
                    <td style="padding:10px"><?= $v['service_type'] ?></td>
                    <td style="padding:10px"><?= $v['phone'] ?></td>
                    <td style="padding:10px; display:flex; gap:5px">
                        <a href="index.php?p=vendors&action=form&id=<?= $v['id'] ?>" class="btn btn-dark btn-sm"><i class="fa-solid fa-pen"></i></a>
                        <form method="POST" onsubmit="return confirm('حذف؟')" style="margin:0">
                            <input type="hidden" name="delete_id" value="<?= $v['id'] ?>">
                            <button class="btn btn-danger btn-sm"><i class="fa-solid fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; endif; ?>
            </tbody>
        </table>

    <?php else: ?>
        <div style="display:flex; justify-content:space-between; margin-bottom:20px; border-bottom:1px solid #333; padding-bottom:10px">
            <h3><?= isset($_GET['id']) ? 'تعديل بيانات المقاول' : 'إضافة مقاول جديد' ?></h3>
            <a href="index.php?p=vendors" class="btn btn-dark">رجوع للقائمة</a>
        </div>

        <form method="POST" style="max-width:600px">
            <input type="hidden" name="save_vendor" value="1">
            <input type="hidden" name="vid" value="<?= $edit_data['id'] ?? '' ?>">

            <div style="margin-bottom:15px">
                <label class="inp-label">اسم المقاول / الشركة</label>
                <input type="text" name="name" class="inp" value="<?= $edit_data['name'] ?? '' ?>" required style="width:100%">
            </div>

            <div style="margin-bottom:15px">
                <label class="inp-label">التخصص (مثال: كهرباء، سباكة)</label>
                <input type="text" name="type" class="inp" value="<?= $edit_data['service_type'] ?? '' ?>" required style="width:100%">
            </div>

            <div style="margin-bottom:20px">
                <label class="inp-label">رقم الجوال</label>
                <input type="text" name="phone" class="inp" value="<?= $edit_data['phone'] ?? '' ?>" required style="width:100%">
            </div>

            <button class="btn btn-primary" style="padding:10px 20px">حفظ البيانات</button>
        </form>
    <?php endif; ?>
</div>
