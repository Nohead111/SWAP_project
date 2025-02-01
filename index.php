<?php
include '../config/config.php';
include '../app/function.php';
include '../app/insert.php';
include '../app/edit.php';
include '../app/delete.php';

// ini_set('session.cookie_httponly',1);
// ini_set('session.cookie_secure',1);
// ini_set('session.use_only_cookies',1);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
session_regenerate_id(true);

// Check if the user is logged in
if (!isset($_SESSION['role'])) {
    header("Location: login.php"); // Redirect to login if not logged in
    exit();
}

$role = $_SESSION['role']; // Get the user's role

// Display error and success messages
if (isset($_SESSION['error'])) {
    echo "<p style='color: red;'>".$_SESSION['error']."</p>";
    unset($_SESSION['error']);
}
if (isset($_SESSION['success'])) {
    echo "<p style='color: green;'>".htmlspecialchars($_SESSION['success'], ENT_QUOTES, 'UTF-8')."</p>";
    unset($_SESSION['success']); // Remove success message after displaying
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure AMC Research Management System</title>
    <link rel="stylesheet" href="style.css"> 
</head>
<body>
    <!-- Header -->
    <header>
        <h1>Secure AMC Research Management System</h1>
    </header>

    <!-- Navigation -->
    <nav>
        <a href="#dashboard">Dashboard</a>
        <a href="#projects">Projects</a>
        <a href="#researchers">Researchers</a>
        <a href="#equipment">Equipment Inventory</a>
    </nav>

    <!-- Main Content -->
    <main>
        <!-- Form Section -->
        <section id="add-researcher">
            <h2>Add New Researcher</h2>
            <form action="index.php" method="POST">
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
            $query=$connect->prepare("select * from researchers");
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
                        <form method='GET' action='edit.php'>
                            <input type='hidden' name='id' value=".$id." />
                            <input type='submit' name='edit_button' value='Edit' class='button' />
                        </form>
                    </td>
                    <td>";
                        if ($role === 'admin') {
                            echo "<form method='POST' action='index.php'>
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