<?php
//include "../config/session_security.php";
include "../config/config.php";

if(isset($_POST["insert"]) && $_POST["insert"] == "yes") {
        //Get user input
        $uid = trim($_POST["uid"]);
        $name = trim($_POST["name"]);
        $email = trim($_POST["email"]);
        $phoneNum = trim($_POST["phoneNum"]);
        $department = trim($_POST["department"]);
        $specialization = trim($_POST["specialization"]);

        // Ensure UID is a number
        if (!is_numeric($uid)){
            $_SESSION['error'] = "UserID must be a number:";
            header("Location: index.php");
            exit();
        }

        //Check for unique uid
        $stmt = $connect->prepare("SELECT UserID FROM researchers WHERE UserID=?");
        $stmt->bind_param("i", $uid);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            echo "<p>This email is UserID is used. Try another one!</p>";
            echo "<a href='javascript:history.back()'><button>Go Back</button></a>";
            exit();
        }

        //Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo "<p>Invalid email format!</p>";
            echo "<a href='javascript:history.back()'><button>Go Back</button></a>";
            exit();
        } 
        //Check for unique email
        $stmt = $connect->prepare("SELECT Email FROM researchers WHERE Email=?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            echo "<p>This email is already used. Try another one!</p>";
            echo "<a href='javascript:history.back()'><button>Go Back</button></a>";
            exit();
        }
        

        //prepare SQL Query   
        $query=$connect->prepare("insert into researchers (UserID, FullName, Email, PhoneNumber, Department, Specialization) values (?, ?, ?, ?, ?, ?);");
        
        // Bind parameters
        $query->bind_param('ississ', $uid, $name, $email, $phoneNum, $department, $specialization);
        
        //Execute SQL Query
        if($query->execute())
        {
            echo "<center>Record Inserted!</center><br>";
        }
    }
?>
