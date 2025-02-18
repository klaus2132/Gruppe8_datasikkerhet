<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

include '../app/config.php';  // Ensure this path is correct

if ($user_role == 'foreleser') {
    // Hent fag som foreleseren underviser
    $subject_sql = "SELECT s.subject_name, s.subject_id 
                    FROM subjects s
                    JOIN lecturers l ON s.subject_id = l.subject_id
                    WHERE l.lecturer_id = ?";
    
    if ($stmt = $conn->prepare($subject_sql)) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->bind_result($subject_name, $subject_id);
        if (!$stmt->fetch()) {
            echo "Ingen emne funnet for denne foreleseren.";
            exit();
        }
        $stmt->close();

        // Hent meldinger for emnet
        $message_sql = "SELECT m.message_id, m.message, m.created_at 
                        FROM messages m
                        WHERE m.subject_id = ? 
                        ORDER BY m.created_at DESC";
        
        if ($stmt = $conn->prepare($message_sql)) {
            $stmt->bind_param("i", $subject_id);
            $stmt->execute();
            $stmt->bind_result($message_id, $message, $created_at);
            $messages = [];
            while ($stmt->fetch()) {
                $messages[] = [
                    'message_id' => $message_id,
                    'message' => $message,
                    'created_at' => $created_at
                ];
            }
            $stmt->close();
        } else {
            echo "Feil ved å hente meldingene: " . $conn->error;
            exit();
        }

        // Hent responser, men kun én per melding
        $responses = [];
        foreach ($messages as $msg) {
            $response_sql = "SELECT r.response_id, r.response, r.created_at 
                             FROM responses r 
                             WHERE r.message_id = ? 
                             LIMIT 1"; // Sikrer at kun én respons hentes
            
            if ($stmt = $conn->prepare($response_sql)) {
                $stmt->bind_param("i", $msg['message_id']);
                $stmt->execute();
                $stmt->bind_result($response_id, $response, $response_created_at);
                if ($stmt->fetch()) {
                    $responses[$msg['message_id']] = [
                        'response_id' => $response_id,
                        'response' => $response,
                        'created_at' => $response_created_at
                    ];
                }
                $stmt->close();
            }
        }
    }
}

if ($user_role == 'student') {
    // Hent studentens meldinger
    $message_sql = "SELECT m.message_id, m.message, m.created_at, s.subject_name 
                    FROM messages m
                    JOIN subjects s ON m.subject_id = s.subject_id
                    WHERE m.student_id = ? 
                    ORDER BY m.created_at DESC";
    
    if ($stmt = $conn->prepare($message_sql)) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->bind_result($message_id, $message, $created_at, $subject_name);
        $messages = [];
        while ($stmt->fetch()) {
            $messages[] = [
                'message_id' => $message_id,
                'message' => $message,
                'created_at' => $created_at,
                'subject_name' => $subject_name
            ];
        }
        $stmt->close();
    } else {
        echo "Feil ved å hente meldingene: " . $conn->error;
        exit();
    }

    // Hent responser (kun én per melding)
    $responses = [];
    foreach ($messages as $msg) {
        $response_sql = "SELECT r.response_id, r.response, r.created_at 
                         FROM responses r 
                         WHERE r.message_id = ? 
                         LIMIT 1"; // Kun én respons per melding
        
        if ($stmt = $conn->prepare($response_sql)) {
            $stmt->bind_param("i", $msg['message_id']);
            $stmt->execute();
            $stmt->bind_result($response_id, $response, $response_created_at);
            if ($stmt->fetch()) {
                $responses[$msg['message_id']] = [
                    'response_id' => $response_id,
                    'response' => $response,
                    'created_at' => $response_created_at
                ];
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
    <title>Dashboard</title>
</head>
<body>
    <h2>Velkommen til dashboardet!</h2>
    <p>Du er logget inn som: <?php echo $user_role; ?></p>
    
    <?php if ($user_role == 'foreleser'): ?>
        <h3>Emnet du underviser:</h3>
        <p><strong>Emnenavn:</strong> <?php echo $subject_name; ?></p>

        <h3>Meldinger for emnet:</h3>
        <?php if (!empty($messages)): ?>
            <ul>
                <?php foreach ($messages as $msg): ?>
                    <li>
                        <strong>Anonym student:</strong> <?php echo $msg['message']; ?>
                        <br>
                        <small>Sendt: <?php echo $msg['created_at']; ?></small>
                        <br>

                        <?php if (isset($responses[$msg['message_id']])): ?>
                            <p><strong>Respons:</strong> <?php echo $responses[$msg['message_id']]['response']; ?></p>
                        <?php else: ?>
                            <a href="reply_message.php?message_id=<?php echo $msg['message_id']; ?>">Svar på denne meldingen</a>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>Ingen meldinger for dette emnet.</p>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($user_role == 'student'): ?>
        <h3>Mine meldinger:</h3>
        <?php if (!empty($messages)): ?>
            <ul>
                <?php foreach ($messages as $msg): ?>
                    <li>
                        <strong>Emne:</strong> <?php echo $msg['subject_name']; ?><br>
                        <strong>Min melding:</strong> <?php echo $msg['message']; ?><br>
                        <small>Sendt: <?php echo $msg['created_at']; ?></small><br>

                        <?php if (isset($responses[$msg['message_id']])): ?>
                            <p><strong>Respons fra foreleser:</strong> <?php echo $responses[$msg['message_id']]['response']; ?></p>
                        <?php else: ?>
                            <p><strong>Ingen respons enda.</strong></p>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>Du har ikke sendt noen meldinger enda.</p>
        <?php endif; ?>
    <?php endif; ?>

    <a href="send_message.php">Send melding</a>
    <br>
    <a href="change_password.php">Bytt passord</a>
    <br>
    <a href="logout.php">Logg ut</a>
</body>
</html>
