<?php
require_once 'db_connection.php';

$input = json_decode(file_get_contents('php://input'), true);

// Check if required fields are provided
if (
    isset($input['title']) &&
    isset($input['description']) &&
    isset($input['due_date']) &&
    isset($input['status']) &&
    isset($input['assignee_id'])
) {
    // Extract the data from the JSON input
    $title = $input['title'];
    $description = $input['description'];
    $dueDate = $input['due_date'];
    $status = $input['status'];
    $assigneeId = $input['assignee_id'];

    // Get the user ID and name from the session
    session_start();
    $userId = $_SESSION['user_id'];
    $userName = $_SESSION['name'];

    try {
        // Query to insert task data into the database
        $query = "INSERT INTO tasks (title, description, due_date, status, assignee_id, last_edit_by, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($query);

        $stmt->execute([$title, $description, $dueDate, $status, $assigneeId, $userId . ': ' . $userName, $userId . ': ' . $userName]);

        if ($assigneeId != $userId) {
            // Retrieve the newly inserted task_id (SQLite-specific)
            $task_id = $pdo->lastInsertId();

            $notificationType = "new";
            $sqlInsertNotification = "INSERT INTO notifications (task_id, user_id, notification_type, new) 
                                      VALUES (:task_id, :user_id, :notification_type, 1)";
            $stmtInsertNotification = $pdo->prepare($sqlInsertNotification);
            $stmtInsertNotification->bindParam(":task_id", $task_id, PDO::PARAM_INT);
            $stmtInsertNotification->bindParam(":user_id", $assigneeId, PDO::PARAM_INT);
            $stmtInsertNotification->bindParam(":notification_type", $notificationType, PDO::PARAM_STR);
            $stmtInsertNotification->execute();
        }

        // Return a success message
        header('Content-Type: application/json');
        echo json_encode(['message' => 'Task created successfully']);
    } catch (PDOException $e) {
        // Handle the exception here
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Error creating task: ' . $e->getMessage()]);
    }
} else {
    // Return an error message if required fields are missing
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Missing or empty input fields']);
}
?>