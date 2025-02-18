<?php
include '../app/config.php';  // Koble til databasen

// Håndter visning av meldinger
$messages = [];
$responses = [];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["subject_id"], $_POST["subject_pin"])) {
    $subject_id = $_POST["subject_id"];
    $subject_pin = $_POST["subject_pin"];

    // Sjekk om PIN-koden er riktig
    $check_sql = "SELECT subject_id FROM subjects WHERE subject_id = ? AND subject_pin = ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("is", $subject_id, $subject_pin);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->close();

        // Hent meldinger for emnet
        $message_sql = "SELECT message_id, message, created_at FROM messages WHERE subject_id = ? ORDER BY created_at DESC";
        $stmt = $conn->prepare($message_sql);
        $stmt->bind_param("i", $subject_id);
        $stmt->execute();
        $stmt->bind_result($message_id, $message, $created_at);
        
        while ($stmt->fetch()) {
            $messages[] = [
                'message_id' => $message_id,
                'message' => $message,
                'created_at' => $created_at
            ];
        }
        $stmt->close();

        // Hent svar til hver melding
        foreach ($messages as $msg) {
            $response_sql = "SELECT response, created_at FROM responses WHERE message_id = ? ORDER BY created_at ASC LIMIT 1"; // Kun ett svar
            $stmt = $conn->prepare($response_sql);
            $stmt->bind_param("i", $msg['message_id']);
            $stmt->execute();
            $stmt->bind_result($response, $response_created_at);
            if ($stmt->fetch()) {
                $responses[$msg['message_id']] = [
                    'response' => $response,
                    'created_at' => $response_created_at
                ];
            }
            $stmt->close();
        }
    } else {
        $error = "Feil PIN-kode eller emne.";
    }
}

// Håndter rapportering av meldinger
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["report_message"])) {
    $message_id = $_POST["message_id"];
    $report_reason = $_POST["report_reason"];

    // Sjekk om meldingen allerede er rapportert
    $check_sql = "SELECT COUNT(*) FROM reported_messages WHERE message_id = ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("i", $message_id);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if ($count > 0) {
        $report_error = "Denne meldingen er allerede rapportert.";
    } else {
        // Sett inn rapportering i databasen
        $report_sql = "INSERT INTO reported_messages (message_id, report_reason, reported_at) VALUES (?, ?, NOW())";
        $stmt = $conn->prepare($report_sql);
        $stmt->bind_param("is", $message_id, $report_reason);
        
        if ($stmt->execute()) {
            $report_success = "Meldingen er rapportert!";
        } else {
            $report_error = "Feil ved rapportering: " . $conn->error;
        }
        
        $stmt->close();
    }
}

// Hent tilgjengelige emner
$subjects = [];
$subject_sql = "SELECT subject_id, subject_name FROM subjects";
$result = $conn->query($subject_sql);
while ($row = $result->fetch_assoc()) {
    $subjects[] = $row;
}
?>

<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gjest Dashboard</title>
</head>
<body>
    <h2>Velkommen til gjestepanelet</h2>
    
    <form method="post" action="guest_dashboard.php">
        <select name="subject_id" required>
            <option value="" selected disabled>Velg emne</option>
            <?php foreach ($subjects as $subject): ?>
                <option value="<?= $subject['subject_id'] ?>"><?= htmlspecialchars($subject['subject_name']) ?></option>
            <?php endforeach; ?>
        </select>

        <label for="subject_pin">Skriv inn PIN:</label>
        <input type="text" name="subject_pin" pattern="\d{4}" title="Fire-sifret kode" required>

        <button type="submit">Se meldinger</button>
    </form>

    <?php if (isset($error)): ?>
        <p style="color:red;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <?php if (!empty($messages)): ?>
        <h3>Meldinger:</h3>
        <ul>
            <?php foreach ($messages as $msg): ?>
                <li>
                    <strong>Anonym student:</strong> <?= htmlspecialchars($msg['message']) ?>
                    <br>
                    <small>Sendt: <?= $msg['created_at'] ?></small>

                    <!-- Rapporteringsskjema -->
                    <form method="post" action="guest_dashboard.php" style="display: inline;">
                        <input type="hidden" name="message_id" value="<?= $msg['message_id'] ?>">
                        <br>
                        <input type="text" name="report_reason" placeholder="Begrunnelse" required>
                        <button type="submit" name="report_message">Rapporter</button>
                    </form>

                    <!-- Viser svar hvis det finnes -->
                    <?php if (isset($responses[$msg['message_id']])): ?>
                        <ul>
                            <li><strong>Foreleser:</strong> <?= htmlspecialchars($responses[$msg['message_id']]['response']) ?>
                                <br><small>Sendt: <?= $responses[$msg['message_id']]['created_at'] ?></small></li>
                        </ul>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <?php if (isset($report_success)): ?>
        <p style="color:green;"><?= htmlspecialchars($report_success) ?></p>
    <?php elseif (isset($report_error)): ?>
        <p style="color:red;"><?= htmlspecialchars($report_error) ?></p>
    <?php endif; ?>
    <br>
    <a href="index.php">Gå til innloggingssiden</a>
</body>
</html>
