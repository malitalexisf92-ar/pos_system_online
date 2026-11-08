<?php

// Load authentication functions
require_once __DIR__ . "/includes/auth.php";


// Check if the user is logged in
if (empty($_SESSION["user"])) {

    // Redirect to login page
    redirect_to("login.php");

}


// Check the user's role
if ($_SESSION["user"]["role"] === "admin") {

    // Admin goes to the dashboard
    redirect_to("admin/dashboard.php");

}


// Cashier goes to the checkout page
redirect_to("cashier/checkout.php");

?>