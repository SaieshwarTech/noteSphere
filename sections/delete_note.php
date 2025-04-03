<?php
session_start();
require_once '../db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("HTTP/1.1 401 Unauthorized");
    exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

// Check if note ID is provided
if (!isset($_POST['note_id'])) {
    header("HTTP/1.1 400 Bad Request");
    exit(json_encode(['success' => false, 'message' => 'Note ID is required']));
}

$userId = $_SESSION['user_id'];
$noteId = $_POST['note_id'];

try {
    // Begin transaction
    $conn->beginTransaction();
    
    // First get the file path if exists
    $stmt = $conn->prepare("SELECT file_path FROM notes WHERE id = :note_id AND user_id = :user_id");
    $stmt->bindValue(':note_id', $noteId, PDO::PARAM_INT);
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $note = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$note) {
        $conn->rollBack();
        header("HTTP/1.1 404 Not Found");
        exit(json_encode(['success' => false, 'message' => 'Note not found or not owned by user']));
    }
    
    // Delete note tags associations
    $stmt = $conn->prepare("DELETE FROM note_tags WHERE note_id = :note_id");
    $stmt->bindValue(':note_id', $noteId, PDO::PARAM_INT);
    $stmt->execute();
    
    // Delete the note
    $stmt = $conn->prepare("DELETE FROM notes WHERE id = :note_id AND user_id = :user_id");
    $stmt->bindValue(':note_id', $noteId, PDO::PARAM_INT);
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    
    // Delete the associated file if exists
    if (!empty($note['file_path'])) {
        $filePath = '../' . $note['file_path'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
    
    // Commit transaction
    $conn->commit();
    
    header("HTTP/1.1 200 OK");
    exit(json_encode(['success' => true, 'message' => 'Note deleted successfully']));
    
} catch (PDOException $e) {
    $conn->rollBack();
    error_log("Delete note error: " . $e->getMessage());
    header("HTTP/1.1 500 Internal Server Error");
    exit(json_encode(['success' => false, 'message' => 'Failed to delete note']));
}