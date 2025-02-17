<?php
include '../app/config.php';  // Sørg for at du har riktig tilkobling til databasen

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Hent verdiene fra skjemaet
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $role = $_POST['role'];

    // Håndter registreringen basert på rollen
    if ($role == "student") {
        $sql = "INSERT INTO students (name, email, password) VALUES (?, ?, ?)";
    } elseif ($role == "foreleser") {
        // Sjekk om emnekode er spesifisert
        $subject_id = isset($_POST['subject_id']) ? $_POST['subject_id'] : null;
        $sql = "INSERT INTO lecturers (name, email, password, subject_id) VALUES (?, ?, ?, ?)";
    } else {
        $sql = "INSERT INTO admin (username, email, password) VALUES (?, ?, ?)";
    }

    // Forbered SQL-spørringen
    if ($stmt = $conn->prepare($sql)) {
        // Bind parametrene
        if ($role == "student" || $role == "admin") {
            $stmt->bind_param("sss", $name, $email, $password);
        } elseif ($role == "foreleser") {
            $stmt->bind_param("ssss", $name, $email, $password, $subject_id);
        }

        // Utfør SQL-spørringen
        if ($stmt->execute()) {
            // Redirect etter vellykket registrering
            header("Location: index.php"); // Sender brukeren til innloggingssiden
            exit(); // Sørg for at ingen ytterligere kode blir kjørt
        } else {
            echo "Feil under registrering: " . $stmt->error;
        }

        // Lukk statement
        $stmt->close();
    } else {
        echo "Feil ved forberedelse av spørring: " . $conn->error;
    }
}
?>

<form method="post" action="register.php">
    <input type="text" name="name" placeholder="Navn" required>
    <input type="email" name="email" placeholder="E-post" required>
    <input type="password" name="password" placeholder="Passord" required>
    
    <select name="role" required>
        <option value="student">Student</option>
        <option value="foreleser">Foreleser</option>
        <option value="admin">Admin</option>
    </select>
    
    <div>
        <!-- Emnekode er ikke nødvendig, så det er ikke nødvendig å vise dette feltet -->
        <input type="text" name="subject_id" placeholder="Emnekode (for foreleser)">
    </div>

    <button type="submit">Registrer</button>
</form>
