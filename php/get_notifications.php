<?php
require_once 'db_connection.php';

session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get the user ID from the session
$user_id = $_SESSION['user_id'];

// SQL query to retrieve notifications
$sql = "SELECT * FROM notifications WHERE user_id = :user_id ORDER BY id DESC";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

$notificationList = '';

foreach ($notifications as $notification) {
    $notificationType = $notification['notification_type'];
    $taskId = $notification['task_id'];

    // Retrieve last_edit_by information from the tasks table
    $lastEditBy = getLastEditBy($taskId);

    // Generate notification message based on the notification type
    $notificationMessage = generateNotificationMessage($notificationType, $taskId, $lastEditBy);
    $notificationClass = ($notification['new'] == 1) ? 'bold' : ''; // Apply bold styling for unread notifications
    
    // Construct the URL for the task details page
    $taskDetailsURL = "http://localhost/task-details.html?task_id=$taskId";

    // Wrap the notification message in an anchor tag with the task details URL
    $notificationList .= '<a href="' . $taskDetailsURL . '" class="notification-item ' . $notificationClass . '">' . $notificationMessage . '</a>';
}

echo $notificationList;

// Function to get the last_edit_by information for a task
function getLastEditBy($taskId) {
    global $pdo;
    $sql = "SELECT last_edit_by FROM tasks WHERE id = :task_id";
    $stmt = $pdo->prepare($sql); // False positive error, pdo is not null, its initialized in db_connection.php
    $stmt->bindParam(':task_id', $taskId, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    return ($result && isset($result['last_edit_by'])) ? $result['last_edit_by'] : 'Unknown';
}

// Function to generate notification messages based on notification type
function generateNotificationMessage($notificationType, $taskId, $lastEditBy) {
    switch ($notificationType) {
        case 'new':
            return "<b>$lastEditBy</b> has assigned you a new task <b>$taskId</b>";
        case 'update':
            return "<b>($lastEditBy)</b> has added an update on task <b>($taskId)</b>";
        case 'comment':
            return "<b>($lastEditBy)</b> has added a comment on task <b>($taskId)</b>";
        case 'attachment':
            return "<b>($lastEditBy)</b> has uploaded an attachment on task <b>($taskId)</b>";
        case 'status':
            return "<b>($lastEditBy)</b> has updated the status of task <b>($taskId)</b>";
        default:
            return "Unknown notification type for task <b>$taskId</b>";
    }
}
?>