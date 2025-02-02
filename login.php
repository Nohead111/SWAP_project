<?php
session_start();
require "config.php"; // Include database connection

// Check if the user is already logged in
if (isset($_SESSION["user_id"])) {
    header("Location: dashboard.php");
    exit();
}


// Handle login form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo "<script>alert('CSRF validation failed!'); window.history.back();</script>";
        exit();
    }

    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);

    // Validate input
    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {
        // Retrieve user from database
        function printerror($message, $con) {
            //echo "<pre>";
            //echo "$message<br>";
            if ($con) echo "FAILED: ". mysqli_error($con). "<br>";
            //echo "</pre>";
        }
        
        function printok($message) {
            //echo "<pre>";
            //echo "$message<br>";
            //echo "OK<br>";
            //echo "</pre>";
        }
        
        try {
        $con=mysqli_connect($db_hostname,$db_username,$db_password);
        }
        catch (Exception $e) {
            printerror($e->getMessage(),$con);
        }
        if (!$con) {
            printerror("Connecting to $db_hostname", $con);
            die();
        }
        else printok("Connecting to $db_hostname");
        
        $result=mysqli_select_db($con, $db_database);
        if (!$result) {
            printerror("Selecting $db_database",$con);
            die();
        }
        else printok("Selecting $db_database");
        
        $query = "SELECT UserID, Username, PasswordHash, RoleID FROM users WHERE Username = ?";
        $stmt = $con->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            // Verify hashed password
            if (password_verify($password, $user["PasswordHash"])) {
                // Set session variables
                $_SESSION["user_id"] = $user["UserID"];
                $_SESSION["user_name"] = $user["Username"];
                $_SESSION["user_role"] = $user["RoleID"];

                // Redirect user based on role
                if ($user["RoleID"] == 1) {
                    header("Location: dashboard.php");
                } else {
                    header("Location: dashboard.php");
                }
                exit();
            } else {
                $error = "Invalid email or password.";
            }
        } else {
            $error = "Invalid email or password.";
        }
        
        $stmt->close();
        $con->close();
    }
}
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Header -->
    <header>
        <h1>Login to Secure AMC Research Management System</h1>
    </header>

    <!-- Login Form -->
    <main>
        <section id="login-form">
            <h2>Sign In</h2>
            <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>

            <form action="login.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>

                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>

                <button type="submit">Login</button>
            </form>

            <p><a href="register.php">Forgot Password?</a></p>
        </section>
    </main>

    <!-- Footer -->
    <footer>
        <p>&copy; 2024 AMC Corporation. All Rights Reserved.</p>
    </footer>
</body>
</html>
