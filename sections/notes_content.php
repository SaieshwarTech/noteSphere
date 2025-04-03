<?php
session_start();
require_once '../db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$notes = [];
$searchQuery = $_GET['search'] ?? '';
$subjectFilter = $_GET['subject'] ?? '';
$tagFilter = $_GET['tag'] ?? '';
$sortBy = $_GET['sort'] ?? 'newest';
$page = $_GET['page'] ?? 1;
$limit = 15;
$offset = ($page - 1) * $limit;

try {
    // Base query with count for pagination
    $countQuery = "SELECT COUNT(DISTINCT n.id) as total 
                   FROM notes n
                   LEFT JOIN note_tags nt ON n.id = nt.note_id
                   LEFT JOIN tags t ON nt.tag_id = t.id
                   LEFT JOIN subjects s ON n.subject_id = s.id
                   WHERE n.user_id = :user_id";
    
    $query = "SELECT n.id, n.title, n.content, n.created_at, n.updated_at, n.favorite, n.subject_id,
                     GROUP_CONCAT(DISTINCT t.name) as tags,
                     s.name as subject_name
              FROM notes n
              LEFT JOIN note_tags nt ON n.id = nt.note_id
              LEFT JOIN tags t ON nt.tag_id = t.id
              LEFT JOIN subjects s ON n.subject_id = s.id
              WHERE n.user_id = :user_id";
    
    // Add search filter if provided
    if (!empty($searchQuery)) {
        $query .= " AND (n.title LIKE :search OR n.content LIKE :search OR t.name LIKE :search)";
        $countQuery .= " AND (n.title LIKE :search OR n.content LIKE :search OR t.name LIKE :search)";
    }
    
    // Add subject filter if provided
    if (!empty($subjectFilter)) {
        $query .= " AND n.subject_id = :subject_id";
        $countQuery .= " AND n.subject_id = :subject_id";
    }
    
    // Add tag filter if provided
    if (!empty($tagFilter)) {
        $query .= " AND t.id = :tag_id";
        $countQuery .= " AND t.id = :tag_id";
    }
    
    // Group by note
    $query .= " GROUP BY n.id";
    
    // Add sorting
    switch ($sortBy) {
        case 'oldest':
            $query .= " ORDER BY n.created_at ASC";
            break;
        case 'title':
            $query .= " ORDER BY n.title ASC";
            break;
        case 'subject':
            $query .= " ORDER BY s.name ASC, n.created_at DESC";
            break;
        case 'updated':
            $query .= " ORDER BY n.updated_at DESC";
            break;
        case 'favorite':
            $query .= " ORDER BY n.favorite DESC, n.created_at DESC";
            break;
        default: // newest
            $query .= " ORDER BY n.created_at DESC";
    }
    
    // Add pagination
    $query .= " LIMIT :limit OFFSET :offset";
    
    // Get total count for pagination
    $countStmt = $conn->prepare($countQuery);
    $countStmt->bindValue(":user_id", $userId, PDO::PARAM_INT);
    
    if (!empty($searchQuery)) {
        $searchParam = "%$searchQuery%";
        $countStmt->bindValue(":search", $searchParam, PDO::PARAM_STR);
    }
    
    if (!empty($subjectFilter)) {
        $countStmt->bindValue(":subject_id", $subjectFilter, PDO::PARAM_INT);
    }
    
    if (!empty($tagFilter)) {
        $countStmt->bindValue(":tag_id", $tagFilter, PDO::PARAM_INT);
    }
    
    $countStmt->execute();
    $totalNotes = $countStmt->fetchColumn();
    $totalPages = ceil($totalNotes / $limit);
    
    // Get notes
    $stmt = $conn->prepare($query);
    $stmt->bindValue(":user_id", $userId, PDO::PARAM_INT);
    $stmt->bindValue(":limit", $limit, PDO::PARAM_INT);
    $stmt->bindValue(":offset", $offset, PDO::PARAM_INT);
    
    if (!empty($searchQuery)) {
        $stmt->bindValue(":search", $searchParam, PDO::PARAM_STR);
    }
    
    if (!empty($subjectFilter)) {
        $stmt->bindValue(":subject_id", $subjectFilter, PDO::PARAM_INT);
    }
    
    if (!empty($tagFilter)) {
        $stmt->bindValue(":tag_id", $tagFilter, PDO::PARAM_INT);
    }
    
    $stmt->execute();
    $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get all subjects for filter dropdown
    $stmt = $conn->prepare("SELECT id, name FROM subjects ORDER BY name");
    $stmt->execute();
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get all tags for filter dropdown
    $stmt = $conn->prepare("SELECT t.id, t.name, COUNT(nt.note_id) as note_count 
                           FROM tags t
                           JOIN note_tags nt ON t.id = nt.tag_id
                           JOIN notes n ON nt.note_id = n.id
                           WHERE n.user_id = :user_id
                           GROUP BY t.id
                           ORDER BY note_count DESC");
    $stmt->bindValue(":user_id", $userId, PDO::PARAM_INT);
    $stmt->execute();
    $tags = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Notes error: " . $e->getMessage());
}

$conn = null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notes Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            padding: 20px;
        }
        .note-card {
            transition: transform 0.2s;
            margin-bottom: 20px;
            border-left: 4px solid #48A860;
        }
        .note-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .note-card.favorite {
            border-left-color: #ffc107;
        }
        .note-title {
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .note-content {
            height: 100px;
            overflow: hidden;
            position: relative;
        }
        .note-content::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 30px;
            background: linear-gradient(to bottom, transparent, white);
        }
        .tag {
            display: inline-block;
            background-color: #e9ecef;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.8rem;
            margin-right: 5px;
            margin-bottom: 5px;
        }
        .badge {
            font-weight: 500;
        }
        .empty-state {
            text-align: center;
            padding: 40px;
            border-radius: 10px;
            background-color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>My Notes</h1>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newNoteModal">
                <i class="fas fa-plus"></i> New Note
            </button>
        </div>

        <!-- Search and Filters -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" class="form-control" placeholder="Search..." id="searchInput" 
                           value="<?= htmlspecialchars($searchQuery) ?>">
                </div>
            </div>
            <div class="col-md-3">
                <select class="form-select" id="subjectFilter">
                    <option value="">All Subjects</option>
                    <?php foreach ($subjects as $subject): ?>
                        <option value="<?= $subject['id'] ?>" <?= $subjectFilter == $subject['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($subject['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select" id="tagFilter">
                    <option value="">All Tags</option>
                    <?php foreach ($tags as $tag): ?>
                        <option value="<?= $tag['id'] ?>" <?= $tagFilter == $tag['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($tag['name']) ?> (<?= $tag['note_count'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select" id="sortFilter">
                    <option value="newest" <?= $sortBy === 'newest' ? 'selected' : '' ?>>Newest</option>
                    <option value="oldest" <?= $sortBy === 'oldest' ? 'selected' : '' ?>>Oldest</option>
                    <option value="title" <?= $sortBy === 'title' ? 'selected' : '' ?>>Title</option>
                    <option value="subject" <?= $sortBy === 'subject' ? 'selected' : '' ?>>Subject</option>
                    <option value="updated" <?= $sortBy === 'updated' ? 'selected' : '' ?>>Updated</option>
                    <option value="favorite" <?= $sortBy === 'favorite' ? 'selected' : '' ?>>Favorites</option>
                </select>
            </div>
        </div>

        <!-- Notes Grid -->
        <div class="row">
            <?php if (empty($notes)): ?>
                <div class="col-12">
                    <div class="empty-state">
                        <i class="fas fa-book-open fa-3x mb-3 text-muted"></i>
                        <h3>No Notes Found</h3>
                        <p class="text-muted">You don't have any notes yet. Create your first note to get started!</p>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newNoteModal">
                            <i class="fas fa-plus"></i> Create First Note
                        </button>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($notes as $note): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card note-card h-100 <?= $note['favorite'] ? 'favorite' : '' ?>">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <h5 class="card-title note-title"><?= htmlspecialchars($note['title']) ?></h5>
                                    <button class="btn btn-sm favorite-btn <?= $note['favorite'] ? 'text-warning' : 'text-secondary' ?>" 
                                            data-note-id="<?= $note['id'] ?>">
                                        <i class="fas fa-star"></i>
                                    </button>
                                </div>
                                
                                <?php if (!empty($note['subject_name'])): ?>
                                    <span class="badge bg-success mb-2"><?= htmlspecialchars($note['subject_name']) ?></span>
                                <?php endif; ?>
                                
                                <div class="card-text note-content mb-2">
                                    <?= nl2br(htmlspecialchars(substr($note['content'], 0, 200))) ?>
                                    <?php if (strlen($note['content']) > 200): ?>...<?php endif; ?>
                                </div>
                                
                                <?php if (!empty($note['tags'])): ?>
                                    <div class="mb-2">
                                        <?php 
                                        $tags = explode(',', $note['tags']);
                                        foreach ($tags as $tag): 
                                            if (!empty(trim($tag))):
                                        ?>
                                            <span class="tag"><?= htmlspecialchars(trim($tag)) ?></span>
                                        <?php 
                                            endif;
                                        endforeach; 
                                        ?>
                                    </div>
                                <?php endif; ?>
                                
                                <small class="text-muted d-block mb-2">
                                    Created: <?= date('M j, Y', strtotime($note['created_at'])) ?>
                                </small>
                                
                                <div class="d-flex justify-content-between">
                                    <button class="btn btn-sm btn-outline-primary edit-note-btn" 
                                            data-note-id="<?= $note['id'] ?>"
                                            data-note-title="<?= htmlspecialchars($note['title']) ?>"
                                            data-note-content="<?= htmlspecialchars($note['content']) ?>"
                                            data-subject-id="<?= $note['subject_id'] ?? '' ?>"
                                            data-tags="<?= htmlspecialchars($note['tags']) ?>"
                                            data-favorite="<?= $note['favorite'] ?>">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger delete-note-btn" 
                                            data-note-id="<?= $note['id'] ?>">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if (isset($totalPages) && $totalPages > 1): ?>
            <nav>
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>">
                                <i class="fas fa-angle-double-left"></i>
                            </a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
                                <i class="fas fa-angle-left"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($page + 2, $totalPages); $i++): ?>
                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">
                                <i class="fas fa-angle-right"></i>
                            </a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $totalPages])) ?>">
                                <i class="fas fa-angle-double-right"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>

    <!-- New Note Modal -->
    <div class="modal fade" id="newNoteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create New Note</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="noteForm" action="save_note.php" method="post">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="noteTitle" class="form-label">Title *</label>
                            <input type="text" class="form-control" id="noteTitle" name="title" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="noteSubject" class="form-label">Subject</label>
                            <select class="form-select" id="noteSubject" name="subject_id">
                                <option value="">Select a subject</option>
                                <?php foreach ($subjects as $subject): ?>
                                    <option value="<?= $subject['id'] ?>"><?= htmlspecialchars($subject['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="noteContent" class="form-label">Content *</label>
                            <textarea class="form-control" id="noteContent" name="content" rows="8" required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="noteTags" class="form-label">Tags</label>
                            <input type="text" class="form-control" id="noteTags" name="tags" 
                                   placeholder="Add tags separated by commas">
                            <small class="text-muted">Example: math, algebra, homework</small>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="noteFavorite" name="favorite">
                            <label class="form-check-label" for="noteFavorite">Mark as favorite</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Note</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Note Modal -->
    <div class="modal fade" id="editNoteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Note</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editNoteForm" action="update_note.php" method="post">
                    <input type="hidden" name="note_id" id="editNoteId">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="editNoteTitle" class="form-label">Title *</label>
                            <input type="text" class="form-control" id="editNoteTitle" name="title" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editNoteSubject" class="form-label">Subject</label>
                            <select class="form-select" id="editNoteSubject" name="subject_id">
                                <option value="">Select a subject</option>
                                <?php foreach ($subjects as $subject): ?>
                                    <option value="<?= $subject['id'] ?>"><?= htmlspecialchars($subject['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editNoteContent" class="form-label">Content *</label>
                            <textarea class="form-control" id="editNoteContent" name="content" rows="8" required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editNoteTags" class="form-label">Tags</label>
                            <input type="text" class="form-control" id="editNoteTags" name="tags" 
                                   placeholder="Add tags separated by commas">
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="editNoteFavorite" name="favorite">
                            <label class="form-check-label" for="editNoteFavorite">Mark as favorite</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Note Modal -->
    <div class="modal fade" id="deleteNoteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Delete Note</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="deleteNoteForm" action="delete_note.php" method="post">
                    <input type="hidden" name="note_id" id="deleteNoteId">
                    <div class="modal-body">
                        <p>Are you sure you want to delete this note? This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete Permanently</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize Bootstrap tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Edit note button click handler
        document.querySelectorAll('.edit-note-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const noteId = this.getAttribute('data-note-id');
                const noteTitle = this.getAttribute('data-note-title');
                const noteContent = this.getAttribute('data-note-content');
                const subjectId = this.getAttribute('data-subject-id');
                const tags = this.getAttribute('data-tags');
                const favorite = this.getAttribute('data-favorite') === '1';
                
                document.getElementById('editNoteId').value = noteId;
                document.getElementById('editNoteTitle').value = noteTitle;
                document.getElementById('editNoteContent').value = noteContent;
                document.getElementById('editNoteTags').value = tags;
                
                if (subjectId) {
                    document.getElementById('editNoteSubject').value = subjectId;
                }
                
                document.getElementById('editNoteFavorite').checked = favorite;
                
                // Show the modal
                const editModal = new bootstrap.Modal(document.getElementById('editNoteModal'));
                editModal.show();
            });
        });

        // Delete note button click handler
        document.querySelectorAll('.delete-note-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const noteId = this.getAttribute('data-note-id');
                document.getElementById('deleteNoteId').value = noteId;
                
                // Show the modal
                const deleteModal = new bootstrap.Modal(document.getElementById('deleteNoteModal'));
                deleteModal.show();
            });
        });

        // Favorite button click handler
        document.querySelectorAll('.favorite-btn').forEach(btn => {
            btn.addEventListener('click', async function() {
                const noteId = this.getAttribute('data-note-id');
                const isFavorite = this.classList.contains('text-warning');
                
                try {
                    const response = await fetch('toggle_favorite.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ note_id: noteId, favorite: !isFavorite })
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        // Toggle the visual state
                        if (isFavorite) {
                            this.classList.remove('text-warning');
                            this.classList.add('text-secondary');
                            this.closest('.note-card').classList.remove('favorite');
                        } else {
                            this.classList.remove('text-secondary');
                            this.classList.add('text-warning');
                            this.closest('.note-card').classList.add('favorite');
                        }
                    } else {
                        alert('Failed to update favorite status');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('An error occurred while updating favorite status');
                }
            });
        });

        // Filter functionality
        function applyFilters() {
            const params = new URLSearchParams();
            
            const search = document.getElementById('searchInput').value;
            const subject = document.getElementById('subjectFilter').value;
            const tag = document.getElementById('tagFilter').value;
            const sort = document.getElementById('sortFilter').value;
            
            if (search) params.set('search', search);
            if (subject) params.set('subject', subject);
            if (tag) params.set('tag', tag);
            if (sort !== 'newest') params.set('sort', sort);
            
            window.location.search = params.toString();
        }
        
        // Add event listeners for filters
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') applyFilters();
        });
        
        document.getElementById('subjectFilter').addEventListener('change', applyFilters);
        document.getElementById('tagFilter').addEventListener('change', applyFilters);
        document.getElementById('sortFilter').addEventListener('change', applyFilters);
    </script>
</body>
</html>