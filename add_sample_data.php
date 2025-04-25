<?php
require_once 'includes/db_connection.php';

try {
    // Sample users
    $users = [
        ['name' => 'Sarah Johnson', 'email' => 'sarah@example.com', 'password' => password_hash('user123', PASSWORD_DEFAULT)],
        ['name' => 'Michael Chen', 'email' => 'michael@example.com', 'password' => password_hash('user123', PASSWORD_DEFAULT)],
        ['name' => 'Emma Davis', 'email' => 'emma@example.com', 'password' => password_hash('user123', PASSWORD_DEFAULT)],
        ['name' => 'Alex Thompson', 'email' => 'alex@example.com', 'password' => password_hash('user123', PASSWORD_DEFAULT)]
    ];

    // Insert users
    foreach ($users as $user) {
        $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
        $stmt->execute([$user['name'], $user['email'], $user['password']]);
    }

    // Sample events
    $events = [
        [
            'name' => 'Summer Wedding Gala',
            'description' => 'Luxurious summer wedding event with outdoor ceremony',
            'start_date' => '2024-06-15',
            'end_date' => '2024-06-15'
        ],
        [
            'name' => 'Tech Conference 2024',
            'description' => 'Annual technology conference with industry leaders',
            'start_date' => '2024-07-20',
            'end_date' => '2024-07-22'
        ],
        [
            'name' => 'Cultural Festival',
            'description' => 'Celebrating diverse cultures with food, music, and dance',
            'start_date' => '2024-08-10',
            'end_date' => '2024-08-12'
        ]
    ];

    // Insert events
    foreach ($events as $event) {
        $stmt = $conn->prepare("INSERT INTO events (name, description, start_date, end_date) VALUES (?, ?, ?, ?)");
        $stmt->execute([$event['name'], $event['description'], $event['start_date'], $event['end_date']]);
        $eventId = $conn->lastInsertId();

        // Create shopping event for each event
        $stmt = $conn->prepare("INSERT INTO shopping_events (event_id, budget) VALUES (?, ?)");
        $stmt->execute([$eventId, rand(5000, 20000)]);
        $shoppingEventId = $conn->lastInsertId();

        // Add shopping items
        $items = [
            ['name' => 'Decorations', 'description' => 'Event decorations and supplies', 'price' => rand(500, 2000)],
            ['name' => 'Catering', 'description' => 'Food and beverage service', 'price' => rand(1000, 5000)],
            ['name' => 'Equipment', 'description' => 'Audio/Visual equipment', 'price' => rand(800, 3000)]
        ];

        foreach ($items as $item) {
            $stmt = $conn->prepare("INSERT INTO shopping_items (name, description, estimated_price) VALUES (?, ?, ?)");
            $stmt->execute([$item['name'], $item['description'], $item['price']]);
            $itemId = $conn->lastInsertId();

            // Create shopping task
            $stmt = $conn->prepare("INSERT INTO shopping_tasks (shopping_event_id, shopping_item_id, quantity, status) VALUES (?, ?, ?, ?)");
            $stmt->execute([$shoppingEventId, $itemId, rand(1, 5), array_rand(['pending' => 0, 'completed' => 1])]);
        }
    }

    // Sample tasks
    $tasks = [
        ['name' => 'Venue Booking', 'description' => 'Book and confirm venue location', 'due_date' => '2024-05-15', 'status' => 'completed'],
        ['name' => 'Catering Arrangements', 'description' => 'Finalize menu and catering service', 'due_date' => '2024-05-20', 'status' => 'pending'],
        ['name' => 'Send Invitations', 'description' => 'Design and send event invitations', 'due_date' => '2024-05-25', 'status' => 'pending'],
        ['name' => 'Equipment Setup', 'description' => 'Arrange and test AV equipment', 'due_date' => '2024-06-01', 'status' => 'pending']
    ];

    // Insert tasks and associate with events
    foreach ($tasks as $task) {
        $stmt = $conn->prepare("INSERT INTO tasks (name, description, due_date, status) VALUES (?, ?, ?, ?)");
        $stmt->execute([$task['name'], $task['description'], $task['due_date'], $task['status']]);
        $taskId = $conn->lastInsertId();

        // Associate task with a random event
        $stmt = $conn->prepare("INSERT INTO event_tasks (event_id, task_id) VALUES (?, ?)");
        $stmt->execute([rand(1, count($events)), $taskId]);
    }

    // Sample templates
    $templates = [
        ['name' => 'Corporate Event', 'description' => 'Standard template for corporate events'],
        ['name' => 'Wedding Package', 'description' => 'Complete wedding planning template'],
        ['name' => 'Festival Setup', 'description' => 'Template for organizing festivals']
    ];

    // Insert templates
    foreach ($templates as $template) {
        $stmt = $conn->prepare("INSERT INTO templates (name, description) VALUES (?, ?)");
        $stmt->execute([$template['name'], $template['description']]);
        $templateId = $conn->lastInsertId();

        // Create template tasks
        $templateTasks = [
            ['name' => 'Initial Planning', 'description' => 'Create event outline and timeline'],
            ['name' => 'Budget Planning', 'description' => 'Develop detailed budget'],
            ['name' => 'Vendor Selection', 'description' => 'Research and select vendors']
        ];

        foreach ($templateTasks as $task) {
            $stmt = $conn->prepare("INSERT INTO tasks (name, description, status) VALUES (?, ?, 'pending')");
            $stmt->execute([$task['name'], $task['description']]);
            $taskId = $conn->lastInsertId();

            // Associate task with template
            $stmt = $conn->prepare("INSERT INTO template_tasks (template_id, task_id) VALUES (?, ?)");
            $stmt->execute([$templateId, $taskId]);
        }
    }

    echo "Sample data added successfully!\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
} 