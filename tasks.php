<?php
require_once 'includes/db_connection.php';
require_once 'includes/auth_handler.php';
require_once 'includes/session_handler.php';
require_once 'includes/task_handler.php';

// Initialize handlers
$authHandler = new AuthHandler($conn);
$sessionHandler = new CustomSessionHandler($conn);
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

  switch ($_POST['action']) {
    case 'create':
      $result = $taskHandler->createTask($_SESSION['user_id'], [
        'title' => $_POST['title'],
        'description' => $_POST['description'],
        'deadline' => $_POST['deadline'],
        'priority' => $_POST['priority'],
        'category' => $_POST['category']
      ]);
      echo json_encode($result);
      exit;

    case 'update':
      $result = $taskHandler->updateTask($_POST['id'], $_SESSION['user_id'], [
        'title' => $_POST['title'],
        'description' => $_POST['description'],
        'deadline' => $_POST['deadline'],
        'priority' => $_POST['priority'],
        'category' => $_POST['category']
      ]);
      echo json_encode($result);
      exit;

    case 'delete':
      $result = $taskHandler->deleteTask($_POST['id'], $_SESSION['user_id']);
      echo json_encode($result);
      exit;

    case 'updateStatus':
      $result = $taskHandler->updateTaskStatus($_POST['id'], $_SESSION['user_id'], $_POST['status']);
      echo json_encode($result);
      exit;

    case 'updateProgress':
      $result = $taskHandler->updateTaskProgress($_POST['id'], $_SESSION['user_id'], $_POST['progress']);
      echo json_encode($result);
      exit;
  }
}

// Get tasks with filters
$filters = [
  'status' => $_GET['status'] ?? '',
  'priority' => $_GET['priority'] ?? '',
  'category' => $_GET['category'] ?? '',
  'sort' => $_GET['sort'] ?? 'deadline'
];

