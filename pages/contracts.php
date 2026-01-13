<?php
// الحذف
if (isset($_POST['delete_id'])) {
    check_csrf();
    // نحتاج أولاً تحديث حالة الوحدة لتصبح شاغرة
    $c = $pdo->query("SELECT unit_id FROM contracts WHERE id=".$_POST['delete_id'])->fetch();
    if($c) {
        $pdo->prepare("UPDATE units SET status='available' WHERE id=?")->execute([$c['unit_id']]);
    }
    // ثم نحذف العقد (سيتم حذف الدفعات تلقائياً)
    $pdo->prepare("DELETE FROM contracts WHERE id=?")->execute([$_POST['delete_id']]);
    echo "<script>window.location='index.php?p=contracts';</script>";
}
// (كود الإضافة يبقى كما هو السابق...)
?>

<tbody>
    <?php 
    $conts = $pdo->query("SELECT c.*, t.name as tname, u.unit_name FROM contracts c JOIN tenants t ON c.tenant_id=t.id JOIN units u ON c.unit_id=u.id ORDER BY id DESC");
    if($conts->rowCount() == 0): ?>
        <tr><td colspan="7" style="text-align:center; padding:30px; color:#666">لا توجد عقود حالياً</td></tr>
    <?php else: ?>
        <?php while($r = $conts->fetch()): ?>
        <tr>
            <td>#<?= $r['id'] ?></td>
            <td style="font-weight:bold"><?= $r['tname'] ?></td>
            <td><?= $r['unit_name'] ?></td>
            <td><?= number_format($r['total_amount']) ?></td>
            <td><span class="badge" style="background:rgba(16,185,129,0.2); color:#10b981">نشط</span></td>
            <td style="display:flex; gap:5px">
                <a href="index.php?p=contract_view&id=<?= $r['id'] ?>" class="btn btn-dark" style="padding:5px 10px"><i class="fa-solid fa-eye"></i></a>
                
                <form method="POST" onsubmit="return confirm('تحذير: حذف العقد سيحذف جميع الدفعات وتوقيعات العقد! هل أنت متأكد؟');" style="margin:0">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="delete_id" value="<?= $r['id'] ?>">
                    <button class="btn btn-danger" style="padding:5px 10px"><i class="fa-solid fa-trash"></i></button>
                </form>
            </td>
        </tr>
        <?php endwhile; ?>
    <?php endif; ?>
</tbody>
