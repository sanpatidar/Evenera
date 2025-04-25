<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
  header('Location: login.php');
  exit();
}

// Database connection
try {
  $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
  die("Connection failed: " . $e->getMessage());
}

// Get user details
$stmt = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Set user details in variables and session
$userName = $user['name'] ?? 'User';
$_SESSION['email'] = $user['email'] ?? '';

// Function to get all templates
function getAllTemplates($pdo)
{
  $stmt = $pdo->prepare("
        SELECT t.*, 
               COUNT(tk.id) as task_count,
               GROUP_CONCAT(
                   JSON_OBJECT(
                       'id', tk.id,
                       'title', tk.title,
                       'category', tk.category,
                       'priority', tk.priority,
                       'due_date', tk.due_date,
                       'assigned_to', tk.assigned_to,
                       'notes', tk.notes
                   )
               ) as tasks
        FROM templates t
        LEFT JOIN template_tasks tk ON t.id = tk.template_id
        GROUP BY t.id
        ORDER BY t.category, t.name
    ");
  $stmt->execute();
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to get upcoming events
function getUpcomingEvents($pdo)
{
  $stmt = $pdo->prepare("
        SELECT e.*, 
               COUNT(et.id) as task_count
        FROM events e
        LEFT JOIN event_tasks et ON e.id = et.event_id
        WHERE e.start_date >= CURDATE()
        AND e.start_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
        GROUP BY e.id
        ORDER BY e.start_date ASC
        LIMIT 5
    ");
  $stmt->execute();
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';

  switch ($action) {
    case 'create_template':
      try {
        $pdo->beginTransaction();

        // Insert template
        $stmt = $pdo->prepare("
                    INSERT INTO templates (name, category, type, description, budget, is_custom, created_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
        $stmt->execute([
          $_POST['name'],
          $_POST['category'],
          $_POST['type'],
          $_POST['description'],
          $_POST['budget'],
          true,
          $_SESSION['user_id']
        ]);

        $templateId = $pdo->lastInsertId();

        // Insert tasks
        if (!empty($_POST['tasks'])) {
          $stmt = $pdo->prepare("
                        INSERT INTO template_tasks (template_id, title, category, priority, due_date, assigned_to, notes)
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");

          foreach ($_POST['tasks'] as $task) {
            $stmt->execute([
              $templateId,
              $task['title'],
              $task['category'],
              $task['priority'],
              $task['due_date'],
              $task['assigned_to'],
              $task['notes']
            ]);
          }
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Template created successfully']);
      } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Error creating template: ' . $e->getMessage()]);
      }
      exit();

    case 'update_template':
      try {
        $pdo->beginTransaction();

        // Update template
        $stmt = $pdo->prepare("
                    UPDATE templates 
                    SET name = ?, category = ?, type = ?, description = ?, budget = ?
                    WHERE id = ? AND created_by = ?
                ");
        $stmt->execute([
          $_POST['name'],
          $_POST['category'],
          $_POST['type'],
          $_POST['description'],
          $_POST['budget'],
          $_POST['template_id'],
          $_SESSION['user_id']
        ]);

        // Delete existing tasks
        $stmt = $pdo->prepare("DELETE FROM template_tasks WHERE template_id = ?");
        $stmt->execute([$_POST['template_id']]);

        // Insert new tasks
        if (!empty($_POST['tasks'])) {
          $stmt = $pdo->prepare("
                        INSERT INTO template_tasks (template_id, title, category, priority, due_date, assigned_to, notes)
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");

          foreach ($_POST['tasks'] as $task) {
            $stmt->execute([
              $_POST['template_id'],
              $task['title'],
              $task['category'],
              $task['priority'],
              $task['due_date'],
              $task['assigned_to'],
              $task['notes']
            ]);
          }
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Template updated successfully']);
      } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Error updating template: ' . $e->getMessage()]);
      }
      exit();

    case 'delete_template':
      try {
        $pdo->beginTransaction();

        // Delete tasks first
        $stmt = $pdo->prepare("DELETE FROM template_tasks WHERE template_id = ?");
        $stmt->execute([$_POST['template_id']]);

        // Delete template
        $stmt = $pdo->prepare("DELETE FROM templates WHERE id = ? AND created_by = ?");
        $stmt->execute([$_POST['template_id'], $_SESSION['user_id']]);

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Template deleted successfully']);
      } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Error deleting template: ' . $e->getMessage()]);
      }
      exit();

    case 'create_event':
      try {
        $pdo->beginTransaction();

        // Insert event
        $stmt = $pdo->prepare("
                    INSERT INTO events (title, category, description, start_date, end_date, venue, budget, created_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
        $stmt->execute([
          $_POST['title'],
          $_POST['category'],
          $_POST['description'],
          $_POST['start_date'],
          $_POST['end_date'],
          $_POST['venue'],
          $_POST['budget'],
          $_SESSION['user_id']
        ]);

        $eventId = $pdo->lastInsertId();

        // Insert tasks
        if (!empty($_POST['tasks'])) {
          $stmt = $pdo->prepare("
                        INSERT INTO event_tasks (event_id, title, category, priority, due_date, assigned_to, notes)
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");

          foreach ($_POST['tasks'] as $task) {
            $stmt->execute([
              $eventId,
              $task['title'],
              $task['category'],
              $task['priority'],
              $task['due_date'],
              $task['assigned_to'],
              $task['notes']
            ]);
          }
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Event created successfully']);
      } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Error creating event: ' . $e->getMessage()]);
      }
      exit();
  }
}

// Get data for the page
$templates = getAllTemplates($pdo);
$upcomingEvents = getUpcomingEvents($pdo);

// Include the HTML template
include 'template.html';
