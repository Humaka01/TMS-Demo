<?php
require_once 'db_connection.php';

if (!isset($_GET['task_id'])) {
    header("HTTP/1.1 400 Bad Request");
    exit();
}

$taskId = $_GET['task_id'];

$query = "SELECT id, file_name FROM attachments WHERE task_id = :task_id";
$stmt = $pdo->prepare($query);
$stmt->bindParam(':task_id', $taskId, PDO::PARAM_INT);
$stmt->execute();

$attachments = $stmt->fetchAll(PDO::FETCH_ASSOC);
$pdo = null;

// Return attachment details as JSON response
header('Content-Type: application/json');
echo json_encode(['attachments' => $attachments]);
?>