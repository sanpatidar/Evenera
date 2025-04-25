<?php
require_once 'includes/db_connection.php';
require_once 'includes/session_handler.php';

$sessionHandler = new CustomSessionHandler($conn);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_login'])) {
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);

    if ($username && $password) {
        try {
            $stmt = $conn->prepare("SELECT ac.id, ac.admin_id, ac.username, ac.password, a.name FROM admin_credentials ac JOIN admins a ON ac.admin_id = a.id WHERE ac.username = ?");
            $stmt->execute([$username]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($admin && password_verify($password, $admin['password'])) {
                $_SESSION['admin_id'] = $admin['admin_id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_name'] = $admin['name'];
                header('Location: admin_dashboard.php');
                exit;
            } else {
                $error = 'Invalid username or password.';
            }
        } catch (PDOException $e) {
            $error = 'Error logging in. Please try again later.';
        }
    } else {
        $error = 'Please fill in all fields.';
    }
}
?>

<!DOCTYPE html>
<html lang="en" dir="ltr" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Admin login for Evenera event planning">
    <title>Evenera - Admin Login</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#2563EB',
                        'primary-hover': '#1D4ED8',
                        secondary: '#059669',
                        'secondary-hover': '#047857',
                        accent: '#F59E0B',
                        'accent-hover': '#D97706',
                        neutral: {
                            50: '#F9FAFB',
                            100: '#F3F4F6',
                            200: '#E5E7EB',
                            300: '#D1D5DB',
                            400: '#9CA3AF',
                            500: '#6B7280',
                            600: '#4B5563',
                            700: '#374151',
                            800: '#1F2937',
                            900: '#111827',
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        hindi: ['Tiro Devanagari Hindi', 'serif']
                    },
                    animation: {
                        'spin-slow': 'spin 3s linear infinite',
                    },
                }
            }
        }
    </script>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Tiro+Devanagari+Hindi&display=swap" rel="stylesheet">

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- GSAP for Animations -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js"></script>

    <!-- Main Script -->
    <script src="js/admin_login.js" defer></script>
</head>

<body class="min-h-screen bg-neutral-50 text-neutral-900 font-sans flex items-center justify-center">
    <!-- Loading Overlay -->
    <div id="loading-overlay" class="fixed inset-0 bg-white bg-opacity-90 z-50 flex items-center justify-center hidden">
        <div class="animate-spin rounded-full h-16 w-16 border-t-4 border-b-4 border-primary-600"></div>
    </div>

    <!-- Login Form -->
    <div class="max-w-md w-full bg-white rounded-lg p-8 border border-neutral-200 shadow-lg">
        <div class="flex items-center justify-center mb-6">
            <img src="assets/images/logo.png" alt="Evenera logo" class="h-12 mr-2">
            <span class="text-2xl font-bold text-primary">Evenera Admin</span>
        </div>
        <h2 class="text-2xl font-bold text-center mb-6">Admin Login</h2>
        <?php if ($error): ?>
            <div class="mb-6 p-4 bg-red-100 text-red-700 rounded-sm">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        <form method="POST" class="space-y-6">
            <div>
                <label for="username" class="block text-sm font-medium text-neutral-700">Username</label>
                <input type="text" name="username" id="username" class="mt-1 block w-full px-4 py-3 border border-neutral-300 rounded-sm focus:ring-primary focus:border-primary" required>
            </div>
            <div>
                <label for="password" class="block text-sm font-medium text-neutral-700">Password</label>
                <input type="password" name="password" id="password" class="mt-1 block w-full px-4 py-3 border border-neutral-300 rounded-sm focus:ring-primary focus:border-primary" required>
            </div>
            <div class="text-center">
                <button type="submit" name="admin_login" class="px-8 py-3 bg-primary hover:bg-primary-hover text-white font-bold text-lg transition-all rounded-sm">
                    Login
                </button>
            </div>
        </form>
        <div class="mt-4 text-center">
            <a href="index.php" class="inline-flex items-center text-primary hover:text-primary-hover transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Home
            </a>
        </div>
    </div>
</body>
</html>