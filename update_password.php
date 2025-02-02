<?php
session_set_cookie_params([
    'httponly' => true, // Prevent JavaScript access to session cookies
    'secure' => true,   // Ensures cookies are only sent over HTTPS
    'samesite' => 'Strict' // Protects against CSRF attacks
]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Regenerate session ID after login (Prevents fixation attacks)
if (!isset($_SESSION['INITIATED'])) {
    session_regenerate_id(true);
    $_SESSION['INITIATED'] = true;
}

// Bind session to IP address & User-Agent (Prevents session hijacking)
if (!isset($_SESSION['IP_ADDRESS'])) {
    $_SESSION['IP_ADDRESS'] = $_SERVER['REMOTE_ADDR'];
    $_SESSION['USER_AGENT'] = $_SERVER['HTTP_USER_AGENT'];
} elseif ($_SESSION['IP_ADDRESS'] !== $_SERVER['REMOTE_ADDR'] || $_SESSION['USER_AGENT'] !== $_SERVER['HTTP_USER_AGENT']) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}

require "config.php"; // Include database connection

//  Redirect if not logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION["user_id"];

//  Handle password update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $current_password = trim($_POST["current_password"]);
    $new_password = trim($_POST["new_password"]);
    $confirm_password = trim($_POST["confirm_password"]);

    //  Validate new password
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        echo "<script>alert('Error: All fields are required!'); window.history.back();</script>";
        exit();
    }

    if ($new_password !== $confirm_password) {
        echo "<script>alert('Error: New passwords do not match!'); window.history.back();</script>";
        exit();
    }

    if (!preg_match("/^(?=.*[A-Z])(?=.*\d)[A-Za-z\d@$!%*?&]{8,}$/", $new_password)) {
        echo "<script>alert('Error: Password must be at least 8 characters, include 1 uppercase letter, and 1 number!'); window.history.back();</script>";
        exit();
    }

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

    //  Get current password from database
    $query = "SELECT PasswordHash FROM users WHERE UserID = ?";
    $stmt = $con->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($stored_password);
    $stmt->fetch();

    //  Verify current password
    if (!password_verify($current_password, $stored_password)) {
        echo "<script>alert('Error: Current password is incorrect!'); window.history.back();</script>";
        exit();
    }

    //  Hash new password
    $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

    //  Update password in database
    $update_query = "UPDATE users SET PasswordHash = ? WHERE UserID = ?";
    $stmt = $con->prepare($update_query);
    $stmt->bind_param("si", $hashed_password, $user_id);

    if ($stmt->execute()) {
        echo "<script>alert('Password updated successfully!'); window.location.href='dashboard.php';</script>";
    } else {
        echo "<script>alert('Error: Could not update password!'); window.history.back();</script>";
    }

    $stmt->close();
    $con->close();
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Password</title>
    <link rel="stylesheet" href="style.css">
    
</head>
<body>

    <header>
        <h1>Update Password</h1>
    </header>

    <div class="container">
        <h2>Change Your Password</h2>
        <form action="update_password.php" method="POST">
            <label for="current_password">Current Password:</label>
            <input type="password" id="current_password" name="current_password" required>

            <label for="new_password">New Password:</label>
            <input type="password" id="new_password" name="new_password" required>

            <label for="confirm_password">Confirm New Password:</label>
            <input type="password" id="confirm_password" name="confirm_password" required>

            <button type="submit" class="btn">Update Password</button>
        </form>
        <p><a href="dashboard.php">Back to Dashboard</a></p>
    </div>

</body>
</html>