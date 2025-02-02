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

$equipmentID = intval($_GET['id']); // Ensure ID is an integer

// Prepare delete query
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

$query = "DELETE FROM equipment WHERE EquipmentID = ?";
$stmt = $con->prepare($query);
$stmt->bind_param("i", $equipmentID);

if ($stmt->execute()) {
    echo "<script>alert('Equipment deleted successfully!'); window.location.href='equipment_inventory.php';</script>";
} else {
    echo "Error deleting equipment: " . $con->error;
}

$stmt->close();
$con->close();
?>