$tasks = $taskHandler->getTasks($_SESSION['user_id'], $filters);
$taskStats = $taskHandler->getTaskStats($_SESSION['user_id']);
$upcomingReminders = $taskHandler->getUpcomingReminders($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en" dir="ltr" class="h-full">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Task Management - Bharat Event Planner</title>

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

  <!-- GSAP for animations -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.11.4/gsap.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.11.4/ScrollTrigger.min.js"></script>

  <!-- Page specific script -->
  <script src="js/tasks.js" defer></script>
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
        <a href="/F&B1/tasks.php" class="flex items-center space-x-3 px-4 py-3 rounded-sm transition-colors bg-primary/10 text-primary">
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
        <a href="/F&B1/profile.php" class="flex items-center space-x-3 px-4 py-3 rounded-sm transition-colors hover:bg-neutral-100 text-neutral-600 hover:text-primary">
          <i class="fas fa-user w-6"></i>
          <span>Profile</span>
        </a>
      </nav>

      <!-- Upcoming Events Section -->
      <div class="p-4 border-t border-neutral-200">
        <h3 class="font-medium text-neutral-900 mb-3">Upcoming Events</h3>
        <div class="space-y-3">
          <?php foreach ($upcomingReminders as $reminder): ?>
            <div class="bg-neutral-50 p-3 rounded-sm">
              <p class="font-medium text-neutral-800"><?php echo htmlspecialchars($reminder['title']); ?></p>
              <div class="flex items-center justify-between mt-1">
                <span class="text-sm text-neutral-500">
                  <i class="fas fa-calendar-alt mr-1"></i>
                  <?php echo date('d M Y', strtotime($reminder['deadline'])); ?>
                </span>
                <span class="text-xs px-2 py-1 bg-primary/10 text-primary rounded-full">
                  <?php
                  $diffDays = ceil((strtotime($reminder['deadline']) - time()) / (86400));
                  echo $diffDays . ' day' . ($diffDays !== 1 ? 's' : '') . ' left';
                  ?>
                </span>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <a href="events.php" class="text-sm text-primary hover:text-primary-hover flex items-center">
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
          <h1 class="text-2xl font-bold text-primary">Task Management</h1>
        </div>
        <div class="flex items-center space-x-4">
          <button id="create-task-btn" class="bg-primary hover:bg-primary-hover text-white px-4 py-2 rounded-sm flex items-center space-x-2 transition-colors shadow-sm">
            <i class="fas fa-plus"></i>
            <span>New Task</span>
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

    <!-- Task Management Interface -->
    <div class="p-6">
      <!-- Task Filters and Controls -->
      <div class="mb-6 flex flex-wrap gap-4">
        <div class="flex-1 min-w-[200px]">
          <select id="task-filter" class="w-full bg-white border border-neutral-200 rounded-sm px-4 py-2 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
            <option value="all">All Tasks</option>
            <option value="incomplete" <?php echo $filters['status'] === 'pending' ? 'selected' : ''; ?>>Incomplete</option>
            <option value="completed" <?php echo $filters['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
            <option value="high-priority" <?php echo $filters['priority'] === 'high' ? 'selected' : ''; ?>>High Priority</option>
          </select>
        </div>
        <div class="flex-1 min-w-[200px]">
          <select id="task-sort" class="w-full bg-white border border-neutral-200 rounded-sm px-4 py-2 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
            <option value="deadline" <?php echo $filters['sort'] === 'deadline' ? 'selected' : ''; ?>>Sort by Deadline</option>
            <option value="priority" <?php echo $filters['sort'] === 'priority' ? 'selected' : ''; ?>>Sort by Priority</option>
            <option value="progress" <?php echo $filters['sort'] === 'progress' ? 'selected' : ''; ?>>Sort by Progress</option>
          </select>
        </div>
      </div>

      <!-- Task Statistics -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-white border border-neutral-200 rounded-sm p-6 shadow-sm">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-neutral-500">Total Tasks</p>
              <h3 class="text-2xl font-bold text-neutral-900" data-stat="total-tasks"><?php echo $taskStats['total']; ?></h3>
            </div>
            <div class="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center">
              <i class="fas fa-tasks text-primary"></i>
            </div>
          </div>
        </div>
        <div class="bg-white border border-neutral-200 rounded-sm p-6 shadow-sm">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-neutral-500">Completed Tasks</p>
              <h3 class="text-2xl font-bold text-neutral-900" data-stat="completed-tasks"><?php echo $taskStats['completed']; ?></h3>
            </div>
            <div class="w-12 h-12 rounded-full bg-secondary/10 flex items-center justify-center">
              <i class="fas fa-check text-secondary"></i>
            </div>
          </div>
        </div>
        <div class="bg-white border border-neutral-200 rounded-sm p-6 shadow-sm">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-neutral-500">High Priority</p>
              <h3 class="text-2xl font-bold text-neutral-900" data-stat="high-priority-tasks"><?php echo $taskStats['highPriority']; ?></h3>
            </div>
            <div class="w-12 h-12 rounded-full bg-error-100 flex items-center justify-center">
              <i class="fas fa-exclamation-triangle text-error-600"></i>
            </div>
          </div>
        </div>
        <div class="bg-white border border-neutral-200 rounded-sm p-6 shadow-sm">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-neutral-500">Upcoming Deadlines</p>
              <h3 class="text-2xl font-bold text-neutral-900" data-stat="upcoming-deadlines"><?php echo $taskStats['upcomingDeadlines']; ?></h3>
            </div>
            <div class="w-12 h-12 rounded-full bg-accent/10 flex items-center justify-center">
              <i class="fas fa-clock text-accent"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- Task List -->
      <div class="bg-white border border-neutral-200 rounded-sm shadow-sm mb-6">
        <div class="p-6 border-b border-neutral-200">
          <h2 class="text-xl font-semibold text-neutral-900">Tasks</h2>
        </div>
        <div class="p-6">
          <div class="task-list space-y-4">
            <?php foreach ($tasks as $task): ?>
              <div class="task-item bg-white border border-neutral-200 rounded-sm p-4">
                <div class="flex items-center justify-between">
                  <div class="flex items-center space-x-4 flex-1">
                    <input type="checkbox" class="w-5 h-5 accent-primary"
                      <?php echo $task['status'] === 'completed' ? 'checked' : ''; ?>
                      onchange="taskManager.toggleTaskComplete(<?php echo $task['id']; ?>)">
                    <div class="flex-1">
                      <h3 class="text-lg font-medium <?php echo $task['status'] === 'completed' ? 'line-through text-neutral-500' : 'text-neutral-900'; ?>">
                        <?php echo htmlspecialchars($task['title']); ?>
                      </h3>
                      <p class="text-sm text-neutral-500">Due: <?php echo date('d M Y', strtotime($task['deadline'])); ?></p>
                      <p class="text-sm text-neutral-500">Category: <?php echo $task['category'] ? htmlspecialchars($task['category']) : 'None'; ?></p>
                      <div class="mt-2 flex items-center space-x-2">
                        <span class="text-sm text-neutral-500">Progress: <?php echo $task['progress']; ?>%</span>
                        <input type="range" min="0" max="100" value="<?php echo $task['progress']; ?>"
                          class="w-32 accent-primary"
                          oninput="taskManager.updateProgress(<?php echo $task['id']; ?>, this.value)">
                      </div>
                    </div>
                  </div>
                  <div class="flex items-center space-x-2">
                    <span class="px-3 py-1 text-xs font-medium rounded-full <?php
                                                                            echo $task['priority'] === 'high' ? 'bg-error-100 text-error-600' : ($task['priority'] === 'medium' ? 'bg-accent/10 text-accent' :
                                                                              'bg-secondary/10 text-secondary');
                                                                            ?>">
                      <?php echo ucfirst($task['priority']); ?>
                    </span>
                    <button onclick="taskManager.showTaskModal(<?php echo $task['id']; ?>)" class="text-primary hover:text-primary-hover">
                      <i class="fas fa-edit"></i>
                    </button>
                    <button onclick="taskManager.deleteTask(<?php echo $task['id']; ?>)" class="text-error-500 hover:text-error-600">
                      <i class="fas fa-trash"></i>
                    </button>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- Reminders -->
      <div class="bg-white border border-neutral-200 rounded-sm shadow-sm">
        <div class="p-6 border-b border-neutral-200">
          <h2 class="text-xl font-semibold text-neutral-900">Upcoming Reminders</h2>
        </div>
        <div class="p-6">
          <div id="reminders-list" class="space-y-4">
            <?php foreach ($upcomingReminders as $reminder): ?>
              <div class="flex items-center justify-between p-4 bg-neutral-50 rounded-sm">
                <div class="flex items-center space-x-4">
                  <div class="w-10 h-10 rounded-full bg-accent/10 flex items-center justify-center">
                    <i class="fas fa-bell text-accent"></i>
                  </div>
                  <div>
                    <h3 class="text-lg font-medium text-neutral-900"><?php echo htmlspecialchars($reminder['title']); ?></h3>
                    <p class="text-sm text-neutral-500">
                      Due: <?php echo date('d M Y', strtotime($reminder['deadline'])); ?>
                      (<?php
                        $diffDays = ceil((strtotime($reminder['deadline']) - time()) / (86400));
                        echo $diffDays . ' day' . ($diffDays !== 1 ? 's' : '') . ' left';
                        ?>)
                    </p>
                  </div>
                </div>
                <span class="px-3 py-1 text-xs font-medium rounded-full <?php
                                                                        echo $reminder['priority'] === 'high' ? 'bg-error-100 text-error-600' : ($reminder['priority'] === 'medium' ? 'bg-accent/10 text-accent' :
                                                                          'bg-secondary/10 text-secondary');
                                                                        ?>">
                  <?php echo ucfirst($reminder['priority']); ?>
                </span>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
  </main>

  <!-- Task Modal -->
  <div id="task-modal" class="fixed inset-0 bg-neutral-900/95 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-sm w-full max-w-2xl p-6">
      <div class="flex items-center justify-between mb-6">
        <h2 id="modal-title" class="text-xl font-semibold text-neutral-900">Create Task</h2>
        <button id="modal-close" class="text-neutral-500 hover:text-neutral-700">
          <i class="fas fa-times"></i>
        </button>
      </div>
      <form id="task-form" class="space-y-4">
        <input type="hidden" id="task-id" value="">
        <div>
          <label class="block text-sm font-medium mb-1 text-neutral-700">Title</label>
          <input type="text" id="task-title" placeholder="Enter task title"
            class="w-full bg-neutral-50 border border-neutral-200 rounded-sm px-4 py-2 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
            required>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1 text-neutral-700">Description</label>
          <textarea id="task-description" placeholder="Enter task description" rows="3"
            class="w-full bg-neutral-50 border border-neutral-200 rounded-sm px-4 py-2 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"></textarea>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium mb-1 text-neutral-700">Deadline</label>
            <input type="date" id="task-deadline"
              class="w-full bg-neutral-50 border border-neutral-200 rounded-sm px-4 py-2 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
              required>
          </div>
          <div>
            <label class="block text-sm font-medium mb-1 text-neutral-700">Priority</label>
            <select id="task-priority"
              class="w-full bg-neutral-50 border border-neutral-200 rounded-sm px-4 py-2 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
              <option value="low">Low</option>
              <option value="medium">Medium</option>
              <option value="high">High</option>
            </select>
          </div>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1 text-neutral-700">Category</label>
          <select id="task-category"
            class="w-full bg-neutral-50 border border-neutral-200 rounded-sm px-4 py-2 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
            <option value="">Select Category</option>
            <option value="venue">Venue</option>
            <option value="catering">Catering</option>
            <option value="decoration">Decoration</option>
            <option value="entertainment">Entertainment</option>
            <option value="photography">Photography</option>
            <option value="guest-management">Guest Management</option>
            <option value="other">Other</option>
          </select>
        </div>
        <div class="flex justify-end space-x-4">
          <button type="button" id="cancel-task-btn"
            class="px-4 py-2 border border-neutral-200 rounded-sm text-neutral-700 hover:bg-neutral-100 transition-colors">
            Cancel
          </button>
          <button type="submit" id="save-task-btn"
            class="px-4 py-2 bg-primary hover:bg-primary-hover text-white rounded-sm transition-colors">
            Save Task
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Loading Overlay -->
  <div id="loading-overlay" class="fixed inset-0 bg-neutral-900/95 flex items-center justify-center z-50 hidden">
    <div class="text-center">
      <div class="w-16 h-16 border-4 border-primary border-t-transparent rounded-full animate-spin-slow mx-auto mb-4"></div>
      <p class="text-white text-lg">Loading...</p>
    </div>
  </div>

  <!-- Initialize GSAP Animations -->
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      gsap.registerPlugin(ScrollTrigger);

      // Animate statistics cards
      gsap.from('.bg-white', {
        opacity: 0,
        y: 20,
        duration: 0.5,
        stagger: 0.1,
        scrollTrigger: {
          trigger: 'main',
          start: 'top 80%'
        }
      });

      // Animate task items
      gsap.from('.task-item', {
        opacity: 0,
        y: 20,
        duration: 0.5,
        stagger: 0.1,
        scrollTrigger: {
          trigger: '.task-list',
          start: 'top 80%'
        }
      });

      // Animate filter controls
      gsap.from('#task-filter, #task-sort', {
        opacity: 0,
        y: 20,
        duration: 0.5,
        stagger: 0.1,
        scrollTrigger: {
          trigger: '.mb-6',
          start: 'top 80%'
        }
      });

      // Animate reminders
      gsap.from('#reminders-list > div', {
        opacity: 0,
        y: 20,
        duration: 0.5,
        stagger: 0.1,
        scrollTrigger: {
          trigger: '#reminders-list',
          start: 'top 80%'
        }
      });
    });
  </script>
</body>

</html>