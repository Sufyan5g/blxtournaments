<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/db.php';
header('Content-Type: application/json');

$response = ['success' => false, 'updates' => []];

$tournament_id = isset($_GET['tournament_id']) ? intval($_GET['tournament_id']) : 0;

if ($tournament_id > 0) {
    // FIX: The column name is 'message'. Reverted this to match the database and the admin panel fix.
    $stmt = $conn->prepare("SELECT message, created_at FROM tournament_updates WHERE tournament_id = ? ORDER BY created_at DESC");
    if ($stmt) {
        $stmt->bind_param("i", $tournament_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $updates = [];
        while ($row = $result->fetch_assoc()) {
            $updates[] = [
                // FIX: Changed back to 'message' to read from the correct column.
                'message' => nl2br(htmlspecialchars($row['message'])),
                'time' => date('d M, Y h:i A', strtotime($row['created_at']))
            ];
        }
        $response['success'] = true;
        $response['updates'] = $updates;
        $stmt->close();
    } else {
        $response['message'] = 'Failed to prepare statement.';
    }
} else {
    $response['message'] = 'Invalid Tournament ID.';
}

// conn->close() ko comment kar diya, kyunki yeh dusri jagah par masla kar sakta hai
// $conn->close();
echo json_encode($response);
?>