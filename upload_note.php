<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

// Validate input
if (empty($_POST['noteTitle']) || empty($_POST['noteContent'])) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'Title and content are required']));
}

try {
    // Start transaction
    $conn->beginTransaction();
    
    // Insert note
    $stmt = $conn->prepare("
        INSERT INTO notes (user_id, title, content, subject_id, favorite, created_at, updated_at) 
        VALUES (:user_id, :title, :content, :subject_id, :favorite, NOW(), NOW())
    ");
    
    $stmt->execute([
        ':user_id' => $_SESSION['user_id'],
        ':title' => $_POST['noteTitle'],
        ':content' => $_POST['noteContent'],
        ':subject_id' => !empty($_POST['noteSubject']) ? $_POST['noteSubject'] : null,
        ':favorite' => isset($_POST['noteFavorite']) ? 1 : 0
    ]);
    
    $noteId = $conn->lastInsertId();
    
    // Handle file upload if present
    $pdfPath = null;
    if (!empty($_FILES['noteFile']['name'])) {
        $uploadDir = '../uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileExt = pathinfo($_FILES['noteFile']['name'], PATHINFO_EXTENSION);
        $fileName = 'note_' . $noteId . '_' . time() . '.' . $fileExt;
        $targetPath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['noteFile']['tmp_name'], $targetPath)) {
            $pdfPath = 'uploads/' . $fileName;
            
            // Update note with PDF path
            $updateStmt = $conn->prepare("UPDATE notes SET pdf_path = :pdf_path WHERE id = :id");
            $updateStmt->execute([':pdf_path' => $pdfPath, ':id' => $noteId]);
        }
    }
    
    // Process tags if provided
    if (!empty($_POST['noteTags'])) {
        $tags = explode(',', $_POST['noteTags']);
        
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
        VALUES (:user_id, 'note_create', :description, :note_id)
    ");
    $activityStmt->execute([
        ':user_id' => $_SESSION['user_id'],
        ':description' => 'Created new note: ' . substr($_POST['noteTitle'], 0, 50),
        ':note_id' => $noteId
    ]);
    
    $conn->commit();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Note saved successfully',
        'noteId' => $noteId,
        'pdfPath' => $pdfPath
    ]);
    
} catch (PDOException $e) {
    $conn->rollBack();
    error_log("Error saving note: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error saving note: ' . $e->getMessage()]);
}