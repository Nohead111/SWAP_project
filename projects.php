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


if ($con->connect_error) {
    die("Connection failed: " . $con->connect_error);
}

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header("Location: login.php"); // Redirect to login if not logged in
    exit();
}
$log_in_user_id = $_SESSION['user_id']; // user id
$log_in_user = $_SESSION['user_name']; // Assuming you store the username in session
$log_in_role = $_SESSION['user_role']; // Admin, Researcher, etc.

// Generate CSRF Token if not set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Fetch the first 7 users by default
$initial_users = [];
$user_query = $con->query("SELECT UserID, Username FROM Users ORDER BY UserID ASC LIMIT 7");
while ($row = $user_query->fetch_assoc()) {
    $initial_users[] = $row;
}

// Fetch existing projects
$projects = [];

if ($_SESSION['user_role'] == 1 || $_SESSION['user_role'] == 2) {
    $result = $con->query("SELECT * FROM Projects ORDER BY StartDate DESC");
    while ($row = $result->fetch_assoc()) {
        $projects[] = $row;
    }
} else if ($_SESSION['user_role'] ==3) {
    $user_id = $_SESSION['user_id'];
    $stmt = $con->prepare("SELECT p.* FROM Projects p JOIN Researcher_Project rp ON p.ProjectID = rp.ProjectID WHERE rp.ResearcherID = ? ORDER BY p.StartDate DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $projects[] = $row;
    }
}

// Handle AJAX user search
if (isset($_GET['q'])) {
    $search_query = $con->real_escape_string($_GET['q']);
    $search_results = [];

    $query = $con->prepare("SELECT UserID, Username FROM Users WHERE Username LIKE CONCAT('%', ?, '%') ORDER BY UserID ASC");
    $query->bind_param("s", $search_query);
    $query->execute();
    $result = $query->get_result();

    while ($row = $result->fetch_assoc()) {
        $search_results[] = $row;
    }
    echo json_encode($search_results);
    exit();
}

// Handle project creation
if (isset($_POST['create_project'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token validation failed.");
    }

    $title = $con->real_escape_string($_POST['title']);
    $description = $con->real_escape_string($_POST['description']);
    $funding = floatval($_POST['funding']);
    $startDate = $con->real_escape_string($_POST['start_date']);
    $endDate = !empty($_POST['end_date']) ? $con->real_escape_string($_POST['end_date']) : NULL;
    $status = 'Active';
    $createdBy = $_SESSION['user_id'];

    // Store team members
    $team_members_raw = isset($_POST['assigned_team']) ? $_POST['assigned_team'] : "";
    $team_members = explode(',', $team_members_raw);

    $stmt = $con->prepare("INSERT INTO Projects (ProjectName, Description, StartDate, EndDate, Status, CreatedBy, ProjectFunding) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssid", $title, $description, $startDate, $endDate, $status, $createdBy, $funding);
    $stmt->execute();
    $project_id = $stmt->insert_id;

    foreach ($team_members as $member) {
        $member = trim($member);

        // Validate ResearcherID exists
        $check_stmt = $con->prepare("SELECT ResearcherID FROM Researchers WHERE ResearcherID = ?");
        $check_stmt->bind_param("i", $member);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $stmt = $con->prepare("INSERT INTO Researcher_Project (ProjectID, ResearcherID) VALUES (?, ?)");
            $stmt->bind_param("ii", $project_id, $member);
            $stmt->execute();
        }
    }

    header("Location: projects.php"); // Refresh project list after creation
    exit();
}

