<?php
session_start();
require_once '../db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("HTTP/1.1 401 Unauthorized");
    exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

$userId = $_SESSION['user_id'];
$uploadDir = '../uploads/';

// Process file upload
$filePath = '';
if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
    // Validate file type and size
    $allowedTypes = [
        'application/pdf' => 'pdf',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'text/plain' => 'txt',
        'image/jpeg' => 'jpg',
        'image/png' => 'png'
    ];
    
    $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($fileInfo, $_FILES['file']['tmp_name']);
    
    if (array_key_exists($mimeType, $allowedTypes) && $_FILES['file']['size'] <= 5 * 1024 * 1024) {
        $fileExt = $allowedTypes[$mimeType];
        $fileName = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9\.\-]/', '_', $_FILES['file']['name']);
        $targetPath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['file']['tmp_name'], $targetPath)) {
            $filePath = 'uploads/' . $fileName;
        }
    }
}

try {
    // Begin transaction
    $conn->beginTransaction();
    
    // Insert the note
    $stmt = $conn->prepare("INSERT INTO notes (user_id, title, content, subject_id, favorite, file_path) 
                           VALUES (:user_id, :title, :content, :subject_id, :favorite, :file_path)");
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':title', $_POST['title'], PDO::PARAM_STR);
    $stmt->bindValue(':content', $_POST['content'], PDO::PARAM_STR);
    $stmt->bindValue(':subject_id', !empty($_POST['subject_id']) ? $_POST['subject_id'] : null, PDO::PARAM_INT);
    $stmt->bindValue(':favorite', isset($_POST['favorite']) ? 1 : 0, PDO::PARAM_INT);
    $stmt->bindValue(':file_path', !empty($filePath) ? $filePath : null, PDO::PARAM_STR);
    $stmt->execute();
    
    $noteId = $conn->lastInsertId();
    
    // Process tags if provided
    if (!empty($_POST['tags'])) {
        $tags = array_map('trim', explode(',', $_POST['tags']));
        $tags = array_filter($tags);
        
        foreach ($tags as $tagName) {
            // Check if tag exists
            $stmt = $conn->prepare("SELECT id FROM tags WHERE name = :name");
            $stmt->bindValue(':name', $tagName, PDO::PARAM_STR);
            $stmt->execute();
            $tag = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$tag) {
                // Create new tag
                $stmt = $conn->prepare("INSERT INTO tags (name) VALUES (:name)");
                $stmt->bindValue(':name', $tagName, PDO::PARAM_STR);
                $stmt->execute();
                $tagId = $conn->lastInsertId();
            } else {
                $tagId = $tag['id'];
            }
            
            // Link tag to note
            $stmt = $conn->prepare("INSERT INTO note_tags (note_id, tag_id) VALUES (:note_id, :tag_id)");
            $stmt->bindValue(':note_id', $noteId, PDO::PARAM_INT);
            $stmt->bindValue(':tag_id', $tagId, PDO::PARAM_INT);
            $stmt->execute();
        }
    }
    
    // Commit transaction
    $conn->commit();
    
    header("HTTP/1.1 200 OK");
    exit(json_encode(['success' => true, 'message' => 'Note created successfully']));
    
} catch (PDOException $e) {
    $conn->rollBack();
    
    // Delete uploaded file if transaction failed
    if (!empty($filePath)) {
        $fullPath = '../' . $filePath;
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
    }
    
    error_log("Save note error: " . $e->getMessage());
    header("HTTP/1.1 500 Internal Server Error");
    exit(json_encode(['success' => false, 'message' => 'Failed to save note']));
}
