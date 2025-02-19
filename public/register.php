<?php
include '../app/config.php';  // Sørg for at du har riktig tilkobling til databasen

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Hent verdiene fra skjemaet
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $role = $_POST['role'];
    $image_path = NULL; // Standardverdi

    // Håndter bildeopplastning for forelesere
    if ($role == "foreleser" && isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $target_dir = "../uploads/";
        $image_file = basename($_FILES['profile_image']['name']);
        $target_file = $target_dir . $image_file;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Sjekk om filen er et bilde
        $check = getimagesize($_FILES['profile_image']['tmp_name']);
        if ($check !== false) {
            // Begrens filtyper
            if (in_array($imageFileType, ["jpg", "jpeg", "png", "gif"])) {
                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file)) {
                    $image_path = $image_file; // Lagre bare filnavnet
                } else {
                    echo "Feil ved opplasting av bilde.";
                    exit();
                }
            } else {
                echo "Kun JPG, JPEG, PNG og GIF filer er tillatt.";
                exit();
            }
        } else {
            echo "Filen er ikke et gyldig bilde.";
            exit();
        }
    }

    // Håndter registreringen basert på rollen
    if ($role == "student") {
        $sql = "INSERT INTO students (name, email, password) VALUES (?, ?, ?)";
    } elseif ($role == "foreleser") {
        $subject_name = $_POST['subject_name'];
        $subject_pin = $_POST['subject_pin'];

        // Sjekk om faget eksisterer
        $subject_sql = "SELECT subject_id FROM subjects WHERE subject_name = ? AND subject_pin = ?";
        if ($stmt = $conn->prepare($subject_sql)) {
            $stmt->bind_param("ss", $subject_name, $subject_pin);
            $stmt->execute();
            $stmt->bind_result($subject_id);
            $stmt->fetch();
            $stmt->close();

            if (!$subject_id) {
                $insert_subject_sql = "INSERT INTO subjects (subject_name, subject_pin) VALUES (?, ?)";
                if ($insert_stmt = $conn->prepare($insert_subject_sql)) {
                    $insert_stmt->bind_param("ss", $subject_name, $subject_pin);
                    $insert_stmt->execute();
                    $subject_id = $insert_stmt->insert_id;
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

        $sql = "INSERT INTO lecturers (name, email, password, subject_id, image_path) VALUES (?, ?, ?, ?, ?)";
    } else {
        $sql = "INSERT INTO admin (username, email, password) VALUES (?, ?, ?)";
    }

    // Forbered SQL-spørringen
    if ($stmt = $conn->prepare($sql)) {
        if ($role == "student") {
            $stmt->bind_param("sss", $name, $email, $password);
        } elseif ($role == "foreleser") {
            $stmt->bind_param("sssss", $name, $email, $password, $subject_id, $image_path);
        } else {
            $stmt->bind_param("sss", $name, $email, $password);
        }

        if ($stmt->execute()) {
            header("Location: index.php");
            exit();
        } else {
            echo "Feil under registrering: " . $stmt->error;
        }
        $stmt->close();
    } else {
        echo "Feil ved forberedelse av spørring: " . $conn->error;
    }
}
?>

<form method="post" action="register.php" enctype="multipart/form-data">
    <input type="text" name="name" placeholder="Navn" required>
    <input type="email" name="email" placeholder="E-post" required>
    <input type="password" name="password" placeholder="Passord" required>

    <select name="role" id="role" required onchange="toggleFields()">
        <option value="student">Student</option>
        <option value="foreleser">Foreleser</option>
    </select>

    <div id="subjectField" style="display: none;">
        <input type="text" name="subject_name" id="subject_name" placeholder="Kursnavn">
        <input type="text" name="subject_pin" id="subject_pin" placeholder="Emne Pin">
        <input type="file" name="profile_image" id="profile_image" accept="image/*">
    </div>

    <button type="submit">Registrer</button>
</form>

<script>
document.addEventListener("DOMContentLoaded", function() {
    function toggleFields() {
        let role = document.getElementById("role").value;
        let subjectField = document.getElementById("subjectField");
        let subjectName = document.getElementById("subject_name");
        let subjectPin = document.getElementById("subject_pin");
        let profileImage = document.getElementById("profile_image");

        if (role === "foreleser") {
            subjectField.style.display = "block";
            subjectName.setAttribute("required", "required");
            subjectPin.setAttribute("required", "required");
            profileImage.setAttribute("required", "required");
        } else {
            subjectField.style.display = "none";
            subjectName.removeAttribute("required");
            subjectPin.removeAttribute("required");
            profileImage.removeAttribute("required");
        }
    }
    toggleFields();
    document.getElementById("role").addEventListener("change", toggleFields);
});
</script>
