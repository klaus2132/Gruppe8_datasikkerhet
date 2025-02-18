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
                    WHERE l.lecturer_id = ?";
    
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
        $message_sql = "SELECT m.message_id, m.message, m.created_at 
                        FROM messages m
                        WHERE m.subject_id = ? 
                        ORDER BY m.created_at DESC";
        
        if ($stmt = $conn->prepare($message_sql)) {
            $stmt->bind_param("i", $subject_id); // Bind the subject_id
            $stmt->execute();
            $stmt->bind_result($message_id, $message, $created_at);
            $messages = [];
            while ($stmt->fetch()) {
                // Store each message in an array
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

        // Fetch the responses to each message
        $responses = [];
        foreach ($messages as $msg) {
            $response_sql = "SELECT r.response_id, r.response, r.created_at 
                             FROM responses r 
                             WHERE r.message_id = ? 
                             ORDER BY r.created_at ASC";
            
            if ($stmt = $conn->prepare($response_sql)) {
                $stmt->bind_param("i", $msg['message_id']);
                $stmt->execute();
                $stmt->bind_result($response_id, $response, $response_created_at);
                $response_data = [];
                while ($stmt->fetch()) {
                    $response_data[] = [
                        'response_id' => $response_id,
                        'response' => $response,
                        'created_at' => $response_created_at
                    ];
                }
                $responses[$msg['message_id']] = $response_data;
                $stmt->close();
            }
        }
    }
}

// If the user is a student
if ($user_role == 'student') {
    include '../app/config.php';  // Ensure this path is correct
    
    // Query to fetch the student's messages
    $message_sql = "SELECT m.message_id, m.message, m.created_at, s.subject_name 
                    FROM messages m
                    JOIN subjects s ON m.subject_id = s.subject_id
                    WHERE m.student_id = ? 
                    ORDER BY m.created_at DESC";
    
    if ($stmt = $conn->prepare($message_sql)) {
        $stmt->bind_param("i", $user_id); // Bind the user_id of the logged-in student
        $stmt->execute();
        $stmt->bind_result($message_id, $message, $created_at, $subject_name);
        $messages = [];
        while ($stmt->fetch()) {
            // Store each message in an array
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

    // Fetch the responses to each message
    $responses = [];
    foreach ($messages as $msg) {
        $response_sql = "SELECT r.response_id, r.response, r.created_at 
                         FROM responses r 
                         WHERE r.message_id = ? 
                         ORDER BY r.created_at ASC";
        
        if ($stmt = $conn->prepare($response_sql)) {
            $stmt->bind_param("i", $msg['message_id']);
            $stmt->execute();
            $stmt->bind_result($response_id, $response, $response_created_at);
            $response_data = [];
            while ($stmt->fetch()) {
                $response_data[] = [
                    'response_id' => $response_id,
                    'response' => $response,
                    'created_at' => $response_created_at
                ];
            }
            $responses[$msg['message_id']] = $response_data;
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
    <link rel="stylesheet">
</head>
<body>
    <h2>Velkommen til dashboardet!</h2>
    <p>Du er logget inn som: <?php echo $user_role; ?></p>
    <p>Bruker-ID: <?php echo $user_id; ?></p> <!-- Display user ID -->

    <!-- If user is a lecturer, display the subject they are teaching -->
    <?php if ($user_role == 'foreleser'): ?>
        <h3>Emnet du underviser:</h3>
        <p><strong>Emnenavn:</strong> <?php echo $subject_name; ?></p>
        
        <!-- Display messages related to the subject -->
        <h3>Meldinger for emnet:</h3>
        <?php if (!empty($messages)): ?>
            <ul>
                <?php foreach ($messages as $msg): ?>
                    <li>
                        <strong>Anonym student:</strong> 
                        <?php echo $msg['message']; ?>
                        <br>
                        <small>Sendt: <?php echo $msg['created_at']; ?></small>
                        <br>
                        <!-- Reply button -->
                        <a href="reply_message.php?message_id=<?php echo $msg['message_id']; ?>">Svar på denne meldingen</a>
                        
                        <!-- Display responses -->
                        <?php if (isset($responses[$msg['message_id']])): ?>
                            <ul>
                                <?php foreach ($responses[$msg['message_id']] as $response): ?>
                                    <li><strong>Foreleser:</strong> <?php echo $response['response']; ?>
                                        <br><small>Sendt: <?php echo $response['created_at']; ?></small></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>Ingen meldinger for dette emnet.</p>
        <?php endif; ?>
    <?php endif; ?>

    <!-- If user is a student, display their own messages and responses -->
    <?php if ($user_role == 'student'): ?>
        <h3>Mine meldinger:</h3>
        <?php if (!empty($messages)): ?>
            <ul>
                <?php foreach ($messages as $msg): ?>
                    <li>
                        <strong>Emne:</strong> <?php echo $msg['subject_name']; ?>
                        <br>
                        <strong>Min melding:</strong> 
                        <?php echo $msg['message']; ?>
                        <br>
                        <small>Sendt: <?php echo $msg['created_at']; ?></small>
                        <br>
                        
                        <!-- Display responses -->
                        <?php if (isset($responses[$msg['message_id']])): ?>
                            <ul>
                                <?php foreach ($responses[$msg['message_id']] as $response): ?>
                                    <li><strong>Respons fra foreleser:</strong> <?php echo $response['response']; ?>
                                        <br><small>Sendt: <?php echo $response['created_at']; ?></small></li>
                                <?php endforeach; ?>
                            </ul>
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
    <hr>
    <a href="change_password.php">Bytt passord</a>
    <br>
    <a href="logout.php">Logg ut</a>
</body>
</html>
