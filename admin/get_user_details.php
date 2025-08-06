<?php
require_once '../includes/db.php';
header('Content-Type: application/json');

$response = ['success' => false];
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

if ($user_id > 0) {
    // UPDATED: Added 'freefire_id' to the SELECT query
    $stmt = $conn->prepare("SELECT id, name, email, phone, freefire_id, coins, role, created_at FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($user = $result->fetch_assoc()) {
        $user['created_at_formatted'] = date('d M, Y h:i A', strtotime($user['created_at']));
        $response['success'] = true;
        $response['data'] = $user;
    } else {
        $response['message'] = 'User not found.';
    }
    $stmt->close();
} else {
    $response['message'] = 'Invalid User ID.';
}

echo json_encode($response);
?>