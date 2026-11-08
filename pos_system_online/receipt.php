<?php

// Load authentication functions
require_once __DIR__ . "/includes/auth.php";

// Make sure the user is logged in
require_login();


// Get the receipt ID from the URL
$id = (int)($_GET["id"] ?? 0);


// Get the sale information
$stmt = $pdo->prepare(
    "SELECT s.*, u.name, u.role
     FROM sales s
     LEFT JOIN users u ON u.id = s.user_id
     WHERE s.id = ?"
);

$stmt->execute([$id]);

$sale = $stmt->fetch();


// Check if the receipt exists
if (!$sale) {

    http_response_code(404);
    exit("Receipt not found.");

}


// Check if the user is allowed to view this receipt
if (
    $_SESSION["user"]["role"] !== "admin" &&
    (int)$sale["user_id"] !== (int)$_SESSION["user"]["id"]
) {

    http_response_code(403);
    exit("Access denied.");

}


// Get the products included in this sale
$stmt = $pdo->prepare(
    "SELECT si.*, p.product_name
     FROM sale_items si
     LEFT JOIN products p ON p.id = si.product_id
     WHERE si.sale_id = ?"
);

$stmt->execute([$id]);

$items = $stmt->fetchAll();


// Set page information
$page_title = "Receipt #" . $id;
$asset_prefix = "";

// Load the page header
require __DIR__ . "/includes/header.php";

?>


<!-- Receipt Container -->
<div class="receipt card">

    <!-- Store Information -->
    <div class="text-center">

        <h3>POS STORE</h3>

        <p class="mb-1">
            Sales Receipt
        </p>

        <hr>

    </div>


    <!-- Receipt Details -->
    <p class="mb-1">

        <strong>Receipt No:</strong>

        <?= (int)$sale["id"] ?>

    </p>

    <p class="mb-1">

        <strong>Date:</strong>

        <?= h($sale["sale_date"]) ?>

    </p>

    <p>

        <strong>Cashier:</strong>

        <?= h($sale["name"] ?? "Deleted user") ?>

    </p>


    <!-- Purchased Products -->
    <table class="table table-sm">

        <thead>

            <tr>

                <th>Item</th>

                <th>Qty</th>

                <th class="text-end">
                    Subtotal
                </th>

            </tr>

        </thead>


        <tbody>

            <?php foreach ($items as $item): ?>

                <tr>

                    <td>

                        <?= h($item["product_name"] ?? "Deleted product") ?>

                        <br>

                        <small>
                            <?= money($item["price"]) ?> each
                        </small>

                    </td>

                    <td>
                        <?= (int)$item["quantity"] ?>
                    </td>

                    <td class="text-end">
                        <?= money($item["subtotal"]) ?>
                    </td>

                </tr>

            <?php endforeach; ?>

        </tbody>

    </table>


    <!-- Payment Summary -->
    <div class="d-flex justify-content-between">

        <strong>Total</strong>

        <strong>
            <?= money($sale["total_amount"]) ?>
        </strong>

    </div>


    <div class="d-flex justify-content-between">

        <span>Amount paid</span>

        <span>
            <?= money($sale["amount_paid"]) ?>
        </span>

    </div>


    <div class="d-flex justify-content-between">

        <span>Change</span>

        <span>
            <?= money($sale["change_amount"]) ?>
        </span>

    </div>


    <hr>


    <!-- Thank You Message -->
    <p class="text-center mb-0">
        Thank you for your purchase!
    </p>

</div>


<!-- Receipt Buttons -->
<div class="text-center no-print">

    <!-- Print Receipt -->
    <button
        class="btn btn-primary"
        onclick="window.print()"
    >
        Print Receipt
    </button>


    <!-- Return to Sales Page -->
    <a
        class="btn btn-secondary"
        href="<?= $_SESSION["user"]["role"] === "admin"
            ? "admin/sales.php"
            : "cashier/sales.php" ?>"
    >
        Back
    </a>

</div>


<?php

// Load the page footer
require __DIR__ . "/includes/footer.php";

?>