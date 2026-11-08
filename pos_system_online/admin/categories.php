
<?php

require_once __DIR__ . "/../includes/auth.php";

require_role("admin");

// Add or delete category
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    verify_csrf();

    $action = $_POST["action"] ?? "";

    // Delete category
    if ($action == "delete") {

        $id = (int) $_POST["id"];

        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$id]);

    } else {

        // Add category
        $category_name = trim($_POST["category_name"] ?? "");

        if ($category_name != "") {

            $stmt = $pdo->prepare(
                "INSERT INTO categories (category_name) VALUES (?)"
            );

            $stmt->execute([$category_name]);
        }
    }

    redirect_to("categories.php");
}

// Get all categories and count their products
$sql = "
    SELECT c.*, COUNT(p.id) AS product_count
    FROM categories c
    LEFT JOIN products p ON c.id = p.category_id
    GROUP BY c.id
    ORDER BY c.category_name
";

$rows = $pdo->query($sql)->fetchAll();

$page_title = "Categories";
$asset_prefix = "../";

require __DIR__ . "/../includes/header.php";

?>

<h2 class="mb-3">Category Management</h2>

<div class="row g-3">

    <!-- Add Category Form -->
    <div class="col-md-4">

        <div class="card p-3">

            <h5>Add Category</h5>

            <form method="post">

                <input
                    type="hidden"
                    name="csrf"
                    value="<?= h(csrf_token()) ?>"
                >

                <input
                    type="text"
                    class="form-control mb-3"
                    name="category_name"
                    placeholder="Category name"
                    required
                >

                <button type="submit" class="btn btn-primary">
                    Add Category
                </button>

            </form>

        </div>

    </div>

    <!-- Category List -->
    <div class="col-md-8">

        <div class="card p-3">

            <h5>Category List</h5>

            <div class="table-responsive">

                <table class="table">

                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Products</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($rows as $row): ?>

                            <tr>

                                <td>
                                    <?= h($row["category_name"]) ?>
                                </td>

                                <td>
                                    <?= (int) $row["product_count"] ?>
                                </td>

                                <td>

                                    <form
                                        method="post"
                                        onsubmit="return confirm('Delete this category? Products will become uncategorized.')"
                                    >

                                        <input
                                            type="hidden"
                                            name="csrf"
                                            value="<?= h(csrf_token()) ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="delete"
                                        >

                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?= (int) $row["id"] ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="btn btn-sm btn-outline-danger"
                                        >
                                            Delete
                                        </button>

                                    </form>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                        <?php if (empty($rows)): ?>

                            <tr>
                                <td colspan="3" class="text-center text-muted">
                                    No categories yet.
                                </td>
                            </tr>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>

<?php

require __DIR__ . "/../includes/footer.php";

?>
