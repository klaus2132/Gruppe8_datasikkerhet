<?php
include 'config.php';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $role = $_POST['role'];

    if ($role == "student") {
        $sql = "INSERT INTO students (name, email, password) VALUES ('$name', '$email', '$password')";
    } elseif ($role == "foreleser") {
        $subject_id = $_POST['subject_id'];
        $sql = "INSERT INTO lecturers (name, email, password, subject_id) VALUES ('$name', '$email', '$password', '$subject_id')";
    } else {
        $sql = "INSERT INTO admin (username, email, password) VALUES ('$name', '$email', '$password')";
    }

    if ($conn->query($sql) === TRUE) {
        echo "Bruker registrert!";
    } else {
        echo "Feil: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <title>Registrer Bruker</title>
</head>
<body>
    <h2>Registrer Bruker</h2>
    <form method="post">
        <input type="text" name="name" placeholder="Navn" required>
        <input type="email" name="email" placeholder="E-post" required>
        <input type="password" name="password" placeholder="Passord" required>
        <select name="role">
            <option value="student">Student</option>
            <option value="foreleser">Foreleser</option>
            <option value="admin">Admin</option>
        </select>
        <button type="submit">Registrer</button>
    </form>
</body>
</html>
