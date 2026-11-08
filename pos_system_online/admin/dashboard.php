
<?php
require_once __DIR__ . "/../includes/auth.php";
require_role("admin");

// Today's sales
$today = $pdo->query("
    SELECT
        COUNT(*) AS transactions,
        COALESCE(SUM(total_amount), 0) AS total
    FROM sales
    WHERE DATE(sale_date) = CURDATE()
")->fetch();

// This week's sales (Monday to today)
$week = $pdo->query("
    SELECT
        COUNT(*) AS transactions,
        COALESCE(SUM(total_amount), 0) AS total
    FROM sales
    WHERE DATE(sale_date) >=
        DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY)
      AND DATE(sale_date) <= CURDATE()
")->fetch();

// This month's sales
$month = $pdo->query("
    SELECT
        COUNT(*) AS transactions,
        COALESCE(SUM(total_amount), 0) AS total
    FROM sales
    WHERE MONTH(sale_date) = MONTH(CURDATE())
      AND YEAR(sale_date) = YEAR(CURDATE())
")->fetch();

// Total products
$products = (int)$pdo->query("
    SELECT COUNT(*) FROM products
")->fetchColumn();

// Low-stock products
$low = (int)$pdo->query("
    SELECT COUNT(*) FROM products
    WHERE stock <= 5
")->fetchColumn();

// Top-selling products this month
$topProducts = $pdo->query("
    SELECT
        p.product_name,
        SUM(si.quantity) AS quantity_sold,
        SUM(si.subtotal) AS total_sales
    FROM sale_items si
    INNER JOIN products p ON p.id = si.product_id
    INNER JOIN sales s ON s.id = si.sale_id
    WHERE MONTH(s.sale_date) = MONTH(CURDATE())
      AND YEAR(s.sale_date) = YEAR(CURDATE())
    GROUP BY p.id, p.product_name
    ORDER BY quantity_sold DESC
    LIMIT 5
")->fetchAll();

// Recent sales
$recent = $pdo->query("
    SELECT
        s.id,
        s.total_amount,
        s.sale_date,
        u.name
    FROM sales s
    LEFT JOIN users u ON u.id = s.user_id
    ORDER BY s.id DESC
    LIMIT 8
")->fetchAll();

$page_title = "Dashboard";
$asset_prefix = "../";

require __DIR__ . "/../includes/header.php";
?>

<h2 class="mb-4">Dashboard Analytics</h2>

<!-- Sales Summary -->
<div class="row g-3 mb-4">

    <div class="col-md-4">
        <div class="card stat-card p-3">
            <div class="text-muted">Today's Sales</div>
            <h3><?= money($today["total"]) ?></h3>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card stat-card p-3">
            <div class="text-muted">This Week's Sales</div>
            <h3><?= money($week["total"]) ?></h3>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card stat-card p-3">
            <div class="text-muted">This Month's Sales</div>
            <h3><?= money($month["total"]) ?></h3>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card stat-card p-3">
            <div class="text-muted">Transactions Today</div>
            <h3><?= (int)$today["transactions"] ?></h3>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card stat-card p-3">
            <div class="text-muted">Total Products</div>
            <h3><?= $products ?></h3>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card stat-card p-3">
            <div class="text-muted">Low Stock (5 or less)</div>
            <h3><?= $low ?></h3>
        </div>
    </div>

</div>

<!-- Top Selling Products -->
<div class="card p-3 mb-4">

    <h5>Top-Selling Products This Month</h5>

    <div class="table-responsive">
        <table class="table table-hover">

            <thead>
                <tr>
                    <th>Product</th>
                    <th>Quantity Sold</th>
                    <th>Total Sales</th>
                </tr>
            </thead>

            <tbody>

                <?php foreach ($topProducts as $p): ?>
                    <tr>
                        <td><?= h($p["product_name"]) ?></td>
                        <td><?= (int)$p["quantity_sold"] ?></td>
                        <td><?= money($p["total_sales"]) ?></td>
                    </tr>
                <?php endforeach; ?>

                <?php if (!$topProducts): ?>
                    <tr>
                        <td colspan="3" class="text-center text-muted">
                            No product sales this month.
                        </td>
                    </tr>
                <?php endif; ?>

            </tbody>

        </table>
    </div>

</div>

<!-- Recent Sales -->
<div class="card p-3">

    <h5>Recent Sales</h5>

    <div class="table-responsive">
        <table class="table table-hover">

            <thead>
                <tr>
                    <th>Sale #</th>
                    <th>Date</th>
                    <th>Cashier</th>
                    <th>Total</th>
                    <th></th>
                </tr>
            </thead>

            <tbody>

                <?php foreach ($recent as $s): ?>
                    <tr>
                        <td><?= (int)$s["id"] ?></td>
                        <td><?= h($s["sale_date"]) ?></td>
                        <td><?= h($s["name"] ?? "Deleted user") ?></td>
                        <td><?= money($s["total_amount"]) ?></td>
                        <td>
                            <a
                                class="btn btn-sm btn-outline-primary"
                                href="../receipt.php?id=<?= (int)$s["id"] ?>"
                            >
                                Receipt
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (!$recent): ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted">
                            No sales yet.
                        </td>
                    </tr>
                <?php endif; ?>

            </tbody>

        </table>
    </div>

</div>

<?php require __DIR__ . "/../includes/footer.php"; ?>
