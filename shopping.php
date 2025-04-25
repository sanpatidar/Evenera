<?php
require_once 'includes/db_connection.php';
require_once 'includes/auth_handler.php';
require_once 'includes/session_handler.php';
require_once 'includes/shopping_handler.php';

// Initialize handlers
$authHandler = new AuthHandler($conn);
$sessionHandler = new CustomSessionHandler($conn);
$shoppingHandler = new ShoppingHandler($conn);

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

if (!$userData) {
  session_destroy();
  header('Location: /F&B1/auth/login.php');
  exit;
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
  header('Content-Type: application/json');
  
  error_log('Received POST request with action: ' . $_POST['action']);
  error_log('POST data: ' . print_r($_POST, true));

  switch ($_POST['action']) {
    case 'create':
      try {
        $result = $shoppingHandler->createShoppingEvent($_SESSION['user_id'], [
          'title' => $_POST['title'],
          'category' => $_POST['category'],
          'date' => $_POST['date'],
          'budget' => $_POST['budget'],
          'location' => $_POST['location'],
          'description' => $_POST['description'],
          'shoppingList' => json_decode($_POST['shoppingList'], true),
          'tasks' => json_decode($_POST['tasks'], true)
        ]);
        error_log('Create result: ' . print_r($result, true));
        echo json_encode($result);
      } catch (Exception $e) {
        error_log('Error in create action: ' . $e->getMessage());
        echo json_encode([
          'success' => false,
          'message' => 'Server error: ' . $e->getMessage()
        ]);
      }
      exit;

    case 'update':
      try {
        $result = $shoppingHandler->updateShoppingEvent($_POST['id'], $_SESSION['user_id'], [
          'title' => $_POST['title'],
          'category' => $_POST['category'],
          'date' => $_POST['date'],
          'budget' => $_POST['budget'],
          'location' => $_POST['location'],
          'description' => $_POST['description'],
          'shoppingList' => json_decode($_POST['shoppingList'], true),
          'tasks' => json_decode($_POST['tasks'], true)
        ]);
        error_log('Update result: ' . print_r($result, true));
        echo json_encode($result);
      } catch (Exception $e) {
        error_log('Error in update action: ' . $e->getMessage());
        echo json_encode([
          'success' => false,
          'message' => 'Server error: ' . $e->getMessage()
        ]);
      }
      exit;

    case 'delete':
      $result = $shoppingHandler->deleteShoppingEvent($_POST['id'], $_SESSION['user_id']);
      echo json_encode($result);
      exit;
  }
}

// Handle AJAX GET requests
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
  header('Content-Type: application/json');
  
  switch ($_GET['action']) {
    case 'upcoming':
      $upcomingEvents = $shoppingHandler->getUpcomingEvents($_SESSION['user_id']);
      echo json_encode($upcomingEvents);
      exit;
  }
}

// Get shopping events with filters
$filters = [
  'search' => $_GET['search'] ?? '',
  'category' => $_GET['category'] ?? '',
  'status' => $_GET['status'] ?? '',
  'sort' => $_GET['sort'] ?? 'date-asc'
];

$shoppingEvents = $shoppingHandler->getShoppingEvents($_SESSION['user_id'], $filters);
?>
<!DOCTYPE html>
<html lang="en" dir="ltr" class="h-full">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Shopping Events - Bharat Event Planner</title>

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

  <!-- GSAP for Animations -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.11.4/gsap.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.11.4/ScrollTrigger.min.js"></script>

  <!-- Page specific script -->
  <script src="js/shopping.js" defer></script>
</head>

