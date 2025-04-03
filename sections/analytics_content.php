<?php
session_start();
require_once '../db_connect.php';

// Initialize stats array with default values
$stats = [
    'notes_created' => [],
    'collaboration_stats' => [
        'total' => 0,
        'active' => 0,
        'pending' => 0
    ],
    'note_categories' => [],
    'performance' => [
        'uploaded' => 0,
        'read' => 0,
        'shared' => 0
    ],
    'study_trends' => [],
    'top_notes' => [],
    'engagement_score' => 0
];

try {
    if (isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
        
        // Get notes created per month
        $stmt = $conn->prepare("SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count 
            FROM notes WHERE user_id = :user_id GROUP BY month ORDER BY month DESC LIMIT 6");
        $stmt->bindValue(":user_id", $userId, PDO::PARAM_INT);
        $stmt->execute();
        $stats['notes_created'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get collaboration stats
        $stmt = $conn->prepare("SELECT COUNT(*) as total, 
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active, 
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending
            FROM collaborations WHERE user_id = :user_id");
        $stmt->bindValue(":user_id", $userId, PDO::PARAM_INT);
        $stmt->execute();
        $collabStats = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($collabStats) {
            $stats['collaboration_stats'] = $collabStats;
        }
        
        // Get note categories breakdown
        $stmt = $conn->prepare("SELECT category, COUNT(*) as count FROM notes WHERE user_id = :user_id GROUP BY category");
        $stmt->bindValue(":user_id", $userId, PDO::PARAM_INT);
        $stmt->execute();
        $stats['note_categories'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Performance Tracker
        $stmt = $conn->prepare("
            SELECT 
                (SELECT COUNT(*) FROM notes WHERE user_id = :user_id) as uploaded,
                (SELECT COUNT(*) FROM note_views WHERE user_id = :user_id) as `read`,
                (SELECT COUNT(*) FROM collaborations WHERE user_id = :user_id) as shared
        ");
        $stmt->bindValue(":user_id", $userId);
        $stmt->execute();
        $performance = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($performance) {
            $stats['performance'] = $performance;
        }

        // Study Trends
        $stmt = $conn->prepare("
            SELECT category, SUM(duration_minutes) as total_time 
            FROM study_sessions 
            WHERE user_id = :user_id 
            GROUP BY category
        ");
        $stmt->bindValue(":user_id", $userId);
        $stmt->execute();
        $stats['study_trends'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Top Notes
        $stmt = $conn->prepare("
            SELECT id, title, downloads, likes 
            FROM notes 
            WHERE user_id = :user_id 
            ORDER BY downloads + likes DESC 
            LIMIT 3
        ");
        $stmt->bindValue(":user_id", $userId);
        $stmt->execute();
        $stats['top_notes'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calculate Engagement Score safely
        $notesCount = count($stats['notes_created']);
        $activeCollabs = $stats['collaboration_stats']['active'] ?? 0;
        $readCount = $stats['performance']['read'] ?? 0;
        
        $stats['engagement_score'] = min(100, 
            ($notesCount * 5) + 
            ($activeCollabs * 10) + 
            ($readCount * 2)
        );
    }
} catch (Exception $e) {
    error_log("Analytics error: " . $e->getMessage());
    // You might want to display a user-friendly error message here
}

$conn = null;
$lastUpdated = date('Y-m-d H:i:s');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Dashboard</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#f0fdf4',
                            100: '#dcfce7',
                            200: '#bbf7d0',
                            300: '#86efac',
                            400: '#4ade80',
                            500: '#22c55e',
                            600: '#16a34a',
                            700: '#15803d',
                            800: '#166534',
                            900: '#14532d',
                        },
                        secondary: {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            200: '#bae6fd',
                            300: '#7dd3fc',
                            400: '#38bdf8',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            700: '#0369a1',
                            800: '#075985',
                            900: '#0c4a6e',
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                }
            }
        }
    </script>
    
    <!-- Material Icons -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            background-color: #111827;
            color: #f3f4f6;
        }
        
        .glass-card {
            background: rgba(30, 30, 30, 0.7);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.1);
        }
        
        .engagement-ring {
            position: relative;
            width: 120px;
            height: 120px;
        }
        
        .engagement-ring-circle {
            transform: rotate(-90deg);
            transform-origin: 50% 50%;
        }
        
        .engagement-ring-bg {
            stroke: #374151;
            stroke-width: 8;
            fill: none;
        }
        
        .engagement-ring-progress {
            stroke: url(#engagementGradient);
            stroke-width: 8;
            stroke-linecap: round;
            fill: none;
            stroke-dasharray: 314;
            stroke-dashoffset: 314;
            animation: engagementFill 1.5s ease-out forwards;
        }
        
        @keyframes engagementFill {
            to {
                stroke-dashoffset: calc(314 - (314 * var(--score)) / 100);
            }
        }
        
        .dropdown-menu {
            background-color: #1f2937;
            border-color: #374151;
        }
        
        .dropdown-item {
            color: #f3f4f6;
        }
        
        .dropdown-item:hover {
            background-color: #374151;
        }
    </style>
</head>
<body class="font-sans">
    <div class="min-h-screen">
        <!-- Header -->
        <header class="bg-gray-800 shadow-sm">
            <div class="max-w-7xl mx-auto px-4 py-4 sm:px-6 lg:px-8 flex justify-between items-center">
                <h1 class="text-2xl font-bold text-white">Study Analytics</h1>
                <div class="flex items-center space-x-4">
                    <button id="themeToggle" class="p-2 rounded-full hover:bg-gray-700">
                        <span class="material-icons-round hidden">dark_mode</span>
                        <span class="material-icons-round text-white">light_mode</span>
                    </button>
                    <div class="relative">
                        <button id="userMenuButton" class="flex items-center space-x-2 focus:outline-none">
                            <span class="material-icons-round text-white">account_circle</span>
                            <span class="hidden md:inline font-medium text-white">My Account</span>
                        </button>
                        <div id="userMenu" class="absolute right-0 mt-2 w-48 bg-gray-800 rounded-md shadow-lg py-1 z-50 hidden border border-gray-700">
                            <a href="#" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700">Profile</a>
                            <a href="#" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700">Settings</a>
                            <a href="#" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700">Sign out</a>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto px-4 py-6 sm:px-6 lg:px-8">
            <!-- Stats Overview -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <div class="glass-card rounded-xl p-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-400">Notes Created</p>
                            <p class="mt-1 text-3xl font-semibold text-white"><?= array_sum(array_column($stats['notes_created'], 'count')) ?></p>
                        </div>
                        <div class="p-3 rounded-lg bg-primary-900/30 text-primary-300">
                            <span class="material-icons-round text-2xl">note_add</span>
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-gray-400">Last updated: <?= $lastUpdated ?></p>
                </div>
                
                <div class="glass-card rounded-xl p-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-400">Notes Read</p>
                            <p class="mt-1 text-3xl font-semibold text-white"><?= $stats['performance']['read'] ?? 0 ?></p>
                        </div>
                        <div class="p-3 rounded-lg bg-blue-900/30 text-blue-300">
                            <span class="material-icons-round text-2xl">visibility</span>
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-gray-400">+12% from last month</p>
                </div>
                
                <div class="glass-card rounded-xl p-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-400">Collaborations</p>
                            <p class="mt-1 text-3xl font-semibold text-white"><?= $stats['collaboration_stats']['total'] ?? 0 ?></p>
                        </div>
                        <div class="p-3 rounded-lg bg-purple-900/30 text-purple-300">
                            <span class="material-icons-round text-2xl">group</span>
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-gray-400"><?= $stats['collaboration_stats']['active'] ?? 0 ?> active</p>
                </div>
                
                <div class="glass-card rounded-xl p-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-400">Engagement</p>
                            <p class="mt-1 text-3xl font-semibold text-white"><?= $stats['engagement_score'] ?>/100</p>
                        </div>
                        <div class="p-3 rounded-lg bg-yellow-900/30 text-yellow-300">
                            <span class="material-icons-round text-2xl">trending_up</span>
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-gray-400">Your learning activity</p>
                </div>
            </div>

            <!-- Main Charts Row -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Notes Created Chart -->
                <div class="glass-card rounded-xl p-6 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-white">Notes Created</h2>
                        <div class="flex items-center space-x-2">
                            <span class="text-xs text-gray-400">Last 6 months</span>
                            <button class="p-1 rounded-full hover:bg-gray-700">
                                <span class="material-icons-round text-gray-400">more_vert</span>
                            </button>
                        </div>
                    </div>
                    <div class="h-64">
                        <canvas id="notesChart"></canvas>
                    </div>
                </div>
                
                <!-- Study Trends Chart -->
                <div class="glass-card rounded-xl p-6 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-white">Study Time by Category</h2>
                        <div class="flex items-center space-x-2">
                            <span class="text-xs text-gray-400">Hours spent</span>
                            <button class="p-1 rounded-full hover:bg-gray-700">
                                <span class="material-icons-round text-gray-400">more_vert</span>
                            </button>
                        </div>
                    </div>
                    <div class="h-64">
                        <canvas id="studyTrendsChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Second Row -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                <!-- Categories Breakdown -->
                <div class="glass-card rounded-xl p-6 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-white">Categories</h2>
                        <button class="p-1 rounded-full hover:bg-gray-700">
                            <span class="material-icons-round text-gray-400">more_vert</span>
                        </button>
                    </div>
                    <div class="h-64">
                        <canvas id="categoryChart"></canvas>
                    </div>
                </div>
                
                <!-- Engagement Score -->
                <div class="glass-card rounded-xl p-6 shadow-sm">
                    <div class="flex flex-col items-center justify-center h-full">
                        <h2 class="text-lg font-semibold text-white mb-4">Engagement Score</h2>
                        <div class="relative">
                            <div class="engagement-ring">
                                <svg class="w-full h-full" viewBox="0 0 120 120">
                                    <defs>
                                        <linearGradient id="engagementGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                            <stop offset="0%" stop-color="#4ade80" />
                                            <stop offset="100%" stop-color="#3b82f6" />
                                        </linearGradient>
                                    </defs>
                                    <circle class="engagement-ring-bg" cx="60" cy="60" r="50"></circle>
                                    <circle 
                                        class="engagement-ring-progress" 
                                        cx="60" 
                                        cy="60" 
                                        r="50"
                                        style="--score: <?= $stats['engagement_score'] ?>"
                                    ></circle>
                                </svg>
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <span class="text-3xl font-bold text-white"><?= $stats['engagement_score'] ?></span>
                                </div>
                            </div>
                        </div>
                        <p class="mt-4 text-sm text-gray-300 text-center">
                            Based on your notes, reading, and collaboration activity
                        </p>
                    </div>
                </div>
                
                <!-- Top Notes -->
                <div class="glass-card rounded-xl p-6 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-white">Top Notes</h2>
                        <button class="p-1 rounded-full hover:bg-gray-700">
                            <span class="material-icons-round text-gray-400">more_vert</span>
                        </button>
                    </div>
                    <div class="space-y-4">
                        <?php foreach ($stats['top_notes'] as $index => $note): ?>
                        <div class="flex items-start p-3 rounded-lg hover:bg-gray-700/50 transition-colors">
                            <div class="flex-shrink-0 mt-1 mr-3 text-primary-400">
                                <span class="material-icons-round">article</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-white truncate">
                                    <?= htmlspecialchars($note['title']) ?>
                                </p>
                                <div class="flex items-center mt-1 text-xs text-gray-400">
                                    <span class="flex items-center mr-3">
                                        <span class="material-icons-round text-xs mr-1">thumb_up</span>
                                        <?= $note['likes'] ?> likes
                                    </span>
                                    <span class="flex items-center">
                                        <span class="material-icons-round text-xs mr-1">download</span>
                                        <?= $note['downloads'] ?> downloads
                                    </span>
                                </div>
                            </div>
                            <div class="flex-shrink-0 ml-2">
                                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-primary-900/30 text-primary-300 text-xs font-medium">
                                    <?= $index + 1 ?>
                                </span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Collaboration Stats -->
            <div class="glass-card rounded-xl p-6 shadow-sm mb-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-white">Collaboration Activity</h2>
                    <button class="p-1 rounded-full hover:bg-gray-700">
                        <span class="material-icons-round text-gray-400">more_vert</span>
                    </button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-gray-700/50 p-4 rounded-lg shadow-sm">
                        <div class="flex items-center">
                            <div class="p-2 rounded-full bg-green-900/30 text-green-300 mr-3">
                                <span class="material-icons-round">check_circle</span>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-400">Active</p>
                                <p class="text-2xl font-semibold text-white"><?= $stats['collaboration_stats']['active'] ?? 0 ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-700/50 p-4 rounded-lg shadow-sm">
                        <div class="flex items-center">
                            <div class="p-2 rounded-full bg-yellow-900/30 text-yellow-300 mr-3">
                                <span class="material-icons-round">pending</span>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-400">Pending</p>
                                <p class="text-2xl font-semibold text-white"><?= $stats['collaboration_stats']['pending'] ?? 0 ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-700/50 p-4 rounded-lg shadow-sm">
                        <div class="flex items-center">
                            <div class="p-2 rounded-full bg-blue-900/30 text-blue-300 mr-3">
                                <span class="material-icons-round">groups</span>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-400">Total</p>
                                <p class="text-2xl font-semibold text-white"><?= $stats['collaboration_stats']['total'] ?? 0 ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Theme toggle (kept for functionality but default to dark)
        const themeToggle = document.getElementById('themeToggle');
        const html = document.documentElement;
        
        // Force dark mode by default
        html.classList.add('dark');
        localStorage.setItem('theme', 'dark');
        
        themeToggle.addEventListener('click', () => {
            html.classList.toggle('dark');
            localStorage.setItem('theme', html.classList.contains('dark') ? 'dark' : 'light');
        });
        
        // User menu toggle
        const userMenuButton = document.getElementById('userMenuButton');
        const userMenu = document.getElementById('userMenu');
        
        userMenuButton.addEventListener('click', () => {
            userMenu.classList.toggle('hidden');
        });
        
        // Close menu when clicking outside
        document.addEventListener('click', (e) => {
            if (!userMenu.contains(e.target) && !userMenuButton.contains(e.target)) {
                userMenu.classList.add('hidden');
            }
        });
        
        // Initialize charts with dark theme
        document.addEventListener('DOMContentLoaded', function() {
            // Notes Created Chart
            const notesData = <?= json_encode($stats['notes_created']) ?>;
            const labels = notesData.map(item => new Date(item.month + '-01').toLocaleDateString('en-US', { month: 'short', year: 'numeric' }));
            const data = notesData.map(item => item.count);
            
            const notesCtx = document.getElementById('notesChart').getContext('2d');
            const notesChart = new Chart(notesCtx, {
                type: 'line',
                data: {
                    labels: labels.reverse(),
                    datasets: [{
                        label: 'Notes Created',
                        data: data.reverse(),
                        borderColor: '#4ade80',
                        backgroundColor: 'rgba(74, 222, 128, 0.1)',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true,
                        pointBackgroundColor: '#1f2937',
                        pointBorderColor: '#4ade80',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: '#1f2937',
                            titleColor: '#f9fafb',
                            bodyColor: '#f9fafb',
                            borderColor: '#374151',
                            borderWidth: 1,
                            padding: 12,
                            usePointStyle: true,
                            callbacks: {
                                label: function(context) {
                                    return context.parsed.y + ' notes';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(55, 65, 81, 0.3)',
                                drawBorder: false
                            },
                            ticks: {
                                color: '#9CA3AF'
                            }
                        },
                        x: {
                            grid: {
                                display: false,
                                drawBorder: false
                            },
                            ticks: {
                                color: '#9CA3AF'
                            }
                        }
                    }
                }
            });
            
            // Study Trends Chart
            const studyData = <?= json_encode($stats['study_trends']) ?>;
            const studyLabels = studyData.map(item => item.category);
            const studyValues = studyData.map(item => (item.total_time / 60).toFixed(1)); // Convert to hours
            
            const studyCtx = document.getElementById('studyTrendsChart').getContext('2d');
            const studyChart = new Chart(studyCtx, {
                type: 'bar',
                data: {
                    labels: studyLabels,
                    datasets: [{
                        label: 'Hours',
                        data: studyValues,
                        backgroundColor: [
                            'rgba(99, 102, 241, 0.7)',
                            'rgba(59, 130, 246, 0.7)',
                            'rgba(16, 185, 129, 0.7)',
                            'rgba(245, 158, 11, 0.7)',
                            'rgba(244, 63, 94, 0.7)'
                        ],
                        borderColor: [
                            'rgba(99, 102, 241, 1)',
                            'rgba(59, 130, 246, 1)',
                            'rgba(16, 185, 129, 1)',
                            'rgba(245, 158, 11, 1)',
                            'rgba(244, 63, 94, 1)'
                        ],
                        borderWidth: 1,
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: '#1f2937',
                            titleColor: '#f9fafb',
                            bodyColor: '#f9fafb',
                            borderColor: '#374151',
                            borderWidth: 1,
                            padding: 12,
                            callbacks: {
                                label: function(context) {
                                    return context.parsed.y + ' hours';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(55, 65, 81, 0.3)',
                                drawBorder: false
                            },
                            ticks: {
                                color: '#9CA3AF'
                            }
                        },
                        x: {
                            grid: {
                                display: false,
                                drawBorder: false
                            },
                            ticks: {
                                color: '#9CA3AF'
                            }
                        }
                    }
                }
            });
            
            // Categories Chart
            const categoriesData = <?= json_encode($stats['note_categories']) ?>;
            const categoryCtx = document.getElementById('categoryChart').getContext('2d');
            const categoryChart = new Chart(categoryCtx, {
                type: 'doughnut',
                data: {
                    labels: categoriesData.map(item => item.category),
                    datasets: [{
                        data: categoriesData.map(item => item.count),
                        backgroundColor: [
                            'rgba(99, 102, 241, 0.7)',
                            'rgba(59, 130, 246, 0.7)',
                            'rgba(16, 185, 129, 0.7)',
                            'rgba(245, 158, 11, 0.7)',
                            'rgba(244, 63, 94, 0.7)'
                        ],
                        borderColor: [
                            'rgba(99, 102, 241, 1)',
                            'rgba(59, 130, 246, 1)',
                            'rgba(16, 185, 129, 1)',
                            'rgba(245, 158, 11, 1)',
                            'rgba(244, 63, 94, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                color: '#9CA3AF',
                                usePointStyle: true,
                                pointStyle: 'circle',
                                padding: 20
                            }
                        },
                        tooltip: {
                            backgroundColor: '#1f2937',
                            titleColor: '#f9fafb',
                            bodyColor: '#f9fafb',
                            borderColor: '#374151',
                            borderWidth: 1,
                            padding: 12,
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.raw || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = Math.round((value / total) * 100);
                                    return `${label}: ${value} (${percentage}%)`;
                                }
                            }
                        }
                    },
                    cutout: '70%'
                }
            });
        });
    </script>
</body>
</html>