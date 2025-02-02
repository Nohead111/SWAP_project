<?php
session_start();
require "config.php";
// Set a timeout duration
$session_timeout = 300; // 5 minutes

// Check if the session has timed out
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $session_timeout) {
    session_unset();
    session_destroy();
    header("Location: login.php?timeout=true"); // Redirect with timeout parameter
    exit();
}
$_SESSION['LAST_ACTIVITY'] = time();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";	function printerror($message, $con) {
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
$conn=mysqli_connect($db_hostname,$db_username,$db_password);
}
catch (Exception $e) {
    printerror($e->getMessage(),$conn);
}
if (!$conn) {
    printerror("Connecting to $db_hostname", $conn);
    die();
}
else printok("Connecting to $db_hostname");

$result=mysqli_select_db($conn, $db_database);
if (!$result) {
    printerror("Selecting $db_database",$conn);
    die();
}
else printok("Selecting $db_database");


if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header("Location: login.php"); // Redirect to login if not logged in
    exit();
}

// Fetch project details before displaying the form
$project = [];
$assigned_team = [];

if (isset($_GET['project_id'])) {
    $project_id = intval($_GET['project_id']);
    $result = $conn->query("SELECT * FROM Projects WHERE ProjectID = $project_id");
    if ($result->num_rows > 0) {
        $project = $result->fetch_assoc();
    }

    // Fetch assigned team members
    $team_query = $conn->prepare("
        SELECT u.UserID, u.Username 
        FROM Researcher_Project rp
        JOIN Users u ON rp.ResearcherID = u.UserID
        WHERE rp.ProjectID = ?
    ");
    $team_query->bind_param("i", $project_id);
    $team_query->execute();
    $team_result = $team_query->get_result();

    while ($row = $team_result->fetch_assoc()) {
        $assigned_team[] = $row;
    }
}

// Handle AJAX search request
if (isset($_GET['q'])) {
    $search_query = $conn->real_escape_string($_GET['q']);
    $search_results = [];

    $query = $conn->prepare("SELECT UserID, Username FROM Users WHERE Username LIKE CONCAT('%', ?, '%') ORDER BY UserID ASC");
    $query->bind_param("s", $search_query);
    $query->execute();
    $result = $query->get_result();

    while ($row = $result->fetch_assoc()) {
        $search_results[] = $row;
    }
    echo json_encode($search_results);
    exit();
}

// Handle project update
if (isset($_POST['update_project'])) {
    $project_id = intval($_POST['project_id']);
    $title = $conn->real_escape_string($_POST['title']);
    $description = $conn->real_escape_string($_POST['description']);
    $funding = floatval($_POST['funding']);
    $startDate = $conn->real_escape_string($_POST['start_date']);
    $endDate = !empty($_POST['end_date']) ? $conn->real_escape_string($_POST['end_date']) : NULL;
    $status = $conn->real_escape_string($_POST['status']);

    // Update project details
    $stmt = $conn->prepare("UPDATE Projects SET ProjectName=?, Description=?, StartDate=?, EndDate=?, Status=?, ProjectFunding=? WHERE ProjectID=?");
    $stmt->bind_param("sssssid", $title, $description, $startDate, $endDate, $status, $funding, $project_id);
    $stmt->execute();

    // Update assigned team members
    if (isset($_POST['assigned_team'])) {
        $team_members_raw = $_POST['assigned_team'];
        $team_members = explode(',', $team_members_raw);

        // Remove existing assignments
        $delete_stmt = $conn->prepare("DELETE FROM Researcher_Project WHERE ProjectID=?");
        $delete_stmt->bind_param("i", $project_id);
        $delete_stmt->execute();

        // Add new assignments
        foreach ($team_members as $member) {
            $member = intval($member);

            // Ensure user exists in Users table before adding to Researchers
            $check_user_stmt = $conn->prepare("SELECT UserID, Username FROM Users WHERE UserID = ?");
            $check_user_stmt->bind_param("i", $member);
            $check_user_stmt->execute();
            $check_user_result = $check_user_stmt->get_result();
            $user_result  = $check_user_result->fetch_assoc();
            //echo  $user_result['Username']+ "<br>";
            if ($check_user_result->num_rows > 0) {

                $check_researcher_stmt = $conn->prepare("SELECT ResearcherID FROM Researchers WHERE UserID = ?");
                $check_researcher_stmt->bind_param("i", $member);
                $check_researcher_stmt->execute();
                $check_researcher_result = $check_researcher_stmt->get_result();
                if ($check_researcher_result->num_rows == 0) {
                    // Insert user into Researchers table if not found
                    $insert_researcher_stmt = $conn->prepare("INSERT INTO Researchers (ResearcherID, UserID, Email) VALUES (?,?,?)");
                    $insert_researcher_stmt->bind_param("iis", $member, $member, $user_result['Username']);
                    $insert_researcher_stmt->execute();
                }
                // Check if the researcher already exists in Researchers table
                //$check_researcher_stmt = $conn->prepare("SELECT ResearcherID FROM Researchers WHERE UserID = ?");
               // $check_researcher_stmt->bind_param("i", $member);
               // $check_researcher_stmt->execute();
               // $check_researcher_result = $check_researcher_stmt->get_result();
              //  $researcher_result  = $check_researcher_result->fetch_assoc();

                // Insert into Researcher_Project
                $insert_stmt = $conn->prepare("INSERT INTO Researcher_Project (ProjectID, ResearcherID) VALUES (?, ?)");
                $insert_stmt->bind_param("ii", $project_id, $member);
                $insert_stmt->execute();
            }
        }
    }

    header("Location: projects.php"); // Redirect back to refresh project list
    exit();
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
        <h1>Update Project</h1>
        <a href="logout.php" class="logout-btn">Logout</a>
    </header>

    <!-- Navigation -->
    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="projects.php">Projects</a>
        <a href="researcher_profile.php">Researchers</a>
        <a href="equipment_inventory.php" class="active">Equipment Inventory</a>
    </nav>
    <main>
        <form action="" method="POST">
            <input type="hidden" name="project_id" value="<?= isset($project['ProjectID']) ? htmlspecialchars($project['ProjectID']) : ''; ?>">
            <input type="hidden" id="assigned_team" name="assigned_team">

            <label for="title">Project Title:</label>
            <input type="text" id="title" name="title" value="<?= isset($project['ProjectName']) ? htmlspecialchars($project['ProjectName']) : ''; ?>" required>

            <label for="description">Description:</label>
            <textarea id="description" name="description" rows="4" required><?= isset($project['Description']) ? htmlspecialchars($project['Description']) : ''; ?></textarea>

            <label for="funding">Funding:</label>
            <input type="number" id="funding" name="funding" value="<?= isset($project['Funding']) ? htmlspecialchars($project['Funding']) : ''; ?>" required>

            <label for="start_date">Start Date:</label>
            <input type="date" id="start_date" name="start_date" value="<?= isset($project['StartDate']) ? htmlspecialchars($project['StartDate']) : ''; ?>" required>

            <label for="end_date">End Date (optional):</label>
            <input type="date" id="end_date" name="end_date" value="<?= isset($project['EndDate']) ? htmlspecialchars($project['EndDate']) : ''; ?>">

            <label for="status">Status:</label>
            <select id="status" name="status" required>
                <option value="Active" <?= (isset($project['Status']) && $project['Status'] == 'Active') ? 'selected' : ''; ?>>Active</option>
                <option value="Completed" <?= (isset($project['Status']) && $project['Status'] == 'Completed') ? 'selected' : ''; ?>>Completed</option>
                <option value="On Hold" <?= (isset($project['Status']) && $project['Status'] == 'On Hold') ? 'selected' : ''; ?>>On Hold</option>
            </select>

            <label for="search">Search Team Members:</label>
            <input type="text" id="search" onkeyup="searchUsers()" placeholder="Search for users...">
            <div id="search-results"></div>

            <h3>Assigned Team Members:</h3>
            <ul id="selected-users-list">
                <?php foreach ($assigned_team as $user): ?>
                    <li id="user-<?= $user['UserID'] ?>"><?= htmlspecialchars($user['Username']) ?>
                        <button type="button" onclick="removeUser(<?= $user['UserID'] ?>)">Remove</button>
                    </li>
                <?php endforeach; ?>
            </ul>

            <button type="submit" name="update_project">Update</button>
        </form>
    </main>

    <!-- Footer -->
    <footer>
        <p>&copy; 2024 AMC Corporation. All Rights Reserved. <a href="#contact">Contact Us</a></p>
    </footer>

    <script>
        let selectedUsers = <?= json_encode($assigned_team) ?>;
        updateUserList();

        function searchUsers() {
            let searchQuery = document.getElementById('search').value.trim();
            fetch('projects.php?q=' + searchQuery)
                .then(response => response.json())
                .then(data => displaySearchResults(data));
        }

        function displaySearchResults(users) {
            let resultsContainer = document.getElementById('search-results');
            resultsContainer.innerHTML = users.map(user => `
                <button type="button" onclick="addUser(${user.UserID}, '${user.Username}')">${user.Username}</button>
            `).join('');
        }

        function addUser(userID, username) {
            if (!selectedUsers.some(u => u.UserID === userID)) {
                selectedUsers.push({ UserID: userID, Username: username });
                updateUserList();
            }
        }

        function removeUser(userID) {
            selectedUsers = selectedUsers.filter(user => user.UserID !== userID);
            updateUserList();
        }

        function updateUserList() {
            let list = document.getElementById('selected-users-list');
            list.innerHTML = selectedUsers.map(user => `
                <li>${user.Username} <button type="button" onclick="removeUser(${user.UserID})">Remove</button></li>
            `).join('');
            document.getElementById('assigned_team').value = selectedUsers.map(user => user.UserID).join(',');
        }
    </script>
</body>
</html>