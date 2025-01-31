<?php
require "config.php"; // Include database connection

// Role mapping: Ensure this matches your Role table
$roleMapping = [
    "Researcher" => 2,
    "Research Assistant" => 3
    
];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);
    $role = trim($_POST["role"]);

    // Validate input
    if (empty($email) || empty($role)) {
        echo "<script>alert('Error: Please enter your email and select a role!'); window.history.back();</script>";
        exit;
    }

    // Validate role
    if (!array_key_exists($role, $roleMapping)) {
        echo "<script>alert('Error: Invalid role selected!'); window.history.back();</script>";
        exit;
    }

    $roleID = $roleMapping[$role]; // Convert role name to RoleID

    // Check if email already exists
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
	
   

    $query = "SELECT UserID FROM users WHERE Username = ?";
    $stmt = $con->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        // Email does not exist, create a new user account
        $defaultPassword = "defaultPassword"; 

        // Insert new user into users table with email as username
        $insertUserQuery = "INSERT INTO users (Username, PasswordHash, RoleID, CreatedAt) VALUES (?, ?, ?, NOW())";
        $stmt = $con->prepare($insertUserQuery);
        $stmt->bind_param("ssi", $email, $defaultPassword, $roleID);
        $stmt->execute();
    }
    $stmt->close();

    // Generate a secure reset token
    $token = bin2hex(random_bytes(32));
    $expires_at = date("Y-m-d H:i:s", strtotime("+1 hour")); // Token valid for 1 hour

    // Store token in password_reset_tokens table
    $insertTokenQuery = "INSERT INTO password_reset_tokens (email, token, expires_at) VALUES (?, ?, ?)";
    $stmt = $con->prepare($insertTokenQuery);
    $stmt->bind_param("sss", $email, $token, $expires_at);
    $stmt->execute();
    $stmt->close();

    // Send password setup link via email
    $setup_link = "http://yourwebsite.com/reset_password.php?token=$token";
    $subject = "Set Up Your Password";
    $message = "Welcome to AMC Research System!\n\nClick the following link to set up your password: $setup_link \n\nThis link is valid for 1 hour.";
    $headers = "From: noreply@yourwebsite.com";

    mail($email, $subject, $message, $headers);

    echo "<script>alert('A password setup link has been sent to your email.'); window.location.href='login.php';</script>";
    $con->close();
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set Up Password</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1>Set Up Your Password</h1>
    </header>

    <main>
        <section id="register">
            <h2>First-Time Login</h2>
            <form action="register.php" method="POST">
                <label for="email">Enter Your Email:</label>
                <input type="email" id="email" name="email" required>

                <label for="role">Select Your Role:</label>
                <select id="role" name="role" required>
                    <option value="">-- Select Role --</option>
                    <option value="Researcher">Researcher</option>
                    <option value="Research Assistant">Research Assistant</option>
                    
                </select>

                <button type="submit">Send Setup Link</button>
            </form>
            <p><a href="login.php">Back to Login</a></p>
        </section>
    </main>

    <footer>
        <p>&copy; 2024 AMC Corporation. All Rights Reserved.</p>
    </footer>
</body>
</html>
