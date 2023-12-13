<?php
require_once 'db_connection.php';

// Check if the necessary POST parameters are set
if (!isset($_POST['task_id']) || !isset($_POST['comment'])) {
    header("HTTP/1.1 400 Bad Request");
    echo json_encode(['error' => 'Missing task_id or comment']);
    exit();
}

$taskId = $_POST['task_id'];
$comment = $_POST['comment'];

session_start();

// Check if the user is logged in and get their user ID from the session
if (isset($_SESSION['user_id'])) {
    $currentUserId = $_SESSION['user_id'];

    // Fetch the user's name from the database
    $queryFetchUserName = "SELECT name FROM users WHERE id = :user_id";
    $stmtFetchUserName = $pdo->prepare($queryFetchUserName);
    $stmtFetchUserName->bindValue(':user_id', $currentUserId, PDO::PARAM_INT);
    $stmtFetchUserName->execute();
    $userData = $stmtFetchUserName->fetch(PDO::FETCH_ASSOC);

    if ($userData) {
        $currentUserName = $userData['name'];

        // Insert a new comment record into the comments table
        $queryInsertComment = "INSERT INTO comments (task_id, comment, created_at) VALUES (:task_id, :comment, CURRENT_TIMESTAMP)";
        $stmtInsertComment = $pdo->prepare($queryInsertComment);
        $stmtInsertComment->bindValue(':task_id', $taskId, PDO::PARAM_INT);
        $stmtInsertComment->bindValue(':comment', $comment, PDO::PARAM_STR);
        $resultInsertComment = $stmtInsertComment->execute();

        // Update the last_edit_by column in the tasks table
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
            $notificationType = "comment";
            $sqlInsertNotification = "INSERT INTO notifications (task_id, user_id, notification_type, new) VALUES (:task_id, :user_id, :notification_type, 1)";
            $stmtInsertNotification = $pdo->prepare($sqlInsertNotification);
            $stmtInsertNotification->bindParam(":task_id", $taskId, PDO::PARAM_INT);
            $stmtInsertNotification->bindParam(":user_id", $assigneeId, PDO::PARAM_INT);
            $stmtInsertNotification->bindParam(":notification_type", $notificationType, PDO::PARAM_STR);
            $stmtInsertNotification->execute();
        }

        if ($resultInsertComment && $resultUpdateLastEdit) {
            $pdo = null;
            header('Content-Type: application/json');
            echo json_encode(['message' => 'Comment added successfully']);
        } else {
            header("HTTP/1.1 500 Internal Server Error");
            echo json_encode(['error' => 'Failed to insert comment or update last_edit_by']);
        }
    } else {
        header("HTTP/1.1 500 Internal Server Error");
        echo json_encode(['error' => 'Failed to fetch user data']);
    }
} else {
    header("HTTP/1.1 401 Unauthorized");
    echo json_encode(['error' => 'User is not logged in']);
}
?>