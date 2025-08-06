<?php
// FILE: search_users.php (UPDATED TO SHOW COINS)
require_once 'includes/db.php';
header('Content-Type: application/json');

if (!isset($_GET['q']) || !isset($_GET['tournament_id'])) {
    echo json_encode(['success' => false, 'users' => []]);
    exit();
}

$current_user_id = $_SESSION['user_id'] ?? 0;
$query = sanitize_input($_GET['q']);
$tournament_id = intval($_GET['tournament_id']);
$search_term = "%" . $query . "%";

// Tournament ki entry fee get karein
$stmt_fee = $conn->prepare("SELECT entryFee FROM tournaments WHERE id = ?");
$stmt_fee->bind_param("i", $tournament_id);
$stmt_fee->execute();
$tournament_fee = $stmt_fee->get_result()->fetch_assoc()['entryFee'] ?? 0;

// Find users who are NOT the current user and NOT already in this tournament
$stmt = $conn->prepare("
    SELECT u.id, u.name, u.uid, u.avatar_url, u.coins
    FROM users u
    WHERE (u.name LIKE ? OR u.uid LIKE ?) 
    AND u.id != ?
    AND NOT EXISTS (
        SELECT 1 FROM participants p WHERE p.user_id = u.id AND p.tournament_id = ?
    )
    LIMIT 10
");
$stmt->bind_param("ssii", $search_term, $search_term, $current_user_id, $tournament_id);
$stmt->execute();
$result = $stmt->get_result();
$users = [];
while($user = $result->fetch_assoc()){
    $user['has_enough_coins'] = ($user['coins'] >= $tournament_fee);
    $users[] = $user;
}

echo json_encode(['success' => true, 'users' => $users]);
?>