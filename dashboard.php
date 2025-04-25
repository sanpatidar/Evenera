<?php
require_once 'includes/db_connection.php';
require_once 'includes/auth_handler.php';
require_once 'includes/session_handler.php';
require_once 'includes/event_handler.php';
require_once 'includes/shopping_handler.php';
require_once 'includes/task_handler.php';

// Initialize handlers
$authHandler = new AuthHandler($conn);
$sessionHandler = new CustomSessionHandler($conn);
$eventHandler = new EventHandler($conn);
$shoppingHandler = new ShoppingHandler($conn);
$taskHandler = new TaskHandler($conn);

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
  $response = [];

  switch ($_POST['action']) {
    case 'get_dashboard_data':
      try {
        // Get upcoming events (next 30 days)
        $upcomingEvents = $eventHandler->getUpcomingEvents($_SESSION['user_id'], 30);

        // Get upcoming shopping events
        $upcomingShoppingEvents = $shoppingHandler->getUpcomingEvents($_SESSION['user_id'], 30);

        // Get pending tasks
        $stmt = $conn->prepare("
          SELECT t.*, e.title as event_title 
          FROM tasks t 
          LEFT JOIN events e ON t.event_id = e.id 
          WHERE t.user_id = ? AND t.status = 'pending'
          ORDER BY t.deadline ASC
          LIMIT 5
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $pendingTasks = $stmt->fetchAll();

        // Get event statistics
        $stmt = $conn->prepare("
          SELECT 
            COUNT(*) as total_events,
            SUM(CASE WHEN date >= CURDATE() THEN 1 ELSE 0 END) as upcoming_events
          FROM events 
          WHERE user_id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $eventStats = $stmt->fetch();

        // Get shopping event statistics
        $stmt = $conn->prepare("
          SELECT 
            COUNT(*) as total_shopping_events
          FROM shopping_events 
          WHERE user_id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $shoppingStats = $stmt->fetch();

        // Get task statistics
        $stmt = $conn->prepare("
          SELECT 
            COUNT(*) as total_tasks,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_tasks
          FROM tasks 
          WHERE user_id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $taskStats = $stmt->fetch();

        // Get template statistics
        $stmt = $conn->prepare("
          SELECT COUNT(*) as total_templates
          FROM templates 
          WHERE created_by = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $templateStats = $stmt->fetch();

        $response = [
          'success' => true,
          'data' => [
            'upcomingEvents' => $upcomingEvents,
            'upcomingShoppingEvents' => $upcomingShoppingEvents,
            'pendingTasks' => $pendingTasks,
            'stats' => [
              'events' => $eventStats,
              'shopping' => $shoppingStats,
              'tasks' => $taskStats,
              'templates' => $templateStats
            ]
          ]
        ];
      } catch (Exception $e) {
        $response = [
          'success' => false,
          'message' => 'Error fetching dashboard data: ' . $e->getMessage()
        ];
      }
      break;
  }

  echo json_encode($response);
  exit;
}

// Get initial data for the page
$taskStats = ['total_tasks' => 0, 'completed_tasks' => 0];
$eventStats = ['total_events' => 0, 'upcoming_events' => 0];
$shoppingStats = ['total_shopping_events' => 0];
$templateStats = ['total_templates' => 0];
$upcomingEvents = [];
$upcomingShoppingEvents = [];
$pendingTasks = [];

try {
  // Get upcoming events for sidebar
  $upcomingEvents = $eventHandler->getUpcomingEvents($_SESSION['user_id'], 5);

  // Get upcoming shopping events
  $upcomingShoppingEvents = $shoppingHandler->getUpcomingEvents($_SESSION['user_id'], 5);

  // Get pending tasks
  $stmt = $conn->prepare("
    SELECT t.*, e.title as event_title 
    FROM tasks t 
    LEFT JOIN events e ON t.event_id = e.id 
    WHERE t.user_id = ? AND t.status = 'pending'
    ORDER BY t.deadline ASC
    LIMIT 5
  ");
  $stmt->execute([$_SESSION['user_id']]);
  $pendingTasks = $stmt->fetchAll();

  // Get event statistics
  $stmt = $conn->prepare("
    SELECT 
      COUNT(*) as total_events,
      SUM(CASE WHEN date >= CURDATE() THEN 1 ELSE 0 END) as upcoming_events
    FROM events 
    WHERE user_id = ?
  ");
  $stmt->execute([$_SESSION['user_id']]);
  $eventStats = $stmt->fetch();

  // Get shopping event statistics
  $stmt = $conn->prepare("
    SELECT 
      COUNT(*) as total_shopping_events
    FROM shopping_events 
    WHERE user_id = ?
  ");
  $stmt->execute([$_SESSION['user_id']]);
  $shoppingStats = $stmt->fetch();

  // Get task statistics
  $stmt = $conn->prepare("
    SELECT 
      COUNT(*) as total_tasks,
      SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_tasks
    FROM tasks 
    WHERE user_id = ?
  ");
  $stmt->execute([$_SESSION['user_id']]);
  $taskStats = $stmt->fetch();

  // Get template statistics
  $stmt = $conn->prepare("
    SELECT COUNT(*) as total_templates
    FROM templates 
    WHERE created_by = ?
  ");
  $stmt->execute([$_SESSION['user_id']]);
  $templateStats = $stmt->fetch();
} catch (Exception $e) {
  error_log('Error fetching initial dashboard data: ' . $e->getMessage());
  // Keep the default values set above
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr" class="h-full">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard - Bharat Event Planner</title>

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
  <link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Tiro+Devanagari+Hindi&display=swap"
    rel="stylesheet">

  <!-- Font Awesome Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <!-- GSAP for Animations -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.11.4/gsap.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.11.4/ScrollTrigger.min.js"></script>

  <!-- Scripts -->
  <script src="js/dashboard.js" defer></script>
</head>

<body class="min-h-screen bg-neutral-50 text-neutral-900 font-sans overflow-x-hidden">
  <!-- Animated Background Elements -->
  <div class="fixed inset-0 overflow-hidden pointer-events-none">
    <div class="absolute top-20 left-10 w-40 h-40 bg-primary/10 rounded-full filter blur-xl" data-speed="0.3"></div>
    <div class="absolute bottom-1/4 right-20 w-60 h-60 bg-primary/10 rounded-full filter blur-xl" data-speed="0.5">
    </div>
  </div>

  <!-- Sidebar -->
  <aside
    class="fixed inset-y-0 left-0 w-64 bg-white border-r border-neutral-200 transform -translate-x-full md:translate-x-0 transition-transform duration-300 ease-in-out z-50 shadow-sm"
    id="sidebar">
    <div class="flex flex-col h-full">
      <!-- Logo -->
      <div class="p-4 border-b border-neutral-200">
        <div class="flex items-center space-x-2">
          <img src="https://images.pexels.com/photos/1391487/pexels-photo-1391487.jpeg?auto=compress&cs=tinysrgb&w=200"
            alt="Bharat Event Planner logo" class="h-10 rounded-lg">
          <span class="text-2xl font-bold text-primary">Evenera</span>
        </div>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 p-4 space-y-2">
        <a href="/F&B1/dashboard.php"
          class="flex items-center space-x-3 px-4 py-3 rounded-sm transition-colors bg-primary/10 text-primary">
          <i class="fas fa-home w-6"></i>
          <span>Dashboard</span>
        </a>
        <a href="/F&B1/tasks.php"
          class="flex items-center space-x-3 px-4 py-3 rounded-sm transition-colors hover:bg-neutral-100 text-neutral-600 hover:text-primary">
          <i class="fas fa-tasks w-6"></i>
          <span>Tasks</span>
        </a>
        <a href="/F&B1/events.php"
          class="flex items-center space-x-3 px-4 py-3 rounded-sm transition-colors hover:bg-neutral-100 text-neutral-600 hover:text-primary">
          <i class="fas fa-calendar-alt w-6"></i>
          <span>Events</span>
        </a>
        <a href="/F&B1/template.html"
          class="flex items-center space-x-3 px-4 py-3 rounded-sm transition-colors hover:bg-neutral-100 text-neutral-600 hover:text-primary">
          <i class="fas fa-file-alt w-6"></i>
          <span>Templates</span>
        </a>
        <a href="/F&B1/shopping.php"
          class="flex items-center space-x-3 px-4 py-3 rounded-sm transition-colors hover:bg-neutral-100 text-neutral-600 hover:text-primary">
          <i class="fas fa-store w-6"></i>
          <span>Shopping</span>
        </a>
        <!-- Profile Link (New) -->
        <a href="/F&B1/profile.php"
          class="flex items-center space-x-3 px-4 py-3 rounded-sm transition-colors hover:bg-neutral-100 text-neutral-600 hover:text-primary">
          <i class="fas fa-user w-6"></i>
          <span>Profile</span>
        </a>
      </nav>

      <!-- Upcoming Events Section -->
      <div class="p-4 border-t border-neutral-200">
        <h3 class="font-medium text-neutral-900 mb-3">Upcoming Events</h3>
        <div class="space-y-3" id="sidebar-upcoming-events">
          <!-- Events will be loaded dynamically -->
        </div>
        <a href="/F&B1/events.php" class="text-sm text-primary hover:text-primary-hover flex items-center">
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
          <h1 class="text-2xl font-bold text-primary">Dashboard</h1>
        </div>
        <div class="flex items-center space-x-4">
          <button id="add-event-btn"
            class="bg-primary hover:bg-primary-hover text-white px-4 py-2 rounded-sm flex items-center space-x-2 transition-colors shadow-sm">
            <i class="fas fa-plus"></i>
            <span>Add Event</span>
          </button>
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
              <a href="#" id="logout-btn"
                class="block px-4 py-2 hover:bg-primary hover:text-white transition-colors text-neutral-700">
                <i class="fas fa-sign-out-alt mr-2"></i>Logout
              </a>
            </div>
          </div>
        </div>
      </div>
    </header>

    <!-- Page Content -->
    <div class="p-6">
      <!-- Welcome Message -->
      <div class="mb-6">
        <h2 class="text-2xl font-bold text-neutral-900" id="welcome-message">
          Welcome back, <?php echo htmlspecialchars($userData['name']); ?>!
        </h2>
      </div>

      <!-- Overview Cards -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="bg-white border border-neutral-200 rounded-sm p-6 shadow-sm">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-neutral-500">Total Events</p>
              <h3 class="text-2xl font-bold text-neutral-900" data-stat="total-events"><?php echo $eventStats['total_events'] ?? 0; ?></h3>
            </div>
            <div class="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center">
              <i class="fas fa-calendar text-primary"></i>
            </div>
          </div>
        </div>
        <div class="bg-white border border-neutral-200 rounded-sm p-6 shadow-sm">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-neutral-500">Total Tasks</p>
              <h3 class="text-2xl font-bold text-neutral-900" data-stat="total-tasks"><?php echo $taskStats['total_tasks'] ?? 0; ?></h3>
              <p class="text-sm text-neutral-500 mt-1" data-stat="task-completion"><?php
                echo $taskStats['total_tasks'] ?
                  round(($taskStats['completed_tasks'] / $taskStats['total_tasks']) * 100) : 0;
                ?>% Complete</p>
            </div>
            <div class="w-12 h-12 rounded-full bg-accent/10 flex items-center justify-center">
              <i class="fas fa-tasks text-accent"></i>
            </div>
          </div>
        </div>
        <div class="bg-white border border-neutral-200 rounded-sm p-6 shadow-sm">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-neutral-500">Shopping Events</p>
              <h3 class="text-2xl font-bold text-neutral-900" data-stat="total-shopping-events"><?php echo $shoppingStats['total_shopping_events'] ?? 0; ?></h3>
            </div>
            <div class="w-12 h-12 rounded-full bg-secondary/10 flex items-center justify-center">
              <i class="fas fa-store text-secondary"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- Main Content Grid -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <!-- Upcoming Events -->
        <div class="bg-white border border-neutral-200 rounded-sm shadow-sm">
          <div class="p-6 border-b border-neutral-200">
            <h2 class="text-xl font-semibold text-neutral-900">Upcoming Events</h2>
          </div>
          <div class="p-6">
            <div class="space-y-4 upcoming-events">
              <?php if (!empty($upcomingEvents)): ?>
                <?php foreach ($upcomingEvents as $event): ?>
                  <div class="bg-white p-4 rounded-sm border border-neutral-200 hover:shadow-md transition-shadow">
                    <div class="flex justify-between items-start">
                      <div>
                        <h3 class="font-semibold text-neutral-900"><?php echo htmlspecialchars($event['title']); ?></h3>
                        <p class="text-sm text-neutral-500"><?php echo htmlspecialchars($event['category']); ?></p>
                      </div>
                      <span class="px-2 py-1 text-xs rounded-full <?php
                                                                  echo $event['status'] === 'upcoming' ? 'bg-primary/10 text-primary' : ($event['status'] === 'ongoing' ? 'bg-secondary/10 text-secondary' :
                                                                    'bg-neutral-100 text-neutral-600');
                                                                  ?>"><?php echo ucfirst($event['status']); ?></span>
                    </div>
                    <div class="mt-2 space-y-1">
                      <div class="flex items-center text-sm text-neutral-600">
                        <i class="fas fa-calendar-alt w-5"></i>
                        <span><?php echo date('d M Y', strtotime($event['date'])); ?></span>
                      </div>
                      <div class="flex items-center text-sm text-neutral-600">
                        <i class="fas fa-map-marker-alt w-5"></i>
                        <span><?php echo $event['venue'] ? htmlspecialchars($event['venue']) : 'No venue set'; ?></span>
                      </div>
                      <div class="flex items-center text-sm text-neutral-600">
                        <i class="fas fa-wallet w-5"></i>
                        <span>₹<?php echo number_format($event['budget'] ?? 0); ?></span>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php else: ?>
                <p class="text-neutral-500">No upcoming events</p>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Upcoming Shopping Events -->
        <div class="bg-white border border-neutral-200 rounded-sm shadow-sm">
          <div class="p-6 border-b border-neutral-200">
            <h2 class="text-xl font-semibold text-neutral-900">Shopping Events</h2>
          </div>
          <div class="p-6">
            <div class="space-y-4 upcoming-shopping-events">
              <?php if (!empty($upcomingShoppingEvents)): ?>
                <?php foreach ($upcomingShoppingEvents as $event): ?>
                  <div class="bg-white p-4 rounded-sm border border-neutral-200 hover:shadow-md transition-shadow">
                    <div class="flex justify-between items-start">
                      <div>
                        <h3 class="font-semibold text-neutral-900"><?php echo htmlspecialchars($event['title']); ?></h3>
                        <p class="text-sm text-neutral-500"><?php echo htmlspecialchars($event['category']); ?></p>
                      </div>
                      <span class="px-2 py-1 text-xs rounded-full <?php
                                                                  echo $event['status'] === 'upcoming' ? 'bg-primary/10 text-primary' :
                                                                    'bg-neutral-100 text-neutral-600';
                                                                  ?>"><?php echo ucfirst($event['status']); ?></span>
                    </div>
                    <div class="mt-2 space-y-1">
                      <div class="flex items-center text-sm text-neutral-600">
                        <i class="fas fa-calendar-alt w-5"></i>
                        <span><?php echo date('d M Y', strtotime($event['date'])); ?></span>
                      </div>
                      <div class="flex items-center text-sm text-neutral-600">
                        <i class="fas fa-store w-5"></i>
                        <span><?php echo $event['location'] ? htmlspecialchars($event['location']) : 'No location set'; ?></span>
                      </div>
                      <div class="flex items-center text-sm text-neutral-600">
                        <i class="fas fa-wallet w-5"></i>
                        <span>₹<?php echo number_format($event['budget'] ?? 0); ?></span>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php else: ?>
                <p class="text-neutral-500">No upcoming shopping events</p>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Pending Tasks -->
        <div class="bg-white border border-neutral-200 rounded-sm shadow-sm">
          <div class="p-6 border-b border-neutral-200">
            <h2 class="text-xl font-semibold text-neutral-900">Pending Tasks</h2>
          </div>
          <div class="p-6">
            <div class="space-y-4 recent-tasks">
              <?php if (!empty($pendingTasks)): ?>
                <?php foreach ($pendingTasks as $task): ?>
                  <div class="bg-white p-4 rounded-sm border border-neutral-200 hover:shadow-md transition-shadow">
                    <div class="flex items-start justify-between">
                      <div class="flex-1">
                        <h4 class="font-medium text-neutral-900"><?php echo htmlspecialchars($task['title']); ?></h4>
                        <?php if ($task['event_title']): ?>
                          <p class="text-sm text-neutral-500">For: <?php echo htmlspecialchars($task['event_title']); ?></p>
                        <?php endif; ?>
                      </div>
                      <span class="px-2 py-1 text-xs rounded-full <?php
                                                                  echo $task['priority'] === 'high' ? 'bg-error-100 text-error-600' : ($task['priority'] === 'medium' ? 'bg-accent/10 text-accent' :
                                                                    'bg-neutral-100 text-neutral-600');
                                                                  ?>"><?php echo ucfirst($task['priority']); ?></span>
                    </div>
                    <div class="mt-2 flex items-center text-sm text-neutral-600">
                      <i class="fas fa-clock w-5"></i>
                      <span>Due: <?php echo date('d M Y', strtotime($task['deadline'])); ?></span>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php else: ?>
                <p class="text-neutral-500">No pending tasks</p>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

      <!-- Calendar -->
      <div class="bg-neutral-900 text-white rounded-sm shadow-sm">
        <div class="p-6 border-b border-neutral-200 flex justify-between items-center">
          <h2 class="text-xl font-semibold">April 2025</h2>
          <div class="flex space-x-2">
            <button id="prev-month" class="text-white hover:text-primary">
              <i class="fas fa-chevron-left"></i>
            </button>
            <button id="next-month" class="text-white hover:text-primary">
              <i class="fas fa-chevron-right"></i>
            </button>
          </div>
        </div>

        <div class="p-6">
          <div id="calendar" class="text-sm">
            <div class="grid grid-cols-7 gap-1 text-center">
              <div class="text-neutral-400">S</div>
              <div class="text-neutral-400">M</div>
              <div class="text-neutral-400">T</div>
              <div class="text-neutral-400">W</div>
              <div class="text-neutral-400">T</div>
              <div class="text-neutral-400">F</div>
              <div class="text-neutral-400">S</div>
            </div>
            <div id="calendar-body" class="grid grid-cols-7 gap-1 text-center"></div>
          </div>
        </div>
      </div>

      <!-- Digital Clock -->
      <div class="bg-white border border-neutral-200 rounded-sm shadow-sm">
        <div class="p-6 border-b border-neutral-200">
          <h2 class="text-xl font-semibold text-neutral-900">Digital Clock</h2>
        </div>
        <div class="p-6 flex flex-col items-center">
          <div class="bg-primary/10 rounded-lg p-4 shadow-inner">
            <div id="digital-clock-time" class="text-3xl font-bold text-primary mb-2"></div>
            <div id="digital-clock-date" class="text-sm text-neutral-500"></div>
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
</body>

</html>