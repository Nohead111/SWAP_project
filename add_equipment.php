<?php
require "config.php"; // Database connection

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

if ($_SESSION["user_role"] == 2 ) {
    echo "<script>alert('Access Denied: Only Admins and Research Assistants can add equipment!'); window.location.href='equipment_inventory.php';</script>";
    exit();
}
$createdBy = $_SESSION['user_name'];

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        //echo "<script>alert('CSRF validation failed!'); window.history.back();</script>";
        //exit();
    }


    // Retrieve and sanitize input values
    $name = htmlspecialchars(trim($_POST['name']));
    $description = htmlspecialchars(trim($_POST['description']));
    $serialNumber = htmlspecialchars(trim($_POST['serialNumber']));
    $status = htmlspecialchars(trim($_POST['status']));
    $purchaseDate = htmlspecialchars(trim($_POST['purchaseDate']));
    $lastServicedDate = htmlspecialchars(trim($_POST['lastServicedDate']));

    
    // Validate required fields
    if (empty($name) || empty($serialNumber) || empty($status)) {
        echo "<script>alert('Error: Name, Serial Number, and Status are required fields!'); window.history.back();</script>";
        exit;
    }
    if (!preg_match("/^[a-zA-Z0-9\s\-]+$/", $name)) {
        echo "<script>alert('Error: Invalid equipment name!'); window.history.back();</script>";
        exit();
    }
    if (!preg_match("/^[a-zA-Z0-9\-]+$/", $serialNumber)) {
        echo "<script>alert('Error: Invalid serial number!'); window.history.back();</script>";
        exit();
    }

    // Check for duplicate serial number
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
	
    $checkQuery = "SELECT SerialNumber FROM equipment WHERE SerialNumber = ?";
    $stmt = $con->prepare($checkQuery);
    $stmt->bind_param("s", $serialNumber);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        echo "<script>alert('Error: Equipment with this Serial Number already exists!'); window.history.back();</script>";
        exit;
    }
    $stmt->close();


    // Insert into database using prepared statement
    $insertQuery = "INSERT INTO equipment (Name, Description, SerialNumber, Status, PurchaseDate, LastServicedDate, CreatedBy) 
                    VALUES (?, ?, ?, ?, ?, ?,?)";
    
    $stmt = $con->prepare($insertQuery);
    $stmt->bind_param("sssssss", $name, $description, $serialNumber, $status, $purchaseDate, $lastServicedDate, $createdBy);

    if ($stmt->execute()) {
        echo "<script>alert('Equipment added successfully!'); window.location.href='equipment_inventory.php';</script>";
    } else {
        echo "<script>alert('Error: Unable to add equipment. Please try again!'); window.history.back();</script>";
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
    <title>Secure AMC Research Management System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Header -->
    <header>
        <h1>Secure AMC Research Management System</h1>
    </header>

   <!-- Navigation -->
   <nav>
    <a href="dashboard.php">Dashboard</a>
        <a href="projects.php">Projects</a>
        <a href="researcher_profile.php">Researchers</a>
        <a href="equipment_inventory.php" class="active">Equipment Inventory</a>
        
</nav>
    <!-- Main Content -->
    <main>
        <!-- Form Section -->
        <section id="add-equipment">
            <h2>Add New Equipment</h2>
            <form action="add_equipment.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">
                <label for="name">Equipment Name:</label>
                <input type="text" id="name" name="name" required>

                <label for="description">Description:</label>
                <textarea id="description" name="description" rows="4"></textarea>

                <label for="serialNumber">Serial Number:</label>
                <input type="text" id="serialNumber" name="serialNumber" required>

                <label for="status">Status:</label>
                <select id="status" name="status" required>
                    <option value="Available">Available</option>
                    <option value="In Use">In Use</option>
                    <option value="Maintenance">Maintenance</option>
                    <option value="Decommissioned">Decommissioned</option>
                </select>

                <label for="purchaseDate">Purchase Date:</label>
                <input type="date" id="purchaseDate" name="purchaseDate">

                <label for="lastServicedDate">Last Serviced Date:</label>
                <input type="date" id="lastServicedDate" name="lastServicedDate">

                <button type="submit">Add Equipment</button>
            </form>
        </section>

       
    </main>

    <!-- Footer -->
    <footer>
        <p>&copy; 2024 AMC Corporation. All Rights Reserved. <a href="#contact">Contact Us</a></p>
    </footer>
</body>
</html>
