<?php
session_start();
require_once '../db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("HTTP/1.1 401 Unauthorized");
    exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!isset($input['note_id']) || !isset($input['favorite'])) {
    header("HTTP/1.1 400 Bad Request");
    exit(json_encode(['success' => false, 'message' => 'Invalid input']));
}

$userId = $_SESSION['user_id'];
$noteId = $input['note_id'];
$favorite = (bool)$input['favorite'];

try {
    // Verify the note belongs to the user
    $stmt = $conn->prepare("SELECT 1 FROM notes WHERE id = :note_id AND user_id = :user_id");
    $stmt->bindValue(':note_id', $noteId, PDO::PARAM_INT);
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    
    if (!$stmt->fetch()) {
        header("HTTP/1.1 404 Not Found");
        exit(json_encode(['success' => false, 'message' => 'Note not found or not owned by user']));
    }
    
    // Update favorite status
    $stmt = $conn->prepare("UPDATE notes SET favorite = :favorite WHERE id = :note_id");
    $stmt->bindValue(':favorite', $favorite ? 1 : 0, PDO::PARAM_INT);
    $stmt->bindValue(':note_id', $noteId, PDO::PARAM_INT);
    $stmt->execute();
    
    header("HTTP/1.1 200 OK");
    exit(json_encode(['success' => true, 'message' => 'Favorite status updated']));
    
} catch (PDOException $e) {
    error_log("Toggle favorite error: " . $e->getMessage());
    header("HTTP/1.1 500 Internal Server Error");
    exit(json_encode(['success' => false, 'message' => 'Failed to update favorite status']));
}