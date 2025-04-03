<?php
session_start();
require_once '../db_connect.php';

$userId = $_SESSION['user_id'];
$messages = [];
$groups = [];
$myGroups = [];
$errors = [];
$success = '';

// CRUD Operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Create new group
    if (isset($_POST['create_group'])) {
        $groupName = trim($_POST['group_name']);
        $description = trim($_POST['description']);
        
        try {
            $conn->beginTransaction();
            
            // Insert group
            $stmt = $conn->prepare("INSERT INTO `groups` (group_name, group_description, created_by) VALUES (?, ?, ?)");
            $stmt->execute([$groupName, $description, $userId]);
            $groupId = $conn->lastInsertId();
            
            // Add creator as member
            $stmt = $conn->prepare("INSERT INTO group_members (group_id, user_id, is_admin) VALUES (?, ?, 1)");
            $stmt->execute([$groupId, $userId]);
            
            $conn->commit();
            $success = "Group created successfully!";
        } catch (PDOException $e) {
            $conn->rollBack();
            $errors[] = "Failed to create group: " . $e->getMessage();
        }
    }
    
    // Join group
    if (isset($_POST['join_group'])) {
        $groupId = $_POST['group_id'];
        
        try {
            $stmt = $conn->prepare("INSERT INTO group_members (group_id, user_id) VALUES (?, ?)");
            $stmt->execute([$groupId, $userId]);
            $success = "You've joined the group!";
        } catch (PDOException $e) {
            $errors[] = "Failed to join group: " . $e->getMessage();
        }
    }
    
    // Leave group
    if (isset($_POST['leave_group'])) {
        $groupId = $_POST['group_id'];
        
        try {
            $stmt = $conn->prepare("DELETE FROM group_members WHERE group_id = ? AND user_id = ?");
            $stmt->execute([$groupId, $userId]);
            $success = "You've left the group.";
        } catch (PDOException $e) {
            $errors[] = "Failed to leave group: " . $e->getMessage();
        }
    }
    
    // Delete group (admin only)
    if (isset($_POST['delete_group'])) {
        $groupId = $_POST['group_id'];
        
        try {
            $conn->beginTransaction();
            
            // Verify user is admin
            $stmt = $conn->prepare("SELECT is_admin FROM group_members WHERE group_id = ? AND user_id = ?");
            $stmt->execute([$groupId, $userId]);
            $isAdmin = $stmt->fetchColumn();
            
            if ($isAdmin) {
                // Delete messages
                $stmt = $conn->prepare("DELETE FROM messages WHERE group_id = ?");
                $stmt->execute([$groupId]);
                
                // Delete members
                $stmt = $conn->prepare("DELETE FROM group_members WHERE group_id = ?");
                $stmt->execute([$groupId]);
                
                // Delete group
                $stmt = $conn->prepare("DELETE FROM `groups` WHERE group_id = ?");
                $stmt->execute([$groupId]);
                
                $conn->commit();
                $success = "Group deleted successfully!";
            } else {
                $errors[] = "Only group admins can delete groups";
                $conn->rollBack();
            }
        } catch (PDOException $e) {
            $conn->rollBack();
            $errors[] = "Failed to delete group: " . $e->getMessage();
        }
    }
}