// Handle project deletion
if (isset($_POST['delete_project']) && isset($_POST['csrf_token'])) {
    if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token validation failed.");
    }

    $project_id = intval($_POST['project_id']);

    // Check project status
    $check_status_stmt = $con->prepare("SELECT Status FROM Projects WHERE ProjectID = ?");
    $check_status_stmt->bind_param("i", $project_id);
    $check_status_stmt->execute();
    $status_result = $check_status_stmt->get_result();

    if ($status_result->num_rows > 0) {
        $status_row = $status_result->fetch_assoc();
        if ($status_row['Status'] !== 'Completed') {
            // Delete project
            $delete_stmt = $con->prepare("DELETE FROM Projects WHERE ProjectID = ?");
            $delete_stmt->bind_param("i", $project_id);
            $delete_stmt->execute();
        }
    }

    header("Location: projects.php"); // Refresh project list
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Projects</title>
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


    <main>
    <?php
    if ($_SESSION['user_role'] !=3) {
    ?>
        <section id="add-research-projects">
            <h2>Add Research Projects</h2>
            <form action="" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">
                <input type="hidden" id="assigned_team" name="assigned_team">

                <label for="title">Project Title:</label>
                <input type="text" id="title" name="title" required>

                <label for="description">Description:</label>
                <textarea id="description" name="description" rows="4" required></textarea>

                <label for="funding">Funding:</label>
                <input type="number" id="funding" name="funding" required>

                <label for="start_date">Start Date:</label>
                <input type="date" id="start_date" name="start_date" required>

                <label for="end_date">End Date (optional):</label>
                <input type="date" id="end_date" name="end_date">

                <label for="search">Search Team Members:</label>
                <input type="text" id="search" onkeyup="searchUsers()">
                <div id="search-results"></div>

                <h3>Assigned Team Members:</h3>
                <ul id="selected-users-list"></ul>

                <button type="submit" name="create_project">Create Project</button>
            </form>
        </section>
        <?php } ?>
        <section id="existing-projects">
            <h3>Existing Projects</h3>
            <table>
                <tr>
                    <th>Title</th>
                    <th>Description</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Status</th>
                    <th>Funding</th>
                    <th>Actions</th>
                </tr>
                <?php foreach ($projects as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['ProjectName']) ?></td>
                        <td><?= htmlspecialchars($row['Description']) ?></td>
                        <td><?= htmlspecialchars($row['StartDate']) ?></td>
                        <td><?= htmlspecialchars($row['EndDate']) ?></td>
                        <td><?= htmlspecialchars($row['Status']) ?></td>
                        <td><?= htmlspecialchars($row['ProjectFunding']) ?></td>
                        <td>
                        <?php if ($_SESSION['user_role'] != 3) { ?>
                            <a href='edit_project.php?project_id=<?= $row['ProjectID']; ?>'>Update</a>
                            <?php } ?>
                            <?php if ($_SESSION['user_role'] == 1 && $row['Status'] !== 'Completed'): ?>
                                <form action="" method="POST" style="display:inline;" onsubmit="return confirmDelete()">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">
                                    <input type="hidden" name="project_id" value="<?= $row['ProjectID']; ?>">
                                    <button type="submit" name="delete_project">Delete</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </section>
    </main>

    <!-- Footer -->
    <footer>
        <p>&copy; 2024 AMC Corporation. All Rights Reserved. <a href="#contact">Contact Us</a></p>
    </footer>

    <script>
        let selectedUsers = [];
        function searchUsers() {
            let searchQuery = document.getElementById('search').value.trim();
            fetch('projects.php?q=' + searchQuery)
                .then(response => response.json())
                .then(data => displaySearchResults(data));
        }

        function displaySearchResults(users) {
            let resultsContainer = document.getElementById('search-results');
            resultsContainer.innerHTML = users.map(user => `
                <button type="button" onclick="addUser(event, ${user.UserID}, '${user.Username}')">${user.Username}</button>
            `).join('');
        }

        function addUser(event, userID, username) {
            event.preventDefault(); // Prevents page refresh
            if (!selectedUsers.some(u => u.UserID === userID)) {
                selectedUsers.push({ UserID: userID, Username: username });
                document.getElementById('assigned_team').value = selectedUsers.map(user => user.UserID).join(',');
                updateUserList();
            }
        }

        function updateUserList() {
            let list = document.getElementById('selected-users-list');
            list.innerHTML = selectedUsers.map(user => `
                <li>${user.Username} <button onclick="removeUser(${user.UserID})">Remove</button></li>
            `).join('');
        }

        function removeUser(userID) {
            selectedUsers = selectedUsers.filter(user => user.UserID !== userID);
            updateUserList();
        }

        function confirmDelete() {
            return confirm("Are you sure you want to delete this project? This action cannot be reversed.");
        }
    </script>
</body>
</html>