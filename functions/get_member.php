<?php
require 'db.php';
header('Content-Type: application/json');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'get_member') {
        if (isset($_POST['id'])) {
            $id = intval($_POST['id']);
            $sql = "SELECT id, name, whatsapp, current_year_of_study, manifest_jewel FROM members WHERE id = ?";
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
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}
?>
