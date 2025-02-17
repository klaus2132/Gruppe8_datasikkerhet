<?php
session_start();
if (!isset($_SESSION['user_role'])) {
    header("Location: login.php");
    exit();
}
?>
<h2>Velkommen til dashboardet!</h2>
<a href="logout.php">Logg ut</a>