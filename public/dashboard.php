<?php
session_start();
if (!isset($_SESSION['user_role'])) {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h2>Velkommen til dashboardet!</h2>
    <p>Du er logget inn som: <?php echo $_SESSION['user_role']; ?></p>
    <a href="logout.php">Logg ut</a>
</body>
</html>
