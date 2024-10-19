<?php
require 'db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $sql = "SELECT name, whatsapp, current_year_of_study FROM members WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                echo json_encode(['success' => true, 'member' => $result->fetch_assoc()]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Member not found']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'SQL preparation error']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'No ID provided']);
    }
}
?>
