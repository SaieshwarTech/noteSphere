<?php
session_start();

// Handle theme preferences (example: light theme)
if (!isset($_SESSION['theme'])) {
    $_SESSION['theme'] = 'light'; // Default to light theme
}

// Handle logout request
if (isset($_POST['confirm_logout'])) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}

// Toggle "Remember Me" logic (simplified example)
$remember_user = isset($_SESSION['remember_me']) && $_SESSION['remember_me'] === true;
?>

<!DOCTYPE html>
<html lang="en" data-theme="<?php echo $_SESSION['theme']; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logout</title>
    <!-- Modern font (Poppins) -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            /* Light Theme (default) */
            --bg-color: #F5F5F5;
            --text-color: #333333;
            --primary-color: #48A860;
            --accent-color: #7EA8FF;
            --card-bg: rgba(255, 255, 255, 0.85);
        }

        [data-theme='dark'] {
            /* Dark Theme (optional) */
            --bg-color: #222;
            --text-color: #EEE;
            --card-bg: rgba(40, 40, 40, 0.85);
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            transition: all 0.3s ease;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .logout-card {
            background: var(--card-bg);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 30px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .btn {
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(72, 168, 96, 0.3);
        }

        .btn-secondary {
            background: transparent;
            color: var(--text-color);
            border: 1px solid var(--text-color);
        }
    </style>
</head>
<body>
    <div class="logout-card">
        <h2>Are you sure you want to log out?</h2>
        
        <form method="POST" action="logout.php">
            <!-- "Remember Me" Option (if applicable) -->
            <div style="margin: 20px 0;">
                <label>
                    <input type="checkbox" name="remember_me" <?php echo $remember_user ? 'checked' : ''; ?>>
                    Keep me signed in for future visits
                </label>
            </div>

            <!-- Action Buttons -->
            <button type="submit" name="confirm_logout" class="btn">Yes, Log Out</button>
            <a href="dashboard.php" class="btn btn-secondary" style="margin-left: 10px;">Cancel</a>
        </form>
    </div>
</body>
</html>