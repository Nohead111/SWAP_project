<?php
    //include '../config/session_security.php';
    include '../config/config.php';
    //before_every_protected_page();
    //$logged_in_user_id = $_SESSION['id'];

// Check if the form is submitted using POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    // Retrieve and sanitize input data
    $id = $_POST['id'];
    $uid = $_POST['uid'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phoneNum = $_POST['phoneNum'];
    $department = $_POST['department'];
    $specialization = $_POST['specialization'];

    // Prepare and execute the update query using prepared statements
    $query = $connect->prepare("UPDATE researchers SET UserID=?, FullName=?, Email=?, PhoneNumber=?, Department=?, Specialization=? WHERE ResearcherID=?");
    $query->bind_param('isssssi', $uid, $name, $email, $phoneNum, $department, $specialization, $id);

    if ($query->execute()) {
        // Redirect to index.php after successful update
        header("Location: index.php");
        exit();
    } else {
        echo "Error updating record: " . $connect->error;
    }
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

        if ($researcher) {
            // Display the edit form with current data
?>

<?php
        } else {
            echo "<p>Researcher not found.</p>";
        }
    } else {
        echo "<p>Invalid request.</p>";
    }
}
?>
