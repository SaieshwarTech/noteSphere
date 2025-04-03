<?php
session_start();
require_once './db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

// Validate input
if (empty($_POST['title']) || empty($_POST['content'])) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'Title and content are required']));
}

try {
    // Start transaction
    $conn->beginTransaction();
    
    // Insert note
    $stmt = $conn->prepare("
        INSERT INTO notes (user_id, title, content, subject_id, created_at, updated_at) 
        VALUES (:user_id, :title, :content, :subject_id, NOW(), NOW())
    ");
    $stmt->execute([
        ':user_id' => $_SESSION['user_id'],
        ':title' => $_POST['title'],
        ':content' => $_POST['content'],
        ':subject_id' => !empty($_POST['subject_id']) ? $_POST['subject_id'] : null
    ]);
    $noteId = $conn->lastInsertId();
    
    // Process tags if provided
    if (!empty($_POST['tags'])) {
        $tags = explode(',', $_POST['tags']);
        
        foreach ($tags as $tagName) {
            $tagName = trim($tagName);
            if (empty($tagName)) continue;
            
            // Check if tag exists
            $tagStmt = $conn->prepare("SELECT id FROM tags WHERE name = :name");
            $tagStmt->execute([':name' => $tagName]);
            $tag = $tagStmt->fetch();
            
            if (!$tag) {
                // Create new tag
                $tagStmt = $conn->prepare("INSERT INTO tags (name) VALUES (:name)");
                $tagStmt->execute([':name' => $tagName]);
                $tagId = $conn->lastInsertId();
            } else {
                $tagId = $tag['id'];
            }
            
            // Link tag to note
            $linkStmt = $conn->prepare("INSERT INTO note_tags (note_id, tag_id) VALUES (:note_id, :tag_id)");
            $linkStmt->execute([':note_id' => $noteId, ':tag_id' => $tagId]);
        }
    }
    
    // Record activity
    $activityStmt = $conn->prepare("
        INSERT INTO activities (user_id, activity_type, description, note_id) 
        VALUES (:user_id, 'note_create', 'Created new note: " . substr($_POST['title'], 0, 50) . "', :note_id)
    ");
    $activityStmt->execute([
        ':user_id' => $_SESSION['user_id'],
        ':note_id' => $noteId
    ]);
    
    $conn->commit();
    
    echo json_encode(['success' => true, 'message' => 'Note saved successfully']);
    
} catch (PDOException $e) {
    $conn->rollBack();
    error_log("Error saving note: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error saving note']);
}