<?php
include '../app/config.php';  
session_start();

$selected_subject = null;
$messages = [];
$responses = [];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['subject_id'], $_POST['subject_pin'])) {
    $subject_id = $_POST['subject_id'];
    $subject_pin = $_POST['subject_pin'];

    // Sjekk om PIN er riktig
    $pin_check_sql = "SELECT subject_name FROM subjects WHERE subject_id = ? AND subject_pin = ?";
    if ($stmt = $conn->prepare($pin_check_sql)) {
        $stmt->bind_param("is", $subject_id, $subject_pin);
        $stmt->execute();
        $stmt->bind_result($subject_name);
        if ($stmt->fetch()) {
            $selected_subject = [
                'subject_id' => $subject_id,
                'subject_name' => $subject_name
            ];
        }
        $stmt->close();
    }

    // Hent meldinger hvis PIN er riktig
    if ($selected_subject) {
        $message_sql = "SELECT message_id, message, created_at FROM messages WHERE subject_id = ? ORDER BY created_at DESC";
        if ($stmt = $conn->prepare($message_sql)) {
            $stmt->bind_param("i", $subject_id);
            $stmt->execute();
            $stmt->bind_result($message_id, $message, $created_at);
            while ($stmt->fetch()) {
                $messages[$message_id] = [
                    'message_id' => $message_id,
                    'message' => $message,
                    'created_at' => $created_at,
                    'response' => null // Legger til plass for svar
                ];
            }
            $stmt->close();
        }

        // Hent svar til hver melding
        if (!empty($messages)) {
            $message_ids = array_keys($messages);
            $placeholders = implode(',', array_fill(0, count($message_ids), '?'));

            $response_sql = "SELECT message_id, response, created_at FROM responses WHERE message_id IN ($placeholders) ORDER BY created_at ASC";
            if ($stmt = $conn->prepare($response_sql)) {
                $stmt->bind_param(str_repeat("i", count($message_ids)), ...$message_ids);
                $stmt->execute();
                $stmt->bind_result($response_message_id, $response, $response_created_at);
                
                while ($stmt->fetch()) {
                    // Bare lagre første svaret per melding
                    if ($messages[$response_message_id]['response'] === null) {
                        $messages[$response_message_id]['response'] = [
                            'response' => $response,
                            'created_at' => $response_created_at
                        ];
                    }
                }
                $stmt->close();
            }
        }
    }
}

// Hent alle emner (uten PIN)
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
    <h2>Velkommen til gjeste-dashboard</h2>

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

    <?php if ($selected_subject): ?>
        <h3>Meldinger for <?= htmlspecialchars($selected_subject['subject_name']) ?>:</h3>

        <?php if (!empty($messages)): ?>
            <ul>
                <?php foreach ($messages as $msg): ?>
                    <li>
                        <strong>Anonym student:</strong> <?= htmlspecialchars($msg['message']) ?>
                        <br>
                        <small>Sendt: <?= $msg['created_at'] ?></small>

                        <?php if ($msg['response']): ?>
                            <ul>
                                <li><strong>Foreleser:</strong> <?= htmlspecialchars($msg['response']['response']) ?>
                                    <br><small>Sendt: <?= $msg['response']['created_at'] ?></small></li>
                            </ul>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>Ingen meldinger for dette emnet.</p>
        <?php endif; ?>
    <?php endif; ?>
    <br>
    <a href="index.php">Gå til innloggingssiden</a>
</body>
</html>
