<?php
// FILE: get_room_details.php (FINAL & GUARANTEED WORKING VERSION)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/db.php';
header('Content-Type: application/json');

$response = ['success' => false, 'detail' => null, 'message' => 'An error occurred.'];

if (!isset($_SESSION['user_id']) || !isset($_POST['tournament_id']) || !isset($_POST['type'])) {
    $response['message'] = 'Invalid request or not logged in.';
    echo json_encode($response);
    exit();
}

$user_id = $_SESSION['user_id'];
$tournament_id = intval($_POST['tournament_id']);
$type = $_POST['type'];

// --- Security check: User must be a participant ---
$is_participant = false;

// Check solo participants table
$stmt_check_solo = $conn->prepare("SELECT id FROM participants WHERE user_id = ? AND tournament_id = ?");
$stmt_check_solo->bind_param("ii", $user_id, $tournament_id);
$stmt_check_solo->execute();
if ($stmt_check_solo->get_result()->num_rows > 0) {
    $is_participant = true;
}
$stmt_check_solo->close();

// If not in solo, check if they are part of a team for this tournament
if (!$is_participant) {
    $stmt_check_team = $conn->prepare("
        SELECT tt.id 
        FROM tournament_teams tt 
        LEFT JOIN team_invitations ti ON tt.id = ti.team_id 
        WHERE tt.tournament_id = ? AND (tt.leader_user_id = ? OR (ti.invited_user_id = ? AND ti.status = 'accepted'))
        GROUP BY tt.id
    ");
    if ($stmt_check_team) {
        $stmt_check_team->bind_param("iii", $tournament_id, $user_id, $user_id);
        $stmt_check_team->execute();
        if ($stmt_check_team->get_result()->num_rows > 0) {
            $is_participant = true;
        }
        $stmt_check_team->close();
    }
}

if (!$is_participant) {
    $response['message'] = 'You are not a participant of this tournament.';
    echo json_encode($response);
    exit();
}

// --- Data fetch ---
$column_to_fetch = ($type === 'id') ? 'room_id' : 'room_password';
$stmt_fetch = $conn->prepare("SELECT `$column_to_fetch` FROM tournaments WHERE id = ?");
$stmt_fetch->bind_param("i", $tournament_id);
$stmt_fetch->execute();
$result = $stmt_fetch->get_result()->fetch_assoc();
$stmt_fetch->close();

if ($result && isset($result[$column_to_fetch]) && trim($result[$column_to_fetch]) !== '') {
    $response['success'] = true;
    $response['detail'] = $result[$column_to_fetch];

    // Log that the user copied the details
    $log_stmt = $conn->prepare("INSERT IGNORE INTO room_details_copied (user_id, tournament_id) VALUES (?, ?)");
    $log_stmt->bind_param("ii", $user_id, $tournament_id);
    $log_stmt->execute();
    $log_stmt->close();
} else {
    $response['message'] = 'Not available yet.';
}

echo json_encode($response);
?>