<?php
require_once 'db_connection.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("HTTP/1.1 401 Unauthorized");
    echo json_encode(['error' => 'User not authenticated']);
    exit();
}

$userId = $_SESSION['user_id'];

if (!isset($_POST['task_id'])) {
    header("HTTP/1.1 400 Bad Request");
    echo json_encode(['error' => 'Missing task_id']);
    exit();
}

$taskId = $_POST['task_id'];

// Check if a file was uploaded
if (!isset($_FILES['attachment'])) {
    header("HTTP/1.1 400 Bad Request");
    echo json_encode(['error' => 'No file uploaded']);
    exit();
}

$file = $_FILES['attachment'];
$fileName = $file['name'];
$fileData = file_get_contents($file['tmp_name']);

// Retrieve the user's name from the database based on their user ID
$query = "SELECT name FROM users WHERE id = :user_id";
$stmt = $pdo->prepare($query);
$stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
$result = $stmt->execute();

if (!$result) {
    header("HTTP/1.1 500 Internal Server Error");
    echo json_encode(['error' => 'Failed to fetch user information']);
    exit();
}

$row = $stmt->fetch(PDO::FETCH_ASSOC);
$currentUserId = $_SESSION['user_id'];
$userName = $row['name'];

// Prepare and execute the SQL query to insert the attachment
$query = "INSERT INTO attachments (task_id, file_name, data) VALUES (:task_id, :file_name, :file_data)";
$stmt = $pdo->prepare($query);
$stmt->bindParam(':task_id', $taskId, PDO::PARAM_INT);
$stmt->bindParam(':file_name', $fileName, PDO::PARAM_STR);
$stmt->bindParam(':file_data', $fileData, PDO::PARAM_LOB);
$result = $stmt->execute();

if (!$result) {
    header("HTTP/1.1 500 Internal Server Error");
    echo json_encode(['error' => 'Failed to upload file']);
    exit();
}

// Update the last_edit_by column in the tasks table with the desired format
$lastEditBy = "$userId: $userName"; // Concatenate user ID and user name

$query = "UPDATE tasks SET last_edit_by = :last_edit_by WHERE id = :task_id";
$stmt = $pdo->prepare($query);
$stmt->bindParam(':task_id', $taskId, PDO::PARAM_INT);
$stmt->bindParam(':last_edit_by', $lastEditBy, PDO::PARAM_STR);
$result = $stmt->execute();

// Retrieve the assignee_id from the tasks table
$queryGetAssigneeId = "SELECT assignee_id FROM tasks WHERE id = :task_id";
$stmtGetAssigneeId = $pdo->prepare($queryGetAssigneeId);
$stmtGetAssigneeId->bindParam(':task_id', $taskId, PDO::PARAM_INT);
$resultGetAssigneeId = $stmtGetAssigneeId->execute();
$rowAssigneeId = $stmtGetAssigneeId->fetch(PDO::FETCH_ASSOC);
$assigneeId = $rowAssigneeId['assignee_id'];

if ($assigneeId != $currentUserId) {
    // Insert a new row in the notifications table
    $notificationType = "attachment";
    $sqlInsertNotification = "INSERT INTO notifications (task_id, user_id, notification_type, new) VALUES (:task_id, :user_id, :notification_type, 1)";
    $stmtInsertNotification = $pdo->prepare($sqlInsertNotification);
    $stmtInsertNotification->bindParam(":task_id", $taskId, PDO::PARAM_INT);
    $stmtInsertNotification->bindParam(":user_id", $assigneeId, PDO::PARAM_INT);
    $stmtInsertNotification->bindParam(":notification_type", $notificationType, PDO::PARAM_STR);
    $stmtInsertNotification->execute();
}

if (!$result) {
    header("HTTP/1.1 500 Internal Server Error");
    echo json_encode(['error' => 'Failed to update last_edit_by']);
    exit();
}

$pdo = null;

echo json_encode(['message' => 'File uploaded successfully']);
?>