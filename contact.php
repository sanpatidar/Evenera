<?php
require_once 'includes/db_connection.php';
require_once 'includes/cookie_handler.php';
require_once 'includes/auth_handler.php';
require_once 'includes/session_handler.php';

// Initialize handlers
$cookieHandler = new CookieHandler($conn);
$authHandler = new AuthHandler($conn);
$sessionHandler = new CustomSessionHandler($conn);

// Check if user is logged in
$isLoggedIn = false;
$user = null;

if (isset($_SESSION['user_id']) && isset($_SESSION['token'])) {
  $user = $authHandler->validateToken($_SESSION['token']);
  $isLoggedIn = $user !== null;
}

// Check cookie consent
$showCookieBanner = $cookieHandler->checkCookieConsent();

// Predefined admin data with specific photos
$admins = [
    [
        'id' => 1,
        'name' => 'Atishay Sodhiya',
        'email' => 'admin@evenera.com',
        'role' => 'System Administrator',
        'avatar' => 'assets/images/pic1.jpeg'  // Professional male/female in business attire
    ],
    [
        'id' => 2,
        'name' => 'Sanskar Patidar',
        'email' => 'support@evenera.com',
        'role' => 'Customer Support',
        'avatar' => 'assets/images/pic2.png'  // Friendly support representative
    ],
    [
        'id' => 3,
        'name' => 'Nitish Pandey',
        'email' => 'events@evenera.com',
        'role' => 'Event Management',
        'avatar' => 'assets/images/pic3.jpeg'  // Event planner with tablet/clipboard
    ]
];

// Create contact_messages table if it doesn't exist
try {
    $conn->exec("CREATE TABLE IF NOT EXISTS contact_messages (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT,
        admin_id INT NOT NULL,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
} catch (PDOException $e) {
    error_log("Error creating table: " . $e->getMessage());
}

// Handle contact form submission
$contactMessage = '';
$contactError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_submit'])) {
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING);
    
    // Validate inputs
    $errors = [];
    if (empty($name)) {
        $errors[] = "Name is required";
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required";
    }
    if (empty($message)) {
        $errors[] = "Message is required";
    }
    if (strlen($message) < 10) {
        $errors[] = "Message must be at least 10 characters long";
    }

    if (empty($errors)) {
        try {
            $stmt = $conn->prepare("INSERT INTO contact_messages (user_id, name, email, message, created_at) VALUES (?, ?, ?, ?, NOW())");
            $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
            if ($stmt->execute([$userId, $name, $email, $message])) {
                $contactMessage = "Thank you! Your message has been sent successfully.";
                // Clear form
                $_POST = array();
            } else {
                $contactError = "Error sending message. Please try again.";
            }
        } catch (PDOException $e) {
            error_log("Error sending message: " . $e->getMessage());
            $contactError = "An error occurred while sending your message. Please try again later.";
        }
    } else {
        $contactError = implode("<br>", $errors);
    }
}
?>

<!DOCTYPE html>
<html lang="en" dir="ltr" class="h-full">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Contact our team for event planning assistance">
  <title>Evenera - Contact Us</title>

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
  <script src="js/contact.js" defer></script>
  <!-- Cookie Consent Script -->
  <script src="/F&B1/js/cookie-consent.js" defer></script>
</head>

