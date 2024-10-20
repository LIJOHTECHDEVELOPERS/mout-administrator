<?php
session_start();
require_once 'config.php';
require_once 'classes/Auth.php';

$auth = new Auth($db);

if (!$auth->isLoggedIn()) {
    http_response_code(403);
    exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

$user = $auth->getUser();
$reportId = $_POST['id'] ?? null;

if (!$reportId) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'message' => 'Invalid report ID']));
}

try {
    $stmt = $db->prepare("UPDATE reports SET status = 'completed' WHERE id = ? AND user_id = ?");
    $result = $stmt->execute([$reportId, $user['id']]);

    if ($result && $stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Report marked as completed successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Unable to complete the report. It may not exist or you may not have permission to modify it.']);
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while completing the report.']);
}