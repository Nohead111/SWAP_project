<?php
session_start();
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
if(isset($_POST["insert_button"]) ) {
    //Get user input
    $uid = trim($_POST["uid"]);
    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $phoneNum = trim($_POST["phoneNum"]);
    $department = trim($_POST["department"]);
    $specialization = trim($_POST["specialization"]);

    // Ensure UID is numeric and greater than 0
    if (!ctype_digit($uid) || $uid <=0) {
        set_session_message('error', 'Invalid User ID', $redirect = "researcher_profile.php");
    }

    // Check if User existed in `users` table   
    if (check_record_count($con, 'UserID', 'users', 'i', $uid) == 0) {
        // User ID does not exist
        set_session_message('error', 'Error: User ID not found in the database', $redirect = "researcher_profile.php");
    } 
    
    //Check for unique uid
    if (check_record_count($con, 'UserID', 'researchers', 'i', $uid) > 0) {
        // User ID is already in use
        set_session_message('error', 'This User ID is already in use.', $redirect = "researcher_profile.php");
    } 

    //Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        set_session_message('error', 'Invalid email format!', $redirect = "researcher_profile.php");
    } 
    //Check for unique email
    if (check_record_count($con, 'Email', 'researchers', 's', $email) > 0) {
        set_session_message('error', 'This email is already in use.', $redirect = "researcher_profile.php");
    }
    
    // Validate phone number: allow only digits, spaces, dashes, parentheses, and plus sign
    if (!preg_match('/^\+?[0-9\s\-\(\)]{6,20}$/', $phoneNum)) {
        set_session_message('error', 'Invalid phone number format!', $redirect = "researcher_profile.php");
    }

    //prepare SQL Query   
    $query=$con->prepare("insert into researchers (UserID, FullName, Email, PhoneNumber, Department, Specialization) values (?, ?, ?, ?, ?, ?);");
    
    // Bind parameters
    $query->bind_param('isssss', $uid, $name, $email, $phoneNum, $department, $specialization);
    
    //Execute SQL Query
    if($query->execute())
    {
        set_session_message('success', 'Record Inserted!', $redirect = "researcher_profile.php");
    } else {
        error_log("Database error: " . $con->error); // Log the error
        set_session_message('error', 'An error occurred. Please try again later', $redirect = "researcher_profile.php");
    }
}
if(isset($_POST["delete_button"])){
    $id=$_POST["id"];
    $query=$con->prepare("DELETE FROM researchers WHERE ResearcherID =?");
    $query->bind_param('i', $id);
    if($query->execute())
    {
        set_session_message('success', 'Record Deleted', $redirect = "researcher_profile.php");
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Researcher</title>
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

    <!-- Main Content -->
    <main>
        <!-- Form Section -->
        <section id="add-researcher">
            <h2>Add New Researcher</h2>
            <form action="researcher_profile.php" method="POST">
                <label for="uid">User ID:</label>
                <input type="number" id="uid" name="uid" autocomplete="off" value="<?php echo get_sanitized_input('uid'); ?>" required>

                <label for="name">Full Name:</label>
                <input type="text" id="name" name="name" autocomplete="off" value="<?php echo get_sanitized_input('name'); ?>" required>

                <label for="email">Email:</label>
                <input type="text" id="email" name="email" autocomplete="off" value="<?php echo get_sanitized_input('email'); ?>" required>

                <label for="phoneNum">Phone Number:</label>
                <input type="text" id="phoneNum" name="phoneNum" autocomplete="off" value="<?php echo get_sanitized_input('phoneNum'); ?>" required>

                <label for="department">Department:</label>
                <input type="text" id="department" name="department" autocomplete="off" value="<?php echo get_sanitized_input('department'); ?>" required>

                <label for="specialization">Specialization:</label>
                <input type="text" id="specialization" name="specialization" autocomplete="off" value="<?php echo get_sanitized_input('specialization'); ?>" required>

                <input type="hidden" name="id" value="<?php echo get_sanitized_input('id'); ?>">
                <input type="hidden" name="insert" value="yes">
                <button type="submit" name="insert_button">Add Researcher</button>
            </form>
            
        </section>
        <!-- Table Section -->
        <section id="Researchers">
            <h2>Researchers</h2>
            <?php
            $query=$con->prepare("select * from researchers");
            $query->execute();
            $query->bind_result($id, $uid, $name, $email, $phoneNum, $department, $specialization);
            
            //Display the table
            echo "<table>";
            echo "<tr>
                    <th>ID</th>
                    <th>User ID</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Phone Number</th>
                    <th>Department</th>
                    <th>Specialization</th>
                    <th>Edit</th>
                    <th>Delete</th>
                  </tr>";
            
            while ($query->fetch()) {
                echo "<tr>
                        <td>".sanitize_output($id)."</td>
                        <td>".sanitize_output($uid)."</td>
                        <td>".sanitize_output($name)."</td>
                        <td>".sanitize_output($email)."</td>
                        <td>".sanitize_output($phoneNum)."</td>
                        <td>".sanitize_output($department)."</td>
                        <td>".sanitize_output($specialization)."</td>
                        <td>
                        <form method='GET' action='edit_researcher.php'>
                            <input type='hidden' name='id' value=".$id." />
                            <input type='submit' name='edit_button' value='Edit' class='button' />
                        </form>
                    </td>
                    <td>";
                        if ($role === 1) {
                            echo "<form method='POST' action='researcher_profile.php'>
                                <input type='hidden' name='id' value=".$id." />
                                <input type='submit' name='delete_button' value='Delete' class='button' />
                            </form>";
                    
                        } else {
                            echo "Not Allowed";
                        } 
                        echo "</td>
                            </tr>";
            }
            echo "</table>";
            ?>
            </section>
    </main>

    <!-- Footer -->
    <footer>
        <p>&copy; 2024 AMC Corporation. All Rights Reserved. <a href="#contact">Contact Us</a></p>
    </footer>
</body>
</html>