<body class="min-h-screen bg-neutral-50 text-neutral-900 font-sans overflow-x-hidden">
  <!-- Animated Background Elements -->
  <div class="fixed inset-0 overflow-hidden pointer-events-none">
    <div class="absolute top-20 left-10 w-40 h-40 bg-primary/10 rounded-full filter blur-xl" data-speed="0.3"></div>
    <div class="absolute bottom-1/4 right-20 w-60 h-60 bg-primary/10 rounded-full filter blur-xl" data-speed="0.5"></div>
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
        <a href="/F&B1/shopping.php" class="flex items-center space-x-3 px-4 py-3 rounded-sm transition-colors bg-primary/10 text-primary">
          <i class="fas fa-store w-6"></i>
          <span>Shopping</span>
        </a>
        <a href="/F&B1/profile.php" class="flex items-center space-x-3 px-4 py-3 rounded-sm transition-colors hover:bg-neutral-100 text-neutral-600 hover:text-primary">
          <i class="fas fa-user w-6"></i>
          <span>Profile</span>
        </a>
      </nav>

      <!-- Upcoming Events Section -->
      <div class="p-4 border-t border-neutral-200">
        <h3 class="font-medium text-neutral-900 mb-3">Upcoming Events</h3>
        <div id="sidebar-upcoming-events" class="space-y-3">
          <!-- Upcoming events will be dynamically inserted here -->
        </div>
        <a href="events.html" class="text-sm text-primary hover:text-primary-hover flex items-center">
          <span>View All Events</span>
          <i class="fas fa-arrow-right ml-1"></i>
        </a>
      </div>
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
          <h1 class="text-2xl font-bold text-primary">Shopping Events</h1>
        </div>
        <div class="flex items-center space-x-4">
          <button id="create-shopping-event-btn" class="bg-primary hover:bg-primary-hover text-white px-4 py-2 rounded-sm flex items-center space-x-2 transition-colors shadow-sm">
            <i class="fas fa-plus"></i>
            <span>Create Shopping Event</span>
          </button>
          <!-- Profile Button -->
          <div class="relative group">
            <button class="flex items-center space-x-2 px-4 py-2 rounded-sm hover:bg-neutral-100 transition-colors">
              <div class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center">
                <i class="fas fa-user text-primary"></i>
              </div>
              <span class="text-neutral-700"><?php echo htmlspecialchars($userData['name']); ?></span>
              <i class="fas fa-chevron-down text-sm text-neutral-500"></i>
            </button>
            <!-- Dropdown Menu -->
            <div class="absolute right-0 mt-2 w-48 bg-white border border-neutral-200 rounded-sm shadow-lg py-2 hidden group-hover:block">
              <div class="px-4 py-2 border-b border-neutral-200">
                <p class="font-medium text-neutral-900"><?php echo htmlspecialchars($userData['name']); ?></p>
                <p class="text-sm text-neutral-500"><?php echo htmlspecialchars($userData['email']); ?></p>
              </div>
              <a href="/F&B1/auth/logout.php" class="block px-4 py-2 hover:bg-primary hover:text-white transition-colors text-neutral-700">
                <i class="fas fa-sign-out-alt mr-2"></i>Logout
              </a>
            </div>
          </div>
        </div>
      </div>
    </header>

    <!-- Page Content -->
    <div class="p-6">
      <!-- Filters -->
      <div class="mb-6 flex flex-wrap gap-4">
        <div class="flex-1 min-w-[200px]">
          <input type="text" id="search-events" placeholder="Search shopping events..." value="<?php echo htmlspecialchars($filters['search']); ?>" class="w-full bg-white border border-neutral-200 rounded-sm px-4 py-2 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent shadow-sm">
        </div>
        <select id="category-filter" class="bg-white border border-neutral-200 rounded-sm px-4 py-2 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent shadow-sm">
          <option value="">All Categories</option>
          <option value="festival" <?php echo $filters['category'] === 'festival' ? 'selected' : ''; ?>>Festival</option>
          <option value="wedding" <?php echo $filters['category'] === 'wedding' ? 'selected' : ''; ?>>Wedding</option>
          <option value="party" <?php echo $filters['category'] === 'party' ? 'selected' : ''; ?>>Party</option>
          <option value="trip" <?php echo $filters['category'] === 'trip' ? 'selected' : ''; ?>>Trip</option>
        </select>
        <select id="status-filter" class="bg-white border border-neutral-200 rounded-sm px-4 py-2 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent shadow-sm">
          <option value="">All Statuses</option>
          <option value="upcoming" <?php echo $filters['status'] === 'upcoming' ? 'selected' : ''; ?>>Upcoming</option>
          <option value="completed" <?php echo $filters['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
        </select>
      </div>

      <!-- Shopping Events Grid -->
      <div id="shopping-events-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($shoppingEvents as $event): ?>
          <div class="shopping-event-card bg-white border border-neutral-200 rounded-sm shadow-sm transition-colors duration-200">
            <div class="p-6">
              <div class="flex justify-between items-start mb-4">
                <div>
                  <h3 class="text-xl font-semibold mb-1 text-neutral-900"><?php echo htmlspecialchars($event['title']); ?></h3>
                  <p class="text-neutral-500">Category: <?php echo htmlspecialchars($event['category']); ?></p>
                </div>
                <span class="px-3 py-1 rounded-full text-sm <?php echo $event['status'] === 'completed' ? 'bg-secondary/10 text-secondary' : 'bg-primary/10 text-primary'; ?>">
                  <?php echo ucfirst($event['status']); ?>
                </span>
              </div>
              <div class="space-y-4">
                <p class="text-neutral-600 line-clamp-2"><?php echo htmlspecialchars($event['description']); ?></p>
                <div class="flex items-center justify-between text-sm">
                  <div class="flex items-center text-neutral-600">
                    <i class="fas fa-shopping-cart w-5 text-primary"></i>
                    <span class="ml-2"><?php echo isset($event['shoppingList']) ? count($event['shoppingList']) : 0; ?> Items</span>
                  </div>
                  <div class="flex items-center text-neutral-600">
                    <i class="fas fa-tasks w-5 text-accent"></i>
                    <span class="ml-2">
                      <?php
                      $taskProgress = 0;
                      if (isset($event['tasks']) && !empty($event['tasks'])) {
                        $completedTasks = count(array_filter($event['tasks'], function ($task) {
                          return isset($task['completed']) && $task['completed'];
                        }));
                        $taskProgress = round(($completedTasks / count($event['tasks'])) * 100);
                      }
                      echo $taskProgress . '% Tasks Done';
                      ?>
                    </span>
                  </div>
                  <div class="flex items-center text-neutral-600">
                    <i class="fas fa-wallet w-5 text-secondary"></i>
                    <span class="ml-2">₹<?php echo number_format($event['budget']); ?></span>
                  </div>
                </div>
              </div>
              <div class="mt-4 pt-4 border-t border-neutral-200 flex justify-between space-x-2">
                <button class="edit-event-btn bg-neutral-600 hover:bg-neutral-700 text-white px-4 py-2 rounded-sm flex items-center justify-center space-x-2 transition-colors w-full" data-event-id="<?php echo $event['id']; ?>">
                  <i class="fas fa-edit"></i>
                  <span>Edit</span>
                </button>
                <button class="delete-event-btn bg-error-500 hover:bg-error-600 text-white px-4 py-2 rounded-sm flex items-center justify-center space-x-2 transition-colors w-full" data-event-id="<?php echo $event['id']; ?>">
                  <i class="fas fa-trash"></i>
                  <span>Delete</span>
                </button>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Shopping Event Modal -->
      <div id="shopping-event-modal" class="fixed inset-0 bg-neutral-900/50 backdrop-blur-sm hidden">
        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full max-w-lg bg-white rounded-lg shadow-xl p-6 max-h-[80vh] overflow-y-auto">
          <div class="flex justify-between items-center mb-4">
            <h2 id="modal-title" class="text-xl font-semibold text-neutral-900">Create Shopping Event</h2>
            <button id="close-modal" class="text-neutral-500 hover:text-neutral-700">
              <i class="fas fa-times"></i>
            </button>
          </div>

          <form id="shopping-event-form" class="space-y-4">
            <input type="hidden" id="event-id">
            <div>
              <label class="block text-sm font-medium mb-1 text-neutral-700">Event Title</label>
              <input type="text" id="event-title" placeholder="Enter event title" class="w-full px-3 py-2 border border-neutral-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent" required>
            </div>
            <div>
              <label class="block text-sm font-medium mb-1 text-neutral-700">Category</label>
              <select id="event-category" class="w-full px-3 py-2 border border-neutral-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent" required>
                <option value="">Select category</option>
                <option value="festival">Festival</option>
                <option value="wedding">Wedding</option>
                <option value="party">Party</option>
                <option value="trip">Trip</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium mb-1 text-neutral-700">Date</label>
              <input type="date" id="event-date" class="w-full px-3 py-2 border border-neutral-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent" required>
            </div>
            <div>
              <label class="block text-sm font-medium mb-1 text-neutral-700">Total Budget (₹)</label>
              <input type="number" id="event-budget" placeholder="Enter total budget" class="w-full px-3 py-2 border border-neutral-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent" required>
            </div>
            <div>
              <label class="block text-sm font-medium mb-1 text-neutral-700">Location</label>
              <input type="text" id="event-location" placeholder="Enter location" class="w-full px-3 py-2 border border-neutral-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
            </div>
            <div>
              <label class="block text-sm font-medium mb-1 text-neutral-700">Description</label>
              <textarea id="event-description" rows="3" placeholder="Enter event description" class="w-full px-3 py-2 border border-neutral-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"></textarea>
            </div>

            <!-- Shopping List Section -->
            <div class="border-t border-neutral-200 pt-4">
              <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-neutral-900">Shopping List</h3>
                <button type="button" id="add-item-modal-btn" class="text-primary hover:text-primary-hover flex items-center space-x-2">
                  <i class="fas fa-plus"></i>
                  <span>Add Item</span>
                </button>
              </div>
              <div id="modal-shopping-list" class="space-y-3"></div>
            </div>

            <!-- Task Management Section -->
            <div class="border-t border-neutral-200 pt-4">
              <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-neutral-900">Tasks</h3>
                <button type="button" id="add-task-modal-btn" class="text-primary hover:text-primary-hover flex items-center space-x-2">
                  <i class="fas fa-plus"></i>
                  <span>Add Task</span>
                </button>
              </div>
              <div id="modal-task-list" class="space-y-3"></div>
            </div>

            <div class="flex justify-end space-x-4">
              <button type="button" id="cancel-event" class="px-4 py-2 rounded-sm border border-neutral-200 hover:bg-neutral-100 transition-colors text-neutral-700">
                Cancel
              </button>
              <button type="submit" class="px-4 py-2 rounded-sm bg-primary hover:bg-primary-hover text-white transition-colors shadow-sm">
                Save Event
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </main>

  <!-- Item Template -->
  <template id="item-template">
    <div class="item-row bg-white border border-neutral-200 rounded-sm p-4">
      <div class="flex items-center space-x-4">
        <input type="checkbox" class="item-purchased w-4 h-4 accent-primary">
        <input type="text" placeholder="Item name (e.g., Decorative Lights)" class="item-name flex-1 bg-transparent border-0 focus:outline-none focus:ring-0 text-sm" required>
        <button type="button" class="toggle-details text-neutral-500 hover:text-primary">
          <i class="fas fa-chevron-down"></i>
        </button>
        <button type="button" class="delete-item text-error-600 hover:text-error-700">
          <i class="fas fa-trash"></i>
        </button>
      </div>

      <!-- Item Details (Collapsible) -->
      <div class="item-details mt-3 pl-9 space-y-3 hidden">
        <div class="flex items-center space-x-4">
          <div class="flex-1">
            <label class="block text-sm font-medium mb-1 text-neutral-700">Category</label>
            <select class="item-category w-full bg-white border border-neutral-200 rounded-sm px-3 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
              <option value="clothing">Clothing</option>
              <option value="electronics">Electronics</option>
              <option value="groceries">Groceries</option>
              <option value="decorations">Decorations</option>
              <option value="other">Other</option>
            </select>
          </div>
          <div class="flex-1">
            <label class="block text-sm font-medium mb-1 text-neutral-700">Priority</label>
            <select class="item-priority w-full bg-white border border-neutral-200 rounded-sm px-3 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
              <option value="high">High</option>
              <option value="medium">Medium</option>
              <option value="low">Low</option>
            </select>
          </div>
        </div>
        <div class="flex items-center space-x-4">
          <div class="flex-1">
            <label class="block text-sm font-medium mb-1 text-neutral-700">Quantity</label>
            <input type="number" placeholder="Quantity" class="item-quantity w-full bg-white border border-neutral-200 rounded-sm px-3 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent" required>
          </div>
          <div class="flex-1">
            <label class="block text-sm font-medium mb-1 text-neutral-700">Budget (₹)</label>
            <input type="number" placeholder="Budget" class="item-budget w-full bg-white border border-neutral-200 rounded-sm px-3 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent" required>
          </div>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1 text-neutral-700">Vendor</label>
          <select class="item-vendor w-full bg-white border border-neutral-200 rounded-sm px-3 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
            <option value="">Select Vendor</option>
            <option value="flipkart">Flipkart</option>
            <option value="amazon">Amazon</option>
            <option value="local">Local Store</option>
            <option value="myntra">Myntra</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1 text-neutral-700">Estimated Delivery Date</label>
          <input type="date" class="item-delivery-date w-full bg-white border border-neutral-200 rounded-sm px-3 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
        </div>
        <div>
          <label class="block text-sm font-medium mb-1 text-neutral-700">Notes</label>
          <textarea placeholder="Add notes..." rows="2" class="item-notes w-full bg-neutral-50 border border-neutral-200 rounded-sm px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"></textarea>
        </div>
      </div>
    </div>
  </template>

  <!-- Task Template -->
  <template id="task-template">
    <div class="task-item bg-white border border-neutral-200 rounded-sm p-4">
      <div class="flex items-center space-x-4">
        <input type="checkbox" class="task-checkbox w-4 h-4 accent-primary">
        <input type="text" placeholder="Task (e.g., Compare prices)" class="task-title flex-1 bg-transparent border-0 focus:outline-none focus:ring-0 text-sm" required>
        <button type="button" class="toggle-details text-neutral-500 hover:text-primary">
          <i class="fas fa-chevron-down"></i>
        </button>
        <button type="button" class="delete-task text-error-600 hover:text-error-700">
          <i class="fas fa-trash"></i>
        </button>
      </div>

      <!-- Task Details (Collapsible) -->
      <div class="task-details mt-3 pl-9 space-y-3 hidden">
        <div class="flex items-center space-x-4">
          <div class="flex-1">
            <label class="block text-sm font-medium mb-1 text-neutral-700">Priority</label>
            <select class="task-priority w-full bg-white border border-neutral-200 rounded-sm px-3 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
              <option value="high">High</option>
              <option value="medium">Medium</option>
              <option value="low">Low</option>
            </select>
          </div>
          <div class="flex-1">
            <label class="block text-sm font-medium mb-1 text-neutral-700">Due Date</label>
            <input type="date" class="task-due-date w-full bg-white border border-neutral-200 rounded-sm px-3 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent" required>
          </div>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1 text-neutral-700">Assigned To</label>
          <select class="task-assigned w-full bg-white border border-neutral-200 rounded-sm px-3 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
            <option value="">Unassigned</option>
            <option value="user1">Priya Sharma</option>
            <option value="user2">Rahul Patel</option>
            <option value="user3">Ananya Reddy</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1 text-neutral-700">Notes</label>
          <textarea placeholder="Add notes..." rows="2" class="task-notes w-full bg-neutral-50 border border-neutral-200 rounded-sm px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"></textarea>
        </div>
      </div>
    </div>
  </template>
</body>

</html>