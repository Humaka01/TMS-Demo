<?php
require_once 'db_connection.php';

if (!isset($_GET['attachment_id'])) {
    header("HTTP/1.1 400 Bad Request");
    exit();
}

$attachmentId = $_GET['attachment_id'];

$query = "SELECT file_name, data FROM attachments WHERE id = :attachment_id";
$stmt = $pdo->prepare($query);
$stmt->bindParam(':attachment_id', $attachmentId, PDO::PARAM_INT);
$stmt->execute();

$attachment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$attachment) {
    header("HTTP/1.1 404 Not Found");
    exit();
}

$fileName = $attachment['file_name'];
$fileData = $attachment['data'];

// Set appropriate headers for download
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Content-Length: ' . strlen($fileData));

// Output the file data for download
echo $fileData;

exit();
?>
