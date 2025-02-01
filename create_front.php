<?php
// Start the session
session_start();
session_regenerate_id(true);

if (isset($_GET['success']) && $_GET['success'] == 'true') {
    echo "<p style='color: green; text-align: center;'>Project created successfully!</p>";
}

// Redirect if the user is not logged in or has an incorrect role
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Admin', 'Researcher'])) {
    header("Location: login.php"); // Redirect to login if not logged in or role is not valid
    exit();
}

// Generate CSRF token if it doesn't exist
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Database connection details
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "amc_data";

// Create a connection to the MySQL database
$conn = new mysqli($servername, $username, $password, $dbname);

// Check for a connection error
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set a timeout duration
$session_timeout = 300; // 5 minutes

// Check if the session has timed out
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $session_timeout) {
    session_unset();
    session_destroy();
    header("Location: login.php?timeout=true"); // / Redirect with timeout parameter
}
$_SESSION['LAST_ACTIVITY'] = time(); // Update last activity time

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token validation failed. Possible security risk.");
    }

    // Get form data
    $project_name = filter_input(INPUT_POST, 'project_name', FILTER_SANITIZE_STRING);
    $description = htmlspecialchars($_POST['description'], ENT_QUOTES, 'UTF-8');
    $assigned_team = $_POST['assigned_team']; // Comma-separated list of user IDs
    $funding = $_POST['funding'];
    // Ensure funding is a valid number and greater than or equal to zero
    if (!is_numeric($funding) || $funding < 0) {
    echo "Invalid funding amount. Please enter a valid positive number.";
    exit();
    }
    $start_date = $_POST['start_date'];
    $created_by = $_SESSION['user_id'];

    // Server-side validation
    if (empty($project_name) || empty($description) || empty($assigned_team) || empty($funding) || empty($start_date)) {
        echo "All fields are required!";
        exit();
    }

// Insert the project into the Projects table with funding included
$sql = "INSERT INTO Projects (ProjectName, Description, StartDate, CreatedBy, Funding) VALUES (?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sssdi", $project_name, $description, $start_date, $created_by, $funding);

if ($stmt->execute()) {
    $project_id = $stmt->insert_id; // Get the last inserted project ID

    // Assign team members to the project
    $team_members = explode(',', $assigned_team); // Convert to an array

    foreach ($team_members as $user_id) {
        $user_id = trim($user_id);

        // Check if UserID exists in Researchers table
        $check_query = "SELECT COUNT(*) FROM Researchers WHERE UserID = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("i", $user_id);
        $check_stmt->execute();
        $check_stmt->bind_result($exists);
        $check_stmt->fetch();
        $check_stmt->close();

        if ($exists == 0) {
            // Add UserID to Researchers table
            $user_query = "SELECT Username FROM Users WHERE UserID = ?";
            $user_stmt = $conn->prepare($user_query);
            $user_stmt->bind_param("i", $user_id);
            $user_stmt->execute();
            $user_stmt->bind_result($username);
            $user_stmt->fetch();
            $user_stmt->close();

            // Generate a unique email address (placeholder if email doesn't exist)
            $generated_email = strtolower($username) . "+generated" . $user_id . "@example.com";

            $insert_query = "INSERT INTO Researchers (UserID, FullName, Email) VALUES (?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_query);
            $insert_stmt->bind_param("iss", $user_id, $username, $generated_email);
            if (!$insert_stmt->execute()) {
                echo "Failed to add UserID $user_id to Researchers table: " . $insert_stmt->error;
                exit();
            }
            $insert_stmt->close();
        }

        // Assign the researcher to the project
        $assign_query = "INSERT INTO Researcher_Project (ResearcherID, ProjectID) VALUES ((SELECT ResearcherID FROM Researchers WHERE UserID = ?), ?)";
        $assign_stmt = $conn->prepare($assign_query);
        $assign_stmt->bind_param("ii", $user_id, $project_id);
        if (!$assign_stmt->execute()) {
            echo "Failed to assign UserID $user_id to ProjectID $project_id: " . $assign_stmt->error;
            exit();
        }
        $assign_stmt->close();
    }

    // Redirect to project list page
    header("Location: project_list.php?success=true");
    exit();
} else {
    echo "Error: " . $stmt->error;
    exit();
}

}

// Handle AJAX request for user search
if (isset($_GET['q'])) {
    $search_query = $_GET['q'];
    $sql = "SELECT UserID, Username FROM Users WHERE Username LIKE ?"; // Remove the LIMIT clause
    $stmt = $conn->prepare($sql);
    $like_search = "%" . $search_query . "%";
    $stmt->bind_param("s", $like_search);
    $stmt->execute();
    $result = $stmt->get_result();

    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }

    echo json_encode($users); // Send all results
    exit();
}

