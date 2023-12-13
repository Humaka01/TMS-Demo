<?php
require_once 'db_connection.php';

session_start();
$userID = $_SESSION['user_id'];

// Query to retrieve user information based on user ID
$query = "SELECT name, email FROM users WHERE id = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$userID]);
$userInfo = $stmt->fetch(PDO::FETCH_ASSOC);

// Return user information as JSON
header('Content-Type: application/json');
echo json_encode($userInfo);
?>