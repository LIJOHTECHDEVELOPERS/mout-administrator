<?php
// add_account.php
require_once 'db_connection.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $balance = $_POST['balance'];
    $icon = $_POST['icon'];

    // Validate input
    if (empty($name) || !is_numeric($balance)) {
        echo json_encode(['success' => false, 'error' => 'Invalid input']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO accounts (name, balance, icon) VALUES (?, ?, ?)");
        $result = $stmt->execute([$name, $balance, $icon]);

        if ($result) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to insert account']);
        }
    } catch (PDOException $e) {
        error_log('Database error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Database error. Please try again later.']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}