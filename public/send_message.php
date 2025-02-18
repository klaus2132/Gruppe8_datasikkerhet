<?php
include '../app/config.php';  // Sørg for at du har riktig tilkobling til databasen

// Ensure the user is logged in as a student
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header("Location: index.php");
    exit();
}

// Fetch the subjects for the dropdown
$subjects_sql = "SELECT subject_id, subject_name FROM subjects";
$subjects_result = $conn->query($subjects_sql);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Hent verdiene fra skjemaet
    $subject_id = $_POST['subject_id'];
    $message = $_POST['message'];

    // Get student_id from session
    $student_id = $_SESSION['user_id'];

    // Set the anonymous field to 1 (default)
    $anonymous = 1;

    // Prepare the SQL query
    $sql = "INSERT INTO messages (student_id, subject_id, message, anonymous, created_at) VALUES (?, ?, ?, ?, NOW())";

    if ($stmt = $conn->prepare($sql)) {
        // Bind parameters
        $stmt->bind_param("iisi", $student_id, $subject_id, $message, $anonymous);

        // Execute the query
        if ($stmt->execute()) {
            echo "Svar sendt!";
            header("Location: dashboard.php"); // Redirect back to dashboard after response
            exit();
        } else {
            echo "Feil under sending av melding: " . $stmt->error;
        }

        // Close the statement
        $stmt->close();
    } else {
        echo "Feil ved forberedelse av spørring: " . $conn->error;
    }
}
?>

<form method="post" action="send_message.php">
    <select name="subject_id" required>
        <option value="">Velg kurs</option>
        <?php while ($row = $subjects_result->fetch_assoc()): ?>
            <option value="<?= $row['subject_id'] ?>"><?= $row['subject_name'] ?></option>
        <?php endwhile; ?>
    </select>
    <br>
    <textarea name="message" placeholder="Skriv din melding her..." required></textarea>
    <br>
    <button type="submit">Send melding</button>

    <br>
    <a href="dashboard.php">Tilbake til dashboard</a>
</form>
