<?php
// Start the session
session_start();
session_regenerate_id(true);

// Generate CSRF token if it doesn't exist
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Redirect if the user is not logged in
if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: login.php");
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "swap_project";
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get project ID from URL
$project_id = intval($_GET['id']);


if (isset($_GET['q'])) {
    header('Content-Type: application/json');
    $search_query = "%" . $_GET['q'] . "%";

    $sql = "SELECT UserID, Username FROM Users WHERE Username LIKE ? LIMIT 10";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $search_query);
    
    if (!$stmt->execute()) {
        echo json_encode(["error" => "Error executing query: " . $stmt->error]);
        exit();
    }

    $result = $stmt->get_result();
    $users = [];
    
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }

    ob_clean(); // Clear any previous output before JSON response
    echo json_encode($users);
    exit();
}


// Fetch all users initially
$sql = "SELECT UserID, Username FROM Users";
$result = $conn->query($sql);
$initial_users = [];
while ($row = $result->fetch_assoc()) {
    $initial_users[] = $row;
}


// Fetch project details
$sql_project = "SELECT ProjectName, Description, Funding, StartDate FROM Projects WHERE ProjectID = ?";
$stmt = $conn->prepare($sql_project);
$stmt->bind_param("i", $project_id);
$stmt->execute();
$stmt->bind_result($project_name, $description, $funding, $start_date);
$stmt->fetch();
$stmt->close();

// Fetch assigned team members
$assigned_users_query = "
    SELECT u.UserID, u.Username 
    FROM Users u 
    JOIN Researchers r ON u.UserID = r.UserID
    JOIN Researcher_Project rp ON r.ResearcherID = rp.ResearcherID
    WHERE rp.ProjectID = ?";
$stmt = $conn->prepare($assigned_users_query);
$stmt->bind_param("i", $project_id);
$stmt->execute();
$result = $stmt->get_result();
$assigned_users = [];

while ($row = $result->fetch_assoc()) {
    $assigned_users[] = $row;
}
$stmt->close();


if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token validation failed.");
    }

    $project_name = filter_input(INPUT_POST, 'project_name', FILTER_SANITIZE_STRING);
    $description = htmlspecialchars($_POST['description'], ENT_QUOTES, 'UTF-8');
    $funding = filter_input(INPUT_POST, 'funding', FILTER_VALIDATE_FLOAT);
    $start_date = filter_input(INPUT_POST, 'start_date', FILTER_SANITIZE_STRING);
    $assigned_team = isset($_POST['assigned_team']) ? $_POST['assigned_team'] : '';

    if (empty($project_name) || empty($description) || $funding === false || empty($start_date)) {
        echo "All fields are required!";
        exit();
    }

    // Update project details in the database
    $update_project_sql = "UPDATE Projects SET ProjectName = ?, Description = ?, Funding = ?, StartDate = ? WHERE ProjectID = ?";
    $stmt = $conn->prepare($update_project_sql);
    if (!$stmt) {
        die("Error preparing SQL statement: " . $conn->error);
    }
    $stmt->bind_param("ssdsi", $project_name, $description, $funding, $start_date, $project_id);
    if (!$stmt->execute()) {
        die("Error updating project: " . $stmt->error);
    }
    $stmt->close();

    // Remove existing team members
    $delete_stmt = $conn->prepare("DELETE FROM Researcher_Project WHERE ProjectID = ?");
    $delete_stmt->bind_param("i", $project_id);
    if (!$delete_stmt->execute()) {
        die("Error removing team members: " . $delete_stmt->error);
    }
    $delete_stmt->close();
    
