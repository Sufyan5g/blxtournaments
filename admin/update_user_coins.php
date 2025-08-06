<?php
require_once '../includes/db.php';
header('Content-Type: application/json');

$response = ['success' => false];

// You should add a security check here to ensure only admins can run this
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $amount_to_deduct = isset($_POST['deduct_amount']) ? intval($_POST['deduct_amount']) : 0;

    if ($user_id > 0 && $amount_to_deduct > 0) {
        // Use a transaction for safety
        $conn->begin_transaction();
        try {
            // Fetch current coins to prevent them from going negative
            $stmt_check = $conn->prepare("SELECT coins FROM users WHERE id = ?");
            $stmt_check->bind_param("i", $user_id);
            $stmt_check->execute();
            $user = $stmt_check->get_result()->fetch_assoc();

            if ($user && $user['coins'] >= $amount_to_deduct) {
                $stmt = $conn->prepare("UPDATE users SET coins = coins - ? WHERE id = ?");
                $stmt->bind_param("ii", $amount_to_deduct, $user_id);
                $stmt->execute();
                
                if ($stmt->affected_rows > 0) {
                    $conn->commit();
                    $response['success'] = true;
                    $response['message'] = 'Coins deducted successfully!';
                } else {
                    $conn->rollback();
                    $response['message'] = 'Failed to update coins.';
                }
            } else {
                 $conn->rollback();
                 $response['message'] = 'Cannot deduct. Insufficient coins or user not found.';
            }

        } catch (Exception $e) {
            $conn->rollback();
            $response['message'] = 'An error occurred: ' . $e->getMessage();
        }
    } else {
        $response['message'] = 'Invalid data provided.';
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
?>