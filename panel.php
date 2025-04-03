<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start secure session
session_start([
    'cookie_lifetime' => 86400,
    'cookie_secure'   => true,
    'cookie_httponly' => true,
    'use_strict_mode' => true
]);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=".urlencode($_SERVER['REQUEST_URI']));
    exit();
}

// Database connection
require_once './db_connect.php';

// Check if profile is complete
try {
    $stmt = $conn->prepare("SELECT bio, address FROM users WHERE id = :id");
    $stmt->bindValue(':id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->execute();
    $profileCheck = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($profileCheck && (empty($profileCheck['bio']) || empty($profileCheck['address']))) {
        // Profile is incomplete, redirect to profile completion page
        header("Location: profile.php");
        exit();
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    die("A system error occurred. Please try again later.");
}

// Function to validate profile picture path
function getValidProfilePicPath($profilePic) {
    $default = 'assets/image.png'; // Changed to image.png
    
    // If no profile picture is set, return default
    if (empty($profilePic)) {
        return $default;
    }
    
    // If it's already a full URL, return as-is
    if (filter_var($profilePic, FILTER_VALIDATE_URL)) {
        return $profilePic;
    }
    
    // For local paths, ensure they're safe
    $uploadDir = 'uploads/';
    
    // If path doesn't start with upload directory, prepend it
    if (strpos($profilePic, $uploadDir) !== 0) {
        $profilePic = $uploadDir . ltrim($profilePic, '/');
    }
    
    // Verify the file exists and is an image
    $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($profilePic, '/');
    if (file_exists($fullPath) && @getimagesize($fullPath)) {
        return $profilePic;
    }
    
    return $default;
}

// Initialize default values
$userData = [
    'fullname' => 'User',
    'role' => 'Member',
    'profile_pic' => 'assets/image.png', // Changed to image.png
    'join_date' => 'Unknown',
    'isNewUser' => false,
    'last_login' => null
];

// Initialize stats variables
$total_notes = 0;
$total_collaborators = 0;
$total_favorites = 0;

try {
    // Get user data
    $stmt = $conn->prepare("SELECT fullname, role, profile_pic, created_at, last_login FROM users WHERE id = :id");
    $stmt->bindValue(':id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Process user data
        $userData = [
            'fullname' => htmlspecialchars($user['fullname'] ?? 'User', ENT_QUOTES, 'UTF-8'),
            'role' => htmlspecialchars($user['role'] ?? 'Member', ENT_QUOTES, 'UTF-8'),
            'profile_pic' => getValidProfilePicPath($user['profile_pic'] ?? null),
            'join_date' => date('F j, Y', strtotime($user['created_at'])),
            'isNewUser' => (time() - strtotime($user['created_at'])) <= 86400,
            'last_login' => !empty($user['last_login']) ? date('M j, Y g:i a', strtotime($user['last_login'])) : 'First login'
        ];
        
        // Update last login
        $updateStmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = :id");
        $updateStmt->bindValue(':id', $_SESSION['user_id'], PDO::PARAM_INT);
        $updateStmt->execute();

        // Get user stats
        $stmt = $conn->prepare("SELECT COUNT(*) as total_notes FROM notes WHERE user_id = :user_id");
        $stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
        $stmt->execute();
        $notesCount = $stmt->fetch(PDO::FETCH_ASSOC);
        $total_notes = $notesCount['total_notes'] ?? 0;

        $stmt = $conn->prepare("SELECT COUNT(DISTINCT collaborator_id) as total_collaborators FROM note_collaborators WHERE note_id IN (SELECT id FROM notes WHERE user_id = :user_id)");
        $stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
        $stmt->execute();
        $collaboratorsCount = $stmt->fetch(PDO::FETCH_ASSOC);
        $total_collaborators = $collaboratorsCount['total_collaborators'] ?? 0;

        $stmt = $conn->prepare("SELECT COUNT(*) as total_favorites FROM notes WHERE user_id = :user_id AND favorite = 1");
        $stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
        $stmt->execute();
        $favoritesCount = $stmt->fetch(PDO::FETCH_ASSOC);
        $total_favorites = $favoritesCount['total_favorites'] ?? 0;

    } else {
        session_unset();
        session_destroy();
        header("Location: login.php?error=account_invalid");
        exit();
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    die("A system error occurred. Please try again later.");
}

// Extract variables for use in HTML
extract($userData);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NoteSphere Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/@yaireo/tagify/dist/tagify.css" rel="stylesheet">
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
            --bg-sidebar: rgba(255,255,255,0.8);
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
            --bg-sidebar: rgba(30,41,59,0.8);
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
            min-height: 100vh;
            display: flex;
            flex-direction: column;
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
        
        /* Main Layout */
        .app-container {
            display: flex;
            flex: 1;
            min-height: 100vh;
            padding-top: 70px; /* Space for fixed header */
        }
        
        /* Sidebar - Modern Glass Design */
        .sidebar {
            width: 280px;
            background-color: var(--bg-sidebar);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-right: 1px solid var(--border-color);
            position: fixed;
            top: 70px;
            bottom: 0;
            left: 0;
            z-index: 100;
            transition: all 0.3s ease;
            overflow-y: auto;
            box-shadow: var(--shadow);
        }
        
        /* Main Content Area */
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 25px;
            transition: all 0.3s ease;
        }
        
        /* Top Navigation - Glass Effect */
        .top-nav {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 70px;
            background-color: var(--bg-nav);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            z-index: 1000;
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            padding: 0 25px;
            border-bottom: 1px solid var(--border-color);
        }
        
        /* Sidebar Navigation */
        .sidebar-nav {
            padding: 15px 0;
        }
        
        .sidebar-nav .nav-link {
            color: var(--text-secondary);
            padding: 12px 20px;
            margin: 5px 15px;
            border-radius: 8px;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            font-weight: 500;
        }
        
        .sidebar-nav .nav-link i {
            width: 24px;
            text-align: center;
            margin-right: 12px;
            color: var(--text-secondary);
        }
        
        .sidebar-nav .nav-link.active {
            background: linear-gradient(135deg, rgba(34,197,94,0.1), rgba(14,165,233,0.1));
            color: var(--primary);
            font-weight: 600;
        }
        
        .sidebar-nav .nav-link.active i {
            color: var(--primary);
        }
        
        .sidebar-nav .nav-link:hover {
            background-color: var(--hover-bg);
            color: var(--text-primary);
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
        
        .btn-outline-primary {
            color: var(--primary);
            border-color: var(--primary);
        }
        
        .btn-outline-primary:hover {
            background: linear-gradient(135deg, var(--primary), var(--accent));
            border-color: transparent;
            color: white;
        }
        
        /* Profile Image */
        .profile-img {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid white;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }
        
        /* Badges */
        .badge-primary {
            background: rgba(34,197,94,0.1);
            color: var(--primary);
            font-weight: 500;
        }
        
        /* Notification Badge */
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            width: 20px;
            height: 20px;
            background-color: var(--danger);
            color: white;
            border-radius: 50%;
            font-size: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        /* Floating Action Button */
        .fab {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 6px 20px rgba(34,197,94,0.3);
            z-index: 100;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 1.2rem;
        }
        
        .fab:hover {
            transform: translateY(-5px) scale(1.05);
            box-shadow: 0 10px 25px rgba(34,197,94,0.4);
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
        
        /* Responsive Adjustments */
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .mobile-menu-toggle {
                display: block;
            }
        }
        
        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .animate-fade {
            animation: fadeIn 0.4s ease forwards;
        }
        
        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
        }
        
        ::-webkit-scrollbar-track {
            background: rgba(0,0,0,0.05);
        }
        
        ::-webkit-scrollbar-thumb {
            background: linear-gradient(var(--primary), var(--accent));
            border-radius: 3px;
        }
        
        /* Tooltip Customization */
        .tooltip-inner {
            background-color: var(--bg-card);
            color: var(--text-primary);
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
        }
        
        .bs-tooltip-auto[data-popper-placement^=top] .tooltip-arrow::before, 
        .bs-tooltip-top .tooltip-arrow::before {
            border-top-color: var(--border-color);
        }
        
        /* Loading Skeleton */
        .skeleton {
            background: linear-gradient(90deg, rgba(0,0,0,0.05), rgba(0,0,0,0.1), rgba(0,0,0,0.05));
            background-size: 200% 100%;
            border-radius: 8px;
            animation: pulse 1.5s infinite ease-in-out;
        }
        
        @keyframes pulse {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
        
        /* Notification Toast */
        .notification-toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background-color: var(--bg-card);
            border-left: 4px solid var(--primary);
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: var(--shadow);
            max-width: 300px;
            transform: translateY(20px);
            opacity: 0;
            transition: all 0.3s ease;
            z-index: 1100;
        }
        
        .notification-toast.show {
            transform: translateY(0);
            opacity: 1;
        }
        
        /* Gradient Text */
        .gradient-text {
            background: linear-gradient(135deg, var(--primary), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: inline-block;
        }
    </style>
</head>
<body>
    <!-- Top Navigation -->
    <nav class="top-nav glass-effect">
        <div class="d-flex align-items-center">
            <button class="btn btn-link d-lg-none me-3 mobile-menu-toggle" id="mobileMenuToggle">
                <i class="fas fa-bars"></i>
            </button>
            <h1 class="h5 mb-0">
                <i class="fas fa-book-open me-2 gradient-text"></i>
                <span class="gradient-text">Note</span>Sphere
            </h1>
        </div>
        
        <div class="ms-auto d-flex align-items-center gap-3">
            <div class="dark-mode-toggle me-2" id="darkModeToggle"></div>
            
            <div class="position-relative">
                <button class="btn btn-link p-0" id="notificationsBtn">
                    <i class="fas fa-bell" style="color: var(--text-secondary);"></i>
                    <span class="notification-badge">3</span>
                </button>
            </div>
            
            <div class="dropdown">
                <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" id="profileDropdown" data-bs-toggle="dropdown">
                    <img src="<?php echo $profile_pic; ?>" class="profile-img me-2" alt="<?php echo $fullname; ?>">
                    <span class="d-none d-md-inline"><?php echo $fullname; ?></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end glass-effect" style="border: 1px solid var(--border-color);">
                    <li><a class="dropdown-item" href="#"><i class="fas fa-user me-2"></i> Profile</a></li>
                    <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-2"></i> Settings</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- App Container -->
    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar glass-effect" id="sidebar">
            <div class="sidebar-nav">
                <a href="#" class="nav-link load-content active" data-section="sections/dashboard_content.php">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                <a href="#" class="nav-link load-content" data-section="sections/notes_content.php">
                    <i class="fas fa-book"></i>
                    <span>My Notes</span>
                </a>
                <a href="#" class="nav-link load-content" data-section="sections/community_content.php">
                    <i class="fas fa-users"></i>
                    <span>Community</span>
                </a>
                <a href="#" class="nav-link load-content" data-section="sections/analytics_content.php">
                    <i class="fas fa-chart-line"></i>
                    <span>Analytics</span>
                </a>
                <a href="#" class="nav-link load-content" data-section="sections/update_profile.php">
                    <i class="fas fa-user-cog"></i>
                    <span>Profile Settings</span>
                </a>
                <div class="px-3 mt-4">
                    <div class="card bg-light border-0 p-3 glass-effect">
                        <h6 class="mb-2">Storage Usage</h6>
                        <div class="progress mb-2" style="height: 6px;">
                            <div class="progress-bar" role="progressbar" style="width: 45%; background: linear-gradient(90deg, var(--primary), var(--accent));"></div>
                        </div>
                        <small class="text-muted">4.5 GB of 10 GB used</small>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content" id="mainContent">
            <div class="container-fluid">
                <!-- Welcome Banner -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card animate-fade">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h4 class="mb-1">Welcome back, <?php echo $fullname; ?>!</h4>
                                        <p class="text-muted mb-0"><?php echo date('l, F j, Y'); ?></p>
                                    </div>
                                    <?php if ($isNewUser): ?>
                                        <span class="badge bg-primary bg-opacity-10 text-primary">
                                            <i class="fas fa-gift me-1"></i> New User
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card animate-fade" style="animation-delay: 0.1s;">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-2">Total Notes</h6>
                                        <h3 class="mb-0"><?php echo isset($total_notes) ? $total_notes : 0; ?></h3>
                                    </div>
                                    <div class="bg-primary bg-opacity-10 p-3 rounded">
                                        <i class="fas fa-book text-primary" style="font-size: 1.5rem;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card animate-fade" style="animation-delay: 0.2s;">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-2">Collaborators</h6>
                                        <h3 class="mb-0"><?php echo isset($total_collaborators) ? $total_collaborators : 0; ?></h3>
                                    </div>
                                    <div class="bg-info bg-opacity-10 p-3 rounded">
                                        <i class="fas fa-users text-info" style="font-size: 1.5rem;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card animate-fade" style="animation-delay: 0.3s;">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-2">Your Level</h6>
                                        <h3 class="mb-0"><?php echo $role; ?></h3>
                                    </div>
                                    <div class="bg-warning bg-opacity-10 p-3 rounded">
                                        <i class="fas fa-trophy text-warning" style="font-size: 1.5rem;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Activity -->
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card animate-fade" style="animation-delay: 0.5s;">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <h5 class="card-title mb-0">Recent Activity</h5>
                                    <a href="#" class="btn btn-sm btn-outline-primary">View All</a>
                                </div>
                                <div class="list-group list-group-flush">
                                    <a href="#" class="list-group-item list-group-item-action border-0 py-3">
                                        <div class="d-flex align-items-center">
                                            <div class="bg-primary bg-opacity-10 p-2 rounded me-3">
                                                <i class="fas fa-file-alt text-primary"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1">Created new note</h6>
                                                <small class="text-muted">10 minutes ago</small>
                                            </div>
                                            <div class="badge bg-primary bg-opacity-10 text-primary">Study</div>
                                        </div>
                                    </a>
                                    <a href="#" class="list-group-item list-group-item-action border-0 py-3">
                                        <div class="d-flex align-items-center">
                                            <div class="bg-info bg-opacity-10 p-2 rounded me-3">
                                                <i class="fas fa-share-alt text-info"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1">Shared note with team</h6>
                                                <small class="text-muted">2 hours ago</small>
                                            </div>
                                            <div class="badge bg-info bg-opacity-10 text-info">Work</div>
                                        </div>
                                    </a>
                                    <a href="#" class="list-group-item list-group-item-action border-0 py-3">
                                        <div class="d-flex align-items-center">
                                            <div class="bg-success bg-opacity-10 p-2 rounded me-3">
                                                <i class="fas fa-tag text-success"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1">Added tags to notes</h6>
                                                <small class="text-muted">Yesterday</small>
                                            </div>
                                            <div class="badge bg-success bg-opacity-10 text-success">Organization</div>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <div class="card animate-fade" style="animation-delay: 0.6s;">
                            <div class="card-body">
                                <h5 class="card-title mb-4">Quick Tips</h5>
                                <div class="mb-3">
                                    <div class="d-flex mb-3">
                                        <div class="me-3">
                                            <div class="bg-primary bg-opacity-10 text-primary p-2 rounded-circle">
                                                <i class="fas fa-lightbulb"></i>
                                            </div>
                                        </div>
                                        <div>
                                            <h6 class="mb-1">Use Markdown</h6>
                                            <p class="small text-muted mb-0">Format your notes with simple markdown syntax</p>
                                        </div>
                                    </div>
                                    <div class="d-flex mb-3">
                                        <div class="me-3">
                                            <div class="bg-info bg-opacity-10 text-info p-2 rounded-circle">
                                                <i class="fas fa-lightbulb"></i>
                                            </div>
                                        </div>
                                        <div>
                                            <h6 class="mb-1">Keyboard Shortcuts</h6>
                                            <p class="small text-muted mb-0">Press Ctrl+K to quickly search your notes</p>
                                        </div>
                                    </div>
                                    <div class="d-flex">
                                        <div class="me-3">
                                            <div class="bg-success bg-opacity-10 text-success p-2 rounded-circle">
                                                <i class="fas fa-lightbulb"></i>
                                            </div>
                                        </div>
                                        <div>
                                            <h6 class="mb-1">Version History</h6>
                                            <p class="small text-muted mb-0">Right-click any note to view its version history</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Floating Action Button -->
    <div class="fab" id="quickNoteBtn" data-bs-toggle="tooltip" data-bs-placement="left" title="Create new note">
        <i class="fas fa-plus"></i>
    </div>

    <!-- Quick Note Modal -->
    <div class="modal fade" id="quickNoteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content glass-effect" style="border: 1px solid var(--border-color);">
                <div class="modal-header">
                    <h5 class="modal-title">Create New Note</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <input type="text" class="form-control mb-3" placeholder="Note Title">
                        <textarea class="form-control" rows="5" placeholder="Write your note here..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tags</label>
                        <input class="tags-input form-control" name="tags" placeholder="Add tags (press enter to add)">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary">Save Note</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Notifications Dropdown -->
    <div class="dropdown-menu dropdown-menu-end p-2 glass-effect" id="notificationsDropdown" style="width: 320px; border: 1px solid var(--border-color);">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="mb-0">Notifications</h6>
            <small><a href="#" class="text-primary">Mark all as read</a></small>
        </div>
        <div class="list-group">
            <a href="#" class="list-group-item list-group-item-action border-0 py-2">
                <div class="d-flex align-items-center">
                    <div class="bg-primary bg-opacity-10 p-2 rounded me-3">
                        <i class="fas fa-bell text-primary"></i>
                    </div>
                    <div>
                        <h6 class="mb-0">New feature available</h6>
                        <small class="text-muted">10 minutes ago</small>
                    </div>
                </div>
            </a>
            <a href="#" class="list-group-item list-group-item-action border-0 py-2">
                <div class="d-flex align-items-center">
                    <div class="bg-info bg-opacity-10 p-2 rounded me-3">
                        <i class="fas fa-share-alt text-info"></i>
                    </div>
                    <div>
                        <h6 class="mb-0">Your note was shared</h6>
                        <small class="text-muted">2 hours ago</small>
                    </div>
                </div>
            </a>
            <a href="#" class="list-group-item list-group-item-action border-0 py-2">
                <div class="d-flex align-items-center">
                    <div class="bg-success bg-opacity-10 p-2 rounded me-3">
                        <i class="fas fa-trophy text-success"></i>
                    </div>
                    <div>
                        <h6 class="mb-0">New achievement unlocked</h6>
                        <small class="text-muted">1 day ago</small>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <!-- Notification Toast (Hidden by default) -->
    <div class="notification-toast glass-effect" id="notificationToast" style="border: 1px solid var(--border-color);">
        <div class="d-flex align-items-center">
            <i class="fas fa-bell me-3 gradient-text"></i>
            <div>
                <h6 class="mb-0">Welcome to NoteSphere!</h6>
                <small class="text-muted">You have 3 unread notifications</small>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://unpkg.com/@yaireo/tagify"></script>
    <script src="https://unpkg.com/@yaireo/tagify/dist/tagify.polyfills.min.js"></script>
    <script src="https://unpkg.com/shepherd.js@8.3.1/dist/js/shepherd.min.js"></script>
    <script>
        // Initialize AOS animation
        AOS.init({ 
            duration: 800, 
            once: true,
            easing: 'ease-out-quart'
        });

        // Initialize Tagify
        if (document.querySelector('.tags-input')) {
            new Tagify(document.querySelector('.tags-input'));
        }

        // Initialize tooltips
        $(function () {
            $('[data-bs-toggle="tooltip"]').tooltip();
        });

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

        // Mobile menu toggle
        document.getElementById('mobileMenuToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('active');
        });

        // Quick note modal
        document.getElementById('quickNoteBtn').addEventListener('click', function() {
            var quickNoteModal = new bootstrap.Modal(document.getElementById('quickNoteModal'));
            quickNoteModal.show();
        });

        // Notifications dropdown
        document.getElementById('notificationsBtn').addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('notificationsDropdown').classList.toggle('show');
        });

        // Close notifications dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('#notificationsBtn') && !e.target.closest('#notificationsDropdown')) {
                document.getElementById('notificationsDropdown').classList.remove('show');
            }
        });

        // Show welcome notification
        setTimeout(function() {
            const toast = document.getElementById('notificationToast');
            toast.classList.add('show');
            
            setTimeout(function() {
                toast.classList.remove('show');
            }, 5000);
        }, 1000);

        // Load content dynamically when sidebar links are clicked
        $('.load-content').click(function(e) {
            e.preventDefault();
            
            // Update active state
            $('.nav-link').removeClass('active');
            $(this).addClass('active');
            
            const section = $(this).data('section');
            if (section) {
                // Show loading skeleton
                $('#mainContent').html(`
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="skeleton" style="height: 40px; width: 60%; margin-bottom: 20px;"></div>
                                        <div class="skeleton" style="height: 20px; width: 100%; margin-bottom: 10px;"></div>
                                        <div class="skeleton" style="height: 20px; width: 80%; margin-bottom: 10px;"></div>
                                        <div class="skeleton" style="height: 20px; width: 90%; margin-bottom: 30px;"></div>
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="skeleton" style="height: 100px; width: 100%;"></div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="skeleton" style="height: 100px; width: 100%;"></div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="skeleton" style="height: 100px; width: 100%;"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `);
                
                // Load the content
                setTimeout(() => {
                    $.get(section, function(data) {
                        $('#mainContent').html(data);
                        AOS.refresh();
                        
                        // Reinitialize any plugins for the new content
                        if (document.querySelector('.tags-input')) {
                            new Tagify(document.querySelector('.tags-input'));
                        }
                    }).fail(function() {
                        $('#mainContent').html(`
                            <div class="container-fluid">
                                <div class="row">
                                    <div class="col-12">
                                        <div class="alert alert-danger">
                                            <i class="fas fa-exclamation-circle me-2"></i> Failed to load content. Please try again.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `);
                    });
                }, 500);
            }
        });

        // Initialize tour with improved design
        function startTour() {
            const tour = new Shepherd.Tour({
                defaultStepOptions: {
                    classes: 'shepherd-theme-custom',
                    scrollTo: { 
                        behavior: 'smooth', 
                        block: 'center' 
                    },
                    arrow: true,
                    floatingUIOptions: {
                        middleware: [window.FloatingUIDOM.offset(10)]
                    }
                },
                useModalOverlay: true
            });

            // Custom style for the tour
            const style = document.createElement('style');
            style.textContent = `
                .shepherd-theme-custom {
                    border-radius: 12px;
                    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
                    font-family: 'Poppins', sans-serif;
                    max-width: 380px;
                    background: var(--bg-card);
                    border: 1px solid var(--border-color);
                }
                
                .shepherd-theme-custom .shepherd-header {
                    background: transparent;
                    padding: 16px 20px 0;
                    border: none;
                }
                
                .shepherd-theme-custom .shepherd-title {
                    background: linear-gradient(135deg, var(--primary), var(--accent));
                    -webkit-background-clip: text;
                    -webkit-text-fill-color: transparent;
                    font-size: 1.2rem;
                    font-weight: 600;
                    margin-bottom: 8px;
                }
                
                .shepherd-theme-custom .shepherd-text {
                    color: var(--text-secondary);
                    font-size: 0.95rem;
                    padding: 0 20px;
                }
                
                .shepherd-theme-custom .shepherd-footer {
                    padding: 16px 20px;
                    border-top: 1px solid var(--border-color);
                    background: transparent;
                }
                
                .shepherd-theme-custom .shepherd-button {
                    border-radius: 8px;
                    padding: 8px 16px;
                    font-weight: 500;
                    transition: all 0.2s ease;
                }
                
                .shepherd-theme-custom .shepherd-button.shepherd-button-secondary {
                    background: var(--hover-bg);
                    color: var(--text-primary);
                }
                
                .shepherd-theme-custom .shepherd-button:not(.shepherd-button-secondary) {
                    background: linear-gradient(135deg, var(--primary), var(--accent));
                    color: white;
                }
                
                .shepherd-theme-custom .shepherd-button:not(.shepherd-button-secondary):hover {
                    opacity: 0.9;
                }
                
                .shepherd-theme-custom .shepherd-cancel-icon {
                    color: var(--text-secondary);
                }
                
                .shepherd-modal-overlay-container {
                    opacity: 0.7;
                    background: var(--bg-main);
                }
            `;
            document.head.appendChild(style);

            // Add steps with improved content
            tour.addStep({
                title: '🌟 Welcome to NoteSphere!',
                text: 'Get ready to boost your productivity. This quick tour will show you around.',
                buttons: [
                    {
                        text: 'Skip',
                        action: tour.cancel,
                        classes: 'shepherd-button-secondary'
                    },
                    {
                        text: 'Next',
                        action: tour.next
                    }
                ],
                beforeShowPromise: function() {
                    return new Promise(function(resolve) {
                        setTimeout(function() {
                            document.querySelector('.shepherd-element').style.opacity = '0';
                            document.querySelector('.shepherd-element').animate([
                                { opacity: 0, transform: 'translateY(20px)' },
                                { opacity: 1, transform: 'translateY(0)' }
                            ], {
                                duration: 300,
                                easing: 'ease-out'
                            });
                            resolve();
                        }, 50);
                    });
                }
            });

            tour.addStep({
                title: '🚀 Navigation Menu',
                text: 'All features are accessible through this sidebar. Click any item to explore.',
                attachTo: {
                    element: '.sidebar',
                    on: 'right'
                },
                buttons: [
                    {
                        text: 'Back',
                        action: tour.back,
                        classes: 'shepherd-button-secondary'
                    },
                    {
                        text: 'Next',
                        action: tour.next
                    }
                ],
                highlightClass: 'tour-highlight',
                beforeShowPromise: function() {
                    return new Promise(function(resolve) {
                        document.querySelector('.sidebar').classList.add('tour-highlight');
                        resolve();
                    });
                },
                beforeHidePromise: function() {
                    return new Promise(function(resolve) {
                        document.querySelector('.sidebar').classList.remove('tour-highlight');
                        resolve();
                    });
                }
            });

            tour.addStep({
                title: '✏️ Quick Notes',
                text: 'Create notes instantly with this floating button. Try it anytime!',
                attachTo: {
                    element: '#quickNoteBtn',
                    on: 'left'
                },
                buttons: [
                    {
                        text: 'Back',
                        action: tour.back,
                        classes: 'shepherd-button-secondary'
                    },
                    {
                        text: 'Next',
                        action: tour.next
                    }
                ],
                beforeShowPromise: function() {
                    return new Promise(function(resolve) {
                        document.querySelector('#quickNoteBtn').classList.add('pulse-animation');
                        resolve();
                    });
                },
                beforeHidePromise: function() {
                    return new Promise(function(resolve) {
                        document.querySelector('#quickNoteBtn').classList.remove('pulse-animation');
                        resolve();
                    });
                }
            });

            tour.addStep({
                title: '🔔 Notifications',
                text: 'Stay updated with important alerts. Click the bell to see your notifications.',
                attachTo: {
                    element: '#notificationsBtn',
                    on: 'bottom'
                },
                buttons: [
                    {
                        text: 'Back',
                        action: tour.back,
                        classes: 'shepherd-button-secondary'
                    },
                    {
                        text: 'Finish Tour',
                        action: tour.complete
                    }
                ],
                beforeShowPromise: function() {
                    return new Promise(function(resolve) {
                        document.querySelector('#notificationsBtn').classList.add('shake-animation');
                        resolve();
                    });
                },
                beforeHidePromise: function() {
                    return new Promise(function(resolve) {
                        document.querySelector('#notificationsBtn').classList.remove('shake-animation');
                        resolve();
                    });
                }
            });

            // Add animation styles
            const animationStyle = document.createElement('style');
            animationStyle.textContent = `
                @keyframes pulse {
                    0% { transform: scale(1); }
                    50% { transform: scale(1.1); }
                    100% { transform: scale(1); }
                }
                
                @keyframes shake {
                    0%, 100% { transform: rotate(0deg); }
                    25% { transform: rotate(-5deg); }
                    75% { transform: rotate(5deg); }
                }
                
                .pulse-animation {
                    animation: pulse 1.5s infinite;
                }
                
                .shake-animation {
                    animation: shake 0.5s infinite;
                }
                
                .tour-highlight {
                    box-shadow: 0 0 0 4px rgba(34,197,94,0.3);
                    transition: box-shadow 0.3s ease;
                }
            `;
            document.head.appendChild(animationStyle);

            // Start the tour
            setTimeout(() => {
                tour.start();
                
                // Save to localStorage that user has completed the tour
                localStorage.setItem('noteSphereTourCompleted', 'true');
            }, 1500);
        }

        // Only start tour if it hasn't been completed before
        if (!localStorage.getItem('noteSphereTourCompleted')) {
            // Load Shepherd.js CSS if not already loaded
            if (!document.querySelector('link[href*="shepherd.css"]')) {
                const link = document.createElement('link');
                link.rel = 'stylesheet';
                link.href = 'https://cdn.jsdelivr.net/npm/shepherd.js@10.0.1/dist/css/shepherd.css';
                document.head.appendChild(link);
            }
            
            // Load Floating UI if not already loaded
            if (typeof FloatingUIDOM === 'undefined') {
                const script = document.createElement('script');
                script.src = 'https://cdn.jsdelivr.net/npm/@floating-ui/dom@1.2.1';
                script.onload = startTour;
                document.head.appendChild(script);
            } else {
                startTour();
            }
        }
    </script>
</body>
</html>