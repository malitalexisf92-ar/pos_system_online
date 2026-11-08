
<?php

require_once __DIR__ . "/../includes/auth.php";

require_role("cashier");

$error = "";

// Process checkout
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    verify_csrf();

    $quantities = $_POST["qty"] ?? [];

    $cart = [];
    $total = 0;

    // Get selected products
    foreach ($quantities as $id => $qty) {

        $id = (int) $id;
        $qty = (int) $qty;

        if ($id > 0 && $qty > 0) {

            $stmt = $pdo->prepare("
                SELECT id, product_name, price, stock
                FROM products
                WHERE id = ?
            ");

            $stmt->execute([$id]);

            $product = $stmt->fetch();

            if (!$product) {

                $error = "A selected product no longer exists.";
                break;

            }

            // Check available stock
            if ($qty > (int) $product["stock"]) {

                $error = "Not enough stock for " .
                    $product["product_name"] . ".";

                break;
            }

            // Calculate subtotal
            $product["quantity"] = $qty;

            $product["subtotal"] =
                (float) $product["price"] * $qty;

            $cart[] = $product;

            $total += $product["subtotal"];
        }
    }

    // Get payment
    $paid = (float) ($_POST["amount_paid"] ?? 0);

    // Validate checkout
    if (!$error && empty($cart)) {

        $error = "Select at least one product quantity.";

    } elseif (!$error && $paid + 0.00001 < $total) {

        $error = "Payment is less than the total amount.";
    }

    // Save sale to database
    if (!$error) {

        try {

            $pdo->beginTransaction();

            // Insert sale record
            $stmt = $pdo->prepare("
                INSERT INTO sales
                (user_id, total_amount, amount_paid, change_amount)
                VALUES (?, ?, ?, ?)
            ");

            $stmt->execute([
                $_SESSION["user"]["id"],
                $total,
                $paid,
                $paid - $total
            ]);

            $saleId = (int) $pdo->lastInsertId();

            // Prepare sale item query
            $itemStmt = $pdo->prepare("
                INSERT INTO sale_items
                (sale_id, product_id, quantity, price, subtotal)
                VALUES (?, ?, ?, ?, ?)
            ");

            // Prepare stock deduction query
            $stockStmt = $pdo->prepare("
                UPDATE products
                SET stock = stock - ?
                WHERE id = ? AND stock >= ?
            ");

            // Save each product sold
            foreach ($cart as $item) {

                $stockStmt->execute([
                    $item["quantity"],
                    $item["id"],
                    $item["quantity"]
                ]);

                if ($stockStmt->rowCount() !== 1) {

                    throw new RuntimeException(
                        "Stock changed during checkout. Please try again."
                    );
                }

                $itemStmt->execute([
                    $saleId,
                    $item["id"],
                    $item["quantity"],
                    $item["price"],
                    $item["subtotal"]
                ]);
            }

            // Save all changes
            $pdo->commit();

            // Open receipt
            redirect_to("../receipt.php?id=" . $saleId);

        } catch (Throwable $e) {

            // Cancel transaction if an error occurs
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            if ($e instanceof RuntimeException) {
                $error = $e->getMessage();
            } else {
                $error = "Checkout failed. Please try again.";
            }
        }
    }
}

// Get available products
$products = $pdo->query("
    SELECT id, product_name, price, stock
    FROM products
    WHERE stock > 0
    ORDER BY product_name
")->fetchAll();

$page_title = "Checkout";
$asset_prefix = "../";

require __DIR__ . "/../includes/header.php";

?>

<h2 class="mb-3">Cashier Checkout</h2>

<!-- Display Error -->
<?php if ($error != ""): ?>

    <div class="alert alert-danger">
        <?= h($error) ?>
    </div>

<?php endif; ?>

<!-- Checkout Form -->
<form method="post">

    <input
        type="hidden"
        name="csrf"
        value="<?= h(csrf_token()) ?>"
    >

    <div class="card p-3">

        <div class="table-responsive">

            <table class="table align-middle">

                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Available Stock</th>
                        <th style="width: 130px;">Quantity</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($products as $product): ?>

                        <tr>

                            <td>
                                <?= h($product["product_name"]) ?>
                            </td>

                            <td>
                                <?= money($product["price"]) ?>
                            </td>

                            <td>
                                <?= (int) $product["stock"] ?>
                            </td>

                            <td>

                                <input
                                    class="form-control qty"
                                    type="number"
                                    min="0"
                                    max="<?= (int) $product["stock"] ?>"
                                    value="0"
                                    name="qty[<?= (int) $product["id"] ?>]"
                                    data-price="<?= h($product["price"]) ?>"
                                >

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    <?php if (empty($products)): ?>

                        <tr>
                            <td colspan="4" class="text-center text-muted">
                                No products in stock.
                                Ask the admin to add products.
                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

        <!-- Payment Section -->
        <div class="row justify-content-end">

            <div class="col-md-4">

                <div class="d-flex justify-content-between fs-5 mb-3">

                    <strong>Estimated Total</strong>

                    <strong id="total">₱0.00</strong>

                </div>

                <label class="form-label">Amount Paid (₱)</label>

                <input
                    class="form-control mb-3"
                    type="number"
                    min="0"
                    step="0.01"
                    name="amount_paid"
                    required
                >

                <button
                    type="submit"
                    class="btn btn-success w-100"
                    <?= empty($products) ? "disabled" : "" ?>
                >
                    Complete Sale
                </button>

            </div>

        </div>

    </div>

</form>

<!-- Calculate Total -->
<script>

const fields = [...document.querySelectorAll(".qty")];

function updateTotal() {

    let sum = 0;

    fields.forEach(function(field) {

        let quantity = Math.max(0, Number(field.value) || 0);

        let maxStock = Number(field.max);

        if (quantity > maxStock) {

            quantity = maxStock;
            field.value = maxStock;

        }

        let price = Number(field.dataset.price);

        sum += quantity * price;

    });

    document.getElementById("total").textContent =
        new Intl.NumberFormat("en-PH", {
            style: "currency",
            currency: "PHP"
        }).format(sum);
}

// Update total when quantity changes
fields.forEach(function(field) {

    field.addEventListener("input", updateTotal);

});

</script>

<?php

require __DIR__ . "/../includes/footer.php";

?>
