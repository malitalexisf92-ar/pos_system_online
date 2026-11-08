
<?php

require_once __DIR__ . "/../includes/auth.php";

require_role("admin");

$error = "";
$success = "";

// Adjust this if your auth.php uses a different session structure.
$current_user_id = $_SESSION["user_id"]
    ?? $_SESSION["user"]["id"]
    ?? null;

$current_user_id = $current_user_id !== null
    ? (int)$current_user_id
    : null;

// Check whether at least one other admin exists.
function has_other_admin(PDO $pdo, int $user_id): bool
{
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM users
        WHERE role = 'admin' AND id != ?
    ");

    $stmt->execute([$user_id]);

    return (int)$stmt->fetchColumn() > 0;
}

// Handle POST actions
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    verify_csrf();

    $action = $_POST["action"] ?? "create";

    // CREATE USER
    if ($action === "create") {

        $name = trim($_POST["name"] ?? "");
        $username = trim($_POST["username"] ?? "");
        $password = $_POST["password"] ?? "";
        $role = $_POST["role"] ?? "cashier";

        if (
            $name === "" ||
            $username === "" ||
            strlen($password) < 8 ||
            !in_array($role, ["admin", "cashier"], true)
        ) {

            $error = "Complete all fields. Password must be at least 8 characters.";

        } else {

            try {

                $hashed_password = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                $stmt = $pdo->prepare("
                    INSERT INTO users (name, username, password, role)
                    VALUES (?, ?, ?, ?)
                ");

                $stmt->execute([
                    $name,
                    $username,
                    $hashed_password,
                    $role
                ]);

                redirect_to("users.php");

            } catch (PDOException $e) {

                $error = "Could not add user. The username may already be taken.";

            }
        }

    // UPDATE USER
    } elseif ($action === "update") {

        $id = (int)($_POST["id"] ?? 0);
        $name = trim($_POST["name"] ?? "");
        $username = trim($_POST["username"] ?? "");
        $password = $_POST["password"] ?? "";
        $role = $_POST["role"] ?? "cashier";

        if (
            $id <= 0 ||
            $name === "" ||
            $username === "" ||
            !in_array($role, ["admin", "cashier"], true)
        ) {

            $error = "Please provide valid user information.";

        } else {

            $stmt = $pdo->prepare("
                SELECT id, role
                FROM users
                WHERE id = ?
            ");

            $stmt->execute([$id]);
            $target_user = $stmt->fetch();

            if (!$target_user) {

                $error = "User not found.";

            } elseif (
                $current_user_id === $id &&
                $role !== "admin"
            ) {

                $error = "You cannot remove your own admin role.";

            } elseif (
                $target_user["role"] === "admin" &&
                $role !== "admin" &&
                !has_other_admin($pdo, $id)
            ) {

                $error = "You cannot demote the last administrator.";

            } elseif (
                $password !== "" &&
                strlen($password) < 8
            ) {

                $error = "Password must be at least 8 characters.";

            } else {

                try {

                    if ($password !== "") {

                        $hashed_password = password_hash(
                            $password,
                            PASSWORD_DEFAULT
                        );

                        $stmt = $pdo->prepare("
                            UPDATE users
                            SET name = ?,
                                username = ?,
                                password = ?,
                                role = ?
                            WHERE id = ?
                        ");

                        $stmt->execute([
                            $name,
                            $username,
                            $hashed_password,
                            $role,
                            $id
                        ]);

                    } else {

                        $stmt = $pdo->prepare("
                            UPDATE users
                            SET name = ?,
                                username = ?,
                                role = ?
                            WHERE id = ?
                        ");

                        $stmt->execute([
                            $name,
                            $username,
                            $role,
                            $id
                        ]);

                    }

                    redirect_to("users.php");

                } catch (PDOException $e) {

                    $error = "Could not update user. The username may already be taken.";

                }
            }
        }

    // DELETE USER
    } elseif ($action === "delete") {

        $id = (int)($_POST["id"] ?? 0);

        if ($id <= 0) {

            $error = "Invalid user.";

        } elseif ($current_user_id === $id) {

            $error = "You cannot delete your own account.";

        } else {

            $stmt = $pdo->prepare("
                SELECT id, role
                FROM users
                WHERE id = ?
            ");

            $stmt->execute([$id]);
            $target_user = $stmt->fetch();

            if (!$target_user) {

                $error = "User not found.";

            } elseif (
                $target_user["role"] === "admin" &&
                !has_other_admin($pdo, $id)
            ) {

                $error = "You cannot delete the last administrator.";

            } else {

                $stmt = $pdo->prepare("
                    DELETE FROM users
                    WHERE id = ?
                ");

                $stmt->execute([$id]);

                redirect_to("users.php");

            }
        }
    }
}

// Edit mode
$edit_user = null;

if (isset($_GET["edit"])) {

    $edit_id = (int)$_GET["edit"];

    $stmt = $pdo->prepare("
        SELECT id, name, username, role
        FROM users
        WHERE id = ?
    ");

    $stmt->execute([$edit_id]);
    $edit_user = $stmt->fetch();

    if (!$edit_user) {
        $error = "User not found.";
    }
}

// Get registered users
$users = $pdo->query("
    SELECT id, name, username, role, created_at
    FROM users
    ORDER BY id DESC
")->fetchAll();

$page_title = "Users";
$asset_prefix = "../";

require __DIR__ . "/../includes/header.php";

?>

<h2 class="mb-3">User Management</h2>

<div class="row g-3">

    <!-- Add / Edit User -->
    <div class="col-lg-4">

        <div class="card p-3">

            <h5>
                <?= $edit_user ? "Edit User" : "Add User" ?>
            </h5>

            <?php if ($error !== ""): ?>
                <div class="alert alert-danger">
                    <?= h($error) ?>
                </div>
            <?php endif; ?>

            <form method="post">

                <input
                    type="hidden"
                    name="csrf"
                    value="<?= h(csrf_token()) ?>"
                >

                <input
                    type="hidden"
                    name="action"
                    value="<?= $edit_user ? "update" : "create" ?>"
                >

                <?php if ($edit_user): ?>
                    <input
                        type="hidden"
                        name="id"
                        value="<?= (int)$edit_user["id"] ?>"
                    >
                <?php endif; ?>

                <label class="form-label">Full Name</label>

                <input
                    type="text"
                    class="form-control mb-2"
                    name="name"
                    value="<?= h($edit_user["name"] ?? "") ?>"
                    required
                >

                <label class="form-label">Username</label>

                <input
                    type="text"
                    class="form-control mb-2"
                    name="username"
                    value="<?= h($edit_user["username"] ?? "") ?>"
                    required
                >

                <label class="form-label">
                    Password
                    <?= $edit_user
                        ? "(Leave blank to keep current password)"
                        : "(8+ characters)" ?>
                </label>

                <input
                    type="password"
                    class="form-control mb-2"
                    name="password"
                    minlength="8"
                    <?= $edit_user ? "" : "required" ?>
                >

                <label class="form-label">Role</label>

                <select class="form-select mb-3" name="role" required>

                    <option
                        value="cashier"
                        <?= ($edit_user["role"] ?? "cashier") === "cashier"
                            ? "selected" : "" ?>
                    >
                        Cashier
                    </option>

                    <option
                        value="admin"
                        <?= ($edit_user["role"] ?? "") === "admin"
                            ? "selected" : "" ?>
                    >
                        Admin
                    </option>

                </select>

                <button type="submit" class="btn btn-primary">
                    <?= $edit_user ? "Save Changes" : "Create User" ?>
                </button>

                <?php if ($edit_user): ?>

                    <a href="users.php" class="btn btn-secondary">
                        Cancel
                    </a>

                <?php endif; ?>

            </form>

        </div>

    </div>

    <!-- Registered Users -->
    <div class="col-lg-8">

        <div class="card p-3">

            <h5>Registered Users</h5>

            <div class="table-responsive">

                <table class="table align-middle">

                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Date Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($users as $user): ?>

                            <tr>

                                <td><?= h($user["name"]) ?></td>

                                <td><?= h($user["username"]) ?></td>

                                <td>
                                    <?= h(ucfirst($user["role"])) ?>
                                </td>

                                <td><?= h($user["created_at"]) ?></td>

                                <td>
                                    <div class="d-flex gap-1">

                                        <a
                                            href="users.php?edit=<?= (int)$user["id"] ?>"
                                            class="btn btn-sm btn-warning"
                                        >
                                            Edit
                                        </a>

                                        <form
                                            method="post"
                                            onsubmit="return confirm('Are you sure you want to delete this user?');"
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
                                                value="<?= (int)$user["id"] ?>"
                                            >

                                            <button
                                                type="submit"
                                                class="btn btn-sm btn-danger"
                                            >
                                                Delete
                                            </button>

                                        </form>

                                    </div>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                        <?php if (empty($users)): ?>

                            <tr>
                                <td colspan="5" class="text-center text-muted">
                                    No users registered yet.
                                </td>
                            </tr>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>

<?php require __DIR__ . "/../includes/footer.php"; ?>
