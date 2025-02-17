<?php
include '../app/auth.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Call the login function
    $redirect = login($email, $password);
    
    if ($redirect) {
        header("Location: $redirect");
        exit();
    } else {
        echo "<p style='color: red;'>Feil e-post eller passord!</p>";
    }
}
?>
<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h2>Logg inn</h2>
    <form method="post">
        <input type="email" name="email" placeholder="E-post" required>
        <input type="password" name="password" placeholder="Passord" required>
        <button type="submit">Logg inn</button>
    </form>
    <p>Har du ikke en konto? <a href="register.php">Registrer deg her</a></p>
</body>
</html>

