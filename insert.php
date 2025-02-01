<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if(isset($_POST["insert"]) && $_POST["insert"] == "yes") {
        //Get user input
        $uid = trim($_POST["uid"]);
        $name = trim($_POST["name"]);
        $email = trim($_POST["email"]);
        $phoneNum = trim($_POST["phoneNum"]);
        $department = trim($_POST["department"]);
        $specialization = trim($_POST["specialization"]);

        // Ensure UID is numeric and greater than 0
        if (!ctype_digit($uid) || $uid <=0) {
            set_session_message('error', 'Invalid User ID', $redirect = "index.php");
        }

        // Check if User existed in `users` table   
        if (check_record_count($connect, 'UserID', 'users', 'i', $uid) == 0) {
            // User ID does not exist
            set_session_message('error', 'Error: User ID not found in the database', $redirect = "index.php");
        } 
        
        //Check for unique uid
        if (check_record_count($connect, 'UserID', 'researchers', 'i', $uid) > 0) {
            // User ID is already in use
            set_session_message('error', 'This User ID is already in use.', $redirect = "index.php");
        } 

        //Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            set_session_message('error', 'Invalid email format!', $redirect = "index.php");
        } 
        //Check for unique email
        if (check_record_count($connect, 'Email', 'researchers', 's', $email) > 0) {
            set_session_message('error', 'This email is already in use.', $redirect = "index.php");
        }
        
        // Validate phone number: allow only digits, spaces, dashes, parentheses, and plus sign
        if (!preg_match('/^\+?[0-9\s\-\(\)]{6,20}$/', $phoneNum)) {
            set_session_message('error', 'Invalid phone number format!', $redirect = "index.php");
        }

        //prepare SQL Query   
        $query=$connect->prepare("insert into researchers (UserID, FullName, Email, PhoneNumber, Department, Specialization) values (?, ?, ?, ?, ?, ?);");
        
        // Bind parameters
        $query->bind_param('isssss', $uid, $name, $email, $phoneNum, $department, $specialization);
        
        //Execute SQL Query
        if($query->execute())
        {
            set_session_message('success', 'Record Inserted!', $redirect = "index.php");
        } else {
            error_log("Database error: " . $connect->error); // Log the error
            set_session_message('error', 'An error occurred. Please try again later', $redirect = "index.php");
        }
    }
?>