// Default: Get all users to display
$sql = "SELECT UserID, Username FROM Users"; // No limit here
$result = $conn->query($sql);
$initial_users = [];
while ($row = $result->fetch_assoc()) {
    $initial_users[] = $row;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Research Project</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #fff;
            color: #000;
            margin: 0;
            padding: 0;
        }

        h1 {
            text-align: center;
            margin: 20px 0;
        }

        form {
            width: 60%;
            margin: 0 auto;
            background-color: #f7f7f7;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        input[type="text"], input[type="date"], textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        input[type="submit"] {
            background-color: #000;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        input[type="submit"]:hover {
            background-color: #333;
        }

        .user-list {
            margin-top: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            overflow-y: auto;
            max-height: 200px;
        }

        .user-table {
            width: 100%;
            border-collapse: collapse;
        }

        .user-table th, .user-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .user-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .user-table th {
            background-color: #000;
            color: white;
        }

        .assigned-users {
            margin-top: 20px;
        }

        .assigned-users ul {
            list-style-type: none;
            padding: 0;
        }

        .assigned-users li {
            padding: 5px;
        }

        .assigned-users button {
            margin-left: 10px;
            background-color: red;
            color: white;
            border: none;
            padding: 5px;
            cursor: pointer;
            border-radius: 3px;
        }

        .assigned-users button:hover {
            background-color: #cc0000;
        }
    </style>
</head>
<body>
    <h1>Create New Research Project</h1>
    <form id="projectForm" action="create_front.php" method="POST">
        <label for="project_name">Project Title:</label><br>
        <input type="text" id="project_name" name="project_name" required><br><br>

        <label for="description">Description:</label><br>
        <textarea id="description" name="description" rows="4" cols="50" required></textarea><br><br>

        <label for="assigned_team">Assigned Team Members:</label><br>
        <input type="text" id="search" placeholder="Search for users..." onkeyup="searchUsers()"><br><br>
        
        <div id="search-results" class="user-list"></div>

        <div class="assigned-users">
            <h3>Selected Team Members:</h3>
            <ul id="selected-users-list"></ul>
        </div>

        <!-- CSRF Token --> 
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <input type="hidden" name="assigned_team" id="assigned_team">

        <label for="funding">Funding:</label><br>
        <input type="text" id="funding" name="funding" required><br><br>

        <label for="start_date">Start Date:</label><br>
        <input type="date" id="start_date" name="start_date" required><br><br>

        <input type="submit" value="Create Project">
    </form>

    <script>
    let selectedUsers = [];
    let debounceTimeout;

    // Default users to display (first 7 users)
    let users = <?php echo json_encode($initial_users); ?>;
    displaySearchResults(users); // Display the first 7 users by default

    // Debounced search function
    function searchUsers() {
        clearTimeout(debounceTimeout);  // Clear any previous debounce timeout
        let searchQuery = document.getElementById('search').value.trim();

        if (searchQuery.length >= 1) {  // Search starts after 1 character
            debounceTimeout = setTimeout(function() {
                let xhr = new XMLHttpRequest();
                xhr.open('GET', 'create_front.php?q=' + searchQuery, true);  // AJAX request to fetch users
                xhr.onreadystatechange = function() {
                    if (xhr.readyState == 4 && xhr.status == 200) {
                        let users = JSON.parse(xhr.responseText);
                        displaySearchResults(users);
                    }
                };
                xhr.send();
            }, 300);  // 300ms debounce delay
        } else {
            // When the search is empty, display the first 7 users
            displaySearchResults(<?php echo json_encode($initial_users); ?>);
        }
    }


    // Display search results in a table
    function displaySearchResults(users) {
    let resultsContainer = document.getElementById('search-results');
    resultsContainer.innerHTML = ''; // Clear previous results

    if (users.length > 0) {
        let table = document.createElement('table');
        table.classList.add('user-table');
        table.innerHTML = `
            <thead>
                <tr>
                    <th>User ID</th>
                    <th>Username</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody></tbody>
        `;

        let tbody = table.querySelector('tbody');

        users.forEach(user => {
            let row = document.createElement('tr');
            row.innerHTML = `
                <td>${user.UserID}</td>
                <td>${user.Username}</td>
                <td><button type="button" onclick="addUserToAssigned(${user.UserID}, '${user.Username}')">Add</button></td>
            `;
            tbody.appendChild(row);
        });

        resultsContainer.appendChild(table);
    } else {
        resultsContainer.innerHTML = '<p>No users found.</p>';
        }
    }

    // Add user to assigned list
    function addUserToAssigned(userID, username) {
        if (selectedUsers.some(u => u.UserID === userID)) {
            alert('User already added!');
            return;
        }

        selectedUsers.push({ UserID: userID, Username: username });
        updateAssignedUsersList();
    }

    // Update the list of selected users
    function updateAssignedUsersList() {
        let selectedList = document.getElementById('selected-users-list');
        selectedList.innerHTML = '';
        selectedUsers.forEach(user => {
            let li = document.createElement('li');
            li.innerHTML = `ID: ${user.UserID} | Username: ${user.Username} 
                <button type="button" onclick="removeUser(${user.UserID})">Remove</button>`;
            selectedList.appendChild(li);
        });

        // Update the hidden input with the selected user IDs
        let userIds = selectedUsers.map(user => user.UserID);  // Array of User IDs
        document.getElementById('assigned_team').value = userIds.join(',');  // Convert to comma-separated string
    }

    // Remove user from assigned list
    function removeUser(userID) {
        selectedUsers = selectedUsers.filter(user => user.UserID !== userID);
        updateAssignedUsersList();
    }

    // Validate form before submission
    function validateForm(event) {
        if (selectedUsers.length === 0) {  // If no team member selected
            event.preventDefault();  // Prevent form submission
            alert('Please add at least one team member to the project.');
        }
    }

    // Bind the validation function to the form's submit event
    document.querySelector('form').addEventListener('submit', validateForm);

</script>

</body>
</html>

