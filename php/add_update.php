<?php
require_once 'db_connection.php';

// Check if the necessary POST parameters are set
if (!isset($_POST['task_id']) || !isset($_POST['update_text'])) {
    header("HTTP/1.1 400 Bad Request");
    echo json_encode(['error' => 'Missing task_id or update_text']);
    exit();
}

$taskId = $_POST['task_id'];
$updateText = $_POST['update_text'];

// Start the session (if not already started)
session_start();

// Check if the user is logged in and get their user ID from the session
if (isset($_SESSION['user_id'])) {
    $currentUserId = $_SESSION['user_id'];

    // Insert a new update record into the updates table
    $queryInsertUpdate = "INSERT INTO updates (task_id, update_text, created_at) VALUES (:task_id, :update_text, CURRENT_TIMESTAMP)";
    $stmtInsertUpdate = $pdo->prepare($queryInsertUpdate);
    $stmtInsertUpdate->bindValue(':task_id', $taskId, PDO::PARAM_INT);
    $stmtInsertUpdate->bindValue(':update_text', $updateText, PDO::PARAM_STR);
    $resultInsertUpdate = $stmtInsertUpdate->execute();

    // Get user name
    $queryGetUserName = "SELECT name FROM users WHERE id= :user_id";
    $stmtGetUserName = $pdo->prepare($queryGetUserName);
    $stmtGetUserName->bindValue(':user_id', $currentUserId, PDO::PARAM_INT);
    $resultGetUserName = $stmtGetUserName->execute();
    $userData = $stmtGetUserName->fetch(PDO::FETCH_ASSOC);
    $currentUserName = $userData['name'];

    // Update the last_edit_by
    $queryUpdateLastEdit = "UPDATE tasks SET last_edit_by = :currentUserId || ': ' || :currentUserName WHERE id = :task_id";
    $stmtUpdateLastEdit = $pdo->prepare($queryUpdateLastEdit);
    $stmtUpdateLastEdit->bindValue(':currentUserId', $currentUserId, PDO::PARAM_INT);
    $stmtUpdateLastEdit->bindValue(':currentUserName', $currentUserName, PDO::PARAM_STR);
    $stmtUpdateLastEdit->bindValue(':task_id', $taskId, PDO::PARAM_INT);
    $resultUpdateLastEdit = $stmtUpdateLastEdit->execute();

    // Retrieve the assignee_id from the tasks table
    $queryGetAssigneeId = "SELECT assignee_id FROM tasks WHERE id = :task_id";
    $stmtGetAssigneeId = $pdo->prepare($queryGetAssigneeId);
    $stmtGetAssigneeId->bindParam(':task_id', $taskId, PDO::PARAM_INT);
    $resultGetAssigneeId = $stmtGetAssigneeId->execute();
    $rowAssigneeId = $stmtGetAssigneeId->fetch(PDO::FETCH_ASSOC);
    $assigneeId = $rowAssigneeId['assignee_id'];


    if ($assigneeId != $currentUserId) {
        // Insert a new row in the notifications table
        $notificationType = "update";
        $sqlInsertNotification = "INSERT INTO notifications (task_id, user_id, notification_type, new) VALUES (:task_id, :user_id, :notification_type, 1)";
        $stmtInsertNotification = $pdo->prepare($sqlInsertNotification);
        $stmtInsertNotification->bindParam(":task_id", $taskId, PDO::PARAM_INT);
        $stmtInsertNotification->bindParam(":user_id", $assigneeId, PDO::PARAM_INT);
        $stmtInsertNotification->bindParam(":notification_type", $notificationType, PDO::PARAM_STR);
        $stmtInsertNotification->execute();
    }
    
    if ($resultInsertUpdate && $resultUpdateLastEdit) {
        // Set the response content type to JSON
        header('Content-Type: application/json');

        echo json_encode(['message' => 'Update added successfully']);
    } else {
        header("HTTP/1.1 500 Internal Server Error");
        echo json_encode(['error' => 'Failed to insert update']);
    }
} else {
    header("HTTP/1.1 401 Unauthorized");
    echo json_encode(['error' => 'User is not logged in']);
}
?>