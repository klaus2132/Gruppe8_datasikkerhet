<?php
session_start();
include 'config.php';

function login($email, $password) {
    global $conn;

    // Check if the user exists as a student
    $sql = "SELECT * FROM students WHERE email='$email' AND password='$password'";
    $result = $conn->query($sql);
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc(); // Get user data
        $_SESSION['user_role'] = 'student';
        $_SESSION['user_id'] = $user['student_id']; // Store the student ID in session
        return "dashboard.php"; // Redirect to the dashboard
    }
    
    // Check if the user exists as a lecturer
    $sql = "SELECT * FROM lecturers WHERE email='$email' AND password='$password'";
    $result = $conn->query($sql);
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc(); // Get user data
        $_SESSION['user_role'] = 'foreleser';
        $_SESSION['user_id'] = $user['lecturer_id']; // Store the lecturer ID in session
        return "dashboard.php"; // Redirect to the dashboard
    }

    // Check if the user exists as an admin
    $sql = "SELECT * FROM admin WHERE email='$email' AND password='$password'";
    $result = $conn->query($sql);
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc(); // Get user data
        $_SESSION['user_role'] = 'admin';
        $_SESSION['user_id'] = $user['admin_id']; // Store the admin ID in session
        return "dashboard.php"; // Redirect to the dashboard (admin_panel.php)
    }

    // Return false if no matching user found
    return false;
}

function isAuthenticated() {
    return isset($_SESSION['user_role']);
}

function logout() {
    session_start();
    session_destroy();
    header("Location: ../public/index.php");
    exit();
}
?>
