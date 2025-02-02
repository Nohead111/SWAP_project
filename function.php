<?php
// Retrieve and sanitize GET request input
function get_sanitized_input($key) {
    return isset($_GET[$key]) ? htmlspecialchars($_GET[$key], ENT_QUOTES, 'UTF-8') : '';
}

// Escape output to prevent stored XSS attacks
function sanitize_output($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

// Check if a record exists or is duplicated in a table
function check_record_count($connect, $column, $table, $type, $value) {
    $stmt = $connect->prepare("SELECT $column FROM $table WHERE $column=?");
    $stmt->bind_param($type, $value);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows;
}

// Set session message and redirect
function set_session_message($type, $message, $redirect = "index.php") {
    $_SESSION[$type] = $message;
    header("Location: $redirect");
    exit();
}
?>

