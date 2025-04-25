<?php
require_once 'includes/db_connection.php';
require_once 'includes/auth_handler.php';
require_once 'includes/session_handler.php';

// Initialize handlers
$authHandler = new AuthHandler($conn);
$sessionHandler = new CustomSessionHandler($conn);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
  header('Location: /F&B1/auth/login.php');
  exit;
}

// Get user data
$userData = null;
try {
  $userData = $authHandler->getUserById($_SESSION['user_id']);
} catch (Exception $e) {
  error_log('Error fetching user data: ' . $e->getMessage());
}

// Handle profile picture upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_picture'])) {
  $uploadDir = 'uploads/profile_pictures/';
  
  // Create directory if it doesn't exist
  if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
  }
  
  $fileExtension = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
  $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
  
  if (in_array($fileExtension, $allowedExtensions)) {
    $newFileName = uniqid() . '.' . $fileExtension;
    $uploadPath = $uploadDir . $newFileName;
    
    if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $uploadPath)) {
      // Update database with new profile picture path
      try {
        $stmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
        $stmt->execute([$uploadPath, $_SESSION['user_id']]);
        
        // Update user data
        $userData['profile_picture'] = $uploadPath;
        
        // Show success message
        $successMessage = "Profile picture updated successfully!";
      } catch (PDOException $e) {
        error_log('Error updating profile picture: ' . $e->getMessage());
        $errorMessage = "Error updating profile picture. Please try again.";
      }
    } else {
      $errorMessage = "Error uploading file. Please try again.";
    }
  } else {
    $errorMessage = "Invalid file type. Please upload a JPG, JPEG, PNG, or GIF file.";
  }
}

