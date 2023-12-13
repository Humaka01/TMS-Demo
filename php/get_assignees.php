<?php
require_once 'db_connection.php';

try {
    // Query to fetch assignees from the database
    $query = "SELECT id, name FROM users"; // Update with your actual table name and columns
    $stmt = $pdo->query($query);
    $assignees = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Close the database connection
    $pdo = null;

    // Return assignee data as JSON
    header('Content-Type: application/json');
    echo json_encode($assignees);
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>