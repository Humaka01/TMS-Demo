<?php
require_once 'db_connection.php';
session_start();
$user_id = $_SESSION['user_id'];

try {
    // Query to fetch user-related information from the database
    $query = "SELECT id, name, email FROM users WHERE id = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $tasks = fetchTasks($user_id);
    $pdo = null;

    // Return user data and tasks as JSON
    header('Content-Type: application/json');
    echo json_encode(['user' => $user, 'tasks' => $tasks]);
} catch (PDOException $e) {
    $error = array('error' => 'Error fetching user data');
    header('Content-Type: application/json');
    echo json_encode($error);
}

function fetchTasks($user_id) {
    global $pdo;
    $query = "SELECT id, title, description, due_date, status FROM tasks WHERE assignee_id = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$user_id]);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $tasks;
}
?>