<?php
// NAYI FILE: claim_reward.php
require_once 'includes/db.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'An error occurred.'];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'You must be logged in to claim rewards.';
    echo json_encode($response);
    exit();
}

if (!isset($_POST['level'])) {
    $response['message'] = 'Invalid request.';
    echo json_encode($response);
    exit();
}

$user_id = $_SESSION['user_id'];
$level_to_claim = intval($_POST['level']);

// Transaction for safety
$conn->begin_transaction();
try {
    // Get user's current level and last claimed reward
    $stmt_user = $conn->prepare("SELECT level, last_reward_claimed_level FROM users WHERE id = ? FOR UPDATE");
    $stmt_user->bind_param("i", $user_id);
    $stmt_user->execute();
    $user = $stmt_user->get_result()->fetch_assoc();

    // Get reward details
    $stmt_reward = $conn->prepare("SELECT reward_coins FROM level_rewards WHERE level_milestone = ?");
    $stmt_reward->bind_param("i", $level_to_claim);
    $stmt_reward->execute();
    $reward = $stmt_reward->get_result()->fetch_assoc();

    if (!$user || !$reward) {
        throw new Exception("Invalid data.");
    }

    // --- Validation Checks ---
    if ($user['level'] < $level_to_claim) {
        throw new Exception("You have not reached this level yet.");
    }
    if ($user['last_reward_claimed_level'] >= $level_to_claim) {
        throw new Exception("You have already claimed this or a higher level reward.");
    }

    // All checks passed, award the reward
    $reward_coins = $reward['reward_coins'];
    
    $stmt_update = $conn->prepare("UPDATE users SET coins = coins + ?, last_reward_claimed_level = ? WHERE id = ?");
    $stmt_update->bind_param("iii", $reward_coins, $level_to_claim, $user_id);
    $stmt_update->execute();

    $conn->commit();
    
    // Get new total coins to send back to the frontend
    $result_new_coins = $conn->query("SELECT coins FROM users WHERE id = $user_id");
    $new_total_coins = $result_new_coins->fetch_assoc()['coins'];

    $response['success'] = true;
    $response['message'] = "You have successfully claimed " . $reward_coins . " coins!";
    $response['new_coin_total'] = $new_total_coins;

} catch (Exception $e) {
    $conn->rollback();
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>