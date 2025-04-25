<?php
require_once 'includes/db_connection.php';
require_once 'includes/session_handler.php';

$sessionHandler = new CustomSessionHandler($conn);

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

// Handle logout
if (isset($_POST['logout'])) {
    session_destroy();
    header('Location: admin_login.php');
    exit;
}

// Handle mark as read for contact messages
if (isset($_POST['mark_read'])) {
    $message_id = filter_input(INPUT_POST, 'message_id', FILTER_SANITIZE_NUMBER_INT);
    if ($message_id) {
        $stmt = $conn->prepare("UPDATE contact_messages SET is_read = 1 WHERE id = ?");
        $stmt->execute([$message_id]);
    }
}

// Fetch dashboard data
// Contact messages
$messages = $conn->query("SELECT cm.id, cm.name, cm.email, cm.message, cm.created_at, cm.is_read, a.name AS admin_name 
                         FROM contact_messages cm 
                         JOIN admins a ON cm.admin_id = a.id 
                         ORDER BY cm.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// User statistics
$totalUsers = $conn->query("SELECT COUNT(*) FROM users")->fetchColumn();
$recentUsers = $conn->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

// Event statistics
$upcomingEvents = $conn->query("
    SELECT e.*, COUNT(et.id) as total_tasks, 
           SUM(CASE WHEN et.status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
           se.budget
    FROM events e
    LEFT JOIN event_tasks et ON e.id = et.event_id
    LEFT JOIN shopping_events se ON e.title = se.title
    WHERE e.date >= CURDATE()
    GROUP BY e.id, e.title, e.description, e.date
    ORDER BY e.date ASC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Task overview
$taskStats = $conn->query("
    SELECT 
        COUNT(*) as total_tasks,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
        SUM(CASE WHEN status = 'pending' AND deadline < CURDATE() THEN 1 ELSE 0 END) as overdue_tasks
    FROM tasks
")->fetch(PDO::FETCH_ASSOC);

// Shopping statistics
$shoppingStats = $conn->query("
    SELECT 
        COUNT(*) as total_items,
        SUM(price) as total_budget,
        AVG(price) as avg_price
    FROM shopping_items
")->fetch(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en" dir="ltr" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Admin dashboard for Evenera event planning">
    <title>Evenera - Admin Dashboard</title>

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
                    }
                }
            }
        }
    </script>

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Main Script -->
    <script src="js/admin_dashboard.js" defer></script>
</head>

<body class="min-h-screen bg-neutral-50 text-neutral-900 font-sans">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm">
        <div class="container mx-auto px-6 py-4 flex justify-between items-center">
            <div class="flex items-center space-x-2">
                <img src="assets/images/logo.png" alt="Evenera logo" class="h-10">
                <span class="text-2xl font-bold text-primary">Evenera Admin</span>
            </div>
            <form method="POST">
                <button type="submit" name="logout" class="px-6 py-2 bg-red-600 hover:bg-red-700 text-white font-medium transition-colors rounded-sm">
                    Logout
                </button>
            </form>
        </div>
    </nav>

    <!-- Dashboard Content -->
    <main class="container mx-auto px-6 py-8">
        <h1 class="text-4xl font-bold mb-8">Welcome, <?php echo htmlspecialchars($_SESSION['admin_name']); ?>!</h1>

        <!-- Statistics Overview -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white p-6 rounded-lg shadow-sm border border-neutral-200">
                <h3 class="text-lg font-semibold mb-2">Total Users</h3>
                <p class="text-3xl font-bold text-primary"><?php echo $totalUsers; ?></p>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-sm border border-neutral-200">
                <h3 class="text-lg font-semibold mb-2">Task Progress</h3>
                <p class="text-3xl font-bold text-secondary">
                    <?php echo $taskStats['completed_tasks']; ?>/<?php echo $taskStats['total_tasks']; ?>
                </p>
                <p class="text-sm text-red-600 mt-2">
                    <?php echo $taskStats['overdue_tasks']; ?> overdue
                </p>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-sm border border-neutral-200">
                <h3 class="text-lg font-semibold mb-2">Shopping Budget</h3>
                <p class="text-3xl font-bold text-accent">
                    $<?php echo number_format($shoppingStats['total_budget'], 2); ?>
                </p>
                <p class="text-sm text-neutral-500 mt-2">
                    Avg: $<?php echo number_format($shoppingStats['avg_price'], 2); ?>/item
                </p>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-sm border border-neutral-200">
                <h3 class="text-lg font-semibold mb-2">Upcoming Events</h3>
                <p class="text-3xl font-bold text-primary"><?php echo count($upcomingEvents); ?></p>
            </div>
        </div>

        <!-- Upcoming Events -->
        <section class="mb-12">
            <h2 class="text-2xl font-bold mb-6">Upcoming Events</h2>
            <div class="bg-white border border-neutral-200 rounded-lg overflow-hidden">
                <table class="w-full">
                    <thead>
                        <tr class="bg-neutral-100">
                            <th class="p-4 text-left">Event Name</th>
                            <th class="p-4 text-left">Date</th>
                            <th class="p-4 text-left">Tasks</th>
                            <th class="p-4 text-left">Budget</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($upcomingEvents)): ?>
                            <tr>
                                <td colspan="4" class="p-4 text-center text-neutral-500">No upcoming events</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($upcomingEvents as $event): ?>
                                <tr class="border-t border-neutral-200">
                                    <td class="p-4">
                                        <div class="font-medium"><?php echo htmlspecialchars($event['title']); ?></div>
                                        <div class="text-sm text-neutral-500"><?php echo htmlspecialchars($event['description']); ?></div>
                                    </td>
                                    <td class="p-4">
                                        <?php echo date('M d, Y', strtotime($event['date'])); ?>
                                    </td>
                                    <td class="p-4">
                                        <div class="flex items-center">
                                            <div class="w-full bg-neutral-200 rounded-full h-2 mr-2">
                                                <?php $progress = $event['total_tasks'] ? ($event['completed_tasks'] / $event['total_tasks'] * 100) : 0; ?>
                                                <div class="bg-secondary rounded-full h-2" style="width: <?php echo $progress; ?>%"></div>
                                            </div>
                                            <span class="text-sm text-neutral-600">
                                                <?php echo $event['completed_tasks']; ?>/<?php echo $event['total_tasks']; ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="p-4">$<?php echo number_format($event['budget'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Recent Users -->
        <section class="mb-12">
            <h2 class="text-2xl font-bold mb-6">Recent Users</h2>
            <div class="bg-white border border-neutral-200 rounded-lg overflow-hidden">
                <table class="w-full">
                    <thead>
                        <tr class="bg-neutral-100">
                            <th class="p-4 text-left">Name</th>
                            <th class="p-4 text-left">Email</th>
                            <th class="p-4 text-left">Joined</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentUsers)): ?>
                            <tr>
                                <td colspan="3" class="p-4 text-center text-neutral-500">No users found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentUsers as $user): ?>
                                <tr class="border-t border-neutral-200">
                                    <td class="p-4"><?php echo htmlspecialchars($user['name']); ?></td>
                                    <td class="p-4"><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td class="p-4"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Contact Messages -->
        <section class="mb-12">
            <h2 class="text-2xl font-bold mb-6">Contact Messages</h2>
            <div class="bg-white border border-neutral-200 rounded-lg overflow-hidden">
                <table class="w-full">
                    <thead>
                        <tr class="bg-neutral-100">
                            <th class="p-4 text-left">Name</th>
                            <th class="p-4 text-left">Email</th>
                            <th class="p-4 text-left">Message</th>
                            <th class="p-4 text-left">To Admin</th>
                            <th class="p-4 text-left">Date</th>
                            <th class="p-4 text-left">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($messages)): ?>
                            <tr>
                                <td colspan="6" class="p-4 text-center text-neutral-500">No messages found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($messages as $message): ?>
                                <tr class="<?php echo $message['is_read'] ? 'bg-neutral-50' : 'bg-white'; ?> border-t border-neutral-200">
                                    <td class="p-4"><?php echo htmlspecialchars($message['name']); ?></td>
                                    <td class="p-4"><?php echo htmlspecialchars($message['email']); ?></td>
                                    <td class="p-4"><?php echo htmlspecialchars($message['message']); ?></td>
                                    <td class="p-4"><?php echo htmlspecialchars($message['admin_name']); ?></td>
                                    <td class="p-4"><?php echo date('M d, Y', strtotime($message['created_at'])); ?></td>
                                    <td class="p-4">
                                        <?php if (!$message['is_read']): ?>
                                            <form method="POST">
                                                <input type="hidden" name="message_id" value="<?php echo $message['id']; ?>">
                                                <button type="submit" name="mark_read" class="px-3 py-1 bg-primary hover:bg-primary-hover text-white rounded-sm text-sm">
                                                    Mark as Read
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-neutral-400 text-sm">Read</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="bg-neutral-900 text-white py-8">
        <div class="container mx-auto px-6">
            <div class="flex justify-between items-center">
                <p class="text-neutral-400 text-sm">© 2025 ATY Designs. All rights reserved.</p>
                <div class="flex space-x-6">
                    <a href="/privacy.html" class="text-neutral-400 hover:text-white text-sm transition">Privacy Policy</a>
                    <a href="/terms.html" class="text-neutral-400 hover:text-white text-sm transition">Terms of Service</a>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>