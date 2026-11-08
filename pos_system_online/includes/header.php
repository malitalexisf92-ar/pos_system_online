<?php

// Load authentication functions
require_once __DIR__ . "/auth.php";

// Set the page title
$page_title = $page_title ?? "POS System";

// Get the logged-in user's information
$user = $_SESSION["user"] ?? null;

?>

<!doctype html>
<html lang="en">

<head>

    <meta charset="utf-8">

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title><?= h($page_title) ?> | POS System</title>

    <!-- Bootstrap CSS -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- Custom CSS -->
    <link
        href="<?= h($asset_prefix ?? "") ?>assets/css/style.css"
        rel="stylesheet"
    >

</head>

<body>

    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">

        <div class="container-fluid">

            <!-- Website Name -->
            <a
                class="navbar-brand fw-bold"
                href="<?= h($home_link ?? "index.php") ?>"
            >
                POS Management System
            </a>

            <!-- Mobile Navigation Button -->
            <button
                class="navbar-toggler"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#nav"
            >

                <span class="navbar-toggler-icon"></span>

            </button>

            <!-- Navigation Links -->
            <div class="collapse navbar-collapse" id="nav">

                <?php if ($user): ?>

                    <ul class="navbar-nav me-auto">

                        <?php if ($user["role"] === "admin"): ?>

                            <!-- Admin Navigation -->
                            <li class="nav-item">
                                <a
                                    class="nav-link"
                                    href="<?= h($asset_prefix ?? "") ?>admin/dashboard.php"
                                >
                                    Dashboard
                                </a>
                            </li>

                            <li class="nav-item">
                                <a
                                    class="nav-link"
                                    href="<?= h($asset_prefix ?? "") ?>admin/products.php"
                                >
                                    Products
                                </a>
                            </li>

                            <li class="nav-item">
                                <a
                                    class="nav-link"
                                    href="<?= h($asset_prefix ?? "") ?>admin/categories.php"
                                >
                                    Categories
                                </a>
                            </li>

                            <li class="nav-item">
                                <a
                                    class="nav-link"
                                    href="<?= h($asset_prefix ?? "") ?>admin/users.php"
                                >
                                    Users
                                </a>
                            </li>

                            <li class="nav-item">
                                <a
                                    class="nav-link"
                                    href="<?= h($asset_prefix ?? "") ?>admin/sales.php"
                                >
                                    Sales
                                </a>
                            </li>

                        <?php else: ?>

                            <!-- Cashier Navigation -->
                            <li class="nav-item">
                                <a
                                    class="nav-link"
                                    href="<?= h($asset_prefix ?? "") ?>cashier/checkout.php"
                                >
                                    Checkout
                                </a>
                            </li>

                            <li class="nav-item">
                                <a
                                    class="nav-link"
                                    href="<?= h($asset_prefix ?? "") ?>cashier/sales.php"
                                >
                                    My Sales
                                </a>
                            </li>

                        <?php endif; ?>

                    </ul>

                    <!-- Logged-in User Information -->
                    <span class="navbar-text me-3">

                        <?= h($user["name"]) ?>

                        (<?= h(ucfirst($user["role"])) ?>)

                    </span>

                    <!-- Logout Button -->
                    <a
                        class="btn btn-outline-light btn-sm"
                        href="<?= h($asset_prefix ?? "") ?>logout.php"
                    >
                        Logout
                    </a>

                <?php endif; ?>

            </div>

        </div>

    </nav>

    <!-- Main Page Content -->
    <main class="container-fluid py-4">