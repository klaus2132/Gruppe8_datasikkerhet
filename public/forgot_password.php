<?php
require '../app/config.php';  // Koble til databasen
require '../app/sendmail.php'; // Inkluder PHPMailer-funksjonen

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];

    // Sjekk om e-posten finnes i databasen (mysqli)
    $stmt = $conn->prepare("SELECT lecturer_id FROM lecturers WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    // Rydd opp etter SELECT-resultatene
    $result->free();

    if ($user) {
        // Generer en unik token og lagre i databasen
        $token = bin2hex(random_bytes(50));
        $expires = date("Y-m-d H:i:s", strtotime("+2 hours"));  // Token utløper etter 2 timer

        // Oppdater databasen med reset_token og reset_expires
        $stmt = $conn->prepare("UPDATE lecturers SET reset_token = ?, reset_expires = ? WHERE email = ?");
        $stmt->bind_param("sss", $token, $expires, $email);
        
        if ($stmt->execute()) {
            // Send e-post med tilbakestillingslenke
            if (sendResetEmail($email, $token)) {
                echo "Vi har sendt en e-post med instruksjoner for å tilbakestille passordet. Vennligst sjekk innboksen din.";
            } else {
                echo "Vi kunne ikke sende e-posten. Prøv igjen senere.";
            }
        } else {
            echo "Det oppstod en feil. Vennligst prøv igjen.";
        }
    } else {
        echo "Vi finner ikke e-posten i systemet vårt. Sjekk om du har skrevet den korrekt.";
    }
}
?>

<form method="POST">
    <input type="email" name="email" placeholder="Skriv inn din e-post" required>
    <button type="submit">Send tilbakestillingslenke</button>
</form>
