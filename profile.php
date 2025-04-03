<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$errors = [];

// Fetch User Data including profile completion status
$stmt = $conn->prepare("SELECT username, email, bio, phone, address, profile_pic FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// If profile is already complete, redirect to panel
if (!empty($user['bio']) && !empty($user['address'])) {
    header("Location: panel.php");
    exit();
}

// Default values
$username = $user['username'] ?? '';
$email = $user['email'] ?? '';
$bio = $user['bio'] ?? '';
$phone = $user['phone'] ?? '';
$address = $user['address'] ?? '';
$profile_pic = $user['profile_pic'] ?? 'default_profile.png';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate form data
    $errors = [];
    
    // Profile picture validation
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = $_FILES['profile_pic']['type'];
        if (!in_array($file_type, $allowed_types)) {
            $errors['profile_pic'] = 'Only JPG, PNG, and GIF images are allowed';
        }
    }
    
    // Address validation
    if (empty($_POST['address'])) {
        $errors['address'] = 'Address is required';
    }
    
    // Bio validation
    if (empty($_POST['bio'])) {
        $errors['bio'] = 'Bio is required';
    } elseif (strlen($_POST['bio']) > 500) {
        $errors['bio'] = 'Bio must be less than 500 characters';
    }
    
    // If no errors, process the form
    if (empty($errors)) {
        try {
            $conn->beginTransaction();
            
            // Handle file upload
            $profile_pic = $user['profile_pic'] ?? 'default_profile.png';
            if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = 'uploads/profiles/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $file_ext = pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION);
                $filename = 'profile_' . $user_id . '_' . time() . '.' . $file_ext;
                $destination = $upload_dir . $filename;
                
                if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $destination)) {
                    // Delete old profile picture if it exists and isn't the default
                    if (!empty($user['profile_pic']) && $user['profile_pic'] !== 'default_profile.png') {
                        @unlink($upload_dir . $user['profile_pic']);
                    }
                    $profile_pic = $filename;
                }
            }
            
            // Update user data
            $stmt = $conn->prepare("UPDATE users SET bio = ?, phone = ?, address = ?, profile_pic = ? WHERE id = ?");
            $stmt->execute([
                $_POST['bio'],
                $_POST['phone'] ?? null,
                $_POST['address'],
                $profile_pic,
                $user_id
            ]);
            
            $conn->commit();
            
            // Set success message and redirect
            $_SESSION['success_message'] = "Profile updated successfully!";
            header("Location: panel.php");
            exit();
        } catch (PDOException $e) {
            $conn->rollBack();
            $errors['database'] = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!-- ... [rest of your HTML remains the same] ... -->

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Profile | NoteSphere</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#f0fdf4',
                            100: '#dcfce7',
                            500: '#22c55e',
                            600: '#16a34a',
                        },
                        secondary: {
                            500: '#3b82f6',
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'ui-sans-serif'],
                        display: ['Poppins', 'ui-sans-serif'],
                    },
                    animation: {
                        'float': 'float 3s ease-in-out infinite',
                    },
                    keyframes: {
                        float: {
                            '0%, 100%': { transform: 'translateY(0)' },
                            '50%': { transform: 'translateY(-5px)' },
                        }
                    }
                }
            }
        }
    </script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <style type="text/tailwindcss">
        @layer utilities {
            .glass-card {
                @apply bg-white/80 backdrop-blur-lg border border-white/20;
            }
            .text-gradient {
                @apply bg-gradient-to-r from-primary-500 to-secondary-500 bg-clip-text text-transparent;
            }
            .shadow-glow {
                box-shadow: 0 0 20px rgba(34, 197, 94, 0.2);
            }
        }
    </style>
</head>

