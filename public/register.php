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
        // SQL for å registrere student
        $sql = "INSERT INTO students (name, email, password) VALUES (?, ?, ?)";
    } elseif ($role == "foreleser") {
        // Get subject name and subject pin
        $subject_name = $_POST['subject_name'];
        $subject_pin = $_POST['subject_pin'];

        // First, check if the subject already exists
        $subject_sql = "SELECT subject_id FROM subjects WHERE subject_name = ? AND subject_pin = ?";
        if ($stmt = $conn->prepare($subject_sql)) {
            $stmt->bind_param("ss", $subject_name, $subject_pin);
            $stmt->execute();
            $stmt->bind_result($subject_id);
            $stmt->fetch();
            $stmt->close();

            // If the subject doesn't exist, insert it
            if (!$subject_id) {
                $insert_subject_sql = "INSERT INTO subjects (subject_name, subject_pin) VALUES (?, ?)";
                if ($insert_stmt = $conn->prepare($insert_subject_sql)) {
                    $insert_stmt->bind_param("ss", $subject_name, $subject_pin);
                    $insert_stmt->execute();
                    $subject_id = $insert_stmt->insert_id;  // Get the last inserted subject_id
                    $insert_stmt->close();
                } else {
                    echo "Feil ved opprettelse av emnet.";
                    exit();
                }
            }
        } else {
            echo "Feil ved å sjekke emnet.";
            exit();
        }

        // SQL for å registrere foreleser med subject_id
        $sql = "INSERT INTO lecturers (name, email, password, subject_id) VALUES (?, ?, ?, ?)";
    } else {
        // SQL for å registrere admin
        $sql = "INSERT INTO admin (username, email, password) VALUES (?, ?, ?)";
    }

    // Forbered SQL-spørringen
    if ($stmt = $conn->prepare($sql)) {
        // Bind parametrene basert på rolle
        if ($role == "student") {
            $stmt->bind_param("sss", $name, $email, $password);
        } elseif ($role == "foreleser") {
            $stmt->bind_param("ssss", $name, $email, $password, $subject_id);
        } else {
            $stmt->bind_param("sss", $name, $email, $password);
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

    <select name="role" id="role" required onchange="toggleFields()">
        <option value="student">Student</option>
        <option value="foreleser">Foreleser</option>
        <option value="admin">Admin</option>
    </select>

    <!-- Subject Name and Subject Pin (Only for Lecturers) -->
    <div id="subjectField" style="display: none;">
        <input type="text" name="subject_name" placeholder="Kursnavn" required>
        <input type="text" name="subject_pin" placeholder="Emne Pin" required>
    </div>

    <button type="submit">Registrer</button>
</form>

<script>
function toggleFields() {
    let role = document.getElementById("role").value;
    document.getElementById("subjectField").style.display = (role === "foreleser") ? "block" : "none";
}

// Run once on page load in case of preselected value
document.addEventListener("DOMContentLoaded", toggleFields);
</script>
