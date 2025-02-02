<?php
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
    
$createdBy = $_SESSION['user_name'];

$user_role = $_SESSION["user_role"]; // Get logged-in user's role


$result = get_equipment();

function get_equipment(){


    require "config.php";
    $createdBy = $_SESSION['user_name'];

    $user_role = $_SESSION["user_role"]; // Get logged-in user's role

	function printerror($message, $con) {
		//echo "<pre>";
		//echo "$message<br>";
		if ($con) echo "FAILED: ". mysqli_error($con). "<br>";
		//echo "</pre>";
	}

	function printok($message) {
		//echo "<pre>";
		//echo "$message<br>";
		///echo "OK<br>";
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

    $query="SELECT EquipmentID, Name, Description, SerialNumber, Status, PurchaseDate, LastServicedDate , CreatedBy FROM equipment";
    if ($user_role == 3) {
	    $query="SELECT EquipmentID, Name, Description, SerialNumber, Status, PurchaseDate, LastServicedDate , CreatedBy FROM equipment 
        WHERE CreatedBy = '$createdBy'";
        //echo "$query<br>";
    } else {
        $query="SELECT EquipmentID, Name, Description, SerialNumber, Status, PurchaseDate, LastServicedDate , CreatedBy FROM equipment";
    }

	$result=mysqli_query($con,$query);
	if (!$result) {
		printerror("Selecting $db_database",$con);
		die();
	}
	else {
        printok($query);
        mysqli_close($con);
        printok("Closing connection");
        return $result;
    }

}


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
        <!-- Add Equipment Button -->
        <section id="inventory-header">
            <h2>Equipment List</h2>
            <?php if ($user_role == 1 || $user_role == 3) { ?> 
                <a href="add_equipment.php" class="button">Add Equipment</a>
            <?php } ?>
           
        </section>

        <!-- Equipment Table -->
        <section id="equipment-list">
            <table>
                <thead>
                    <tr>
                        <th>EquipmentID</th>
                        <th>Name</th>
                        <th>Description</th>
                        <th>SerialNumber</th>
                        <th>Status</th>
                        <th>PurchaseDate</th>
                        <th>LastServicedDate</th>
                        <th>CreatedBy</th>
                        <th>Actions</th>

                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>
                                <td>{$row['EquipmentID']}</td>
                                <td>{$row['Name']}</td>
                                <td>{$row['Description']}</td>
                                <td>{$row['SerialNumber']}</td>
                                <td>{$row['Status']}</td>
                                <td>{$row['PurchaseDate']}</td>
                                <td>{$row['LastServicedDate']}</td>
                                <td>{$row['CreatedBy']}</td>
                                <td>";
                                if ($user_role == 1 || $user_role == 3 ) { // Only Admins see Edit button
                                    echo "<a href='edit_equipment.php?id={$row['EquipmentID']}' class='button'>Edit</a>";
                                    if ($user_role == 1 ) { // Only Admins see Edit button                           
                                        echo "<a href='#' class='button delete-button' onclick='confirmDelete({$row['EquipmentID']})'>Delete</a>";
                                    }
                                  
                                }
                                
                                
                                echo "</td></tr>";
                        }
                    } else {
                        echo "<tr><td colspan='8'>No equipment found.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </section>
    </main>

    <!-- Footer -->
    <footer>
        <p>&copy; 2024 AMC Corporation. All Rights Reserved. <a href="#contact">Contact Us</a></p>
    </footer>

    <script>
        function confirmDelete(id) {
            if (confirm("Are you sure you want to delete this equipment?")) {
                window.location.href = `delete_equipment.php?id=${id}`;
            }
        }
    </script>
</body>
</html>
<?php


?>