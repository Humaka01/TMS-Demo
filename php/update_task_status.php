<?php
require_once 'db_connection.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $data = json_decode(file_get_contents("php://input"));
    $taskId = $data->task_id;
    $newStatus = $data->new_status;

    session_start();
    $currentUserId = $_SESSION['user_id'];

    try {
        // Get the user name of the assignee
        $queryGetAssignee = "SELECT assignee_id FROM tasks WHERE id = :task_id";
        $stmtGetAssignee = $pdo->prepare($queryGetAssignee);
        $stmtGetAssignee->bindValue(':task_id', $taskId, PDO::PARAM_INT);
        $stmtGetAssignee->execute();
        $assigneeData = $stmtGetAssignee->fetch(PDO::FETCH_ASSOC);
        $assigneeId = $assigneeData['assignee_id'];

        // Update the task status
        $sql = "UPDATE tasks SET status = :new_status WHERE id = :task_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(":new_status", $newStatus);
        $stmt->bindParam(":task_id", $taskId);
        $stmt->execute();

        // Get user name
        $queryGetUserName = "SELECT name FROM users WHERE id = :user_id";
        $stmtGetUserName = $pdo->prepare($queryGetUserName);
        $stmtGetUserName->bindValue(':user_id', $currentUserId, PDO::PARAM_INT);
        $stmtGetUserName->execute();
        $userData = $stmtGetUserName->fetch(PDO::FETCH_ASSOC);
        $currentUserName = $userData['name'];

        // Update the last_edit_by
        $queryUpdateLastEdit = "UPDATE tasks SET last_edit_by = :currentUserId || ': ' || :currentUserName WHERE id = :task_id";
        $stmtUpdateLastEdit = $pdo->prepare($queryUpdateLastEdit);
        $stmtUpdateLastEdit->bindValue(':currentUserId', $currentUserId, PDO::PARAM_INT);
        $stmtUpdateLastEdit->bindValue(':currentUserName', $currentUserName, PDO::PARAM_STR);
        $stmtUpdateLastEdit->bindValue(':task_id', $taskId, PDO::PARAM_INT);
        $resultUpdateLastEdit = $stmtUpdateLastEdit->execute();

        if ($assigneeId != $currentUserId) {
            // Insert a new row in the notifications table
            $notificationType = "status";
            $sqlInsertNotification = "INSERT INTO notifications (task_id, user_id, notification_type, new) VALUES (:task_id, :user_id, :notification_type, 1)";
            $stmtInsertNotification = $pdo->prepare($sqlInsertNotification);
            $stmtInsertNotification->bindParam(":task_id", $taskId, PDO::PARAM_INT);
            $stmtInsertNotification->bindParam(":user_id", $assigneeId, PDO::PARAM_INT);
            $stmtInsertNotification->bindParam(":notification_type", $notificationType, PDO::PARAM_STR);
            $stmtInsertNotification->execute();
        }

        // Check if the update was successful
        if ($stmt->rowCount() > 0) {
            $response = [
                "status" => "",
                "message" => "Task status updated successfully."
            ];
        } else {
            $response = [
                "status" => "error",
                "message" => "Task not found or status unchanged."
            ];
        }

        echo json_encode($response);
    } catch (PDOException $e) {
        $response = [
            "status" => "error",
            "message" => "Database error: " . $e->getMessage()
        ];
        echo json_encode($response);
    }
} else {
    // Handle invalid HTTP request method (not POST)
    http_response_code(405); // Method Not Allowed
    echo json_encode([
        "status" => "error",
        "message" => "Invalid request method."
    ]);
}
?>