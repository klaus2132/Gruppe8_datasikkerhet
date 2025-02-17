<?php
include '../app/auth.php';
logout();
?>

<!-- Du kan legge til en bekreftelse på utlogging -->
<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logget ut</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h2>Du er nå logget ut</h2>
    <a href="index.php">Tilbake til innloggingssiden</a>
</body>
</html>
