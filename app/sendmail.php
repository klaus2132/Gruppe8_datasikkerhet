<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../PHPMailer-master/src/Exception.php';
require __DIR__ . '/../PHPMailer-master/src/PHPMailer.php';
require __DIR__ . '/../PHPMailer-master/src/SMTP.php';

function sendResetEmail($email, $token) {
    $mail = new PHPMailer(true);

    try {
        // Sett SMTP til å bruke serveren
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // SMTP-server
        $mail->SMTPAuth = true;
        $mail->Username = 'datasikkerhet8@gmail.com'; // E-postadresse
        $mail->Password = 'oujj mukm cobx okdm'; // Bruk App-passordet ditt for Gmail
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Sett e-postinnstillinger
        $mail->setFrom('datasikkerhet8@gmail.com', 'Gruppe 8 Datasikkerhet');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'Tilbakestill passord';
        $mail->Body = "Klikk her for å tilbakestille passordet ditt: <br><br>
        <a href='http://158.39.188.211/steg1/public/reset_password.php?token=$token'>Tilbakestill passord</a>";

        // Deaktiver debug
        $mail->SMTPDebug = 0; // Ingen debug-utdata

        // Send e-post
        if ($mail->send()) {
            return true;  // E-post sendt
        } else {
            return false;  // Hvis e-posten ikke ble sendt
        }
    } catch (Exception $e) {
        // Returner en generell feilmelding uten detaljer
        return false;
    }
}
?>
