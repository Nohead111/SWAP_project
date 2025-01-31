<?php

ini_set('session.cookie_httponly', 1);  // Prevent JavaScript access to session cookies
ini_set('session.cookie_secure', 1);    // Ensure cookies are sent only over HTTPS
ini_set('session.use_only_cookies', 1);  // Disable session ID in URL
header("X-Frame-Options: DENY");
header("Content-Security-Policy: frame-ancestors 'none';");

if (isset($_GET['error']) && $_GET['error'] == 'access_denied') {
    echo "<p style='color: red; text-align: center;'>Access denied. Please log in with a valid role.</p>";
}

// Start the session and check if the user is logged in
session_start();
session_regenerate_id(true);

// Session timeout (5minutes of inactivity)
$session_timeout = 300; // 5 minutes
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $session_timeout)) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}
$_SESSION['last_activity'] = time();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Validate user role
$user_role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

// If the role is invalid, destroy session and redirect with an error message
if ($user_role !== 'Admin' && $user_role !== 'Researcher') {
    session_unset();
    session_destroy();
    header("Location: login.php?error=access_denied");
    exit();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "swap_project");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle project deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_project'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid CSRF token.");
    }

    $project_id = intval($_POST['project_id']);

    // Check if project exists and is not completed
    $check_sql = "SELECT Status FROM Projects WHERE ProjectID = ?";
    $stmt_check = $conn->prepare($check_sql);
    $stmt_check->bind_param("i", $project_id);
    $stmt_check->execute();
    $stmt_check->bind_result($project_status);
    $stmt_check->fetch();
    $stmt_check->close();

    if ($project_status === 'Completed') {
        echo "<p style='color: red; text-align: center;'>Cannot delete a completed project.</p>";
    } else {
        // Delete project
        $delete_sql = "DELETE FROM Projects WHERE ProjectID = ?";
        $stmt_delete = $conn->prepare($delete_sql);
        $stmt_delete->bind_param("i", $project_id);
        if ($stmt_delete->execute()) {
            echo "<p style='color: green; text-align: center;'>Project deleted successfully.</p>";
        } else {
            echo "<p style='color: red; text-align: center;'>Error deleting project.</p>";
        }
        $stmt_delete->close();
    }
}

// Query to fetch projects
$sql = "SELECT ProjectID, ProjectName, Description, StartDate, EndDate, Status, Funding FROM Projects";
$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();

// Generate a CSRF token for delete links if not already set
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project List</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            padding: 0;
        }

        h1 {
            text-align: center;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table, th, td {
            border: 1px solid black;
        }

        th, td {
            text-align: left;
            padding: 10px;
        }

        th {
            background-color: #000;
            color: white;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        tr:hover {
            background-color: #f1f1f1;
        }

        .actions form {
            display: inline;
        }

        .actions button {
            background-color: red;
            color: white;
            border: none;
            padding: 5px 10px;
            cursor: pointer;
        }

        .actions button:hover {
            background-color: darkred;
        }

        .error-message {
            color: red;
            font-weight: bold;
            text-align: center;
        }
    </style>
</head>
<body>

    <!-- Display username and role -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <div>
            <strong>Welcome, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Guest'); ?></strong>
        </div>
        <div>
            <strong>Role: <?php echo htmlspecialchars($_SESSION['role'] ?? 'Unknown'); ?></strong>
        </div>
    </div>

    <h1>Project List</h1>

    <table>
        <thead>
            <tr>
                <th>Project Name</th>
                <th>Description</th>
                <th>Start Date</th>
                <th>End Date</th>
                <th>Status</th>
                <th>Funding</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['ProjectName']); ?></td>
                <td><?php echo htmlspecialchars($row['Description']); ?></td>
                <td><?php echo htmlspecialchars($row['StartDate']); ?></td>
                <td><?php echo htmlspecialchars($row['EndDate'] ?? 'Null'); ?></td> 
                <td><?php echo htmlspecialchars($row['Status']); ?></td>
                <td><?php echo htmlspecialchars(number_format($row['Funding'], 2) ?? '0.00'); ?></td>
                <td class="actions">
                    <a href="update_front.php?id=<?php echo htmlspecialchars($row['ProjectID']); ?>">Edit</a>
                    <?php if ($user_role === 'Admin'): ?>
                        <?php if ($row['Status'] === 'Completed'): ?>
                            <span style="color: gray; font-weight: bold;">Completed</span>
                        <?php else: ?>
                            <form method="POST" onsubmit="return confirm('Are you sure you want to delete this project?');">
                                <input type="hidden" name="project_id" value="<?php echo htmlspecialchars($row['ProjectID']); ?>">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                <button type="submit" name="delete_project">Delete</button>
                            </form>
                        <?php endif; ?>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <?php $conn->close(); ?>
</body>
</html>
