<?php
$notes_count = $conn->query("SELECT COUNT(*) FROM notes WHERE user_id = $user_id")->fetch_row()[0];
$connections_count = $conn->query("SELECT COUNT(*) FROM connections WHERE user_id = $user_id")->fetch_row()[0];
$storage_used = $conn->query("SELECT SUM(size) FROM notes WHERE user_id = $user_id")->fetch_row()[0];
$storage_percent = round(($storage_used / (1024 * 1024)) / 100 * 100; // Example calculation
?>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 p-6">
    <!-- Total Notes Card -->
    <div class="dashboard-card bg-white p-6 rounded-xl shadow-sm border border-gray-100 hover:border-indigo-200">
        <div class="flex justify-between items-center">
            <div>
                <p class="text-gray-500 text-sm">Total Notes</p>
                <h3 class="text-3xl font-bold mt-1 text-indigo-600"><?= $notes_count ?></h3>
            </div>
            <div class="bg-indigo-100 p-3 rounded-lg">
                <i class="fas fa-file-alt text-indigo-600 text-2xl"></i>
            </div>
        </div>
    </div>

    <!-- Connections Card -->
    <div class="dashboard-card bg-white p-6 rounded-xl shadow-sm border border-gray-100 hover:border-green-200">
        <div class="flex justify-between items-center">
            <div>
                <p class="text-gray-500 text-sm">Connections</p>
                <h3 class="text-3xl font-bold mt-1 text-green-600"><?= $connections_count ?></h3>
            </div>
            <div class="bg-green-100 p-3 rounded-lg">
                <i class="fas fa-users text-green-600 text-2xl"></i>
            </div>
        </div>
    </div>

    <!-- Storage Card -->
    <div class="dashboard-card bg-white p-6 rounded-xl shadow-sm border border-gray-100 hover:border-blue-200">
        <div class="flex justify-between items-center">
            <div>
                <p class="text-gray-500 text-sm">Storage Used</p>
                <h3 class="text-3xl font-bold mt-1 text-blue-600"><?= $storage_percent ?>%</h3>
            </div>
            <div class="bg-blue-100 p-3 rounded-lg">
                <i class="fas fa-database text-blue-600 text-2xl"></i>
            </div>
        </div>
    </div>
</div>