<?php
require '../app/config.php';  // Koble til databasen

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // Hent token og sjekk utløp
    $stmt = $conn->prepare("SELECT lecturer_id, reset_expires FROM lecturers WHERE reset_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $expires = $user['reset_expires'];

        // Sjekk om token har utløpt
        if (strtotime($expires) > time()) {
            // Token er gyldig, vis skjema for passordendring
            if ($_SERVER["REQUEST_METHOD"] == "POST") {
                $new_password = $_POST['password'];
                $confirm_password = $_POST['confirm_password'];

                // Sjekk om passordene stemmer overens
                if ($new_password === $confirm_password) {
                    // Oppdater passordet i databasen (uten hashing)
                    $stmt = $conn->prepare("UPDATE lecturers SET password = ?, reset_token = NULL, reset_expires = NULL WHERE lecturer_id = ?");
                    $stmt->bind_param("si", $new_password, $user['lecturer_id']);
                    if ($stmt->execute()) {
                        echo "Passordet ble oppdatert.";
                        // Omleire til index.php etter vellykket oppdatering
                        header("Location: index.php");
                        exit();
                    } else {
                        echo "Noe gikk galt, prøv igjen.";
                    }
                } else {
                    echo "Passordene stemmer ikke overens.";
                }
            }
            // Vis skjema for nytt passord
            echo '<form method="POST">
                    <input type="password" name="password" placeholder="Skriv inn nytt passord" required><br>
                    <input type="password" name="confirm_password" placeholder="Bekreft nytt passord" required><br>
                    <button type="submit">Endre passord</button>
                  </form>';
        } else {
            echo "Token har utløpt.";
        }
    } else {
        echo "Ugyldig token.";
    }
} else {
    echo "Ingen token funnet.";
}
?>
