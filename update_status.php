// update_status.php
<?php
require 'db.php';
header('Content-Type: application/json');

$id = isset($_POST['id']) ? filter_var($_POST['id'], FILTER_VALIDATE_INT) : 0;
$newStatus = isset($_POST['status']) ? filter_var($_POST['status'], FILTER_SANITIZE_STRING) : '';

$sql = "UPDATE members SET status = ? WHERE id = ?";
$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("si", $newStatus, $id);
    $result = $stmt->execute();

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
    } else {
        // Log error for debugging
        error_log("Failed to update status: " . $stmt->error);
        echo json_encode(['success' => false, 'message' => 'Failed to update status']);
    }

    $stmt->close();
} else {
    // Log error for debugging
    error_log("SQL preparation error: " . $conn->error);
    echo json_encode(['success' => false, 'message' => 'SQL preparation error']);
}

$conn->close();
?>