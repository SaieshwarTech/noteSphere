<?php
session_start();
require_once 'db_config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

$userId = $_SESSION['user_id'];
$content = $_POST['message-text'] ?? '';

if (empty($content)) {
    echo json_encode(['success' => false, 'message' => 'Message cannot be empty']);
    exit();
}

try {
    $stmt = $conn->prepare("INSERT INTO messages (user_id, content) VALUES (?, ?)");
    $stmt->bind_param("is", $userId, $content);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to send message']);
    }
} catch (Exception $e) {
    error_log("Message send error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error']);
}

$conn->close();
?>