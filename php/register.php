<?php
require_once 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    try {
        $query = "SELECT COUNT(*) FROM users WHERE email = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$email]);
        $emailExists = $stmt->fetchColumn();

        if ($emailExists) {
            $registerFailed = true;
        } else {
            // Insert user data into the "users" table
            $insertQuery = "INSERT INTO users (name, email, password) VALUES (?, ?, ?)";
            $insertStmt = $pdo->prepare($insertQuery);
            $insertStmt->execute([$name, $email, $password]);

            $getIdQuery = "SELECT id FROM users WHERE email = ?";
            $getIdStmt = $pdo->prepare($getIdQuery);
            $getIdStmt->execute([$email]);
            $id = $getIdStmt->fetchColumn();

            session_start();
            $_SESSION['user_id'] = $id;
            $_SESSION['name'] = $name;
            $_SESSION['email'] = $email;
        }
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}

// Redirect based on registration status
if ($registerFailed) {
    header('Location: ../index.html?registerFailed=true');
} else {
    header('Location: ../dashboard.html');
}
exit();
?>