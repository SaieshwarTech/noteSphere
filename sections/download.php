<?php
session_start();
require_once '../db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("HTTP/1.1 401 Unauthorized");
    exit();
}

if (!isset($_GET['file'])) {
    header("HTTP/1.1 400 Bad Request");
    exit();
}

$filePath = '../' . urldecode($_GET['file']);

// Verify the file belongs to the user
try {
    $stmt = $conn->prepare("SELECT 1 FROM notes WHERE file_path = :file_path AND user_id = :user_id");
    $stmt->bindValue(':file_path', $_GET['file'], PDO::PARAM_STR);
    $stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->execute();
    
    if (!$stmt->fetch()) {
        header("HTTP/1.1 403 Forbidden");
        exit();
    }
    
    if (file_exists($filePath)) {
        header('Content-Description: File Transfer');
        header('Content-Type: ' . mime_content_type($filePath));
        header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit();
    } else {
        header("HTTP/1.1 404 Not Found");
        exit();
    }
} catch (Exception $e) {
    error_log("Download error: " . $e->getMessage());
    header("HTTP/1.1 500 Internal Server Error");
    exit();
}