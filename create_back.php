<?php
// Start the session
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "You must be logged in to create a project.";
    exit();
}

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("CSRF token validation failed.");
}

// Database connection details
$servername = "localhost";  // MySQL server (localhost for XAMPP)
$username = "root";         // MySQL default username in XAMPP is 'root'
$password = "";             // Default password is empty for XAMPP
$dbname = "swap_project";  // Your database name

// Create a connection to the MySQL database
$conn = new mysqli($servername, $username, $password, $dbname);

// Check for a connection error
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $project_name = filter_input(INPUT_POST, 'project_name', FILTER_SANITIZE_STRING);
    $description = htmlspecialchars($_POST['description'], ENT_QUOTES, 'UTF-8');
    $assigned_team = $_POST['assigned_team'];  // Comma-separated list of user IDs
    $funding = $_POST['funding'];
    if (!is_numeric($funding) || $funding < 0) {
        echo "Invalid funding amount. Please enter a valid positive number.";
        exit();
    }    
    $start_date = filter_input(INPUT_POST, 'start_date', FILTER_SANITIZE_STRING);
    $created_by = $_SESSION['user_id'];  // Assuming user ID is stored in the session

    // Server-side validation (basic checks)
    if (empty($project_name) || empty($description) || empty($assigned_team) || empty($funding) || empty($start_date)) {
        echo "All fields are required!";
        exit();
    }

    // Insert the new project into the Projects table
    $sql = "INSERT INTO Projects (ProjectName, Description, StartDate, CreatedBy , Funding)
            VALUES (?, ?, ?, ?, ?)";
    // Prepare and execute the query
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi", $project_name, $description, $start_date, $created_by , $funding);

    if ($stmt->execute()) {
        // Get the last inserted project ID
        $project_id = $stmt->insert_id;

        // Assign team members to the project
        // $assigned_team is now a comma-separated list of user IDs
        $team_members = explode(',', $assigned_team);  // Convert the comma-separated string into an array

// Assign team members to the project
$team_members = explode(',', $assigned_team);  // Convert the comma-separated string into an array

foreach ($team_members as $member_id) {
    $member_id = trim($member_id);  // Remove any extra spaces

    if (is_numeric($member_id)) {
        // Check if ResearcherID exists in the researchers table
        $check_query = "SELECT COUNT(*) FROM researchers WHERE ResearcherID = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("i", $member_id);
        $check_stmt->execute();
        $check_stmt->bind_result($exists);
        $check_stmt->fetch();
        $check_stmt->close(); // Close the check statement to free resources

        if ($exists == 0) {
            // If ResearcherID doesn't exist, show an error and stop execution
            echo "Error: ResearcherID $member_id does not exist.";
            exit();
        }

        // If it exists, insert into Researcher_Project
        $insert_team_query = "INSERT INTO Researcher_Project (ResearcherID, ProjectID) VALUES (?, ?)";
        $team_stmt = $conn->prepare($insert_team_query);
        $team_stmt->bind_param("ii", $member_id, $project_id);
        if (!$team_stmt->execute()) {
            // Log or handle insertion errors
            echo "Error: Could not insert ResearcherID $member_id for ProjectID $project_id.";
        }
        $team_stmt->close(); // Close the insert statement to free resources
    }
}


        // Redirect to a success page (or project list page)
        echo "Project created successfully!";
        header("Location: project_list.php");  // Redirect to the project list page
        exit();

        // Close the database connection at the end
        $conn->close();
    } else {
        // Handle errors
        echo "Error: " . $stmt->error;
    }
}


?>
