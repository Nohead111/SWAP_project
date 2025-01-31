<?php
// Start the session
session_start();

// Check CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("CSRF token validation failed.");
}

// Set session timeout (5 minutes)
$session_timeout = 300;
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $session_timeout) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}
$_SESSION['LAST_ACTIVITY'] = time();


// Database connection
$conn = new mysqli("localhost", "root", "", "swap_project");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Process form submission
$project_id = filter_input(INPUT_POST, 'project_id', FILTER_VALIDATE_INT);
$project_name = filter_input(INPUT_POST, 'project_name', FILTER_SANITIZE_STRING);
$description = htmlspecialchars($_POST['description'], ENT_QUOTES, 'UTF-8');
$funding = filter_input(INPUT_POST, 'funding', FILTER_VALIDATE_FLOAT);
$start_date = filter_input(INPUT_POST, 'start_date', FILTER_SANITIZE_STRING);
$assigned_team = $_POST['assigned_team'];

if (!$project_id || empty($project_name) || empty($description) || $funding === false || empty($start_date)) {
    die("Invalid input data.");
}

// Ensure funding is a positive number
if ($funding < 0) {
    die("Funding amount must be a positive value.");
}

// Update project details
$conn->query("DELETE FROM Researcher_Project WHERE ProjectID = $project_id");

$team_members = explode(',', $assigned_team);
foreach ($team_members as $user_id) {
    $user_id = trim($user_id);

    // Check if the user exists in the Researchers table
    $check_stmt = $conn->prepare("SELECT COUNT(*) FROM Researchers WHERE UserID = ?");
    $check_stmt->bind_param("i", $user_id);
    $check_stmt->execute();
    $check_stmt->bind_result($exists);
    $check_stmt->fetch();
    $check_stmt->close();

    if ($exists > 0) {
        $stmt = $conn->prepare("INSERT INTO Researcher_Project (ResearcherID, ProjectID) 
                               VALUES ((SELECT ResearcherID FROM Researchers WHERE UserID = ?), ?)");
        $stmt->bind_param("ii", $user_id, $project_id);
        $stmt->execute();
        $stmt->close();
    }
}

// Insert updated team members if they exist
if (!empty($assigned_team)) {
    $team_members = explode(',', $assigned_team);
    foreach ($team_members as $user_id) {
        $user_id = trim($user_id);

        // Check if the user exists in the Researchers table
        $check_stmt = $conn->prepare("SELECT COUNT(*) FROM Researchers WHERE UserID = ?");
        $check_stmt->bind_param("i", $user_id);
        $check_stmt->execute();
        $check_stmt->bind_result($exists);
        $check_stmt->fetch();
        $check_stmt->close();

        if ($exists > 0) {
            $stmt = $conn->prepare("INSERT INTO Researcher_Project (ResearcherID, ProjectID) 
                                   VALUES ((SELECT ResearcherID FROM Researchers WHERE UserID = ?), ?)");
            $stmt->bind_param("ii", $user_id, $project_id);
            if (!$stmt->execute()) {
                die("Error assigning team member: " . $stmt->error);
            }
            $stmt->close();
        }
    }
}


// Log action in AuditLog table
$action = "Updated project ID $project_id";
$log_stmt = $conn->prepare("INSERT INTO AuditLog (UserID, Action) VALUES (?, ?)");
$log_stmt->bind_param("is", $_SESSION['user_id'], $action);
$log_stmt->execute();
$log_stmt->close();

// Redirect after successful update
header("Location: project_list.php");
exit();
?>