// Get user's groups
try {
    $stmt = $conn->prepare("SELECT g.*, 
                          (SELECT COUNT(*) FROM group_members gm WHERE gm.group_id = g.group_id) as member_count,
                          (SELECT COUNT(*) FROM messages m WHERE m.group_id = g.group_id) as message_count,
                          (SELECT is_admin FROM group_members gm WHERE gm.group_id = g.group_id AND gm.user_id = ?) as is_admin
                          FROM `groups` g
                          JOIN group_members gm ON g.group_id = gm.group_id
                          WHERE gm.user_id = ?
                          ORDER BY g.created_at DESC");
    $stmt->execute([$userId, $userId]);
    $myGroups = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors[] = "Failed to load your groups: " . $e->getMessage();
}

// Get available groups (excluding user's groups)
try {
    $stmt = $conn->prepare("SELECT g.*, 
                          (SELECT COUNT(*) FROM group_members gm WHERE gm.group_id = g.group_id) as member_count
                          FROM `groups` g
                          WHERE g.group_id NOT IN (
                              SELECT gm.group_id FROM group_members gm WHERE gm.user_id = ?
                          )
                          ORDER BY g.created_at DESC");
    $stmt->execute([$userId]);
    $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors[] = "Failed to load available groups: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Study Groups</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 p-4">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-2xl font-bold text-gray-800 mb-6">Study Groups</h1>
        
        <?php if (!empty($success)): ?>
            <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
            <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                <ul class="list-disc pl-5">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <!-- Create Group Form -->
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <h2 class="text-xl font-semibold mb-4">Create New Group</h2>
            <form method="POST">
                <div class="mb-4">
                    <label class="block text-gray-700 mb-2">Group Name</label>
                    <input type="text" name="group_name" required 
                           class="w-full px-4 py-2 border rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 mb-2">Description</label>
                    <textarea name="description" rows="3" required
                              class="w-full px-4 py-2 border rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                </div>
                <button type="submit" name="create_group" 
                        class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                    Create Group
                </button>
            </form>
        </div>
        
        <!-- My Groups -->
        <div class="mb-8">
            <h2 class="text-xl font-semibold mb-4">My Groups</h2>
            
            <?php if (empty($myGroups)): ?>
                <div class="bg-white rounded-lg shadow p-6 text-center text-gray-500">
                    You haven't joined any groups yet.
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php foreach ($myGroups as $group): ?>
                        <div class="bg-white rounded-lg shadow p-4">
                            <div class="flex justify-between items-start mb-2">
                                <h3 class="font-semibold text-lg"><?= htmlspecialchars($group['group_name']) ?></h3>
                                <?php if ($group['is_admin']): ?>
                                    <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded">Admin</span>
                                <?php endif; ?>
                            </div>
                            <p class="text-gray-600 mb-3"><?= htmlspecialchars($group['group_description']) ?></p>
                            <div class="flex justify-between text-sm text-gray-500 mb-3">
                                <span><i class="fas fa-users mr-1"></i> <?= $group['member_count'] ?> members</span>
                                <span><i class="fas fa-comment mr-1"></i> <?= $group['message_count'] ?> messages</span>
                            </div>
                            <div class="flex space-x-2">
                                <a href="group_chat.php?id=<?= $group['group_id'] ?>" 
                                   class="px-3 py-1 bg-blue-600 text-white rounded text-sm hover:bg-blue-700">
                                    Open Chat
                                </a>
                                <form method="POST" class="inline">
                                    <input type="hidden" name="group_id" value="<?= $group['group_id'] ?>">
                                    <button type="submit" name="leave_group" 
                                            class="px-3 py-1 bg-gray-200 text-gray-800 rounded text-sm hover:bg-gray-300">
                                        Leave Group
                                    </button>
                                </form>
                                <?php if ($group['is_admin']): ?>
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="group_id" value="<?= $group['group_id'] ?>">
                                        <button type="submit" name="delete_group" 
                                                class="px-3 py-1 bg-red-600 text-white rounded text-sm hover:bg-red-700"
                                                onclick="return confirm('Are you sure you want to delete this group?')">
                                            Delete
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Available Groups -->
        <div>
            <h2 class="text-xl font-semibold mb-4">Available Groups</h2>
            
            <?php if (empty($groups)): ?>
                <div class="bg-white rounded-lg shadow p-6 text-center text-gray-500">
                    No groups available to join at this time.
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php foreach ($groups as $group): ?>
                        <div class="bg-white rounded-lg shadow p-4">
                            <h3 class="font-semibold text-lg mb-2"><?= htmlspecialchars($group['group_name']) ?></h3>
                            <p class="text-gray-600 mb-3"><?= htmlspecialchars($group['group_description']) ?></p>
                            <div class="text-sm text-gray-500 mb-3">
                                <span><i class="fas fa-users mr-1"></i> <?= $group['member_count'] ?> members</span>
                            </div>
                            <form method="POST">
                                <input type="hidden" name="group_id" value="<?= $group['group_id'] ?>">
                                <button type="submit" name="join_group" 
                                        class="px-3 py-1 bg-green-600 text-white rounded text-sm hover:bg-green-700">
                                    Join Group
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>