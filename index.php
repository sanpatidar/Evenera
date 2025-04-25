<?php
require_once 'includes/db_connection.php';
require_once 'includes/cookie_handler.php';
require_once 'includes/auth_handler.php';
require_once 'includes/session_handler.php';

$cookieHandler = new CookieHandler($conn);
$authHandler = new AuthHandler($conn);
$sessionHandler = new CustomSessionHandler($conn);

$isLoggedIn = false;
$user = null;

if (isset($_SESSION['user_id']) && isset($_SESSION['token'])) {
  $user = $authHandler->validateToken($_SESSION['token']);
  $isLoggedIn = $user !== null;
}

$showCookieBanner = $cookieHandler->checkCookieConsent();

$cookieSettings = $cookieHandler->getCookieSettings();
?>
<!DOCTYPE html>
<html lang="en" dir="ltr" class="h-full">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Effortless event planning for Indian weddings and celebrations">
  <title>Evenera - Event Planning Checklist</title>

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

  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Tiro+Devanagari+Hindi&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js"></script>

  <script src="js/index.js" defer></script>\
  <script src="/F&B1/js/cookie-consent.js" defer></script>
</head>

<body class="min-h-screen bg-neutral-50 text-neutral-900 font-sans overflow-x-hidden">
  <?php if ($showCookieBanner): ?>
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

  <div id="notification" class="fixed bottom-4 right-4 hidden z-50">
    <div class="bg-white border border-neutral-200 rounded-sm shadow-lg p-4 flex items-center space-x-3">
      <i id="notification-icon" class="fas fa-check-circle text-success-600"></i>
      <p id="notification-message" class="text-neutral-900"></p>
    </div>
  </div>

  <div id="loading-overlay" class="fixed inset-0 bg-white bg-opacity-90 z-50 flex items-center justify-center hidden">
    <div class="animate-spin rounded-full h-16 w-16 border-t-4 border-b-4 border-primary-600"></div>
  </div>

  <div id="cookie-popup-container"></div>

  <div class="fixed inset-0 overflow-hidden pointer-events-none">
    <div class="absolute top-20 left-10 w-40 h-40 bg-primary/10 rounded-full filter blur-xl parallax" data-speed="0.3"></div>
    <div class="absolute bottom-1/4 right-20 w-60 h-60 bg-primary/10 rounded-full filter blur-xl parallax" data-speed="0.5"></div>
  </div>

  <nav class="container mx-auto px-6 py-4 flex justify-between items-center relative z-10 bg-white border-b border-neutral-200 fixed top-0 left-0 right-0">
    <div class="flex items-center space-x-2">
      <img src="assets/images/logo.png" alt="Bharat Event Planner logo" class="h-10">
      <span class="text-2xl font-bold text-primary">Evenera</span>
    </div>

    
    <button id="mobile-menu-button" class="md:hidden text-neutral-600 hover:text-primary rounded-sm">
      <i class="fas fa-bars text-2xl"></i>
    </button>

    <div id="mobile-menu" class="fixed inset-0 bg-white z-50 transform translate-x-full transition-transform duration-300 md:hidden">
      <div class="flex justify-end p-6">
        <button id="close-menu" class="text-neutral-600 hover:text-primary rounded-sm">
          <i class="fas fa-times text-2xl"></i>
        </button>
      </div>
      <div class="flex flex-col items-center space-y-8 mt-20">
        <a href="/F&B1/index.php" class="text-2xl text-neutral-600 hover:text-primary">Home</a>
        <a href="/F&B1/contact.php" class="text-2xl text-neutral-600 hover:text-primary">Contact</a>
        <a href="/F&B1/admin_dashboard.php" class="text-2xl text-neutral-600 hover:text-primary">Admin Dashboard</a>
        <div class="flex flex-col items-center space-y-4">
          <a href="/F&B1/auth/login.php" class="px-6 py-2 border border-primary hover:bg-primary hover:text-white transition-colors rounded-sm">Login</a>
          <a href="/F&B1/auth/register.php" class="px-6 py-2 bg-primary text-white hover:bg-primary-hover transition-colors rounded-sm">Register</a>
        </div>
      </div>
    </div>

    <div class="hidden md:flex items-center space-x-8">
      <a href="/F&B1/" class="text-neutral-600 hover:text-primary transition-colors">Home</a>
      <a href="/F&B1/contact.php" class="text-neutral-600 hover:text-primary transition-colors">Contact</a>
      <a href="/F&B1/admin_dashboard.php" class="text-neutral-600 hover:text-primary transition-colors">Admin Dashboard</a>
    </div>

    <div class="hidden md:flex items-center space-x-4">
      <?php if ($isLoggedIn): ?>
        <div class="relative group">
          <button class="flex items-center space-x-2 text-neutral-600 hover:text-primary">
            <img src="<?php echo (is_array($user) && isset($user['avatar'])) ? $user['avatar'] : 'assets/images/default-avatar.png'; ?>" alt="Profile" class="w-8 h-8 rounded-full">
            <span><?php echo (is_array($user) && isset($user['name'])) ? htmlspecialchars($user['name']) : 'User'; ?></span>
            <i class="fas fa-chevron-down text-sm"></i>
          </button>
          <div class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-2 hidden group-hover:block">
            <a href="/F&B1/profile.php" class="block px-4 py-2 text-neutral-600 hover:bg-neutral-100">Profile</a>
            <a href="/F&B1/settings.php" class="block px-4 py-2 text-neutral-600 hover:bg-neutral-100">Settings</a>
            <a href="/F&B1/auth/logout.php" class="block px-4 py-2 text-red-600 hover:bg-neutral-100">Logout</a>
          </div>
        </div>
      <?php else: ?>
        <a href="/F&B1/auth/login.php" class="px-6 py-2 border border-primary hover:bg-primary hover:text-white transition-colors rounded-sm">Login</a>
        <a href="/F&B1/auth/register.php" class="px-6 py-2 bg-primary hover:bg-primary-hover text-white font-medium transition-colors rounded-sm">Sign Up</a>
      <?php endif; ?>
    </div>
  </nav>

  <section class="container mx-auto px-6 py-32 flex flex-col md:flex-row items-center relative z-10 hero-content">
    <div class="md:w-1/2 mb-12 md:mb-0">
      <h1 class="text-4xl md:text-5xl font-bold leading-tight mb-6">
        Welcome to the <span class="text-primary">New Era</span> of Effortless Event Planning
      </h1>
      <p class="text-xl text-neutral-600 mb-8 max-w-lg">
        From grand weddings to festive celebrations - culturally tailored checklists with regional expertise.
      </p>
      <div class="flex flex-col sm:flex-row space-y-4 sm:space-y-0 sm:space-x-4">
        <a href="/F&B1/dashboard.html" class="px-8 py-4 bg-primary hover:bg-primary-hover text-white font-bold text-lg text-center transition-all rounded-sm">
          Start Planning <i class="fas fa-arrow-right ml-2"></i>
        </a>
        <a href="#features" class="px-8 py-4 bg-white hover:bg-neutral-100 font-medium text-lg text-center border border-neutral-200 transition-all rounded-sm">
          Explore Features
        </a>
      </div>
    </div>
    <div class="md:w-1/2 relative">
      <img src="assets/images/hero.png" alt="Indian wedding with bride and groom under mandap" class="w-full max-w-lg mx-auto rounded-lg shadow-lg border border-neutral-200 transition-transform hover:scale-105">
    </div>
  </section>

  <section id="features" class="py-20 bg-neutral-100 relative overflow-hidden">
    <div class="container mx-auto px-6">
      <div class="text-center mb-16">
        <h2 class="text-3xl md:text-4xl font-bold mb-4">Designed for <span class="text-primary">Indian Celebrations</span></h2>
        <p class="text-xl text-neutral-600 max-w-3xl mx-auto">Every feature crafted keeping traditions in mind</p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-16">
        <div class="feature-card bg-white border border-neutral-200 rounded-lg p-8 will-change-transform" style="transform-style: preserve-3d; backface-visibility: hidden;">
          <div class="w-20 h-20 bg-primary/10 rounded-full flex items-center justify-center mb-6 mx-auto">
            <i class="fas fa-calendar-check text-3xl text-primary"></i>
          </div>
          <h3 class="text-2xl font-bold mb-4 text-center">Wedding Templates</h3>
          <ul class="text-neutral-600 space-y-3">
            <li class="flex items-center"><i class="fas fa-check text-primary mr-2"></i>Hindu Wedding Ceremonies</li>
            <li class="flex items-center"><i class="fas fa-check text-primary mr-2"></i>Muslim Nikah & Walima</li>
            <li class="flex items-center"><i class="fas fa-check text-primary mr-2"></i>Sikh Anand Karaj</li>
            <li class="flex items-center"><i class="fas fa-check text-primary mr-2"></i>Christian Wedding</li>
          </ul>
          <a href="/F&B1/templates.html" class="mt-6 text-primary hover:text-primary-hover flex items-center justify-center group">
            <span>View Templates</span>
            <i class="fas fa-arrow-right ml-2 transform group-hover:translate-x-1 transition-transform"></i>
          </a>
        </div>

        <div class="feature-card bg-white border border-neutral-200 rounded-lg p-8 will-change-transform" style="transform-style: preserve-3d; backface-visibility: hidden;">
          <div class="w-20 h-20 bg-primary/10 rounded-full flex items-center justify-center mb-6 mx-auto">
            <i class="fas fa-star text-3xl text-primary"></i>
          </div>
          <h3 class="text-2xl font-bold mb-4 text-center">Festival Templates</h3>
          <ul class="text-neutral-600 space-y-3">
            <li class="flex items-center"><i class="fas fa-check text-primary mr-2"></i>Diwali Celebration</li>
            <li class="flex items-center"><i class="fas fa-check text-primary mr-2"></i>Holi Festival</li>
            <li class="flex items-center"><i class="fas fa-check text-primary mr-2"></i>Ganesh Chaturthi</li>
            <li class="flex items-center"><i class="fas fa-check text-primary mr-2"></i>Navratri Events</li>
          </ul>
          <a href="/F&B1/templates.html" class="mt-6 text-primary hover:text-primary-hover flex items-center justify-center group">
            <span>Explore Festivals</span>
            <i class="fas fa-arrow-right ml-2 transform group-hover:translate-x-1 transition-transform"></i>
          </a>
        </div>

        <div class="feature-card bg-white border border-neutral-200 rounded-lg p-8 will-change-transform" style="transform-style: preserve-3d; backface-visibility: hidden;">
          <div class="w-20 h-20 bg-primary/10 rounded-full flex items-center justify-center mb-6 mx-auto">
            <i class="fas fa-plane-departure text-3xl text-primary"></i>
          </div>
          <h3 class="text-2xl font-bold mb-4 text-center">Trip Templates</h3>
          <ul class="text-neutral-600 space-y-3">
            <li class="flex items-center"><i class="fas fa-check text-primary mr-2"></i>Hill Station Retreats</li>
            <li class="flex items-center"><i class="fas fa-check text-primary mr-2"></i>Beach Vacations</li>
            <li class="flex items-center"><i class="fas fa-check text-primary mr-2"></i>Cultural Tours</li>
            <li class="flex items-center"><i class="fas fa-check text-primary mr-2"></i>Spiritual Pilgrimages</li>
          </ul>
          <a href="/F&B1/templates.html" class="mt-6 text-primary hover:text-primary-hover flex items-center justify-center group">
            <span>Plan Your Trip</span>
            <i class="fas fa-arrow-right ml-2 transform group-hover:translate-x-1 transition-transform"></i>
          </a>
        </div>
      </div>

      <div class="grid grid-cols-2 md:grid-cols-4 gap-8 mt-16">
        <div class="text-center p-6 bg-white rounded-lg border border-neutral-200 shadow-sm">
          <div class="w-16 h-16 bg-primary/10 rounded-full flex items-center justify-center mb-4 mx-auto">
            <i class="fas fa-tasks text-2xl text-primary"></i>
          </div>
          <h4 class="font-bold text-lg mb-2">Task Management</h4>
          <p class="text-neutral-600">Organized checklists with timeline tracking</p>
        </div>

        <div class="text-center p-6 bg-white rounded-lg border border-neutral-200 shadow-sm">
          <div class="w-16 h-16 bg-primary/10 rounded-full flex items-center justify-center mb-4 mx-auto">
            <i class="fas fa-wallet text-2xl text-primary"></i>
          </div>
          <h4 class="font-bold text-lg mb-2">Budget Tracking</h4>
          <p class="text-neutral-600">Keep expenses in check with smart budgeting</p>
        </div>
        <div class="text-center p-6 bg-white rounded-lg border border-neutral-200 shadow-sm">
          <div class="w-16 h-16 bg-primary/10 rounded-full flex items-center justify-center mb-4 mx-auto">
            <i class="fas fa-users text-2xl text-primary"></i>
          </div>
          <h4 class="font-bold text-lg mb-2">Guest Management</h4>
          <p class="text-neutral-600">RSVP tracking and seating arrangements</p>
        </div>

        <!-- Shopping Lists -->
        <div class="text-center p-6 bg-white rounded-lg border border-neutral-200 shadow-sm">
          <div class="w-16 h-16 bg-primary/10 rounded-full flex items-center justify-center mb-4 mx-auto">
            <i class="fas fa-store text-2xl text-primary"></i>
          </div>
          <h4 class="font-bold text-lg mb-2">Shopping Lists</h4>
          <p class="text-neutral-600">Organized item tracking with categories</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Testimonials Section -->
  <section id="testimonials" class="py-20 relative overflow-hidden bg-white">
    <div class="container mx-auto px-6">
      <div class="text-center mb-16">
        <h2 class="text-3xl md:text-4xl font-bold mb-4">What Our <span class="text-primary">Users Say</span></h2>
        <p class="text-xl text-neutral-600 max-w-3xl mx-auto">Real stories from people who planned their perfect events with us</p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <!-- Testimonial 1 -->
        <div class="testimonial bg-neutral-50 border border-neutral-200 rounded-lg p-8 will-change-transform" style="transform-style: preserve-3d; backface-visibility: hidden;">
          <div class="flex items-center mb-6">
            <img src="https://images.unsplash.com/photo-1519744792095-2f2205e87b6f?ixlib=rb-4.0.3&auto=format&fit=crop&w=100&h=100&q=80" alt="Wedding planner profile" class="w-16 h-16 rounded-full object-cover border-2 border-primary">
            <div class="ml-4">
              <h4 class="font-bold text-lg">Priya Sharma</h4>
              <p class="text-neutral-600">Wedding Planner, Delhi</p>
            </div>
          </div>
          <p class="text-neutral-700 italic mb-4">"The wedding templates are incredibly detailed. They cover all the rituals and customs perfectly. Made planning multiple weddings so much easier!"</p>
          <div class="flex items-center justify-between">
            <div class="text-primary">
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
            </div>
            <span class="text-sm font-medium text-primary">5.0</span>
          </div>
        </div>

        <!-- Testimonial 2 -->
        <div class="testimonial bg-neutral-50 border border-neutral-200 rounded-lg p-8 will-change-transform" style="transform-style: preserve-3d; backface-visibility: hidden;">
          <div class="flex items-center mb-6">
            <img src="assets/images/cart.jpg" alt="Event organizer profile" class="w-16 h-16 rounded-full object-cover border-2 border-primary">
            <div class="ml-4">
              <h4 class="font-bold text-lg">Rahul Patel</h4>
              <p class="text-neutral-600">Event Organizer, Mumbai</p>
            </div>
          </div>
          <p class="text-neutral-700 italic mb-4">"The festival templates are a game-changer. From Diwali to Ganesh Chaturthi, everything is well-organized with proper timelines."</p>
          <div class="flex items-center justify-between">
            <div class="text-primary">
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star-half-alt"></i>
            </div>
            <span class="text-sm font-medium text-primary">4.5</span>
          </div>
        </div>

        <!-- Testimonial 3 -->
        <div class="testimonial bg-neutral-50 border border-neutral-200 rounded-lg p-8 will-change-transform" style="transform-style: preserve-3d; backface-visibility: hidden;">
          <div class="flex items-center mb-6">
            <img src="https://images.pexels.com/photos/1488315/pexels-photo-1488315.jpeg?auto=compress&cs=tinysrgb&w=100&h=100&q=80" alt="Trip planner profile" class="w-16 h-16 rounded-full object-cover border-2 border-primary">
            <div class="ml-4">
              <h4 class="font-bold text-lg">Ananya Reddy</h4>
              <p class="text-neutral-600">Trip Planner, Bangalore</p>
            </div>
          </div>
          <p class="text-neutral-700 italic mb-4">"The trip templates helped us plan perfect vacations for our clients. The attention to detail in each template is impressive!"</p>
          <div class="flex items-center justify-between">
            <div class="text-primary">
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
            </div>
            <span class="text-sm font-medium text-primary">5.0</span>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- CTA Section -->
  <section class="py-20 relative">
    <div class="container mx-auto px-6 text-center">
      <div class="max-w-4xl mx-auto bg-white rounded-lg p-12 border border-neutral-200 relative overflow-hidden cta-content">
        <h2 class="text-3xl md:text-4xl font-bold mb-6">Ready to Plan Your Perfect Event?</h2>
        <p class="text-xl text-neutral-600 mb-8 max-w-2xl mx-auto">Join thousands of Indians who celebrate stress-free with our cultural planning tools.</p>
        <a href="/F&B1/auth/register.php" class="inline-block px-8 py-4 bg-primary hover:bg-primary-hover text-white font-bold text-lg transition-all rounded-sm">
          Get Started - It's Free!
        </a>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer id="about" class="bg-neutral-900 text-white py-12" aria-labelledby="about-heading">
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
              <i class="fas fa-envelope mr-2"></i><a href="mailto:support@bharatevent.com">support@evenera.com</a>
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
            <li><a href="#" class="text-neutral-400 hover:text-white transition">API Documentation</a></li>
            <li><a href="#" class="text-neutral-400 hover:text-white transition">System Requirements</a></li>
          </ul>
        </div>
        <div>
          <h4 class="font-bold text-lg mb-4">Resources</h4>
          <ul class="space-y-2">
            <li><a href="#" class="text-neutral-400 hover:text-white transition">Blog</a></li>
            <li><a href="#" class="text-neutral-400 hover:text-white transition">Planning Tips</a></li>
            <li><a href="#" class="text-neutral-400 hover:text-white transition">Cultural Guides</a></li>
            <li><a href="#" class="text-neutral-400 hover:text-white transition">Vendor Insights</a></li>
            <li><a href="#" class="text-neutral-400 hover:text-white transition">Success Stories</a></li>
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
            <a href="#" class="w-10 h-10 rounded-full bg-neutral-800 flex items-center justify-center hover:bg-primary transition">
              <i class="fab fa-youtube"></i>
            </a>
            <a href="#" class="w-10 h-10 rounded-full bg-neutral-800 flex items-center justify-center hover:bg-primary transition">
              <i class="fab fa-linkedin-in"></i>
            </a>
          </div>
          <p class="text-neutral-400 text-sm mb-2">Subscribe to our newsletter for updates and planning tips</p>
          <div class="mt-2 flex">
            <input type="email" placeholder="Your email" class="px-4 py-2 rounded-l-sm bg-neutral-800 text-white focus:outline-none focus:ring-1 focus:ring-primary w-full">
            <button class="px-4 py-2 bg-primary hover:bg-primary-hover rounded-r-sm">
              <i class="fas fa-paper-plane"></i>
            </button>
          </div>
          <div class="mt-4 space-y-2">
            <p class="text-neutral-400 text-sm">
              <i class="fas fa-map-marker-alt mr-2"></i>123 Tech Park, Bangalore, India
            </p>
            <p class="text-neutral-400 text-sm">
              <i class="fas fa-clock mr-2"></i>Mon-Fri: 9:00 AM - 6:00 PM IST
            </p>
          </div>
        </div>
      </div>
      <div class="border-t border-neutral-800 mt-12 pt-8">
        <div class="grid md:grid-cols-2 gap-8">
          <div>
            <h4 class="font-bold text-lg mb-4">Payment Methods</h4>
            <div class="flex space-x-4">
              <i class="fab fa-cc-visa text-2xl text-neutral-400"></i>
              <i class="fab fa-cc-mastercard text-2xl text-neutral-400"></i>
              <i class="fab fa-cc-paypal text-2xl text-neutral-400"></i>
              <i class="fab fa-cc-amazon-pay text-2xl text-neutral-400"></i>
            </div>
          </div>
          <div>
            <h4 class="font-bold text-lg mb-4">Security & Trust</h4>
            <div class="flex space-x-6">
              <div class="flex items-center text-neutral-400 bg-neutral-800 px-4 py-2 rounded-sm">
                <i class="fas fa-shield-alt text-2xl"></i>
                <span class="ml-2">SSL Secure</span>
              </div>
              <div class="flex items-center text-neutral-400 bg-neutral-800 px-4 py-2 rounded-sm">
                <i class="fas fa-lock text-2xl"></i>
                <span class="ml-2">GDPR Compliant</span>
              </div>
            </div>
          </div>
        </div>
        <div class="mt-8 flex flex-col md:flex-row justify-between items-center">
          <p class="text-neutral-400 text-sm mb-4 md:mb-0">© 2025 ATY Designs. All rights reserved.</p>
          <div class="flex space-x-6">
            <a href="/F&B1/privacy.html" class="text-neutral-400 hover:text-white text-sm transition">Privacy Policy</a>
            <a href="/F&B1/terms.html" class="text-neutral-400 hover:text-white text-sm transition">Terms of Service</a>
            <a href="/F&B1/cookie-policy.html" class="text-neutral-400 hover:text-white text-sm transition">Cookie Policy</a>
            <a href="/F&B1/accessibility.html" class="text-neutral-400 hover:text-white text-sm transition">Accessibility</a>
            <a href="/F&B1/sitemap.html" class="text-neutral-400 hover:text-white text-sm transition">Sitemap</a>
          </div>
        </div>
      </div>
    </div>
  </footer>
</body>

</html>