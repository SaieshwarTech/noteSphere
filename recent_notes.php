<?php
$recent_notes = $conn->query("SELECT * FROM notes WHERE user_id = $user_id ORDER BY updated_at DESC LIMIT 3");
?>

<div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-semibold">📚 Recent Notes</h3>
        <a href="notes.php" class="text-indigo-600 text-sm hover:text-indigo-800">View All →</a>
    </div>
    <div class="space-y-4">
        <?php while($note = $recent_notes->fetch_assoc()): ?>
        <div class="flex items-center p-3 hover:bg-gray-50 rounded-lg transition-colors">
            <div class="bg-indigo-100 p-2 rounded-lg mr-4">
                <i class="fas fa-file-code text-indigo-600"></i>
            </div>
            <div>
                <p class="font-medium"><?= htmlspecialchars($note['title']) ?></p>
                <p class="text-sm text-gray-500">
                    Updated <?= date('M j, Y H:i', strtotime($note['updated_at'])) ?>
                </p>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
</div>