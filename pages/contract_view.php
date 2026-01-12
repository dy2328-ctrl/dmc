<?php
$id = $_GET['id'];
$c = $pdo->query("SELECT * FROM contracts WHERE id=$id")->fetch();
$pays = $pdo->query("SELECT * FROM payments WHERE contract_id=$id");

if(isset($_POST['pay_id'])){
    check_csrf();
    $pid = $_POST['pay_id'];
    $amt = $_POST['amount'];
    $pdo->prepare("UPDATE payments SET paid_amount=paid_amount+?, status='paid', paid_date=CURRENT_DATE WHERE id=?")->execute([$amt, $pid]);
    $pdo->prepare("INSERT INTO transactions (payment_id, amount_paid, transaction_date) VALUES (?,?,CURRENT_DATE)")->execute([$pid, $amt]);
    header("Location: index.php?p=contract_view&id=$id"); exit;
}
?>
<div class="card p-4">
    <h3>تفاصيل العقد #<?= $c['id'] ?></h3>
    <table class="table mt-4">
        <thead><tr><th>الدفعة</th><th>تاريخ الاستحقاق</th><th>المبلغ</th><th>المدفوع</th><th>إجراء</th></tr></thead>
        <tbody>
            <?php while($p=$pays->fetch()): ?>
            <tr>
                <td><?= $p['title'] ?></td>
                <td><?= $p['due_date'] ?></td>
                <td><?= number_format($p['amount']) ?></td>
                <td class="text-success"><?= number_format($p['paid_amount']) ?></td>
                <td>
                    <?php if($p['status']!='paid'): ?>
                    <form method="POST" style="display:inline">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <input type="hidden" name="pay_id" value="<?= $p['id'] ?>">
                        <input type="hidden" name="amount" value="<?= $p['amount']-$p['paid_amount'] ?>">
                        <button class="btn btn-sm btn-success">سداد كامل</button>
                    </form>
                    <?php else: ?>
                    <span class="badge bg-success">تم السداد</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
