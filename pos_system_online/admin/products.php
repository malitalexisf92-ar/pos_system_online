
<?php
require_once __DIR__ . "/../includes/auth.php";
require_role("admin");

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verify_csrf();

    $action = $_POST["action"] ?? "";

    if ($action === "delete") {
        $stmt = $pdo->prepare("DELETE FROM products WHERE id=?");
        $stmt->execute([(int)$_POST["id"]]);
        redirect_to("products.php");
    }

    $name = trim($_POST["product_name"] ?? "");
    $category = ($_POST["category_id"] ?? "") !== ""
        ? (int)$_POST["category_id"] : null;
    $price = (float)($_POST["price"] ?? 0);
    $stock = (int)($_POST["stock"] ?? 0);

    if ($name === "" || $price < 0 || $stock < 0) {
        $error = "Enter valid product details.";
    } elseif ($action === "save") {
        $stmt = $pdo->prepare("
            INSERT INTO products
            (product_name, category_id, price, stock)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$name, $category, $price, $stock]);
        redirect_to("products.php");
    } elseif ($action === "update") {
        $stmt = $pdo->prepare("
            UPDATE products
            SET product_name=?, category_id=?, price=?, stock=?
            WHERE id=?
        ");
        $stmt->execute([
            $name, $category, $price, $stock,
            (int)$_POST["id"]
        ]);
        redirect_to("products.php");
    }
}

// Edit product
$edit = null;

if (isset($_GET["edit"])) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id=?");
    $stmt->execute([(int)$_GET["edit"]]);
    $edit = $stmt->fetch();
}

