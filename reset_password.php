<?php
require "config.php"; // Include database connection

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $token = $_POST["token"];
    $new_password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];

    // Validate inputs
    if (empty($new_password) || empty($confirm_password)) {
        echo "<script>alert('Error: Please enter and confirm your new password!'); window.history.back();</script>";
        exit;
    }

    if ($new_password !== $confirm_password) {
        echo "<script>alert('Error: Passwords do not match!'); window.history.back();</script>";
        exit;
    }

    // Validate token
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

    $query = "SELECT email, expires_at FROM password_reset_tokens WHERE token = ?";
    $stmt = $con->prepare($query);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo "<script>alert('Error: Invalid or expired token!'); window.location.href='forgot_password.php';</script>";
        exit;
    }

    $row = $result->fetch_assoc();
    $email = $row["email"];
    $expires_at = strtotime($row["expires_at"]);

    // Check if token is expired
    if (time() > $expires_at) {
        echo "<script>alert('Error: Token has expired! Please request a new reset link.'); window.location.href='forgot_password.php';</script>";
        exit;
    }

    // Hash new password
    $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

    // Update user's password in the users table
    $update_query = "UPDATE users SET PasswordHash = ? WHERE Username = ?";
    $stmt = $con->prepare($update_query);
    $stmt->bind_param("ss", $hashed_password, $email);
    
    if ($stmt->execute()) {
        // Delete used token
        $delete_query = "DELETE FROM password_reset_tokens WHERE token = ?";
        $stmt = $con->prepare($delete_query);
        $stmt->bind_param("s", $token);
        $stmt->execute();

        echo "<script>alert('Password reset successful! You can now log in with your new password.'); window.location.href='login.php';</script>";
    } else {
        echo "<script>alert('Error: Unable to update password. Please try again!'); window.history.back();</script>";
    }

    $stmt->close();
    $con->close();
} else {
    // If no POST request, check for token in URL
    if (!isset($_GET["token"])) {
        echo "<script>alert('Invalid request. Please use the link from your email.'); window.location.href='forgot_password.php';</script>";
        exit;
    }
    $token = $_GET["token"];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1>Reset Your Password</h1>
    </header>

    <main>
        <section id="reset-password">
            <h2>Enter a New Password</h2>
            <form action="reset_password.php" method="POST">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token); ?>">

                <label for="password">New Password:</label>
                <input type="password" id="password" name="password" required>

                <label for="confirm_password">Confirm Password:</label>
                <input type="password" id="confirm_password" name="confirm_password" required>

                <button type="submit">Reset Password</button>
            </form>
            <p><a href="login.php">Back to Login</a></p>
        </section>
    </main>

    <footer>
        <p>&copy; 2024 AMC Corporation. All Rights Reserved.</p>
    </footer>
</body>
</html>
