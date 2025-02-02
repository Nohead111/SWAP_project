<?php
require "config.php"; // Include database connection
session_start();
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

// Check if an EquipmentID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Invalid request. No Equipment ID provided.");
}

if ($_SESSION["user_role"] == 2 ) {
    echo "<script>alert('Access Denied: Only Admins and Research Assistants can edit equipment!'); window.location.href='equipment_inventory.php';</script>";
    exit();
}

$equipmentID = intval($_GET['id']); // Ensure ID is an integer

// Fetch existing equipment details
function printerror($message, $con) {
    //echo "<pre>";
    //echo "$message<br>";
    if ($con) echo "FAILED: ". mysqli_error($con). "<br>";
    //echo "</pre>";
}

function printok($message) {
    //echo "<pre>";
   // echo "$message<br>";
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
$query = "SELECT * FROM equipment WHERE EquipmentID = ?";
$stmt = $con->prepare($query);
$stmt->bind_param("i", $equipmentID);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Equipment not found.");
}

$equipment = $result->fetch_assoc();
$stmt->close();

// Handle form submission for updating equipment
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = htmlspecialchars(trim($_POST['name']));
    $description = htmlspecialchars(trim($_POST['description']));
    $serialNumber = htmlspecialchars(trim($_POST['serialNumber']));
    $status = htmlspecialchars(trim($_POST['status']));
    $purchaseDate = htmlspecialchars(trim($_POST['purchaseDate']));
    $lastServicedDate = htmlspecialchars(trim($_POST['lastServicedDate']));

    //  Validate Required Fields
    if (empty($name) || empty($serialNumber) || empty($status)) {
        echo "<script>alert('Error: Name, Serial Number, and Status are required!'); window.history.back();</script>";
        exit();
    }

     //  Validate Inputs (Prevent SQL Injection & XSS)
     if (!preg_match("/^[a-zA-Z0-9\s\-]+$/", $name)) {
        echo "<script>alert('Error: Invalid equipment name!'); window.history.back();</script>";
        exit();
    }
    if (!preg_match("/^[a-zA-Z0-9\-]+$/", $serialNumber)) {
        echo "<script>alert('Error: Invalid serial number!'); window.history.back();</script>";
        exit();
    }

    // Update query with prepared statements
    $updateQuery = "UPDATE equipment 
                    SET Name = ?, Description = ?, SerialNumber = ?, Status = ?, PurchaseDate = ?, LastServicedDate = ?
                    WHERE EquipmentID = ?";
    $stmt = $con->prepare($updateQuery);
    $stmt->bind_param("ssssssi", $name, $description, $serialNumber, $status, $purchaseDate, $lastServicedDate, $equipmentID);

    if ($stmt->execute()) {
        echo "<script>alert('Equipment updated successfully!'); window.location.href='equipment_inventory.php';</script>";
    } else {
        echo "Error updating equipment: " . $conn->error;
    }
    $stmt->close();
}

$con->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Equipment</title>
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

    <!-- Main Content -->
    <main>
        <section id="edit-equipment">
            <h2>Edit Equipment Details</h2>
            <form method="POST">
                <label for="name">Equipment Name:</label>
                <input type="text" id="name" name="name" value="<?= htmlspecialchars($equipment['Name']); ?>" required>

                <label for="description">Description:</label>
                <textarea id="description" name="description" rows="4"><?= htmlspecialchars($equipment['Description']); ?></textarea>

                <label for="serialNumber">Serial Number:</label>
                <input type="text" id="serialNumber" name="serialNumber" value="<?= htmlspecialchars($equipment['SerialNumber']); ?>" required>

                <label for="status">Status:</label>
                <select id="status" name="status" required>
                    <option value="Available" <?= ($equipment['Status'] == 'Available') ? 'selected' : ''; ?>>Available</option>
                    <option value="In Use" <?= ($equipment['Status'] == 'In Use') ? 'selected' : ''; ?>>In Use</option>
                    <option value="Maintenance" <?= ($equipment['Status'] == 'Maintenance') ? 'selected' : ''; ?>>Maintenance</option>
                    <option value="Decommissioned" <?= ($equipment['Status'] == 'Decommissioned') ? 'selected' : ''; ?>>Decommissioned</option>
                </select>

                <label for="purchaseDate">Purchase Date:</label>
                <input type="date" id="purchaseDate" name="purchaseDate" value="<?= $equipment['PurchaseDate']; ?>">

                <label for="lastServicedDate">Last Serviced Date:</label>
                <input type="date" id="lastServicedDate" name="lastServicedDate" value="<?= $equipment['LastServicedDate']; ?>">

                <button type="submit">Update Equipment</button>
            </form>
        </section>
    </main>

    <!-- Footer -->
    <footer>
        <p>&copy; 2024 AMC Corporation. All Rights Reserved. <a href="#contact">Contact Us</a></p>
    </footer>
</body>
</html>
