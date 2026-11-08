<?php

// InfinityFree database settings
$host = "sql303.infinityfree.com";

$dbname = "if0_43383923_pos_system";

$username = "if0_43383923";

// Enter your actual vPanel password here.
// Do not share this password.
$password = "ALEXIS1216malit";

try {

    // Connect to the online MySQL database
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );

} catch (PDOException $e) {

    // Save database error details in the server log
    error_log($e->getMessage());

    http_response_code(500);

    exit("Database connection failed. Please check the database settings.");

}

?>