<body class="font-sans bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen flex items-center justify-center p-4">
    <?php if(isset($_SESSION['success_message'])): ?>
        <div class="fixed top-4 right-4 z-50">
            <div class="bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg flex items-center animate-[slideDown_0.5s_ease]">
                <i class="fas fa-check-circle mr-2"></i>
                <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="w-full max-w-2xl">
        <!-- Progress Steps -->
        <div class="flex justify-center mb-8" data-aos="fade-down">
            <div class="flex items-center">
                <?php for($i = 1; $i <= 3; $i++): ?>
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center transition-all
                            <?= $i == 1 ? 'bg-primary-500 text-white' : 'bg-gray-200 text-gray-600' ?>">
                            <?= $i ?>
                        </div>
                        <?php if($i < 3): ?>
                            <div class="w-16 h-1 mx-1 bg-gray-200"></div>
                        <?php endif; ?>
                    </div>
                <?php endfor; ?>
            </div>
        </div>

        <!-- Profile Card -->
        <form method="POST" enctype="multipart/form-data" class="glass-card rounded-2xl p-8 shadow-lg" data-aos="zoom-in">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-800 font-display">Complete Your Profile</h1>
                <p class="text-gray-600 mt-2">Help us personalize your NoteSphere experience</p>
            </div>

            <!-- Step 1: Profile Picture -->
            <div class="step active" data-step="1">
                <div class="text-center">
                    <input type="file" name="profile_pic" id="profileUpload" hidden accept="image/*">
                    <label for="profileUpload" class="cursor-pointer inline-block">
                        <div class="w-32 h-32 rounded-full mx-auto mb-4 relative group">
                            <div class="absolute inset-0 rounded-full border-4 border-primary-500/30 transition-all duration-300 group-hover:border-primary-500/60"></div>
                            <img id="previewImage" class="w-full h-full rounded-full object-cover shadow-md"
                                 src="<?= !empty($profile_pic) ? 'uploads/profiles/' . $profile_pic : 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNCAyNCI+PHBhdGggZD0iTTEyIDJDNi40OCAyIDIgNi40OCAyIDEyczQuNDggMTAgMTAgMTAgMTAtNC40OCAxMC0xMFMxNy41yIDIgMTIgMnptMCAzYzEuNjYgMCAzIDEuMzQgMyAzcy0xLjM0IDMtMyAzLTMtMS4zNC0zLTMgMS4zNC0zIDMtM3ptMCAxNC4yYy0yLjUgMC00LjcxLTEuMjgtNi0zLjIyLjAzLTEuOTkgNC0zLjA4IDYtMy4wOCAxLjk5IDAgNS45NyAxLjA5IDYgMy4wOC0xLjI5IDEuOTQtMy41IDMuMjItNiAzLjIyeiIvPjwvc3ZnPg==' ?>">
                            <div class="absolute inset-0 bg-black/20 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                <i class="fas fa-camera text-white text-2xl"></i>
                            </div>
                        </div>
                        <button type="button" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-gray-700 transition-colors">
                            <i class="fas fa-camera mr-2"></i><?= !empty($profile_pic) ? 'Change Photo' : 'Upload Photo' ?>
                        </button>
                        <?php if(isset($errors['profile_pic'])): ?>
                            <p class="text-red-500 text-sm mt-2"><?= $errors['profile_pic'] ?></p>
                        <?php endif; ?>
                    </label>
                </div>
            </div>

            <!-- Step 2: Contact Info -->
            <div class="step hidden" data-step="2">
                <div class="space-y-4 max-w-md mx-auto">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                        <input type="tel" name="phone" value="<?= htmlspecialchars($phone) ?>" 
                               placeholder="+1 (123) 456-7890"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent transition">
                        <?php if(isset($errors['phone'])): ?>
                            <p class="text-red-500 text-sm mt-1"><?= $errors['phone'] ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                        <input type="text" name="address" value="<?= htmlspecialchars($address) ?>" 
                               placeholder="123 Main St, City, Country" required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent transition">
                        <?php if(isset($errors['address'])): ?>
                            <p class="text-red-500 text-sm mt-1"><?= $errors['address'] ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Step 3: Bio -->
            <div class="step hidden" data-step="3">
                <div class="max-w-md mx-auto">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">About You</label>
                        <textarea name="bio" required
                                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent h-32 transition"
                                  placeholder="Tell us about yourself..."><?= htmlspecialchars($bio) ?></textarea>
                        <p class="text-xs text-gray-500 mt-1">Max 500 characters</p>
                        <?php if(isset($errors['bio'])): ?>
                            <p class="text-red-500 text-sm mt-1"><?= $errors['bio'] ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Preview -->
                    <div class="mt-6 bg-gray-50/50 p-4 rounded-lg border border-gray-200">
                        <h3 class="font-medium text-gray-800 mb-3">Profile Preview</h3>
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 rounded-full overflow-hidden border-2 border-primary-500/30">
                                <img id="finalPreviewImage" class="w-full h-full object-cover" 
                                     src="<?= !empty($profile_pic) ? 'uploads/profiles/' . $profile_pic : 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNCAyNCI+PHBhdGggZD0iTTEyIDJDNi40OCAyIDIgNi40OCAyIDEyczQuNDggMTAgMTAgMTAgMTAtNC40OCAxMC0xMFMxNy41yIDIgMTIgMnptMCAzYzEuNjYgMCAzIDEuMzQgMyAzcy0xLjM0IDMtMyAzLTMtMS4zNC0zLTMgMS4zNC0zIDMtM3ptMCAxNC4yYy0yLjUgMC00LjcxLTEuMjgtNi0zLjIyLjAzLTEuOTkgNC0zLjA4IDYtMy4wOCAxLjk5IDAgNS45NyAxLjA5IDYgMy4wOC0xLjI5IDEuOTQtMy41IDMuMjItNiAzLjIyeiIvPjwvc3ZnPg==' ?>">
                            </div>
                            <div>
                                <p class="font-semibold text-gray-800"><?= htmlspecialchars($username) ?></p>
                                <p class="text-sm text-gray-600"><?= htmlspecialchars($email) ?></p>
                                <p class="text-xs text-gray-500 mt-1"><?= !empty($phone) ? htmlspecialchars($phone) : 'Phone not provided' ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <div class="flex justify-between mt-8">
                <button type="button" id="prevBtn" class="px-6 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-gray-700 transition-colors hidden">
                    <i class="fas fa-arrow-left mr-2"></i> Back
                </button>
                <button type="button" id="nextBtn" class="px-6 py-2 bg-primary-500 hover:bg-primary-600 text-white rounded-lg transition-colors ml-auto">
                    Continue <i class="fas fa-arrow-right ml-2"></i>
                </button>
                <button type="submit" id="submitBtn" class="px-6 py-2 bg-primary-500 hover:bg-primary-600 text-white rounded-lg transition-colors hidden">
                    <i class="fas fa-check-circle mr-2"></i> Complete Profile
                </button>
            </div>
        </form>
    </div>

    <!-- Scripts -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({ duration: 800 });
        
        let currentStep = 1;
        const totalSteps = 3;

        // Initialize with user's current profile pic
        const currentProfilePic = "<?= !empty($profile_pic) ? 'uploads/profiles/' . $profile_pic : '' ?>";
        if (currentProfilePic) {
            document.getElementById('finalPreviewImage').src = currentProfilePic;
        }

        // Image Upload Preview
        document.getElementById('profileUpload').addEventListener('change', function(e) {
            if (e.target.files && e.target.files[0]) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    document.getElementById('previewImage').src = event.target.result;
                    document.getElementById('finalPreviewImage').src = event.target.result;
                };
                reader.readAsDataURL(e.target.files[0]);
            }
        });

        // Step Navigation
        document.getElementById('nextBtn').addEventListener('click', function() {
            if (currentStep >= totalSteps) return;
            
            // Validate current step
            if (currentStep === 2) {
                const address = document.querySelector('[name="address"]').value;
                if (!address) {
                    alert('Please enter your address');
                    return;
                }
            }
            
            document.querySelector(`[data-step="${currentStep}"]`).classList.add('hidden');
            currentStep++;
            document.querySelector(`[data-step="${currentStep}"]`).classList.remove('hidden');
            updateControls();
            updateProgress();
        });

        document.getElementById('prevBtn').addEventListener('click', function() {
            if (currentStep <= 1) return;
            document.querySelector(`[data-step="${currentStep}"]`).classList.add('hidden');
            currentStep--;
            document.querySelector(`[data-step="${currentStep}"]`).classList.remove('hidden');
            updateControls();
            updateProgress();
        });

        function updateProgress() {
            // Update progress indicators
            document.querySelectorAll('[data-step-indicator]').forEach(indicator => {
                const step = parseInt(indicator.getAttribute('data-step-indicator'));
                if (step < currentStep) {
                    indicator.classList.add('bg-primary-500', 'text-white');
                    indicator.classList.remove('bg-gray-200', 'text-gray-600');
                } else if (step === currentStep) {
                    indicator.classList.add('bg-primary-500', 'text-white');
                } else {
                    indicator.classList.remove('bg-primary-500', 'text-white');
                    indicator.classList.add('bg-gray-200', 'text-gray-600');
                }
            });
        }

        function updateControls() {
            document.getElementById('prevBtn').classList.toggle('hidden', currentStep === 1);
            document.getElementById('nextBtn').classList.toggle('hidden', currentStep === totalSteps);
            document.getElementById('submitBtn').classList.toggle('hidden', currentStep !== totalSteps);
        }

        // Auto-hide success message
        const successToast = document.querySelector('.fixed.top-4');
        if (successToast) {
            setTimeout(() => {
                successToast.remove();
            }, 5000);
        }
    </script>
</body>
</html>