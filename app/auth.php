<?php
session_start();
include 'db.php';

function login($email, $password) {
    global $conn;

    // Check if the user exists as a student
    $sql = "SELECT * FROM students WHERE email='$email' AND password='$password'";
    $result = $conn->query($sql);
    if ($result->num_rows == 1) {
        $_SESSION['user_role'] = 'student';
        return 'dashboard.php';
    }
    
    // Check if the user exists as a lecturer
    $sql = "SELECT * FROM lecturers WHERE email='$email' AND password='$password'";
    $result = $conn->query($sql);
    if ($result->num_rows == 1) {
        $_SESSION['user_role'] = 'foreleser';
        return 'dashboard.php';
    }

    // Check if the user exists as an admin
    $sql = "SELECT * FROM admin WHERE email='$email' AND password='$password'";
    $result = $conn->query($sql);
    if ($result->num_rows == 1) {
        $_SESSION['user_role'] = 'admin';
        return 'admin_panel.php';
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