// Insert updated team members if they exist
if (!empty($assigned_team)) {
    $team_members = explode(',', $assigned_team);
    foreach ($team_members as $user_id) {
        $user_id = trim($user_id);

        // Check if the user exists in the Researchers table
        $check_query = "SELECT ResearcherID FROM Researchers WHERE UserID = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("i", $user_id);
        $check_stmt->execute();
        $check_stmt->store_result();

        if ($check_stmt->num_rows == 0) {
            // Fetch user details to insert into the Researchers table
            $get_user_query = "SELECT Username FROM Users WHERE UserID = ?";
            $stmt_user = $conn->prepare($get_user_query);
            $stmt_user->bind_param("i", $user_id);
            $stmt_user->execute();
            $stmt_user->bind_result($username);
            $stmt_user->fetch();
            $stmt_user->close();

            // Create a default email based on username
            $email = $username . "+generated" . $user_id . "@example.com";

            // Insert user into the Researchers table
            $insert_researcher_query = "INSERT INTO Researchers (UserID, FullName, Email) VALUES (?, ?, ?)";
            $stmt_insert = $conn->prepare($insert_researcher_query);
            if (!$stmt_insert) {
                die("Error preparing researcher insert statement.");
            }
            $stmt_insert->bind_param("iss", $user_id, $username, $email);
            if (!$stmt_insert->execute()) {
                die("Error adding new researcher: " . $stmt_insert->error);
            }
            $stmt_insert->close();
        }

        $check_stmt->close();

        // Assign the researcher to the project
        $assign_query = "INSERT INTO Researcher_Project (ResearcherID, ProjectID) 
                         VALUES ((SELECT ResearcherID FROM Researchers WHERE UserID = ?), ?)";
        $stmt = $conn->prepare($assign_query);
        if ($stmt) {
            $stmt->bind_param("ii", $user_id, $project_id);
            if (!$stmt->execute()) {
                die("Error assigning team member: " . $stmt->error);
            }
            $stmt->close();
        } else {
            die("Error preparing team insert statement.");
        }
    }
}


    // Regenerate CSRF token to prevent reuse
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    // Redirect to project list page after successful update
    header("Location: project_list.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Research Project</title>
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
    width: 100%;
    max-height: 250px;
    overflow-y: auto;
    border: 1px solid #ccc;
    border-radius: 5px;
    margin-top: 10px;
}

.user-table {
    width: 100%;
    border-collapse: collapse;
    font-family: Arial, sans-serif;
}

.user-table th, .user-table td {
    padding: 10px 20px;
    border-bottom: 1px solid #ddd;
    text-align: left;
}

.user-table th {
    background-color: #000;
    color: white;
    font-weight: bold;
}

.user-table tr:nth-child(even) {
    background-color: #f9f9f9;
}

.user-table tr:hover {
    background-color: #f1f1f1;
}

.assigned-users ul {
    list-style-type: none;
    padding: 0;
}

.assigned-users li {
    padding: 10px;
    margin-bottom: 5px;
    border-radius: 3px;
    background-color: #f7f7f7;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border: 1px solid #ddd;
}

.assigned-users button {
    background-color: red;
    color: white;
    border: none;
    padding: 5px 10px;
    cursor: pointer;
    border-radius: 3px;
    font-size: 14px;
}

.assigned-users button:hover {
    background-color: #cc0000;
}

    </style>
</head>
<body>
    <h1>Update Research Project</h1>
    <form action="" method="POST">
        <label for="project_name">Project Title:</label><br>
        <input type="text" id="project_name" name="project_name" value="<?php echo htmlspecialchars($project_name); ?>" required><br><br>

        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

        <label for="description">Description:</label><br>
        <textarea id="description" name="description" rows="4" required><?php echo htmlspecialchars($description); ?></textarea><br><br>

        <label for="funding">Funding:</label><br>
        <input type="text" id="funding" name="funding" value="<?php echo htmlspecialchars($funding); ?>" required><br><br>

        <label for="start_date">Start Date:</label><br>
        <input type="date" id="start_date" name="start_date" value="<?php echo $start_date; ?>" required><br><br>

        <label for="assigned_team">Assigned Team Members:</label><br>
        <input type="text" id="search" placeholder="Search for users..." 
            style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px;">


