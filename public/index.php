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
        echo "Feil e-post eller passord!";
    }
}
?>

<form method="post">
    <input type="email" name="email" placeholder="E-post" required>
    <input type="password" name="password" placeholder="Passord" required>
    <button type="submit">Logg inn</button>
</form>
