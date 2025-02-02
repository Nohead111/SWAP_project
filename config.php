<?php
$db_hostname="127.0.0.1";
$db_username="root";
$db_password="";

$db_database="amc_data";

// Generate CSRF token if not already set
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

