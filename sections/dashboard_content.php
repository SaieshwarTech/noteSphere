<?php
session_start();
require_once '../db_connect.php';

// Fetch dashboard stats with error handling
$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    header("Location: login.php");
    exit();
}

$stats = [
    'note_count' => 0,
    'collaborations' => 0,
    'resources' => 0
];

try {
    // Get note count
    $stmt = $conn->prepare("SELECT COUNT(*) FROM notes WHERE user_id = :user_id");
    $stmt->bindValue(":user_id", $userId, PDO::PARAM_INT);
    $stmt->execute();
    $stats['note_count'] = $stmt->fetchColumn();

    // Get collaboration count
    $stmt = $conn->prepare("SELECT COUNT(*) FROM collaborations WHERE user_id = :user_id");
    $stmt->bindValue(":user_id", $userId, PDO::PARAM_INT);
    $stmt->execute();
    $stats['collaborations'] = $stmt->fetchColumn();

    // Get resource count
    $stmt = $conn->prepare("SELECT COUNT(*) FROM resources WHERE user_id = :user_id");
    $stmt->bindValue(":user_id", $userId, PDO::PARAM_INT);
    $stmt->execute();
    $stats['resources'] = $stmt->fetchColumn();

} catch (Exception $e) {
    error_log("Dashboard error: " . $e->getMessage());
}
$conn = null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NoteSphere Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            /* Modern Gradient Color Scheme */
            --primary: #22C55E;
            --primary-light: #86EFAC;
            --primary-dark: #16A34A;
            --accent: #0EA5E9;
            --accent-light: #7DD3FC;
            --danger: #EF4444;
            --warning: #F59E0B;
            --info: #3B82F6;
            
            /* Light Theme */
            --bg-main: #F8FAFC;
            --bg-card: #FFFFFF;
            --bg-nav: rgba(255,255,255,0.8);
            --text-primary: #1E293B;
            --text-secondary: #64748B;
            --border-color: rgba(0,0,0,0.05);
            --shadow: 0 4px 6px rgba(0,0,0,0.05);
            --hover-bg: rgba(0,0,0,0.02);
        }

        [data-theme="dark"] {
            /* Dark Theme */
            --bg-main: #0F172A;
            --bg-card: #1E293B;
            --bg-nav: rgba(30,41,59,0.8);
            --text-primary: #F1F5F9;
            --text-secondary: #94A3B8;
            --border-color: rgba(255,255,255,0.05);
            --shadow: 0 4px 6px rgba(0,0,0,0.2);
            --hover-bg: rgba(255,255,255,0.05);
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-main);
            color: var(--text-primary);
            transition: all 0.3s ease;
        }
        
        /* Gradient Background Utility */
        .gradient-bg {
            background: linear-gradient(135deg, var(--primary), var(--accent));
        }
        
        /* Glassmorphism Effect */
        .glass-effect {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid var(--border-color);
        }
        
        /* Cards with Gradient Border */
        .card {
            background-color: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
            margin-bottom: 25px;
            position: relative;
            overflow: hidden;
        }
        
        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--accent));
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        /* Buttons */
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--accent));
            border: none;
            font-weight: 500;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(34,197,94,0.3);
        }
        
        /* Animations */
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Motivational Quote */
        .motivational-quote {
            font-style: italic;
            color: var(--text-secondary);
            border-left: 3px solid var(--primary);
            padding-left: 15px;
        }
        
        /* Stats Cards */
        .stat-card {
            height: 100%;
            transition: all 0.3s ease;
        }
        
        .stat-card-icon {
            font-size: 2.5rem;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1rem;
        }
        
        /* Header */
        .dashboard-header {
            background-color: var(--bg-nav);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border-color);
            box-shadow: var(--shadow);
        }
        
        /* Gradient Text */
        .gradient-text {
            background: linear-gradient(135deg, var(--primary), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: inline-block;
        }
        
        /* Activity List */
        .activity-item {
            border-left: 3px solid transparent;
            transition: all 0.2s ease;
        }
        
        .activity-item:hover {
            border-left-color: var(--primary);
            background-color: var(--hover-bg);
        }
        
        /* Dark Mode Toggle */
        .dark-mode-toggle {
            width: 44px;
            height: 24px;
            border-radius: 12px;
            background-color: var(--bg-card);
            border: 1px solid var(--border-color);
            position: relative;
            cursor: pointer;
        }
        
        .dark-mode-toggle:after {
            content: '';
            position: absolute;
            top: 2px;
            left: 2px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            transition: all 0.3s ease;
        }
        
        [data-theme="dark"] .dark-mode-toggle:after {
            transform: translateX(20px);
        }
    </style>
</head>
<body>
    <!-- Dashboard Header -->
    <header class="dashboard-header sticky-top py-3 mb-4 glass-effect">
        <div class="container-fluid d-flex justify-content-between align-items-center">
            <h1 class="h4 mb-0">
                <i class="fas fa-book-open me-2 gradient-text"></i>
                <span class="gradient-text">Note</span>Sphere
            </h1>
            
            <div class="d-flex align-items-center gap-3">
                <div class="dark-mode-toggle me-2" id="darkModeToggle"></div>
                <div class="dropdown">
                    <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" id="profileDropdown" data-bs-toggle="dropdown">
                        <img src="assets/default-profile.jpg" class="rounded-circle me-2" width="32" height="32" alt="Profile">
                        <span><?= htmlspecialchars($_SESSION['username'] ?? 'User') ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end glass-effect" style="border: 1px solid var(--border-color);">
                        <li><a class="dropdown-item" href="#"><i class="fas fa-user me-2"></i> Profile</a></li>
                        <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-2"></i> Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="container fade-in">
        <!-- Welcome Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="mb-2">Welcome back, <?= htmlspecialchars($_SESSION['username'] ?? 'User') ?>!</h2>
                            <p class="motivational-quote mb-0">"The expert in anything was once a beginner." - Helen Hayes</p>
                        </div>
                        <div class="d-none d-md-block">
                            <div class="bg-primary bg-opacity-10 p-3 rounded">
                                <i class="fas fa-lightbulb gradient-text" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="card stat-card p-4">
                    <div class="text-center">
                        <i class="fas fa-book stat-card-icon"></i>
                        <h3 class="mb-2"><?= $stats['note_count'] ?></h3>
                        <p class="text-muted mb-3">Your Notes</p>
                        <a href="notes.php" class="btn btn-primary">View All</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-3">
                <div class="card stat-card p-4">
                    <div class="text-center">
                        <i class="fas fa-users stat-card-icon"></i>
                        <h3 class="mb-2"><?= $stats['collaborations'] ?></h3>
                        <p class="text-muted mb-3">Collaborations</p>
                        <a href="collaborations.php" class="btn btn-primary">View All</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-3">
                <div class="card stat-card p-4">
                    <div class="text-center">
                        <i class="fas fa-file-alt stat-card-icon"></i>
                        <h3 class="mb-2"><?= $stats['resources'] ?></h3>
                        <p class="text-muted mb-3">Resources</p>
                        <a href="resources.php" class="btn btn-primary">View All</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card p-4">
                    <h4 class="mb-4">Quick Actions</h4>
                    <div class="d-flex flex-wrap gap-3">
                        <a href="upload.php" class="btn btn-primary">
                            <i class="fas fa-upload me-2"></i>Upload Notes
                        </a>
                        <a href="browse.php" class="btn btn-outline-primary">
                            <i class="fas fa-search me-2"></i>Browse Notes
                        </a>
                        <a href="collaborate.php" class="btn btn-outline-primary">
                            <i class="fas fa-user-plus me-2"></i>Start Collaboration
                        </a>
                        <a href="create.php" class="btn btn-outline-primary">
                            <i class="fas fa-plus me-2"></i>Create New Note
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity & Announcements -->
        <div class="row">
            <div class="col-lg-6 mb-4">
                <div class="card h-100">
                    <div class="card-header gradient-bg text-white">
                        <h4 class="mb-0"><i class="fas fa-history me-2"></i>Recent Activity</h4>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <a href="#" class="list-group-item list-group-item-action activity-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="fas fa-file-alt text-primary me-2"></i>
                                        <span>Created new note "Meeting Notes"</span>
                                    </div>
                                    <small class="text-muted">2 hours ago</small>
                                </div>
                            </a>
                            <a href="#" class="list-group-item list-group-item-action activity-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="fas fa-share-alt text-info me-2"></i>
                                        <span>Shared "Project Ideas" with team</span>
                                    </div>
                                    <small class="text-muted">Yesterday</small>
                                </div>
                            </a>
                            <a href="#" class="list-group-item list-group-item-action activity-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="fas fa-download text-success me-2"></i>
                                        <span>Downloaded "Chemistry 101"</span>
                                    </div>
                                    <small class="text-muted">3 days ago</small>
                                </div>
                            </a>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent">
                        <a href="activity.php" class="btn btn-sm btn-outline-primary">View All Activity</a>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6 mb-4">
                <div class="card h-100">
                    <div class="card-header gradient-bg text-white">
                        <h4 class="mb-0"><i class="fas fa-bullhorn me-2"></i>Announcements</h4>
                    </div>
                    <div class="card-body">
                        <div class="alert bg-primary bg-opacity-10 border-start border-primary border-3 mb-3">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-tools text-primary me-2"></i>
                                <h5 class="mb-0">System Maintenance</h5>
                            </div>
                            <p class="small mb-1">Scheduled maintenance on Saturday, 10 AM - 12 PM.</p>
                            <small class="text-muted">Posted 2 days ago</small>
                        </div>
                        <div class="alert bg-primary bg-opacity-10 border-start border-primary border-3">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-star text-primary me-2"></i>
                                <h5 class="mb-0">New Feature: Group Collaboration</h5>
                            </div>
                            <p class="small mb-1">Now you can create study groups with up to 10 members!</p>
                            <small class="text-muted">Posted 1 week ago</small>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent">
                        <a href="announcements.php" class="btn btn-sm btn-outline-primary">View All Announcements</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Dark mode toggle
        document.getElementById('darkModeToggle').addEventListener('click', function() {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            if (currentTheme === 'dark') {
                document.documentElement.removeAttribute('data-theme');
                localStorage.setItem('theme', 'light');
            } else {
                document.documentElement.setAttribute('data-theme', 'dark');
                localStorage.setItem('theme', 'dark');
            }
        });

        // Check for saved theme preference
        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.setAttribute('data-theme', 'dark');
        }

        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
    </script>
</body>
</html>