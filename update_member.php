// update_member.php
<?php
require 'db.php';
header('Content-Type: application/json');

$id = isset($_POST['id']) ? filter_var($_POST['id'], FILTER_VALIDATE_INT) : 0;
$name = isset($_POST['name']) ? filter_var($_POST['name'], FILTER_SANITIZE_STRING) : '';
$whatsapp = isset($_POST['whatsapp']) ? filter_var($_POST['whatsapp'], FILTER_SANITIZE_STRING) : '';
$yearOfStudy = isset($_POST['current_year_of_study']) ? filter_var($_POST['current_year_of_study'], FILTER_VALIDATE_INT) : 0;

$sql = "UPDATE members SET name = ?, whatsapp = ?, current_year_of_study = ? WHERE id = ?";
$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("ssii", $name, $whatsapp, $yearOfStudy, $id);
    $result = $stmt->execute();

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Member updated successfully']);
    } else {
        // Log error for debugging
        error_log("Failed to update member: " . $stmt->error);
        echo json_encode(['success' => false, 'message' => 'Failed to update member']);
    }

    $stmt->close();
} else {
    // Log error for debugging
    error_log("SQL preparation error: " . $conn->error);
    echo json_encode(['success' => false, 'message' => 'SQL preparation error']);
}

$conn->close();
?>