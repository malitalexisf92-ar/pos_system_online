
<?php

require_once __DIR__ . "/../includes/auth.php";

require_role("cashier");

// Get sales made by the logged-in cashier
$stmt = $pdo->prepare("
    SELECT *
    FROM sales
    WHERE user_id = ?
    ORDER BY id DESC
    LIMIT 200
");

$stmt->execute([
    $_SESSION["user"]["id"]
]);

$sales = $stmt->fetchAll();

$page_title = "My Sales";
$asset_prefix = "../";

require __DIR__ . "/../includes/header.php";

?>

<h2 class="mb-3">My Sales</h2>

<!-- Sales History Table -->
<div class="card p-3">

    <h5>My Sales History</h5>

    <div class="table-responsive">

        <table class="table">

            <thead>

                <tr>
                    <th>Sale #</th>
                    <th>Date</th>
                    <th>Total</th>
                    <th>Paid</th>
                    <th>Change</th>
                    <th>Receipt</th>
                </tr>

            </thead>

            <tbody>

                <?php foreach ($sales as $sale): ?>

                    <tr>

                        <td>
                            <?= (int) $sale["id"] ?>
                        </td>

                        <td>
                            <?= h($sale["sale_date"]) ?>
                        </td>

                        <td>
                            <?= money($sale["total_amount"]) ?>
                        </td>

                        <td>
                            <?= money($sale["amount_paid"]) ?>
                        </td>

                        <td>
                            <?= money($sale["change_amount"]) ?>
                        </td>

                        <td>

                            <a
                                class="btn btn-sm btn-outline-primary"
                                href="../receipt.php?id=<?= (int) $sale["id"] ?>"
                            >
                                View Receipt
                            </a>

                        </td>

                    </tr>

                <?php endforeach; ?>

                <?php if (empty($sales)): ?>

                    <tr>

                        <td colspan="6" class="text-center text-muted">
                            You have no sales yet.
                        </td>

                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

</div>

<?php

require __DIR__ . "/../includes/footer.php";

?>
