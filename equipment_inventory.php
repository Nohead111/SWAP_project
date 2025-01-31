<?php

$result = get_equipment();

function get_equipment(){

    require "config.php";
	
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


	$query="SELECT EquipmentID, Name, Description, SerialNumber, Status, PurchaseDate, LastServicedDate FROM equipment";
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
        <h1>Equipment Inventory</h1>
        <a href="logout.php" class="logout-btn">Logout</a>
    </header>

    <!-- Navigation -->
    <nav>
        <a href="index.html">Dashboard</a>
        <a href="projects.html">Projects</a>
        <a href="researchers.html">Researchers</a>
        <a href="equipment_inventory.php" class="active">Equipment Inventory</a>
    </nav>

    <!-- Main Content -->
    <main>
        <!-- Add Equipment Button -->
        <section id="inventory-header">
            <h2>Equipment List</h2>
            <a href="add_equipment.php" class="button">Add Equipment</a>
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
                                <td>
                                    <a href='edit_equipment.php?id={$row['EquipmentID']}' class='button'>Edit</a>
                                    <a href='#' class='button delete-button' onclick='confirmDelete({$row['EquipmentID']})'>Delete</a>
                                </td>
                            </tr>";
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