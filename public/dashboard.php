<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id']; // Access user ID
$user_role = $_SESSION['user_role']; // Access user role

// Only show subject and messages if the user is a lecturer
if ($user_role == 'foreleser') {
    include '../app/config.php';  // Ensure this path is correct
    
    // Query to get the subject assigned to the lecturer
    $subject_sql = "SELECT s.subject_name, s.subject_id 
                    FROM subjects s
                    JOIN lecturers l ON s.subject_id = l.subject_id
                    WHERE l.lecturer_id = ?";  // 'lecturer_id' used here
    
    if ($stmt = $conn->prepare($subject_sql)) {
        $stmt->bind_param("i", $user_id); // Bind the user_id of the logged-in lecturer
        $stmt->execute();
        $stmt->bind_result($subject_name, $subject_id); // Also get the subject_id
        if ($stmt->fetch()) {
            // Successfully fetched the subject for this lecturer
        } else {
            // No subject found for this lecturer
            echo "Ingen emne funnet for denne foreleseren.";
            exit();
        }
        $stmt->close();
        
        // Now, fetch messages related to this subject
        $message_sql = "SELECT m.message, m.created_at 
                        FROM messages m
                        WHERE m.subject_id = ? 
                        ORDER BY m.created_at DESC";  // Fetch messages for this subject
        
        if ($stmt = $conn->prepare($message_sql)) {
            $stmt->bind_param("i", $subject_id); // Bind the subject_id
            $stmt->execute();
            $stmt->bind_result($message, $created_at);
            $messages = [];
            while ($stmt->fetch()) {
                // Store each message in an array
                $messages[] = [
                    'message' => $message,
                    'created_at' => $created_at
                ];
            }
            $stmt->close();
        } else {
            echo "Feil ved å hente meldingene: " . $conn->error;
            exit();
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
    <link rel="stylesheet">
</head>
<body>
    <h2>Velkommen til dashboardet!</h2>
    <p>Du er logget inn som: <?php echo $user_role; ?></p>
    <p>Bruker-ID: <?php echo $user_id; ?></p> <!-- Display user ID -->

    <!-- If user is a lecturer, display the subject they are teaching -->
    <?php if ($user_role == 'foreleser'): ?>
        <!-- Display messages related to the subject -->
        <h3>Meldinger for <?php echo $subject_name; ?>:</h3>
        <?php if (!empty($messages)): ?>
            <ul>
                <?php foreach ($messages as $msg): ?>
                    <li>
                        <strong>Anonym student:</strong> 
                        <?php echo $msg['message']; ?>
                        <br>
                        <small>Sendt: <?php echo $msg['created_at']; ?></small>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>Ingen meldinger for dette emnet.</p>
        <?php endif; ?>
    <?php endif; ?>

    <a href="send_message.php">Send melding</a>
    <hr>
    <a href="change_password.php">Bytt passord</a>
    <br>
    <a href="logout.php">Logg ut</a>
</body>
</html>
