<?php
require_once 'config.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? null;

// ===== EVENT OPERATIONS =====

// Get all events
if ($action === 'get_events') {
    $result = $conn->query("SELECT id, event_name, event_date, event_time, location, notes FROM events WHERE user_id = $user_id ORDER BY event_date, event_time");
    $events = [];
    while ($row = $result->fetch_assoc()) {
        $events[] = $row;
    }
    echo json_encode(['status' => 'success', 'events' => $events]);
    exit;
}

// Add event
if ($action === 'add_event') {
    $event_name = $conn->real_escape_string($_POST['event_name']);
    $event_date = $conn->real_escape_string($_POST['event_date']);
    $event_time = $conn->real_escape_string($_POST['event_time']);
    $location = $conn->real_escape_string($_POST['location'] ?? '');
    $notes = $conn->real_escape_string($_POST['notes'] ?? '');
    
    $sql = "INSERT INTO events (user_id, event_name, event_date, event_time, location, notes) 
            VALUES ($user_id, '$event_name', '$event_date', '$event_time', '$location', '$notes')";
    
    if ($conn->query($sql)) {
        echo json_encode(['status' => 'success', 'message' => 'Event added', 'id' => $conn->insert_id]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error adding event: ' . $conn->error]);
    }
    exit;
}

// Update event
if ($action === 'update_event') {
    $event_id = intval($_POST['event_id']);
    $event_name = $conn->real_escape_string($_POST['event_name']);
    $event_date = $conn->real_escape_string($_POST['event_date']);
    $event_time = $conn->real_escape_string($_POST['event_time']);
    $location = $conn->real_escape_string($_POST['location'] ?? '');
    $notes = $conn->real_escape_string($_POST['notes'] ?? '');
    
    $sql = "UPDATE events SET event_name = '$event_name', event_date = '$event_date', 
            event_time = '$event_time', location = '$location', notes = '$notes' 
            WHERE id = $event_id AND user_id = $user_id";
    
    if ($conn->query($sql)) {
        echo json_encode(['status' => 'success', 'message' => 'Event updated']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error updating event: ' . $conn->error]);
    }
    exit;
}

// Delete event
if ($action === 'delete_event') {
    $event_id = intval($_POST['event_id']);
    
    if ($conn->query("DELETE FROM events WHERE id = $event_id AND user_id = $user_id")) {
        echo json_encode(['status' => 'success', 'message' => 'Event deleted']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error deleting event: ' . $conn->error]);
    }
    exit;
}

// ===== TO-DO LIST OPERATIONS =====

// Get all todos
if ($action === 'get_todos') {
    $result = $conn->query("SELECT id, name, todo_date, todo_time, notes, status FROM todo_list WHERE user_id = $user_id ORDER BY todo_date, todo_time");
    $todos = [];
    while ($row = $result->fetch_assoc()) {
        // Auto-update status to Missing if past due and not Done
        if ($row['status'] !== 'Done' && $row['todo_date'] && $row['todo_time']) {
            $todoDateTime = strtotime($row['todo_date'] . ' ' . $row['todo_time']);
            if ($todoDateTime < time()) {
                $conn->query("UPDATE todo_list SET status = 'Missing' WHERE id = {$row['id']}");
                $row['status'] = 'Missing';
            }
        }
        $todos[] = $row;
    }
    echo json_encode(['status' => 'success', 'todos' => $todos]);
    exit;
}

// Add todo
if ($action === 'add_todo') {
    $name = $conn->real_escape_string($_POST['name']);
    $todo_date = !empty($_POST['todo_date']) ? $conn->real_escape_string($_POST['todo_date']) : NULL;
    $todo_time = !empty($_POST['todo_time']) ? $conn->real_escape_string($_POST['todo_time']) : NULL;
    $notes = $conn->real_escape_string($_POST['notes'] ?? '');
    
    $dateValue = $todo_date ? "'$todo_date'" : "NULL";
    $timeValue = $todo_time ? "'$todo_time'" : "NULL";
    
    $sql = "INSERT INTO todo_list (user_id, name, todo_date, todo_time, notes, status) 
            VALUES ($user_id, '$name', $dateValue, $timeValue, '$notes', 'Pending')";
    
    if ($conn->query($sql)) {
        echo json_encode(['status' => 'success', 'message' => 'Task added', 'id' => $conn->insert_id]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error adding task: ' . $conn->error]);
    }
    exit;
}

// Update todo
if ($action === 'update_todo') {
    $todo_id = intval($_POST['todo_id']);
    $name = $conn->real_escape_string($_POST['name']);
    $todo_date = !empty($_POST['todo_date']) ? $conn->real_escape_string($_POST['todo_date']) : NULL;
    $todo_time = !empty($_POST['todo_time']) ? $conn->real_escape_string($_POST['todo_time']) : NULL;
    $notes = $conn->real_escape_string($_POST['notes'] ?? '');
    $status = $conn->real_escape_string($_POST['status'] ?? 'Pending');
    
    $dateValue = $todo_date ? "'$todo_date'" : "NULL";
    $timeValue = $todo_time ? "'$todo_time'" : "NULL";
    
    $sql = "UPDATE todo_list SET name = '$name', todo_date = $dateValue, 
            todo_time = $timeValue, notes = '$notes', status = '$status' 
            WHERE id = $todo_id AND user_id = $user_id";
    
    if ($conn->query($sql)) {
        echo json_encode(['status' => 'success', 'message' => 'Task updated']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error updating task: ' . $conn->error]);
    }
    exit;
}

// Delete todo
if ($action === 'delete_todo') {
    $todo_id = intval($_POST['todo_id']);
    
    if ($conn->query("DELETE FROM todo_list WHERE id = $todo_id AND user_id = $user_id")) {
        echo json_encode(['status' => 'success', 'message' => 'Task deleted']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error deleting task: ' . $conn->error]);
    }
    exit;
}

// ===== SCHEDULE (CALENDAR) OPERATIONS =====

// Get all schedules
if ($action === 'get_schedules') {
    $result = $conn->query("SELECT id, subject, instructor, schedule_time, schedule_date, room, mode, notes FROM schedule WHERE user_id = $user_id ORDER BY schedule_date, schedule_time");
    $schedules = [];
    while ($row = $result->fetch_assoc()) {
        $schedules[] = $row;
    }
    echo json_encode(['status' => 'success', 'schedules' => $schedules]);
    exit;
}

// Add schedule
if ($action === 'add_schedule') {
    $subject = $conn->real_escape_string($_POST['subject']);
    $instructor = $conn->real_escape_string($_POST['instructor'] ?? '');
    $schedule_time = $conn->real_escape_string($_POST['schedule_time']);
    $schedule_date = !empty($_POST['schedule_date']) ? $conn->real_escape_string($_POST['schedule_date']) : NULL;
    $room = $conn->real_escape_string($_POST['room'] ?? '');
    $mode = $conn->real_escape_string($_POST['mode'] ?? 'Face-to-Face');
    $notes = $conn->real_escape_string($_POST['notes'] ?? '');
    
    $dateValue = $schedule_date ? "'$schedule_date'" : "NULL";
    
    $sql = "INSERT INTO schedule (user_id, subject, instructor, schedule_time, schedule_date, room, mode, notes) 
            VALUES ($user_id, '$subject', '$instructor', '$schedule_time', $dateValue, '$room', '$mode', '$notes')";
    
    if ($conn->query($sql)) {
        echo json_encode(['status' => 'success', 'message' => 'Class added', 'id' => $conn->insert_id]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error adding class: ' . $conn->error]);
    }
    exit;
}

// Update schedule
if ($action === 'update_schedule') {
    $schedule_id = intval($_POST['schedule_id']);
    $subject = $conn->real_escape_string($_POST['subject']);
    $instructor = $conn->real_escape_string($_POST['instructor'] ?? '');
    $schedule_time = $conn->real_escape_string($_POST['schedule_time']);
    $schedule_date = !empty($_POST['schedule_date']) ? $conn->real_escape_string($_POST['schedule_date']) : NULL;
    $room = $conn->real_escape_string($_POST['room'] ?? '');
    $mode = $conn->real_escape_string($_POST['mode'] ?? 'Face-to-Face');
    $notes = $conn->real_escape_string($_POST['notes'] ?? '');
    
    $dateValue = $schedule_date ? "'$schedule_date'" : "NULL";
    
    $sql = "UPDATE schedule SET subject = '$subject', instructor = '$instructor', schedule_time = '$schedule_time', 
            schedule_date = $dateValue, room = '$room', mode = '$mode', notes = '$notes' 
            WHERE id = $schedule_id AND user_id = $user_id";
    
    if ($conn->query($sql)) {
        echo json_encode(['status' => 'success', 'message' => 'Class updated']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error updating class: ' . $conn->error]);
    }
    exit;
}

// Delete schedule
if ($action === 'delete_schedule') {
    $schedule_id = intval($_POST['schedule_id']);
    
    if ($conn->query("DELETE FROM schedule WHERE id = $schedule_id AND user_id = $user_id")) {
        echo json_encode(['status' => 'success', 'message' => 'Class deleted']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error deleting class: ' . $conn->error]);
    }
    exit;
}

// ===== NOTIFICATION OPERATIONS =====

// Get notifications
if ($action === 'get_notifications') {
    // Return only unread notifications so that once a notification is marked as read
    // it disappears from the dropdown and the counter naturally decreases.
    $result = $conn->query("SELECT id, message, status, created_at FROM notifications WHERE user_id = $user_id AND status = 'unread' ORDER BY created_at DESC LIMIT 50");
    $notifications = [];
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }
    echo json_encode(['status' => 'success', 'notifications' => $notifications]);
    exit;
}

// Add notification
if ($action === 'add_notification') {
    $message = $conn->real_escape_string($_POST['message']);
    
    $sql = "INSERT INTO notifications (user_id, message) VALUES ($user_id, '$message')";
    
    if ($conn->query($sql)) {
        echo json_encode(['status' => 'success', 'message' => 'Notification added']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error adding notification: ' . $conn->error]);
    }
    exit;
}

// Mark notification as read
if ($action === 'mark_notification_read') {
    $notification_id = intval($_POST['notification_id']);
    
    if ($conn->query("UPDATE notifications SET status = 'read' WHERE id = $notification_id AND user_id = $user_id")) {
        echo json_encode(['status' => 'success', 'message' => 'Notification marked as read']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error updating notification: ' . $conn->error]);
    }
    exit;
}

// Get dashboard summary
if ($action === 'get_dashboard_summary') {
    // Get upcoming events (next 5)
    $events = $conn->query("SELECT event_name, event_date, event_time FROM events WHERE user_id = $user_id AND event_date >= CURDATE() ORDER BY event_date, event_time LIMIT 5");
    $upcomingEvents = [];
    while ($row = $events->fetch_assoc()) {
        $upcomingEvents[] = $row;
    }
    
    // Get todo counts
    $pendingCount = $conn->query("SELECT COUNT(*) as count FROM todo_list WHERE user_id = $user_id AND status = 'Pending'")->fetch_assoc()['count'];
    $doneCount = $conn->query("SELECT COUNT(*) as count FROM todo_list WHERE user_id = $user_id AND status = 'Done'")->fetch_assoc()['count'];
    $missingCount = $conn->query("SELECT COUNT(*) as count FROM todo_list WHERE user_id = $user_id AND status = 'Missing'")->fetch_assoc()['count'];
    
    echo json_encode([
        'status' => 'success',
        'upcomingEvents' => $upcomingEvents,
        'todoCounts' => [
            'pending' => $pendingCount,
            'done' => $doneCount,
            'missing' => $missingCount
        ]
    ]);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
?>