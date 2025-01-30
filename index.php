<?php
    include '../config/config.php';
    include '../app/insert.php';
    include '../app/edit.php';
    include '../app/delete.php';
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
                <input type="number" id="uid" name="uid" autocomplete="off" value="<?php echo isset($_GET['uid']) ? htmlspecialchars($_GET['uid'], ENT_QUOTES, 'UTF-8') : ''; ?>" required>

                <label for="name">Full Name:</label>
                <input type="text" id="name" name="name" autocomplete="off" value="<?php echo isset($_GET['name']) ? htmlspecialchars($_GET['name'], ENT_QUOTES, 'UTF-8') : ''; ?>" required>

                <label for="email">Email:</label>
                <input type="text" id="email" name="email" autocomplete="off" value="<?php echo isset($_GET['email']) ? htmlspecialchars($_GET['email'], ENT_QUOTES, 'UTF-8') : ''; ?>" required>

                <label for="phoneNum">Phone Number:</label>
                <input type="text" id="phoneNum" name="phoneNum" autocomplete="off" value="<?php echo isset($_GET['phoneNum']) ? htmlspecialchars($_GET['phoneNum'], ENT_QUOTES, 'UTF-8') : ''; ?>" required>

                <label for="department">Department:</label>
                <input type="text" id="department" name="department" autocomplete="off" value="<?php echo isset($_GET['department']) ? htmlspecialchars($_GET['department'], ENT_QUOTES, 'UTF-8') : ''; ?>" required>

                <label for="specialization">Specialization:</label>
                <input type="text" id="specialization" name="specialization" autocomplete="off" value="<?php echo isset($_GET['specialization']) ? htmlspecialchars($_GET['specialization'], ENT_QUOTES, 'UTF-8') : ''; ?>" required>

                <input type="hidden" name="id" value="<?php echo isset($_GET['id']) ? htmlspecialchars($_GET['id'], ENT_QUOTES, 'UTF-8') : ''; ?>">
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
                echo "<tr>";
                echo "<td>".$id."</td>";
                echo "<td>".$uid."</td>";
                echo "<td>".$name."</td>";
                echo "<td>".$email."</td>";
                echo "<td>".$phoneNum."</td>";
                echo "<td>".$department."</td>";
                echo "<td>".$specialization."</td>";
                echo "<td>";
                echo "<form method='GET' action='edit.php'>";
                echo "<input type='hidden' name='id' value=".$id." />";
                echo "<input type='submit' name='edit_button' value='Edit' class='button' />";
                echo "</form>";
                echo "</td>";
                echo "<td>";
                echo "<form method='POST' action='index.php'>";
                echo "<input type='hidden' name='id' value=".$id." />";
                echo "<input type='submit' name='delete_button' value='Delete' class='button' />";
                echo "</form>";
                echo "</td>";
                echo "</tr>";
            }

            echo "</table>"
            
            ?>
            </section>
    </main>

    <!-- Footer -->
    <footer>
        <p>&copy; 2024 AMC Corporation. All Rights Reserved. <a href="#contact">Contact Us</a></p>
    </footer>
</body>
</html>