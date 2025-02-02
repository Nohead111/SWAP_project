
<?php
session_start();
// Set a timeout duration
$session_timeout = 300; // 5 minutes

// Generate a CSRF token if it doesn't exist
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Redirect to login if the user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); 
    exit();
}
// Check if the session has timed out
    if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $session_timeout) {
        session_unset();
        session_destroy();
        header("Location: login.php?timeout=true"); // Redirect with timeout parameter
        exit();
    }
    $_SESSION['LAST_ACTIVITY'] = time();
$role = $_SESSION['user_role']; // Get the user's role

require "config.php";
require "function.php";
require "session_security.php";

// Display error and success messages
if (isset($_SESSION['error'])) {
    echo "<p style='color: red;'>".$_SESSION['error']."</p>";
    unset($_SESSION['error']);
}
if (isset($_SESSION['success'])) {
    echo "<p style='color: green;'>".htmlspecialchars($_SESSION['success'], ENT_QUOTES, 'UTF-8')."</p>";
    unset($_SESSION['success']); // Remove success message after displaying
}
function printerror($message, $connect) {
    //echo "<pre>";
    //echo "$message<br>";
    if ($con) echo "FAILED: ". mysqli_error($connect). "<br>";
    //echo "</pre>";
}

function printok($message) {
    //echo "<pre>";
    //echo "$message<br>";
    ///echo "OK<br>";
    //echo "</pre>";
}

try {
$connect=mysqli_connect($db_hostname,$db_username,$db_password);
}
catch (Exception $e) {
    printerror($e->getMessage(),$connect);
}
if (!$connect) {
    printerror("Connecting to $db_hostname", $connect);
    die();
}
else printok("Connecting to $db_hostname");

$result=mysqli_select_db($connect, $db_database);
if (!$result) {
    printerror("Selecting $db_database",$connect);
    die();
}
else printok("Selecting $db_database");

// Check if the form is submitted using POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    // CSRF Validation: Ensure the request is legitimate
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF validation failed.");
    }
    // Retrieve and sanitize input data
    $id = trim($_POST['id']);
    $uid = trim($_POST['uid']);
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phoneNum = trim($_POST['phoneNum']);
    $department = trim($_POST['department']);
    $specialization = trim($_POST['specialization']);

    // Ensure UID is purely digits
    if (!ctype_digit($uid) || $uid <=0) {
        set_session_message('error', 'Invalid User ID', $redirect = "researcher_profile.php");
    }

    // Check if User exists in users table
    if (check_record_count($connect, 'UserID', 'users', 'i', $uid) == 0) {
        set_session_message('error', 'Error: User ID not found in the database.', $redirect = "researcher_profile.php");
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        set_session_message('error', 'Invalid email format!', $redirect = "researcher_profile.php");
    }

    // Check for unique email in researchers table, excluding the current record
    if (check_record_count($connect, 'Email', 'researchers', 's', $email) > 0) {
        $stmt = $connect->prepare("SELECT ResearcherID FROM researchers WHERE Email = ? AND ResearcherID != ?");
        $stmt->bind_param('si', $email, $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            set_session_message('error', 'This email is already in use.', $redirect = "researcher_profile.php");
        }
    }

    // Validate phone number: allow only digits, spaces, dashes, parentheses, and plus sign
    if (!preg_match('/^\+?[0-9\s\-\(\)]{6,20}$/', $phoneNum)) {
        set_session_message('error', 'Invalid phone number format!', $redirect = "researcher_profile.php");
    }

    // Prepare and execute the update query using prepared statements
    $query = $connect->prepare("UPDATE researchers SET UserID=?, FullName=?, Email=?, PhoneNumber=?, Department=?, Specialization=? WHERE ResearcherID=?");
    $query->bind_param('isssssi', $uid, $name, $email, $phoneNum, $department, $specialization, $id);

    if ($query->execute()) {
        set_session_message('success', 'Record Updated!', $redirect = "researcher_profile.php");
    } else {
        error_log("Database error: " . $connect->error); // Log the error
        set_session_message('error', 'An error occurred. Please try again later', $redirect = "researcher_profile.php");
    }
    // Regenerate CSRF Token After Every Form Submission
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

} else {
    // Check if an ID is provided via GET
    if (isset($_GET['id'])) {
        $id = $_GET['id'];
        
        // Fetch the researcher's current data
        $query = $connect->prepare("SELECT * FROM researchers WHERE ResearcherID = ?");
        $query->bind_param('i', $id);
        $query->execute();
        $result = $query->get_result();
        $researcher = $result->fetch_assoc();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Researcher</title>
    <link rel="stylesheet" href="style.css"> 
</head>
<body>
    <!-- Header -->
    <header>
        <h1>AMC Research Management System</h1>
    </header>

    <!-- Navigation -->

 <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="projects.php">Projects</a>
        <a href="researcher_profile.php">Researchers</a>
        <a href="equipment_inventory.php" class="active">Equipment Inventory</a>
    </nav>
    <main>
        <section id="edit-researcher">
            <form method="POST" action="edit_researcher.php">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" value="<?php echo $researcher['ResearcherID']; ?>">

                <label for="uid">User ID:</label>
                <input type="number" id="uid" name="uid" autocomplete="off" value="<?php echo htmlspecialchars($researcher['UserID']); ?>" required>

                <label for="name">Full Name:</label>
                <input type="text" id="name" name="name" autocomplete="off" value="<?php echo htmlspecialchars($researcher['FullName']); ?>" required>

                <label for="email">Email:</label>
                <input type="email" id="email" name="email" autocomplete="off" value="<?php echo htmlspecialchars($researcher['Email']); ?>" required>

                <label for="phoneNum">Phone Number:</label>
                <input type="text" id="phoneNum" name="phoneNum" autocomplete="off" value="<?php echo htmlspecialchars($researcher['PhoneNumber']); ?>" required>

                <label for="department">Department:</label>
                <input type="text" id="department" name="department" autocomplete="off" value="<?php echo htmlspecialchars($researcher['Department']); ?>" required>

                <label for="specialization">Specialization:</label>
                <input type="text" id="specialization" name="specialization" autocomplete="off" value="<?php echo htmlspecialchars($researcher['Specialization']); ?>" required>
                <button type="submit">Update Researcher</button>
            </form>
        </section>
    </main>
</body>
</html>