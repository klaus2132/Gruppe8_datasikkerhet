<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id']; // Access user ID
$user_role = $_SESSION['user_role']; // Access user role

if ($user_role != 'foreleser') {
    echo "Uautorisert tilgang.";
    exit();
}

include '../app/config.php';  // Ensure this path is correct

// Check if the message_id is set
if (!isset($_GET['message_id'])) {
    echo "Melding ikke funnet.";
    exit();
}

$message_id = $_GET['message_id'];

// Get the original message from the database
$message_sql = "SELECT message FROM messages WHERE message_id = ?";
if ($stmt = $conn->prepare($message_sql)) {
    $stmt->bind_param("i", $message_id);
    $stmt->execute();
    $stmt->bind_result($original_message);
    $stmt->fetch();
    $stmt->close();
} else {
    echo "Feil ved henting av melding.";
    exit();
}

// Insert the response to the database
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the response from the form
    $response_text = $_POST['response_text'];
    
    // Insert the response into the responses table
    $insert_sql = "INSERT INTO responses (message_id, lecturer_id, response) 
                   VALUES (?, ?, ?)";
    
    if ($stmt = $conn->prepare($insert_sql)) {
        $stmt->bind_param("iis", $message_id, $user_id, $response_text);
        if ($stmt->execute()) {
            echo "Svar sendt!";
            header("Location: dashboard.php"); // Redirect back to dashboard after response
            exit();
        } else {
            echo "Feil ved innsending av svar: " . $stmt->error;
        }
        $stmt->close();
    } else {
        echo "Feil ved forberedelse av spørring: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Svar på Melding</title>

    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h2>Svar på Melding</h2>
    <p>Original melding:</p>
    <blockquote><?php echo $original_message; ?></blockquote>

    <form method="post" action="reply_message.php?message_id=<?php echo $message_id; ?>">
        <textarea name="response_text" rows="5" cols="40" required placeholder="Skriv ditt svar her..."></textarea><br>
        <button type="submit">Send svar</button>
    </form>

    <br>
    <a href="dashboard.php">Tilbake til dashboard</a>
</body>
</html>
