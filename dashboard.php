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



// ✅ Redirect to login if not logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}
$session_timeout = 300; // 5 minutes

// Check if the session has timed out
    if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $session_timeout) {
        session_unset();
        session_destroy();
        header("Location: login.php?timeout=true"); // Redirect with timeout parameter
        exit();
    }
    $_SESSION['LAST_ACTIVITY'] = time();

// ✅ Get user details
$user_name = $_SESSION['user_name'];

$user_role = $_SESSION["user_role"]; // Get logged-in user's role


// ✅ Role Mapping (Optional Display)
$roleName = ($user_role == 1) ? "Admin" : (($user_role == 3) ? "Research Assistant" : "Researcher");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Equipment Inventory</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Header -->
    <header>
        <h1>AMC Research Management System</h1>
        <a href="logout.php" class="logout-btn">Logout</a>
    </header>
 
    <!-- Navigation -->
    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="projects.php">Projects</a>
        <a href="researcher_profile.php">Researchers</a>
        <a href="equipment_inventory.php" class="active">Equipment Inventory</a>
        <a href="update_password.php">Change Password</a>
    </nav>

    <!-- Dashboard Content -->
    <div class="dashboard-container">
        <div class="welcome-box">
            <h2>Welcome, <?= htmlspecialchars($user_name); ?>!</h2>
            <p>You are logged in as <strong><?= $roleName; ?></strong>.</p>
           
        </div>

    </div>

    <!-- Footer -->
    <footer>
        <p>&copy; 2024 AMC Corporation. All Rights Reserved.</p>
    </footer>

</body>
</html>
