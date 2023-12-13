<?php
require_once 'db_connection.php';

// Start or resume the session
session_start();

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    
    // Check for unread notifications
    $sql = "SELECT COUNT(*) AS unread_count FROM notifications WHERE user_id = :user_id AND new = 1";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $unreadCount = $result['unread_count'];
    
    // Send the result as JSON
    header('Content-Type: application/json');
    echo json_encode(['unread_count' => $unreadCount]);
} else {
    // User is not logged in
    echo json_encode(['unread_count' => 0]);
}
?>