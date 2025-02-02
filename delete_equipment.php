<?php
require "config.php"; // Include database connection
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
