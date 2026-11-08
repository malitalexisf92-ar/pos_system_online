<?php

// Load authentication functions
require_once __DIR__ . "/includes/auth.php";


// If the user is already logged in, go to the main page
if (!empty($_SESSION["user"])) {

    redirect_to("index.php");

}


// Store login error message
$error = "";


// Check if the login form was submitted
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Verify the security token
    verify_csrf();


    // Get username and password
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";


    // Find the account using the username
    $stmt = $pdo->prepare(
        "SELECT id, name, username, password, role
         FROM users
         WHERE username = ?"
    );

    $stmt->execute([$username]);

    $account = $stmt->fetch();


    // Check if the account exists and password is correct
    if ($account && password_verify($password, $account["password"])) {

        // Create a new session ID after login
        session_regenerate_id(true);


        // Remove password from account information
        unset($account["password"]);


        // Store user information in the session
        $_SESSION["user"] = $account;


        // Redirect to the correct page
        redirect_to("index.php");

    } else {

        // Display an error message
        $error = "Incorrect username or password.";

    }

}


// Set page title
$page_title = "Login";
$asset_prefix = "";


// Load the page header
require __DIR__ . "/includes/header.php";

?>

<!-- Login Form -->
<div class="login-wrap">

    <div class="card p-4">

        <h2 class="mb-1">POS Login</h2>

        <p class="text-muted">
            Sign in to continue.
        </p>


        <!-- Display login error -->
        <?php if ($error): ?>

            <div class="alert alert-danger">
                <?= h($error) ?>
            </div>

        <?php endif; ?>


        <!-- Login Form -->
        <form method="post">

            <!-- CSRF Security Token -->
            <input
                type="hidden"
                name="csrf"
                value="<?= h(csrf_token()) ?>"
            >


            <!-- Username -->
            <div class="mb-3">

                <label class="form-label">
                    Username
                </label>

                <input
                    class="form-control"
                    type="text"
                    name="username"
                    required
                    autofocus
                >

            </div>


            <!-- Password -->
            <div class="mb-3">

                <label class="form-label">
                    Password
                </label>

                <input
                    class="form-control"
                    type="password"
                    name="password"
                    required
                >

            </div>


            <!-- Login Button -->
            <button class="btn btn-primary w-100">
                Login
            </button>

        </form>


        <!-- Initial Admin Setup Link -->
        <p class="small text-muted mt-3 mb-0">

            First time?

            <a href="setup_admin.php">
                Run initial admin setup
            </a>.

        </p>

    </div>

</div>


<?php

// Load the page footer
require __DIR__ . "/includes/footer.php";

?>