<?php
session_start();
include '../app/config.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    die("Uautorisert tilgang.");
}

$message = "";
$user_id = $_SESSION['user_id'];
$role = $_SESSION['user_role'];

// Velg riktig tabell og ID-felt basert på rollen
if ($role == "student") {
    $table = "students";
    $id_field = "student_id";
} elseif ($role == "foreleser") {
    $table = "lecturers";
    $id_field = "lecturer_id";
} else {
    die("Ugyldig brukerrolle.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $old_password = $_POST['old_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        $message = "Nye passord er ikke like.";
    } else {
        // Hent nåværende passord fra databasen
        $stmt = $conn->prepare("SELECT password FROM $table WHERE $id_field = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->bind_result($stored_password);
        $stmt->fetch();
        $stmt->close();

        if ($old_password !== $stored_password) {
            $message = "Gammelt passord er feil.";
        } else {
            // Oppdater passordet i riktig tabell
            $stmt = $conn->prepare("UPDATE $table SET password = ? WHERE $id_field = ?");
            $stmt->bind_param("si", $new_password, $user_id);

            if ($stmt->execute()) {
                $message = "Passordet ble endret.";
            } else {
                $message = "Feil ved oppdatering av passord.";
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Endre Passord</title>
</head>
<body>

<h2>Endre Passord</h2>

<?php if (!empty($message)) echo "<p><strong>$message</strong></p>"; ?>

<form method="POST" action="">
    <label>Gammelt passord:</label>
    <input type="password" name="old_password" required><br>

    <label>Nytt passord:</label>
    <input type="password" name="new_password" required><br>

    <label>Bekreft nytt passord:</label>
    <input type="password" name="confirm_password" required><br>

    <button type="submit">Oppdater Passord</button>
</form>

<a href="dashboard.php">Tilbake til dashboard</a>

</body>
</html>

