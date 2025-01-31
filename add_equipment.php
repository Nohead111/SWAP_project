<?php
require "config.php"; // Database connection

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Retrieve and sanitize input values
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $serialNumber = trim($_POST['serialNumber']);
    $status = trim($_POST['status']);
    $purchaseDate = trim($_POST['purchaseDate']);
    $lastServicedDate = trim($_POST['lastServicedDate']);

    // Validate required fields
    if (empty($name) || empty($serialNumber) || empty($status)) {
        echo "<script>alert('Error: Name, Serial Number, and Status are required fields!'); window.history.back();</script>";
        exit;
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
    $insertQuery = "INSERT INTO equipment (Name, Description, SerialNumber, Status, PurchaseDate, LastServicedDate) 
                    VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = $con->prepare($insertQuery);
    $stmt->bind_param("ssssss", $name, $description, $serialNumber, $status, $purchaseDate, $lastServicedDate);

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
    <a href="index.html">Dashboard</a>
    <a href="projects.html">Projects</a>
    <a href="researchers.html">Researchers</a>
    <a href="equipment_inventory.php" class="active">Equipment Inventory</a> <!-- Updated hyperlink -->
</nav>
    <!-- Main Content -->
    <main>
        <!-- Form Section -->
        <section id="add-equipment">
            <h2>Add New Equipment</h2>
            <form action="add_equipment.php" method="POST">
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