if (!$userData) {
  session_destroy();
  header('Location: /F&B1/auth/login.php');
  exit;
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr" class="h-full">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Profile - Bharat Event Planner</title>

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
            },
            error: {
              100: '#FEE2E2',
              500: '#EF4444',
              600: '#DC2626',
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
</head>

<body class="min-h-screen bg-neutral-50 text-neutral-900 font-sans overflow-x-hidden">
  <!-- Animated Background Elements -->
  <div class="fixed inset-0 overflow-hidden pointer-events-none">
    <div class="absolute top-20 left-10 w-40 h-40 bg-primary/10 rounded-full filter blur-xl" data-speed="0.3"></div>
    <div class="absolute bottom-1/4 right-20 w-60 h-60 bg-primary/10 rounded-full filter blur-xl" data-speed="0.5">
    </div>
  </div>

  <!-- Sidebar -->
  <aside class="fixed inset-y-0 left-0 w-64 bg-white border-r border-neutral-200 transform -translate-x-full md:translate-x-0 transition-transform duration-300 ease-in-out z-50 shadow-sm" id="sidebar">
    <div class="flex flex-col h-full">
      <!-- Logo -->
      <div class="p-4 border-b border-neutral-200">
        <div class="flex items-center space-x-2">
          <img src="https://images.pexels.com/photos/1391487/pexels-photo-1391487.jpeg?auto=compress&cs=tinysrgb&w=200" alt="Bharat Event Planner logo" class="h-10 rounded-lg">
          <span class="text-2xl font-bold text-primary">Evenera</span>
        </div>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 p-4 space-y-2">
        <a href="/F&B1/dashboard.php" class="flex items-center space-x-3 px-4 py-3 rounded-sm transition-colors hover:bg-neutral-100 text-neutral-600 hover:text-primary">
          <i class="fas fa-home w-6"></i>
          <span>Dashboard</span>
        </a>
        <a href="/F&B1/tasks.php" class="flex items-center space-x-3 px-4 py-3 rounded-sm transition-colors hover:bg-neutral-100 text-neutral-600 hover:text-primary">
          <i class="fas fa-tasks w-6"></i>
          <span>Tasks</span>
        </a>
        <a href="/F&B1/events.php" class="flex items-center space-x-3 px-4 py-3 rounded-sm transition-colors hover:bg-neutral-100 text-neutral-600 hover:text-primary">
          <i class="fas fa-calendar-alt w-6"></i>
          <span>Events</span>
        </a>
        <a href="/F&B1/template.html" class="flex items-center space-x-3 px-4 py-3 rounded-sm transition-colors hover:bg-neutral-100 text-neutral-600 hover:text-primary">
          <i class="fas fa-file-alt w-6"></i>
          <span>Templates</span>
        </a>
        <a href="/F&B1/shopping.php" class="flex items-center space-x-3 px-4 py-3 rounded-sm transition-colors hover:bg-neutral-100 text-neutral-600 hover:text-primary">
          <i class="fas fa-store w-6"></i>
          <span>Shopping</span>
        </a>
        <a href="/F&B1/profile.php" class="flex items-center space-x-3 px-4 py-3 rounded-sm transition-colors bg-primary/10 text-primary">
          <i class="fas fa-user w-6"></i>
          <span>Profile</span>
        </a>
      </nav>
    </div>
  </aside>

  <!-- Main Content -->
  <main class="md:ml-64 min-h-screen">
    <!-- Top Navigation -->
    <header class="sticky top-0 z-40 bg-white border-b border-neutral-200 shadow-sm">
      <div class="flex items-center justify-between px-6 py-4">
        <div class="flex items-center space-x-4">
          <button id="mobile-menu-button" class="md:hidden text-neutral-600 hover:text-primary">
            <i class="fas fa-bars text-xl"></i>
          </button>
          <h1 class="text-2xl font-bold text-primary">Profile</h1>
        </div>
        <div class="flex items-center space-x-4">
          <!-- Profile Button -->
          <div class="relative group">
            <button class="flex items-center space-x-2 px-4 py-2 rounded-sm hover:bg-neutral-100 transition-colors">
              <div class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center">
                <i class="fas fa-user text-primary"></i>
              </div>
              <span><?php echo htmlspecialchars($userData['name']); ?></span>
              <i class="fas fa-chevron-down text-sm text-neutral-500"></i>
            </button>
            <!-- Dropdown Menu -->
            <div class="absolute right-0 mt-2 w-48 bg-white border border-neutral-200 rounded-sm shadow-lg py-2 hidden">
              <div class="px-4 py-2 border-b border-neutral-200">
                <p class="font-medium text-neutral-900"><?php echo htmlspecialchars($userData['name']); ?></p>
                <p class="text-sm text-neutral-500"><?php echo htmlspecialchars($userData['email']); ?></p>
              </div>
              <a href="/F&B1/profile.php" class="block px-4 py-2 hover:bg-neutral-100 transition-colors text-neutral-700">
                <i class="fas fa-user-circle mr-2"></i>Profile
              </a>
              <a href="#" id="logout-btn" class="block px-4 py-2 hover:bg-primary hover:text-white transition-colors text-neutral-700">
                <i class="fas fa-sign-out-alt mr-2"></i>Logout
              </a>
            </div>
          </div>
        </div>
      </div>
    </header>

    <!-- Page Content -->
    <div class="p-6">
      <!-- Profile Information -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Left Column - Personal Information -->
        <div class="md:col-span-2 space-y-6">
          <!-- Personal Information Card -->
          <div class="bg-white border border-neutral-200 rounded-sm shadow-sm">
            <div class="p-6 border-b border-neutral-200">
              <h2 class="text-xl font-semibold text-neutral-900">Personal Information</h2>
            </div>
            <div class="p-6">
              <form id="profile-form" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div>
                    <label for="name" class="block text-sm font-medium text-neutral-700">Full Name</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($userData['name']); ?>" class="mt-1 block w-full rounded-sm border-neutral-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm">
                  </div>
                  <div>
                    <label for="email" class="block text-sm font-medium text-neutral-700">Email Address</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($userData['email']); ?>" class="mt-1 block w-full rounded-sm border-neutral-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" readonly>
                  </div>
                  <div>
                    <label for="phone" class="block text-sm font-medium text-neutral-700">Phone Number</label>
                    <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($userData['phone'] ?? ''); ?>" class="mt-1 block w-full rounded-sm border-neutral-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm">
                  </div>
                  <div>
                    <label for="location" class="block text-sm font-medium text-neutral-700">Location</label>
                    <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($userData['location'] ?? ''); ?>" class="mt-1 block w-full rounded-sm border-neutral-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm">
                  </div>
                </div>
                <div>
                  <label for="bio" class="block text-sm font-medium text-neutral-700">Bio</label>
                  <textarea id="bio" name="bio" rows="4" class="mt-1 block w-full rounded-sm border-neutral-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm"><?php echo htmlspecialchars($userData['bio'] ?? ''); ?></textarea>
                </div>
                <div class="flex justify-end">
                  <button type="submit" class="bg-primary hover:bg-primary-hover text-white px-4 py-2 rounded-sm shadow-sm">
                    Save Changes
                  </button>
                </div>
              </form>
            </div>
          </div>

          <!-- Password Change Card -->
          <div class="bg-white border border-neutral-200 rounded-sm shadow-sm">
            <div class="p-6 border-b border-neutral-200">
              <h2 class="text-xl font-semibold text-neutral-900">Change Password</h2>
            </div>
            <div class="p-6">
              <form id="password-form" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div>
                    <label for="current-password" class="block text-sm font-medium text-neutral-700">Current Password</label>
                    <input type="password" id="current-password" name="current_password" class="mt-1 block w-full rounded-sm border-neutral-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm">
                  </div>
                  <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                      <label for="new-password" class="block text-sm font-medium text-neutral-700">New Password</label>
                      <input type="password" id="new-password" name="new_password" class="mt-1 block w-full rounded-sm border-neutral-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm">
                    </div>
                    <div>
                      <label for="confirm-password" class="block text-sm font-medium text-neutral-700">Confirm New Password</label>
                      <input type="password" id="confirm-password" name="confirm_password" class="mt-1 block w-full rounded-sm border-neutral-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm">
                    </div>
                  </div>
                </div>
                <div class="flex justify-end">
                  <button type="submit" class="bg-primary hover:bg-primary-hover text-white px-4 py-2 rounded-sm shadow-sm">
                    Update Password
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>

        <!-- Right Column - Additional Settings -->
        <div class="space-y-6">
          <!-- Profile Picture Card -->
          <div class="bg-white border border-neutral-200 rounded-sm shadow-sm">
            <div class="p-6 border-b border-neutral-200">
              <h2 class="text-xl font-semibold text-neutral-900">Profile Picture</h2>
            </div>
            <div class="p-6">
              <div class="flex flex-col items-center space-y-4">
                <div class="w-32 h-32 rounded-full bg-primary/10 flex items-center justify-center overflow-hidden">
                  <?php if (isset($userData['profile_picture']) && file_exists($userData['profile_picture'])): ?>
                    <img src="<?php echo htmlspecialchars($userData['profile_picture']); ?>" alt="Profile picture" class="w-full h-full object-cover">
                  <?php else: ?>
                    <i class="fas fa-user text-4xl text-primary"></i>
                  <?php endif; ?>
                </div>
                <form method="POST" enctype="multipart/form-data" class="space-y-4">
                  <div class="flex flex-col items-center space-y-2">
                    <input type="file" name="profile_picture" accept="image/*" class="hidden" id="profile-picture-input">
                    <label for="profile-picture-input" class="bg-primary hover:bg-primary-hover text-white px-4 py-2 rounded-sm shadow-sm cursor-pointer">
                      Change Picture
                    </label>
                    <p class="text-sm text-neutral-500">JPG, JPEG, PNG or GIF (Max. 5MB)</p>
                  </div>
                  <?php if (isset($successMessage)): ?>
                    <p class="text-sm text-green-600"><?php echo htmlspecialchars($successMessage); ?></p>
                  <?php endif; ?>
                  <?php if (isset($errorMessage)): ?>
                    <p class="text-sm text-red-600"><?php echo htmlspecialchars($errorMessage); ?></p>
                  <?php endif; ?>
                </form>
              </div>
            </div>
          </div>

          <!-- Notification Settings Card -->
          <div class="bg-white border border-neutral-200 rounded-sm shadow-sm">
            <div class="p-6 border-b border-neutral-200">
              <h2 class="text-xl font-semibold text-neutral-900">Notification Settings</h2>
            </div>
            <div class="p-6">
              <form id="notification-form" class="space-y-4">
                <div class="flex items-center justify-between">
                  <span class="text-sm text-neutral-700">Email Notifications</span>
                  <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" class="sr-only peer" checked>
                    <div class="w-11 h-6 bg-neutral-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-neutral-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                  </label>
                </div>
                <div class="flex items-center justify-between">
                  <span class="text-sm text-neutral-700">SMS Notifications</span>
                  <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" class="sr-only peer">
                    <div class="w-11 h-6 bg-neutral-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-neutral-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                  </label>
                </div>
                <div class="flex items-center justify-between">
                  <span class="text-sm text-neutral-700">Push Notifications</span>
                  <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" class="sr-only peer" checked>
                    <div class="w-11 h-6 bg-neutral-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-neutral-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                  </label>
                </div>
              </form>
            </div>
          </div>

          <!-- Account Settings Card -->
          <div class="bg-white border border-neutral-200 rounded-sm shadow-sm">
            <div class="p-6 border-b border-neutral-200">
              <h2 class="text-xl font-semibold text-neutral-900">Account Settings</h2>
            </div>
            <div class="p-6 space-y-4">
              <button type="button" class="w-full text-left px-4 py-2 text-sm text-neutral-700 hover:bg-neutral-100 rounded-sm transition-colors">
                <i class="fas fa-download mr-2"></i>
                Download My Data
              </button>
              <button type="button" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 rounded-sm transition-colors">
                <i class="fas fa-trash-alt mr-2"></i>
                Delete Account
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>

  <!-- Loading Overlay -->
  <div id="loading-overlay" class="fixed inset-0 bg-neutral-900/95 flex items-center justify-center z-50 hidden">
    <div class="text-center">
      <div class="w-16 h-16 border-4 border-primary border-t-transparent rounded-full animate-spin-slow mx-auto mb-4">
      </div>
      <p class="text-white text-lg">Loading...</p>
    </div>
  </div>

  <script>
    // Mobile menu toggle
    document.getElementById('mobile-menu-button').addEventListener('click', function() {
      const sidebar = document.getElementById('sidebar');
      sidebar.classList.toggle('-translate-x-full');
    });

    // Profile dropdown toggle
    const profileButton = document.querySelector('.group button');
    const profileDropdown = document.querySelector('.group .absolute');
    profileButton.addEventListener('click', function() {
      profileDropdown.classList.toggle('hidden');
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
      if (!event.target.closest('.group')) {
        profileDropdown.classList.add('hidden');
      }
    });

    // Logout functionality
    document.getElementById('logout-btn').addEventListener('click', async function(e) {
      e.preventDefault();
      document.getElementById('loading-overlay').style.display = 'flex';

      try {
        const response = await fetch('/F&B1/api/auth.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            action: 'logout'
          })
        });

        const data = await response.json();
        if (data.success) {
          window.location.href = '/F&B1/auth/login.php';
        }
      } catch (error) {
        console.error('Logout error:', error);
      } finally {
        document.getElementById('loading-overlay').style.display = 'none';
      }
    });

    // Handle profile form submission
    document.getElementById('profile-form').addEventListener('submit', async function(e) {
      e.preventDefault();
      document.getElementById('loading-overlay').style.display = 'flex';

      try {
        const formData = {
          action: 'update_profile',
          name: document.getElementById('name').value,
          phone: document.getElementById('phone').value,
          location: document.getElementById('location').value,
          bio: document.getElementById('bio').value
        };

        const response = await fetch('/F&B1/api/profile.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(formData)
        });

        const data = await response.json();

        if (data.success) {
          // Update the profile dropdown with new name
          document.querySelector('.group button span').textContent = data.data.name;
          document.querySelector('.group .absolute p.font-medium').textContent = data.data.name;

          // Show success message
          showMessage('Profile updated successfully', 'success');
        } else {
          showMessage(data.message, 'error');
        }
      } catch (error) {
        console.error('Profile update error:', error);
        showMessage('An error occurred while updating profile', 'error');
      } finally {
        document.getElementById('loading-overlay').style.display = 'none';
      }
    });

    // Handle password form submission
    document.getElementById('password-form').addEventListener('submit', async function(e) {
      e.preventDefault();
      document.getElementById('loading-overlay').style.display = 'flex';

      try {
        const currentPassword = document.getElementById('current-password').value;
        const newPassword = document.getElementById('new-password').value;
        const confirmPassword = document.getElementById('confirm-password').value;

        if (newPassword !== confirmPassword) {
          showMessage('New passwords do not match', 'error');
          return;
        }

        const response = await fetch('/F&B1/api/profile.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            action: 'update_password',
            current_password: currentPassword,
            new_password: newPassword,
            confirm_password: confirmPassword
          })
        });

        const data = await response.json();

        if (data.success) {
          // Clear password fields
          document.getElementById('password-form').reset();
          showMessage('Password updated successfully', 'success');
        } else {
          showMessage(data.message, 'error');
        }
      } catch (error) {
        console.error('Password update error:', error);
        showMessage('An error occurred while updating password', 'error');
      } finally {
        document.getElementById('loading-overlay').style.display = 'none';
      }
    });

    // Handle notification settings changes
    document.getElementById('notification-form').addEventListener('change', async function(e) {
      document.getElementById('loading-overlay').style.display = 'flex';

      try {
        const formData = {
          action: 'update_notifications',
          email_notifications: this.querySelector('input[type="checkbox"]:nth-of-type(1)').checked,
          sms_notifications: this.querySelector('input[type="checkbox"]:nth-of-type(2)').checked,
          push_notifications: this.querySelector('input[type="checkbox"]:nth-of-type(3)').checked
        };

        const response = await fetch('/F&B1/api/profile.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(formData)
        });

        const data = await response.json();

        if (data.success) {
          showMessage('Notification preferences updated', 'success');
        } else {
          showMessage(data.message, 'error');
        }
      } catch (error) {
        console.error('Notification settings update error:', error);
        showMessage('An error occurred while updating notification settings', 'error');
      } finally {
        document.getElementById('loading-overlay').style.display = 'none';
      }
    });

    // Add this to your existing JavaScript
    document.getElementById('profile-picture-input').addEventListener('change', function() {
      if (this.files.length > 0) {
        this.form.submit();
      }
    });

    // Helper function to show messages
    function showMessage(message, type = 'success') {
      const messageDiv = document.createElement('div');
      messageDiv.className = `fixed top-4 right-4 p-4 rounded-sm shadow-lg ${
        type === 'success' ? 'bg-green-50 text-green-800 border border-green-200' :
        'bg-red-50 text-red-800 border border-red-200'
      }`;
      messageDiv.textContent = message;

      document.body.appendChild(messageDiv);

      // Remove the message after 3 seconds
      setTimeout(() => {
        messageDiv.remove();
      }, 3000);
    }
  </script>
</body>

</html>