// Categories
$categories = $pdo->query("
    SELECT * FROM categories ORDER BY category_name
")->fetchAll();

// All products
$products = $pdo->query("
    SELECT p.*, c.category_name
    FROM products p
    LEFT JOIN categories c ON c.id = p.category_id
    ORDER BY p.id DESC
")->fetchAll();

// Inventory summary
$totalProducts = count($products);
$lowStock = 0;
$outOfStock = 0;

foreach ($products as $p) {
    if ((int)$p["stock"] === 0) {
        $outOfStock++;
    } elseif ((int)$p["stock"] <= 5) {
        $lowStock++;
    }
}

$page_title = "Products";
$asset_prefix = "../";

require __DIR__ . "/../includes/header.php";
?>

<h2 class="mb-3">Product Management</h2>

<!-- Inventory Summary -->
<div class="row g-3 mb-4">

    <div class="col-md-4">
        <div class="card stat-card p-3">
            <div class="text-muted">Total Products</div>
            <h3><?= $totalProducts ?></h3>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card stat-card p-3">
            <div class="text-muted">Low-Stock Products</div>
            <h3 class="text-warning"><?= $lowStock ?></h3>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card stat-card p-3">
            <div class="text-muted">Out-of-Stock Products</div>
            <h3 class="text-danger"><?= $outOfStock ?></h3>
        </div>
    </div>

</div>

<!-- Alerts -->
<?php if ($outOfStock > 0): ?>
    <div class="alert alert-danger">
        <strong>Out of Stock Alert!</strong>
        <?= $outOfStock ?> product(s) currently have no stock.
        Please restock them.
    </div>
<?php endif; ?>

<?php if ($lowStock > 0): ?>
    <div class="alert alert-warning">
        <strong>Low Stock Warning!</strong>
        <?= $lowStock ?> product(s) have 5 or fewer items remaining.
    </div>
<?php endif; ?>

<div class="row g-3">

    <!-- Add/Edit Product -->
    <div class="col-lg-4">

        <div class="card p-3">

            <h5><?= $edit ? "Edit Product" : "Add Product" ?></h5>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <?= h($error) ?>
                </div>
            <?php endif; ?>

            <form method="post">

                <input type="hidden" name="csrf"
                    value="<?= h(csrf_token()) ?>">

                <input type="hidden" name="action"
                    value="<?= $edit ? "update" : "save" ?>">

                <?php if ($edit): ?>
                    <input type="hidden" name="id"
                        value="<?= (int)$edit["id"] ?>">
                <?php endif; ?>

                <label class="form-label">Product Name</label>

                <input
                    class="form-control mb-2"
                    name="product_name"
                    required
                    value="<?= h($edit["product_name"] ?? "") ?>"
                >

                <label class="form-label">Category</label>

                <select class="form-select mb-2" name="category_id">

                    <option value="">Uncategorized</option>

                    <?php foreach ($categories as $c): ?>

                        <option
                            value="<?= (int)$c["id"] ?>"
                            <?= (($edit["category_id"] ?? "") == $c["id"])
                                ? "selected" : "" ?>
                        >
                            <?= h($c["category_name"]) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

                <label class="form-label">Price (₱)</label>

                <input
                    class="form-control mb-2"
                    type="number"
                    step="0.01"
                    min="0"
                    name="price"
                    required
                    value="<?= h($edit["price"] ?? "0.00") ?>"
                >

                <label class="form-label">Stock</label>

                <input
                    class="form-control mb-3"
                    type="number"
                    min="0"
                    name="stock"
                    required
                    value="<?= h($edit["stock"] ?? "0") ?>"
                >

                <button class="btn btn-primary">
                    <?= $edit ? "Update Product" : "Save Product" ?>
                </button>

                <?php if ($edit): ?>
                    <a class="btn btn-secondary" href="products.php">
                        Cancel
                    </a>
                <?php endif; ?>

            </form>

        </div>

    </div>

    <!-- Product Table -->
    <div class="col-lg-8">

        <div class="card p-3">

            <h5>Product Inventory</h5>

            <!-- Search -->
            <input
                type="text"
                id="productSearch"
                class="form-control mb-3"
                placeholder="Search product or category..."
                onkeyup="searchProducts()"
            >

            <div class="table-responsive">

                <table class="table table-hover align-middle"
                    id="productTable">

                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($products as $p): ?>

                            <?php
                            $quantity = (int)$p["stock"];

                            if ($quantity === 0) {
                                $status = "Out of Stock";
                                $badge = "bg-danger";
                            } elseif ($quantity <= 5) {
                                $status = "Low Stock";
                                $badge = "bg-warning text-dark";
                            } else {
                                $status = "In Stock";
                                $badge = "bg-success";
                            }
                            ?>

                            <tr>

                                <td><?= h($p["product_name"]) ?></td>

                                <td>
                                    <?= h($p["category_name"] ?? "—") ?>
                                </td>

                                <td><?= money($p["price"]) ?></td>

                                <td>
                                    <strong class="<?= $quantity <= 5
                                        ? 'text-danger' : '' ?>">
                                        <?= $quantity ?>
                                    </strong>
                                </td>

                                <td>
                                    <span class="badge <?= $badge ?>">
                                        <?= $status ?>
                                    </span>
                                </td>

                                <td class="text-nowrap">

                                    <a
                                        class="btn btn-sm btn-outline-primary"
                                        href="?edit=<?= (int)$p["id"] ?>"
                                    >
                                        Edit
                                    </a>

                                    <form
                                        class="d-inline"
                                        method="post"
                                        onsubmit="return confirm('Delete this product?')"
                                    >

                                        <input type="hidden" name="csrf"
                                            value="<?= h(csrf_token()) ?>">

                                        <input type="hidden" name="action"
                                            value="delete">

                                        <input type="hidden" name="id"
                                            value="<?= (int)$p["id"] ?>">

                                        <button class="btn btn-sm btn-outline-danger">
                                            Delete
                                        </button>

                                    </form>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                        <?php if (!$products): ?>

                            <tr>
                                <td colspan="6"
                                    class="text-center text-muted">
                                    No products added yet.
                                </td>
                            </tr>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>

<script>
function searchProducts() {
    const input = document.getElementById("productSearch");
    const filter = input.value.toLowerCase();
    const rows = document.querySelectorAll("#productTable tbody tr");

    rows.forEach(function(row) {
        const text = row.innerText.toLowerCase();
        row.style.display = text.includes(filter) ? "" : "none";
    });
}
</script>

<?php require __DIR__ . "/../includes/footer.php"; ?>
