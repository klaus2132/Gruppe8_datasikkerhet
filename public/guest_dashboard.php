<?php
session_start(); // Start sessionen for å kunne bruke sessioner

include '../app/config.php';  // Koble til databasen

// Håndter visning av meldinger og kommentarer
$messages = [];

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
        // Lagre i sessionen at brukeren har tilgang
        $_SESSION['logged_in'] = true;
        $_SESSION['subject_id'] = $subject_id;  // Lagre emne-id for å huske det

        $stmt->close();

        // Hent meldinger for emnet
        $message_sql = "
            SELECT m.message_id, m.message, m.created_at, s.subject_name 
            FROM messages m
            JOIN subjects s ON m.subject_id = s.subject_id
            WHERE m.subject_id = ? ORDER BY m.created_at DESC
        ";
        $stmt = $conn->prepare($message_sql);
        $stmt->bind_param("i", $subject_id);
        $stmt->execute();
        $stmt->bind_result($message_id, $message, $created_at, $subject_name);

        $messages = [];
        while ($stmt->fetch()) {
            $messages[] = [
                'message_id' => $message_id,
                'message' => $message,
                'created_at' => $created_at,
                'subject_name' => $subject_name,
            ];
        }
        $stmt->close();

        // Hent foreleserens informasjon én gang, inkludert bildet
        $lecturer_sql = "
            SELECT l.name, l.email, l.image_path
            FROM lecturers l
            JOIN subjects s ON s.subject_id = l.subject_id
            WHERE s.subject_id = ?
        ";
        $stmt = $conn->prepare($lecturer_sql);
        $stmt->bind_param("i", $subject_id);
        $stmt->execute();
        $stmt->bind_result($lecturer_name, $lecturer_email, $lecturer_image);
        $stmt->fetch(); // Hent én rad med foreleserens informasjon
        $stmt->close();

        // Lagre foreleserens informasjon i session
        $_SESSION['lecturer_name'] = $lecturer_name;
        $_SESSION['lecturer_email'] = $lecturer_email;
        $_SESSION['lecturer_image'] = $lecturer_image;

        // Lagre meldinger i sessionen for senere visning
        $_SESSION['messages'] = $messages;

        // Hent kommentarer for alle meldinger
        $comments = [];
        foreach ($messages as $msg) {
            $comment_sql = "SELECT comment, created_at FROM comments WHERE message_id = ? ORDER BY created_at ASC";
            $stmt = $conn->prepare($comment_sql);
            $stmt->bind_param("i", $msg['message_id']);
            $stmt->execute();
            $stmt->bind_result($comment_text, $comment_created_at);

            // Lag en array med alle kommentarer
            while ($stmt->fetch()) {
                $comments[$msg['message_id']][] = [
                    'comment' => $comment_text,
                    'created_at' => $comment_created_at
                ];
            }
            $stmt->close();
        }

        // Lagre kommentarer i sessionen
        $_SESSION['comments'] = $comments;

        // Hent rapporter for alle meldinger
        $reports = [];
        foreach ($messages as $msg) {
            $report_sql = "SELECT report_reason, reported_at FROM reported_messages WHERE message_id = ? ORDER BY reported_at ASC";
            $stmt = $conn->prepare($report_sql);
            $stmt->bind_param("i", $msg['message_id']);
            $stmt->execute();
            $stmt->bind_result($report_reason, $reported_at);

            // Lag en array med alle rapporter
            while ($stmt->fetch()) {
                $reports[$msg['message_id']][] = [
                    'report_reason' => $report_reason,
                    'reported_at' => $reported_at
                ];
            }
            $stmt->close();
        }

        // Lagre rapporter i sessionen
        $_SESSION['reports'] = $reports;

    } else {
        $error = "Feil PIN-kode eller emne.";
    }
}

// Hent tilgjengelige emner
$subjects = [];
$subject_sql = "SELECT subject_id, subject_name FROM subjects";
$result = $conn->query($subject_sql);
while ($row = $result->fetch_assoc()) {
    $subjects[] = $row;
}

// Håndter kommentarer
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["comment"], $_POST["message_id"])) {
    $comment = $_POST["comment"];
    $message_id = $_POST["message_id"];

    if (!empty($comment)) {
        $comment_sql = "INSERT INTO comments (message_id, comment, created_at) VALUES (?, ?, NOW())";
        $stmt = $conn->prepare($comment_sql);
        $stmt->bind_param("is", $message_id, $comment);

        if ($stmt->execute()) {
            // Når kommentaren er lagt til, oppdater sesjonen med ny kommentar
            $stmt->close();

            // Hent alle kommentarer for denne meldingen
            $comment_sql = "SELECT comment, created_at FROM comments WHERE message_id = ? ORDER BY created_at ASC";
            $stmt = $conn->prepare($comment_sql);
            $stmt->bind_param("i", $message_id);
            $stmt->execute();
            $stmt->bind_result($comment_text, $comment_created_at);

            // Lag en array med alle kommentarer
            $comments = [];
            while ($stmt->fetch()) {
                $comments[] = [
                    'comment' => $comment_text,
                    'created_at' => $comment_created_at
                ];
            }
            $stmt->close();

            // Legg til kommentarer i sessionen
            $_SESSION['comments'][$message_id] = $comments;
        } else {
            $error = "Kommentar kunne ikke legges til.";
        }
    } else {
        $error = "Kommentar kan ikke være tom.";
    }
}

