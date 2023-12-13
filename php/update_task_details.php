<?php
require_once 'db_connection.php';

// Check if task_id is provided
if (!isset($_GET['task_id'])) {
    header("HTTP/1.1 400 Bad Request");
    exit();
}

$taskId = $_GET['task_id'];

// Query the database to get task details
$query = "SELECT tasks.*, users.name AS assignee_name
          FROM tasks
          LEFT JOIN users ON tasks.assignee_id = users.id
          WHERE tasks.id = :task_id";
$stmt = $pdo->prepare($query);
$stmt->bindParam(':task_id', $taskId, PDO::PARAM_INT);
$stmt->execute();

$taskDetails = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if the task exists
if ($taskDetails) {
    // Fetch attachment details for the task
    $attachmentQuery = "SELECT id, file_name FROM attachments WHERE task_id = :task_id";
    $attachmentStmt = $pdo->prepare($attachmentQuery);
    $attachmentStmt->bindParam(':task_id', $taskId, PDO::PARAM_INT);
    $attachmentStmt->execute();
    $attachments = $attachmentStmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch comments for the task
    $commentQuery = "SELECT comment, created_at FROM comments WHERE task_id = :task_id ORDER BY created_at ASC";
    $commentStmt = $pdo->prepare($commentQuery);
    $commentStmt->bindParam(':task_id', $taskId, PDO::PARAM_INT);
    $commentStmt->execute();

    $comments = $commentStmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch updates for the task
    $updateQuery = "SELECT update_text, created_at FROM updates WHERE task_id = :task_id ORDER BY created_at ASC";
    $updateStmt = $pdo->prepare($updateQuery);
    $updateStmt->bindParam(':task_id', $taskId, PDO::PARAM_INT);
    $updateStmt->execute();
    $updates = $updateStmt->fetchAll(PDO::FETCH_ASSOC);

    // Add attachments, comments, and updates to the task details
    $taskDetails['attachments'] = $attachments;
    $taskDetails['comments'] = $comments;
    $taskDetails['updates'] = $updates;

    // Update the "new" column in the notifications table to 0
    session_start();
    $currentUserId = $_SESSION['user_id'];
    
    $notificationUpdateQuery = "UPDATE notifications SET new = 0 WHERE task_id = :task_id AND user_id = :user_id";
    $notificationUpdateStmt = $pdo->prepare($notificationUpdateQuery);
    $notificationUpdateStmt->bindParam(':task_id', $taskId, PDO::PARAM_INT);
    $notificationUpdateStmt->bindParam(':user_id', $currentUserId, PDO::PARAM_INT);
    $notificationUpdateStmt->execute();
    
} else {
    header("HTTP/1.1 404 Not Found");
    echo "Task not found";
    exit();
}

$pdo = null;

// Return task details with attachments, comments, and updates as JSON response
header('Content-Type: application/json');
echo json_encode($taskDetails);
