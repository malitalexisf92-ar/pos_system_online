
<?php
require_once __DIR__ . "/../includes/auth.php";
require_role("admin");

// Get selected dates
$startDate = $_GET["start_date"] ?? date("Y-m-01");
$endDate = $_GET["end_date"] ?? date("Y-m-d");

// Validate date format
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate) ||
    !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate) ||
    !strtotime($startDate) ||
    !strtotime($endDate) ||
    $startDate > $endDate) {

    $startDate = date("Y-m-01");
    $endDate = date("Y-m-d");
}

// Get sales within selected dates
$stmt = $pdo->prepare("
    SELECT s.*, u.name
    FROM sales s
    LEFT JOIN users u ON u.id = s.user_id
    WHERE s.sale_date >= ?
      AND s.sale_date < DATE_ADD(?, INTERVAL 1 DAY)
    ORDER BY s.id DESC
");

$stmt->execute([$startDate, $endDate]);
$sales = $stmt->fetchAll();

// Calculate report summary
$stmt = $pdo->prepare("
    SELECT
        COUNT(*) AS transactions,
        COALESCE(SUM(total_amount), 0) AS total_sales,
        COALESCE(SUM(amount_paid), 0) AS total_paid,
        COALESCE(SUM(change_amount), 0) AS total_change
    FROM sales
    WHERE sale_date >= ?
      AND sale_date < DATE_ADD(?, INTERVAL 1 DAY)
");

$stmt->execute([$startDate, $endDate]);
$summary = $stmt->fetch();

$page_title = "Sales Reports";
$asset_prefix = "../";

require __DIR__ . "/../includes/header.php";
?>

<style>
@media print {
    .no-print, nav, footer {
        display: none !important;
    }

    body {
        background: white !important;
    }

    .card {
        border: 1px solid #ddd !important;
        box-shadow: none !important;
    }

    .container {
        width: 100% !important;
        max-width: 100% !important;
    }
}
</style>

<h2 class="mb-3">Sales Reports</h2>

<!-- Date Filters -->
<div class="card p-3 mb-4 no-print">

    <h5>Filter Sales by Date</h5>

    <form method="GET" class="row g-3">

        <div class="col-md-4">
            <label class="form-label">Start Date</label>
            <input
                type="date"
                name="start_date"
                class="form-control"
                value="<?= h($startDate) ?>"
                required
            >
        </div>

        <div class="col-md-4">
            <label class="form-label">End Date</label>
            <input
                type="date"
                name="end_date"
                class="form-control"
                value="<?= h($endDate) ?>"
                required
            >
        </div>

        <div class="col-md-4 d-flex align-items-end gap-2">

            <button class="btn btn-primary" type="submit">
                Filter
            </button>

            <a href="sales.php" class="btn btn-secondary">
                Reset
            </a>

            <button
                type="button"
                class="btn btn-success"
                onclick="window.print()"
            >
                Print
            </button>

        </div>

    </form>
</div>

<!-- Report Summary -->
<div class="row g-3 mb-4">

    <div class="col-md-3">
        <div class="card stat-card p-3">
            <div class="text-muted">Total Sales</div>
            <h3><?= money($summary["total_sales"]) ?></h3>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card stat-card p-3">
            <div class="text-muted">Transactions</div>
            <h3><?= (int)$summary["transactions"] ?></h3>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card stat-card p-3">
            <div class="text-muted">Total Amount Paid</div>
            <h3><?= money($summary["total_paid"]) ?></h3>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card stat-card p-3">
            <div class="text-muted">Total Change</div>
            <h3><?= money($summary["total_change"]) ?></h3>
        </div>
    </div>

</div>

<!-- Sales Table -->
<div class="card p-3">

    <div class="d-flex justify-content-between flex-wrap mb-3">
        <h5>Sales Transactions</h5>

        <span class="text-muted">
            <?= h($startDate) ?> to <?= h($endDate) ?>
        </span>
    </div>

    <div class="table-responsive">

        <table class="table table-hover">

            <thead>
                <tr>
                    <th>Sale #</th>
                    <th>Date</th>
                    <th>Cashier</th>
                    <th>Total</th>
                    <th>Paid</th>
                    <th>Change</th>
                    <th class="no-print">Receipt</th>
                </tr>
            </thead>

            <tbody>

                <?php foreach ($sales as $s): ?>

                    <tr>
                        <td><?= (int)$s["id"] ?></td>

                        <td><?= h($s["sale_date"]) ?></td>

                        <td>
                            <?= h($s["name"] ?? "Deleted user") ?>
                        </td>

                        <td><?= money($s["total_amount"]) ?></td>

                        <td><?= money($s["amount_paid"]) ?></td>

                        <td><?= money($s["change_amount"]) ?></td>

                        <td class="no-print">
                            <a
                                class="btn btn-sm btn-outline-primary"
                                href="../receipt.php?id=<?= (int)$s["id"] ?>"
                            >
                                View
                            </a>
                        </td>
                    </tr>

                <?php endforeach; ?>

                <?php if (!$sales): ?>

                    <tr>
                        <td colspan="7" class="text-center text-muted">
                            No sales found for this date range.
                        </td>
                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </div>
</div>

<?php require __DIR__ . "/../includes/footer.php"; ?>
