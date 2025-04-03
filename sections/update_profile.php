<?php
// Enable error reporting (for development)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Secure session configuration
ini_set('session.use_strict_mode', 1);
session_start([
    'cookie_lifetime' => 86400,
    'cookie_secure' => true,
    'cookie_httponly' => true,
    'cookie_samesite' => 'Strict'
]);

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

require_once '../db_connect.php'; // Ensure the correct path

// Now, $conn is already available from db_connect.php
if (!$conn) {
    die("Database connection error.");
}


// Initialize variables
$userId = (int)$_SESSION['user_id'];
$userData = [];
$success = false;
$errors = [];
$allowedImageTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$maxFileSize = 2 * 1024 * 1024; // 2MB

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Fetch user data
try {
    $stmt = $conn->prepare("
        SELECT user_id, fullname, email, bio, interests, profile_image, 
               DATE_FORMAT(created_at, '%M %Y') as member_since 
        FROM users 
        WHERE user_id = :user_id
    ");
    $stmt->bindParam(":user_id", $userId, PDO::PARAM_INT);
    $stmt->execute();
    $userData = $stmt->fetch();
    
    if (!$userData) {
        session_destroy();
        header("Location: /login.php?error=user_not_found");
        exit();
    }
    
    // Process interests
    $userData['interests_array'] = !empty($userData['interests']) ? 
        explode(',', $userData['interests']) : [];
        
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $errors[] = "Failed to load profile data";
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token first
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $errors[] = "Invalid form submission";
        http_response_code(403);
    } else {
        // Sanitize inputs
        $fullname = filter_input(INPUT_POST, 'fullname', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $bio = filter_input(INPUT_POST, 'bio', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $interests = isset($_POST['interests']) ? 
            implode(',', array_map(function($interest) {
                return filter_var($interest, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            }, $_POST['interests'])) : '';

        // Validate inputs
        if (empty($fullname) || strlen($fullname) > 100) {
            $errors[] = "Full name must be between 1-100 characters";
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format";
        } elseif (strlen($email) > 255) {
            $errors[] = "Email too long";
        }

        if (strlen($bio) > 500) {
            $errors[] = "Bio must be under 500 characters";
        }

        // Handle password change
        $passwordChanged = false;
        $newPassword = filter_input(INPUT_POST, 'password', FILTER_DEFAULT);
        
        if (!empty($newPassword)) {
            $currentPassword = filter_input(INPUT_POST, 'currentPassword', FILTER_DEFAULT);
            
            if (empty($currentPassword)) {
                $errors[] = "Current password is required";
            } elseif ($newPassword !== filter_input(INPUT_POST, 'confirmPassword', FILTER_DEFAULT)) {
                $errors[] = "New passwords don't match";
            } elseif (strlen($newPassword) < 8) {
                $errors[] = "Password must be at least 8 characters";
            } else {
                try {
                    $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = :user_id LIMIT 1");
                    $stmt->execute([':user_id' => $userId]);
                    $dbPassword = $stmt->fetchColumn();
                    
                    if (!$dbPassword || !password_verify($currentPassword, $dbPassword)) {
                        $errors[] = "Current password is incorrect";
                    } else {
                        $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
                        $passwordChanged = true;
                    }
                } catch (PDOException $e) {
                    error_log("Password verification error: " . $e->getMessage());
                    $errors[] = "Error verifying password";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Manage your profile settings">
    <title>Profile Settings | <?= htmlspecialchars($userData['fullname'] ?? 'User') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <style>
        .avatar-upload {
            position: relative;
            max-width: 120px;
        }
        .avatar-upload .avatar-preview {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background-size: cover;
            background-position: center;
            border: 3px solid #fff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .avatar-upload .upload-btn {
            position: absolute;
            right: 0;
            bottom: 0;
            background: #4F46E5;
            color: white;
            border-radius: 50%;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        .interest-tag {
            display: inline-flex;
            align-items: center;
            background-color: #EFF6FF;
            color: #1E40AF;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
        }
        .interest-tag.selected {
            background-color: #DBEAFE;
            font-weight: 500;
        }
        .password-meter {
            height: 4px;
            background-color: #E5E7EB;
            margin-top: 0.5rem;
            border-radius: 2px;
            overflow: hidden;
        }
        .password-meter-bar {
            height: 100%;
            width: 0%;
            transition: width 0.3s ease;
        }
    </style>
</head>
<body class="h-full">
    <div class="min-h-full">
        <!-- Navigation -->
        <nav class="bg-white border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <div class="flex-shrink-0 flex items-center">
                            <a href="/dashboard">
                                <img class="h-8 w-auto" src="/images/logo.svg" alt="Your Company">
                            </a>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <div class="ml-4 relative flex-shrink-0">
                            <div class="relative group">
                                <button type="button" class="flex items-center focus:outline-none" id="user-menu-button">
                                    <span class="sr-only">Open user menu</span>
                                    <img class="h-8 w-8 rounded-full" 
                                         src="<?= !empty($userData['profile_image']) ? 
                                             '/uploads/profile_images/' . htmlspecialchars($userData['profile_image']) : 
                                             'https://ui-avatars.com/api/?name=' . urlencode($userData['fullname'] ?? 'User') . '&background=4F46E5&color=fff' ?>" 
                                         alt="<?= htmlspecialchars($userData['fullname'] ?? 'User') ?>">
                                    <span class="ml-2 text-sm font-medium text-gray-700 hidden md:inline">
                                        <?= htmlspecialchars($userData['fullname'] ?? 'User') ?>
                                    </span>
                                </button>
                                <div class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1 bg-white ring-1 ring-black ring-opacity-5 hidden group-focus-within:block group-hover:block" 
                                     role="menu" aria-orientation="vertical" tabindex="-1">
                                    <a href="/profile" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">Your Profile</a>
                                    <a href="/settings" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">Settings</a>
                                    <form method="POST" action="/logout">
                                        <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">Sign out</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main content -->
        <div class="py-10">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="px-4 sm:px-0">
                    <h1 class="text-2xl font-bold text-gray-900">Profile Settings</h1>
                    <p class="mt-1 text-sm text-gray-600">Manage your account information and preferences</p>
                </div>

                <!-- Success/Error Messages -->
                <?php if ($success): ?>
                <div class="mt-6 bg-green-50 border-l-4 border-green-400 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-green-700">Your profile has been updated successfully.</p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                <div class="mt-6 bg-red-50 border-l-4 border-red-400 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-700">There were errors with your submission:</h3>
                            <div class="mt-2 text-sm text-red-700">
                                <ul class="list-disc pl-5 space-y-1">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?= htmlspecialchars($error) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="mt-6 bg-white shadow overflow-hidden sm:rounded-lg">
                    <form method="POST" enctype="multipart/form-data" class="divide-y divide-gray-200">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        
                        <!-- Profile Section -->
                        <div class="px-4 py-5 sm:p-6">
                            <div class="flex flex-col sm:flex-row">
                                <div class="flex-shrink-0 mb-4 sm:mb-0 sm:mr-6">
                                    <div class="avatar-upload">
                                        <div class="avatar-preview" id="avatarPreview" 
                                             style="background-image: url('<?= !empty($userData['profile_image']) ? 
                                                 '/uploads/profile_images/' . htmlspecialchars($userData['profile_image']) : 
                                                 'https://ui-avatars.com/api/?name=' . urlencode($userData['fullname'] ?? 'User') . '&background=4F46E5&color=fff' ?>')">
                                        </div>
                                        <label for="profileImage" class="upload-btn" title="Change photo">
                                            <i class="fas fa-camera text-white"></i>
                                            <input type="file" id="profileImage" name="profileImage" class="sr-only" accept="image/*">
                                        </label>
                                    </div>
                                    <p class="mt-2 text-xs text-gray-500 text-center">Click to change</p>
                                </div>
                                <div class="flex-grow">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900">Profile Information</h3>
                                    <p class="mt-1 text-sm text-gray-500">Update your basic information and how others see you.</p>
                                    
                                    <div class="mt-4 grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                                        <div class="sm:col-span-3">
                                            <label for="fullname" class="block text-sm font-medium text-gray-700">Full name</label>
                                            <input type="text" name="fullname" id="fullname" autocomplete="name" 
                                                   value="<?= htmlspecialchars($userData['fullname'] ?? '') ?>" 
                                                   class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
                                        </div>

                                        <div class="sm:col-span-4">
                                            <label for="email" class="block text-sm font-medium text-gray-700">Email address</label>
                                            <input type="email" name="email" id="email" autocomplete="email" 
                                                   value="<?= htmlspecialchars($userData['email'] ?? '') ?>" 
                                                   class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
                                        </div>

                                        <div class="sm:col-span-6">
                                            <label for="bio" class="block text-sm font-medium text-gray-700">About</label>
                                            <div class="mt-1">
                                                <textarea id="bio" name="bio" rows="3" 
                                                          class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border border-gray-300 rounded-md"><?= htmlspecialchars($userData['bio'] ?? '') ?></textarea>
                                            </div>
                                            <p class="mt-2 text-sm text-gray-500">Brief description for your profile.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Interests Section -->
                        <div class="px-4 py-5 sm:p-6">
                            <div>
                                <h3 class="text-lg leading-6 font-medium text-gray-900">Interests</h3>
                                <p class="mt-1 text-sm text-gray-500">Select topics you're interested in to personalize your experience.</p>
                            </div>
                            <div class="mt-4">
                                <input type="hidden" name="interests[]" value=""> <!-- Empty value for when none are selected -->
                                <div class="flex flex-wrap">
                                    <label class="interest-tag <?= in_array('Math', $userData['interests_array'] ?? []) ? 'selected' : '' ?>">
                                        <input type="checkbox" name="interests[]" value="Math" 
                                               <?= in_array('Math', $userData['interests_array'] ?? []) ? 'checked' : '' ?> 
                                               class="sr-only">
                                        <span>Math</span>
                                    </label>
                                    <label class="interest-tag <?= in_array('Science', $userData['interests_array'] ?? []) ? 'selected' : '' ?>">
                                        <input type="checkbox" name="interests[]" value="Science" 
                                               <?= in_array('Science', $userData['interests_array'] ?? []) ? 'checked' : '' ?> 
                                               class="sr-only">
                                        <span>Science</span>
                                    </label>
                                    <label class="interest-tag <?= in_array('History', $userData['interests_array'] ?? []) ? 'selected' : '' ?>">
                                        <input type="checkbox" name="interests[]" value="History" 
                                               <?= in_array('History', $userData['interests_array'] ?? []) ? 'checked' : '' ?> 
                                               class="sr-only">
                                        <span>History</span>
                                    </label>
                                    <label class="interest-tag <?= in_array('Literature', $userData['interests_array'] ?? []) ? 'selected' : '' ?>">
                                        <input type="checkbox" name="interests[]" value="Literature" 
                                               <?= in_array('Literature', $userData['interests_array'] ?? []) ? 'checked' : '' ?> 
                                               class="sr-only">
                                        <span>Literature</span>
                                    </label>
                                    <label class="interest-tag <?= in_array('Programming', $userData['interests_array'] ?? []) ? 'selected' : '' ?>">
                                        <input type="checkbox" name="interests[]" value="Programming" 
                                               <?= in_array('Programming', $userData['interests_array'] ?? []) ? 'checked' : '' ?> 
                                               class="sr-only">
                                        <span>Programming</span>
                                    </label>
                                    <label class="interest-tag <?= in_array('Art', $userData['interests_array'] ?? []) ? 'selected' : '' ?>">
                                        <input type="checkbox" name="interests[]" value="Art" 
                                               <?= in_array('Art', $userData['interests_array'] ?? []) ? 'checked' : '' ?> 
                                               class="sr-only">
                                        <span>Art</span>
                                    </label>
                                    <label class="interest-tag <?= in_array('Music', $userData['interests_array'] ?? []) ? 'selected' : '' ?>">
                                        <input type="checkbox" name="interests[]" value="Music" 
                                               <?= in_array('Music', $userData['interests_array'] ?? []) ? 'checked' : '' ?> 
                                               class="sr-only">
                                        <span>Music</span>
                                    </label>
                                    <label class="interest-tag <?= in_array('Sports', $userData['interests_array'] ?? []) ? 'selected' : '' ?>">
                                        <input type="checkbox" name="interests[]" value="Sports" 
                                               <?= in_array('Sports', $userData['interests_array'] ?? []) ? 'checked' : '' ?> 
                                               class="sr-only">
                                        <span>Sports</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Password Section -->
                        <div class="px-4 py-5 sm:p-6">
                            <div>
                                <h3 class="text-lg leading-6 font-medium text-gray-900">Change Password</h3>
                                <p class="mt-1 text-sm text-gray-500">Leave blank to keep current password.</p>
                            </div>
                            <div class="mt-6 grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                                <div class="sm:col-span-4">
                                    <label for="currentPassword" class="block text-sm font-medium text-gray-700">Current Password</label>
                                    <div class="mt-1 relative rounded-md shadow-sm">
                                        <input type="password" name="currentPassword" id="currentPassword" 
                                               class="block w-full pr-10 border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md" 
                                               placeholder="••••••••">
                                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                            <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                    </div>
                                </div>

                                <div class="sm:col-span-4">
                                    <label for="password" class="block text-sm font-medium text-gray-700">New Password</label>
                                    <div class="mt-1 relative rounded-md shadow-sm">
                                        <input type="password" name="password" id="password" 
                                               class="block w-full pr-10 border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md" 
                                               placeholder="••••••••">
                                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                            <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="password-meter">
                                        <div class="password-meter-bar" id="passwordStrength"></div>
                                    </div>
                                    <p class="mt-1 text-xs text-gray-500">Password strength: <span id="passwordStrengthText">none</span></p>
                                </div>

                                <div class="sm:col-span-4">
                                    <label for="confirmPassword" class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                                    <div class="mt-1 relative rounded-md shadow-sm">
                                        <input type="password" name="confirmPassword" id="confirmPassword" 
                                               class="block w-full pr-10 border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md" 
                                               placeholder="••••••••">
                                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                            <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                    </div>
                                    <p id="passwordMatchError" class="mt-1 text-xs text-red-600 hidden">Passwords don't match</p>
                                </div>
                            </div>
                        </div>

                        <!-- Account Section -->
                        <div class="px-4 py-5 sm:p-6">
                            <div>
                                <h3 class="text-lg leading-6 font-medium text-gray-900">Account Information</h3>
                                <p class="mt-1 text-sm text-gray-500">Details about your account.</p>
                            </div>
                            <div class="mt-6">
                                <dl class="divide-y divide-gray-200">
                                    <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4">
                                        <dt class="text-sm font-medium text-gray-500">Account ID</dt>
                                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2"><?= htmlspecialchars($userData['user_id']) ?></dd>
                                    </div>
                                    <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4">
                                        <dt class="text-sm font-medium text-gray-500">Member since</dt>
                                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2"><?= htmlspecialchars($userData['member_since'] ?? 'Unknown') ?></dd>
                                    </div>
                                </dl>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="px-4 py-4 bg-gray-50 sm:px-6 flex justify-end">
                            <button type="button" class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Cancel
                            </button>
                            <button type="submit" class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Save changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Profile image preview
        document.getElementById('profileImage').addEventListener('change', function(e) {
            if (e.target.files && e.target.files[0]) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    document.getElementById('avatarPreview').style.backgroundImage = `url('${event.target.result}')`;
                };
                reader.readAsDataURL(e.target.files[0]);
            }
        });

        // Password strength meter
        document.getElementById('password').addEventListener('input', function(e) {
            const password = e.target.value;
            const strengthBar = document.getElementById('passwordStrength');
            const strengthText = document.getElementById('passwordStrengthText');
            
            // Reset
            strengthBar.style.width = '0%';
            strengthBar.style.backgroundColor = '#E5E7EB';
            
            if (!password) {
                strengthText.textContent = 'none';
                return;
            }
            
            // Calculate strength (simple version)
            let strength = 0;
            if (password.length >= 8) strength += 1;
            if (password.match(/[a-z]/) strength += 1;
            if (password.match(/[A-Z]/)) strength += 1;
            if (password.match(/[0-9]/)) strength += 1;
            if (password.match(/[^a-zA-Z0-9]/)) strength += 1;
            
            // Update UI
            const width = (strength / 5) * 100;
            strengthBar.style.width = `${width}%`;
            
            let color, text;
            if (strength <= 1) {
                color = '#EF4444'; // red
                text = 'Weak';
            } else if (strength <= 3) {
                color = '#F59E0B'; // amber
                text = 'Medium';
            } else {
                color = '#10B981'; // green
                text = 'Strong';
            }
            
            strengthBar.style.backgroundColor = color;
            strengthText.textContent = text;
            strengthText.className = strength <= 1 ? 'text-red-600' : strength <= 3 ? 'text-yellow-600' : 'text-green-600';
        });

        // Password match validation
        function validatePasswordMatch() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            const errorElement = document.getElementById('passwordMatchError');
            
            if (password && confirmPassword && password !== confirmPassword) {
                errorElement.classList.remove('hidden');
                return false;
            } else {
                errorElement.classList.add('hidden');
                return true;
            }
        }
        
        document.getElementById('confirmPassword').addEventListener('input', validatePasswordMatch);
        
        // Form submission validation
        document.querySelector('form').addEventListener('submit', function(e) {
            // Check password match if password field is filled
            if (document.getElementById('password').value && !validatePasswordMatch()) {
                e.preventDefault();
                document.getElementById('passwordMatchError').scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
            }
        });

        // Interest tag selection
        document.querySelectorAll('.interest-tag input[type="checkbox"]').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                this.closest('.interest-tag').classList.toggle('selected', this.checked);
            });
        });
    </script>
</body>
</html>