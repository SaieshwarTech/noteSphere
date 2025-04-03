<?php
session_start();
include __DIR__ . '/db_connect.php'; // Ensure correct path

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = trim($_POST['fullname']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT); // Secure password hashing

    try {
        if (!isset($conn)) {
            throw new Exception("Database connection error.");
        }

        $stmt = $conn->prepare("INSERT INTO users (fullname, username, email, password) VALUES (?, ?, ?, ?)");
        $stmt->execute([$fullname, $username, $email, $password]);

        $_SESSION['user_id'] = $conn->lastInsertId();
        $_SESSION['username'] = $username;

        header("Location: profile.php");
        exit();
    } catch (PDOException $e) {
        echo "Database Error: " . $e->getMessage();
    } catch (Exception $e) {
        echo "General Error: " . $e->getMessage();
    }
}
?>
