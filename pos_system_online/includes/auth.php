<?php

// Start the session if it has not started yet
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Connect to the database
require_once __DIR__ . "/../config/db.php";


// Check if the user is logged in
function require_login(): void {

    if (empty($_SESSION["user"])) {

        // Return to the login page
        header("Location: ../login.php");
        exit;

    }

}


// Check if the user has the correct role
function require_role(string $role): void {

    // Make sure the user is logged in first
    require_login();

    // Check the user's role
    if (($_SESSION["user"]["role"] ?? "") !== $role) {

        http_response_code(403);
        exit("Access denied.");

    }

}


// Protect output from HTML injection
function h($value): string {

    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        "UTF-8"
    );

}


// Format numbers as Philippine pesos
function money($amount): string {

    return "₱" . number_format((float)$amount, 2);

}


// Generate a CSRF security token
function csrf_token(): string {

    if (empty($_SESSION["csrf"])) {

        $_SESSION["csrf"] = bin2hex(random_bytes(32));

    }

    return $_SESSION["csrf"];

}


// Check the CSRF token when a form is submitted
function verify_csrf(): void {

    if ($_SERVER["REQUEST_METHOD"] === "POST") {

        $token = $_POST["csrf"] ?? "";

        if (!hash_equals($_SESSION["csrf"] ?? "", $token)) {

            http_response_code(419);

            exit("Invalid form token. Please go back and try again.");

        }

    }

}


// Redirect the user to another page
function redirect_to(string $path): void {

    header("Location: " . $path);
    exit;

}

?>