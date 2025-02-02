<?php 
    //include "../config/config.php";
    include "../app/edit.php";
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
    <header>
        <h1>Edit Researcher</h1>
    </header>

    <main>
        <section id="edit-researcher">
            <form method="POST" action="edit.php">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" value="<?php echo $researcher['ResearcherID']; ?>">

                <label for="uid">User ID:</label>
                <input type="number" id="uid" name="uid" autocomplete="off" value="<?php echo htmlspecialchars($researcher['UserID']); ?>" required>

                <label for="name">Full Name:</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($researcher['FullName']); ?>" required>

                <label for="email">Email:</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($researcher['Email']); ?>" required>

                <label for="phoneNum">Phone Number:</label>
                <input type="text" id="phoneNum" name="phoneNum" value="<?php echo htmlspecialchars($researcher['PhoneNumber']); ?>" required>

                <label for="department">Department:</label>
                <input type="text" id="department" name="department" value="<?php echo htmlspecialchars($researcher['Department']); ?>" required>

                <label for="specialization">Specialization:</label>
                <input type="text" id="specialization" name="specialization" value="<?php echo htmlspecialchars($researcher['Specialization']); ?>" required>

                <button type="submit">Update Researcher</button>
            </form>
        </section>
    </main>
</body>
</html>