<body class="min-h-screen bg-neutral-50 text-neutral-900 font-sans overflow-x-hidden">
  <?php if ($showCookieBanner): ?>
    <!-- Cookie Consent Banner -->
    <div id="cookie-consent" class="fixed bottom-0 left-0 right-0 bg-white border-t border-neutral-200 p-4 z-50">
      <div class="container mx-auto px-6">
        <div class="flex flex-col md:flex-row items-center justify-between gap-4">
          <div class="flex-1">
            <h3 class="text-lg font-semibold mb-2">Cookie Preferences</h3>
            <p class="text-neutral-600 text-sm">We use cookies to enhance your experience. By continuing to visit this site you agree to our use of cookies.</p>
          </div>
          <div class="flex items-center gap-4">
            <button id="cookie-settings" class="px-4 py-2 text-neutral-600 hover:text-primary transition-colors">
              Settings
            </button>
            <button id="accept-cookies" class="px-4 py-2 bg-primary hover:bg-primary-hover text-white rounded-sm transition-colors">
              Accept All
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Cookie Settings Modal -->
    <div id="cookie-settings-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
      <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 bg-white rounded-lg p-6 w-full max-w-lg">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-xl font-semibold">Cookie Settings</h3>
          <button id="close-settings" class="text-neutral-600 hover:text-primary">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="space-y-4">
          <div class="flex items-center justify-between">
            <div>
              <h4 class="font-medium">Essential Cookies</h4>
              <p class="text-sm text-neutral-600">Required for the website to function properly</p>
            </div>
            <div class="flex items-center">
              <input type="checkbox" id="essential-cookies" checked disabled class="rounded text-primary focus:ring-primary">
            </div>
          </div>
          <div class="flex items-center justify-between">
            <div>
              <h4 class="font-medium">Analytics Cookies</h4>
              <p class="text-sm text-neutral-600">Help us understand how visitors interact with our website</p>
            </div>
            <div class="flex items-center">
              <input type="checkbox" id="analytics-cookies" class="rounded text-primary focus:ring-primary">
            </div>
          </div>
          <div class="flex items-center justify-between">
            <div>
              <h4 class="font-medium">Marketing Cookies</h4>
              <p class="text-sm text-neutral-600">Used to deliver personalized advertisements</p>
            </div>
            <div class="flex items-center">
              <input type="checkbox" id="marketing-cookies" class="rounded text-primary focus:ring-primary">
            </div>
          </div>
        </div>
        <div class="mt-6 flex justify-end">
          <button id="save-settings" class="px-4 py-2 bg-primary hover:bg-primary-hover text-white rounded-sm transition-colors">
            Save Preferences
          </button>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <!-- Notification Toast -->
  <div id="notification" class="fixed bottom-4 right-4 hidden z-50">
    <div class="bg-white border border-neutral-200 rounded-sm shadow-lg p-4 flex items-center space-x-3">
      <i id="notification-icon" class="fas fa-check-circle text-success-600"></i>
      <p id="notification-message" class="text-neutral-900"></p>
    </div>
  </div>

  <!-- Loading Overlay -->
  <div id="loading-overlay" class="fixed inset-0 bg-white bg-opacity-90 z-50 flex items-center justify-center hidden">
    <div class="animate-spin rounded-full h-16 w-16 border-t-4 border-b-4 border-primary-600"></div>
  </div>

  <!-- Navigation -->
  <nav class="container mx-auto px-6 py-4 flex justify-between items-center relative z-10 bg-white border-b border-neutral-200 fixed top-0 left-0 right-0">
    <div class="flex items-center space-x-2">
      <img src="assets/images/logo.png" alt="Evenera logo" class="h-10">
      <span class="text-2xl font-bold text-primary">Evenera</span>
    </div>

    <!-- Mobile Menu Button -->
    <button id="mobile-menu-button" class="md:hidden text-neutral-600 hover:text-primary rounded-sm">
      <i class="fas fa-bars text-2xl"></i>
    </button>

    <!-- Mobile Menu -->
    <div id="mobile-menu" class="fixed inset-0 bg-white z-50 transform translate-x-full transition-transform duration-300 md:hidden">
      <div class="flex justify-end p-6">
        <button id="close-menu" class="text-neutral-600 hover:text-primary rounded-sm">
          <i class="fas fa-times text-2xl"></i>
        </button>
      </div>
      <div class="flex flex-col items-center space-y-8 mt-20">
        <div class="flex flex-col items-center space-y-4">
          <a href="/F&B1/index.php" class="px-6 py-2 bg-primary text-white hover:bg-primary-hover transition-colors rounded-sm">Back to Home</a>
        </div>
      </div>
    </div>

    <div class="hidden md:flex items-center space-x-4">
      <a href="/F&B1/index.php" class="px-6 py-2 bg-primary hover:bg-primary-hover text-white font-medium transition-colors rounded-sm">Back to Home</a>
    </div>
  </nav>

  <!-- Hero Section -->
  <section class="container mx-auto px-6 py-32 text-center contact-hero">
    <h1 class="text-4xl md:text-5xl font-bold leading-tight mb-6">
      Contact Our <span class="text-primary">Event Planning Experts</span>
    </h1>
    <p class="text-xl text-neutral-600 mb-8 max-w-2xl mx-auto">
      Reach out to our team for personalized assistance with your event planning needs.
    </p>
  </section>

  <!-- Admins Section -->
  <section id="admins" class="py-20 bg-neutral-100">
    <div class="container mx-auto px-6">
      <div class="text-center mb-16">
        <h2 class="text-3xl md:text-4xl font-bold mb-4">Meet Our <span class="text-primary">Team</span></h2>
        <p class="text-xl text-neutral-600 max-w-3xl mx-auto">Our dedicated admins are here to help you plan unforgettable events.</p>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <?php foreach ($admins as $admin): ?>
          <div class="admin-card bg-white border border-neutral-200 rounded-lg p-8 will-change-transform" style="transform-style: preserve-3d; backface-visibility: hidden;">
            <img src="<?php echo htmlspecialchars($admin['avatar']); ?>" alt="<?php echo htmlspecialchars($admin['name']); ?>" class="w-24 h-24 rounded-full mx-auto mb-4 border-2 border-primary">
            <h3 class="text-2xl font-bold text-center mb-2"><?php echo htmlspecialchars($admin['name']); ?></h3>
            <p class="text-neutral-600 text-center mb-4"><?php echo htmlspecialchars($admin['role']); ?></p>
            <div class="flex justify-center">
              <a href="mailto:<?php echo htmlspecialchars($admin['email']); ?>" class="text-primary hover:text-primary-hover">
                <i class="fas fa-envelope text-xl"></i>
              </a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- Contact Form Section -->
  <section id="contact-form" class="py-20">
    <div class="container mx-auto px-6">
      <div class="max-w-2xl mx-auto bg-white rounded-lg p-12 border border-neutral-200">
        <h2 class="text-3xl font-bold mb-6 text-center">Send Us a Message</h2>
        
        <?php if ($contactMessage): ?>
            <div class="mb-6 p-4 bg-green-100 text-green-700 rounded-sm">
                <?php echo htmlspecialchars($contactMessage); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($contactError): ?>
            <div class="mb-6 p-4 bg-red-100 text-red-700 rounded-sm">
                <?php echo htmlspecialchars($contactError); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="space-y-6">
            <input type="hidden" name="admin_id" id="admin_id" value="<?php echo isset($_POST['admin_id']) ? htmlspecialchars($_POST['admin_id']) : ''; ?>">
            
            <div>
                <label for="name" class="block text-sm font-medium text-neutral-700">Your Name</label>
                <input type="text" name="name" id="name" 
                       value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>"
                       class="mt-1 block w-full px-4 py-3 border border-neutral-300 rounded-sm focus:ring-primary focus:border-primary" 
                       required>
            </div>
            
            <div>
                <label for="email" class="block text-sm font-medium text-neutral-700">Your Email</label>
                <input type="email" name="email" id="email" 
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                       class="mt-1 block w-full px-4 py-3 border border-neutral-300 rounded-sm focus:ring-primary focus:border-primary" 
                       required>
            </div>
            
            <div>
                <label for="message" class="block text-sm font-medium text-neutral-700">Message</label>
                <textarea name="message" id="message" rows="5" 
                          class="mt-1 block w-full px-4 py-3 border border-neutral-300 rounded-sm focus:ring-primary focus:border-primary" 
                          required><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
            </div>
            
            <div class="text-center">
                <button type="submit" name="contact_submit" 
                        class="px-8 py-4 bg-primary hover:bg-primary-hover text-white font-bold text-lg transition-all rounded-sm">
                    Send Message
                </button>
            </div>
        </form>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer class="bg-neutral-900 text-white py-12">
    <div class="container mx-auto px-6">
      <div class="grid md:grid-cols-4 gap-8">
        <div>
          <div class="flex items-center mb-4">
            <i class="fas fa-robot text-2xl mr-2 text-primary" aria-hidden="true"></i>
            <span class="text-xl font-bold">Evenera</span>
          </div>
          <p class="text-neutral-400 mb-4">Your personal event planning assistant.</p>
          <div class="space-y-2">
            <p class="text-neutral-400 text-sm">
              <span class="font-medium">ATY Designs</span><br>
              Building innovative solutions for event planning
            </p>
            <p class="text-neutral-400 text-sm">
              <i class="fas fa-envelope mr-2"></i><a href="mailto:support@evenera.com">support@evenera.com</a>
            </p>
            <p class="text-neutral-600 text-sm">
              <i class="fas fa-phone mr-2"></i>+91 98765 43210
            </p>
          </div>
        </div>
        <div>
          <h4 class="font-bold text-lg mb-4">Product</h4>
          <ul class="space-y-2">
            <li><a href="#" class="text-neutral-400 hover:text-white transition">Features</a></li>
            <li><a href="#" class="text-neutral-400 hover:text-white transition">Pricing Plans</a></li>
            <li><a href="#" class="text-neutral-400 hover:text-white transition">Templates</a></li>
            <li><a href="#" class="text-neutral-400 hover:text-white transition">Mobile App</a></li>
          </ul>
        </div>
        <div>
          <h4 class="font-bold text-lg mb-4">Resources</h4>
          <ul class="space-y-2">
            <li><a href="#" class="text-neutral-400 hover:text-white transition">Blog</a></li>
            <li><a href="#" class="text-neutral-400 hover:text-white transition">Planning Tips</a></li>
            <li><a href="#" class="text-neutral-400 hover:text-white transition">Cultural Guides</a></li>
            <li><a href="#" class="text-neutral-400 hover:text-white transition">FAQ</a></li>
          </ul>
        </div>
        <div>
          <h4 class="font-bold text-lg mb-4">Connect</h4>
          <div class="flex space-x-4 mb-4">
            <a href="#" class="w-10 h-10 rounded-full bg-neutral-800 flex items-center justify-center hover:bg-primary transition">
              <i class="fab fa-facebook-f"></i>
            </a>
            <a href="#" class="w-10 h-10 rounded-full bg-neutral-800 flex items-center justify-center hover:bg-primary transition">
              <i class="fab fa-twitter"></i>
            </a>
            <a href="#" class="w-10 h-10 rounded-full bg-neutral-800 flex items-center justify-center hover:bg-primary transition">
              <i class="fab fa-instagram"></i>
            </a>
          </div>
          <p class="text-neutral-400 text-sm mb-2">Subscribe to our newsletter</p>
          <div class="mt-2 flex">
            <input type="email" placeholder="Your email" class="px-4 py-2 rounded-l-sm bg-neutral-800 text-white focus:outline-none focus:ring-1 focus:ring-primary w-full">
            <button class="px-4 py-2 bg-primary hover:bg-primary-hover rounded-r-sm">
              <i class="fas fa-paper-plane"></i>
            </button>
          </div>
        </div>
      </div>
      <div class="border-t border-neutral-800 mt-12 pt-8">
        <div class="mt-8 flex flex-col md:flex-row justify-between items-center">
          <p class="text-neutral-400 text-sm mb-4 md:mb-0">© 2025 ATY Designs. All rights reserved.</p>
          <div class="flex space-x-6">
            <a href="/F&B1/privacy.html" class="text-neutral-400 hover:text-white text-sm transition">Privacy Policy</a>
            <a href="/F&B1/terms.html" class="text-neutral-400 hover:text-white text-sm transition">Terms of Service</a>
            <a href="/F&B1/cookie-policy.html" class="text-neutral-400 hover:text-white text-sm transition">Cookie Policy</a>
          </div>
        </div>
      </div>
    </div>
  </footer>
</body>

</html>