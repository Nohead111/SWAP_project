<?php
session_start();

// ✅ Redirect to login if not logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

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
    <title>Equipment Inventory</title>
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
