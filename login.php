<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

require 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        die("Invalid CSRF token");
    }

    if (!isset($_POST['login'], $_POST['password'])) {
        die("Email/Username and Password are required.");
    }

    $email_username = trim($_POST['login']);
    $password = $_POST['password'];

    // Find user by email or username
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? OR username = ?");
    $stmt->execute([$email_username, $email_username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user'] = [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email']
        ];
        
        // Redirect to panel
        header("Location: panel.php");
        exit();
    } else {
        // Redirect back with error
        $_SESSION['login_error'] = "Invalid credentials";
        header("Location: landing.php");
        exit();
    }
}