<div id="search-results" class="user-list"></div>

<div class="assigned-users">
    <h3>Selected Team Members:</h3>
    <ul id="selected-users-list">
        <?php foreach ($assigned_users as $user): ?>
            <li>
                ID: <?php echo $user['UserID']; ?> | Username: <?php echo $user['Username']; ?>
                <button type="button" onclick="removeUser(<?php echo $user['UserID']; ?>)">Remove</button>
            </li>
        <?php endforeach; ?>
    </ul>
</div>

<input type="hidden" name="assigned_team" id="assigned_team" 
       value="<?php echo !empty($assigned_users) ? implode(',', array_column($assigned_users, 'UserID')) : ''; ?>">


<input type="submit" value="Update Project">

    </form>

<script>
let selectedUsers = <?php echo json_encode(array_map(function($user) {
    return ['UserID' => $user['UserID'], 'Username' => $user['Username']];
}, $assigned_users)); ?>;
updateAssignedUsersList();

let searchTimeout;

document.getElementById('search').addEventListener('input', function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        let searchQuery = this.value.trim();

        if (searchQuery === "") {
            // Display all users when search is empty
            displaySearchResults(<?php echo json_encode($initial_users); ?>);
            return;
        }

        fetch(`update_front.php?q=${encodeURIComponent(searchQuery)}`)
            .then(response => response.json())
            .then(data => {
                displaySearchResults(data);
            })
            .catch(error => {
                console.error('Error fetching users:', error);
                document.getElementById('search-results').innerHTML = '<p>Error loading users.</p>';
            });
    }, 300);  // Debounce with 300ms delay
});


// Display all users when the page loads
document.addEventListener('DOMContentLoaded', function() {
    displaySearchResults(<?php echo json_encode($initial_users); ?>);
});


function displaySearchResults(users) {
    let resultsContainer = document.getElementById('search-results');
    resultsContainer.innerHTML = '';

    if (users.length > 0) {
        let table = document.createElement('table');
        table.classList.add('user-table');
        table.innerHTML = `
            <thead>
                <tr>
                    <th style="padding: 10px; text-align: left;">User ID</th>
                    <th style="padding: 10px; text-align: left;">Username</th>
                    <th style="padding: 10px; text-align: left;">Action</th>
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
                <td><button type="button" onclick="addUser(${user.UserID}, '${user.Username}')">Add</button></td>
            `;
            tbody.appendChild(row);
        });

        resultsContainer.appendChild(table);
    } else {
        resultsContainer.innerHTML = '<p>No users found.</p>';
    }
}

function addUser(userID, username) {
    if (selectedUsers.some(u => u.UserID === userID)) {
        alert('User is already added!');
        return;
    }

    selectedUsers.push({ UserID: userID, Username: username });
    updateAssignedUsersList();
}

function removeUser(userID) {
    selectedUsers = selectedUsers.filter(user => user.UserID !== userID);
    updateAssignedUsersList();
}

function updateAssignedUsersList() {
    let selectedList = document.getElementById('selected-users-list');
    selectedList.innerHTML = '';

    selectedUsers.forEach(user => {
        let li = document.createElement('li');
        li.innerHTML = `ID: ${user.UserID} | Username: ${user.Username} 
            <button style="margin-left: 20px;" type="button" onclick="removeUser(${user.UserID})">Remove</button>`;

        selectedList.appendChild(li);
    });

    document.getElementById('assigned_team').value = selectedUsers.map(user => user.UserID).join(',');
}

// Form submission validation
document.querySelector('form').addEventListener('submit', function(event) {
    let fundingValue = document.getElementById('funding').value.trim();
    if (isNaN(fundingValue) || fundingValue <= 0) {
        event.preventDefault();
        alert('Please enter a valid funding amount.');
    }
    if (selectedUsers.length === 0) {
        event.preventDefault();
        alert('Please add at least one team member to the project.');
    }
});
</script>

</body>
</html>