// Håndter rapportering
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["report_reason"], $_POST["message_id"])) {
    $report_reason = $_POST["report_reason"];
    $message_id = $_POST["message_id"];

    if (!empty($report_reason)) {
        $report_sql = "INSERT INTO reported_messages (message_id, report_reason, reported_at) VALUES (?, ?, NOW())";
        $stmt = $conn->prepare($report_sql);
        $stmt->bind_param("is", $message_id, $report_reason);

        if ($stmt->execute()) {
            // Når rapporten er lagt til, oppdater sesjonen med ny rapport
            $stmt->close();

            // Hent alle rapporter for denne meldingen
            $report_sql = "SELECT report_reason, reported_at FROM reported_messages WHERE message_id = ? ORDER BY reported_at ASC";
            $stmt = $conn->prepare($report_sql);
            $stmt->bind_param("i", $message_id);
            $stmt->execute();
            $stmt->bind_result($report_reason, $reported_at);

            // Lag en array med alle rapporter
            $reports = [];
            while ($stmt->fetch()) {
                $reports[] = [
                    'report_reason' => $report_reason,
                    'reported_at' => $reported_at
                ];
            }
            $stmt->close();

            // Legg til rapporter i sessionen
            $_SESSION['reports'][$message_id] = $reports;
        } else {
            $error = "Rapport kunne ikke legges til.";
        }
    } else {
        $error = "Rapport kan ikke være tom.";
    }
}

// Logg ut
if (isset($_GET['logout'])) {
    session_destroy();  // Ødelegg sessionen for å logge ut
    header("Location: guest_dashboard.php"); // Omfattende redirect for å tvinge brukeren til å logge inn på nytt
    exit;
}
?>
<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gjest Dashboard</title>

    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h2>Velkommen til gjestepanelet</h2>

    <?php if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']): ?>
        <!-- Vis skjema for å skrive inn PIN-kode hvis brukeren ikke er logget inn -->
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

        <!-- Vis linken til innloggingssiden når ikke logget inn -->
        <br>
        <a href="index.php">Innloggingssiden</a>
        
    <?php else: ?>
        <!-- Vis foreleserens navn, e-post og bilde -->
        <h3>Foreleser: <?= htmlspecialchars($_SESSION['lecturer_name']) ?></h3>
        <p><strong>E-post:</strong> <?= htmlspecialchars($_SESSION['lecturer_email']) ?></p>
        <?php if (!empty($_SESSION['lecturer_image'])): ?>
            <img src="../uploads/<?= htmlspecialchars($_SESSION['lecturer_image']) ?>" alt="Foreleserens bilde" style="max-width: 150px; height: auto;">
        <?php endif; ?>

        <!-- Vis meldinger og kommentarer -->
        <?php if (isset($error)): ?>
            <p style="color:red;"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <?php if (!empty($_SESSION['messages'])): ?>
            <h3>Meldinger:</h3>
            <ul>
                <?php foreach ($_SESSION['messages'] as $msg): ?>
                    <li>
                        <strong>Anonym student:</strong> <?= htmlspecialchars($msg['message']) ?>
                        <br>
                        <small>Sendt: <?= $msg['created_at'] ?></small>

                        <!-- Form for å rapportere meldingen -->
                        <form method="post" action="guest_dashboard.php">
                            <textarea name="report_reason" placeholder="Skriv årsak for rapporteringen..." required></textarea><br>
                            <input type="hidden" name="message_id" value="<?= $msg['message_id'] ?>">
                            <button type="submit">Rapporter melding</button>
                        </form>

                        <h4>Kommentarer:</h4>
                        <?php
                        // Hent kommentarer fra session
                        if (isset($_SESSION['comments'][$msg['message_id']])) {
                            echo "<ul>";
                            foreach ($_SESSION['comments'][$msg['message_id']] as $comment) {
                                echo "<li><strong>Gjest:</strong> " . htmlspecialchars($comment['comment']) . "<br><small>Sendt: " . $comment['created_at'] . "</small></li>";
                            }
                            echo "</ul>";
                        } else {
                            echo "<p>Ingen kommentarer ennå.</p>";
                        }
                        ?>

                        <!-- Form for å legge til kommentar til denne meldingen -->
                        <form method="post" action="guest_dashboard.php">
                            <textarea name="comment" placeholder="Skriv en kommentar..." required></textarea><br>
                            <input type="hidden" name="message_id" value="<?= $msg['message_id'] ?>">
                            <button type="submit">Legg til kommentar</button>
                        </form>

                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Flytt logg ut nederst på siden -->
    <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
        <p><a href="?logout=true">Logg ut</a></p>
    <?php endif; ?>

</body>
</html>
