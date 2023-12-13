<?php
require_once 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    try {
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $query = "SELECT id, name, password FROM users WHERE email = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && $password === $user['password']) {
            session_start();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            
            // Redirect to the dashboard
            header('Location: ../dashboard.html');
            exit();
        } else {
            header('Location: ../index.html?loginFailed=true');
            exit();
        }

        $pdo = null;

    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>