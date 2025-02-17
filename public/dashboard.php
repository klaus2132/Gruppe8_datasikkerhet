<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id']; // Access user ID
$user_role = $_SESSION['user_role']; // Access user role
?>

<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet">
</head>
<body>
    <h2>Velkommen til dashboardet!</h2>
    <p>Du er logget inn som: <?php echo $user_role; ?></p>
    <p>Bruker-ID: <?php echo $user_id; ?></p> <!-- Display user ID -->

    <a href="change_password.php">Bytt passord</a>
    <hr>
    <a href="logout.php">Logg ut</a>
</body>
</html>

