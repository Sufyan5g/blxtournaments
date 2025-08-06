<?php
// FILE: replace_teammate.php
require_once 'includes/db.php';
header('Content-Type: application/json');

$response = ['success' => false, 'title' => 'Error', 'message' => 'An unknown error occurred.'];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = "You must be logged in.";
    echo json_encode($response);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $leader_id = $_SESSION['user_id'];
    $team_id = intval($_POST['team_id']);
    $old_user_id = intval($_POST['old_user_id']);
    $new_user_id = intval($_POST['new_user_id']);

    $conn->begin_transaction();
    try {
        // 1. Verify that the current user is the leader of this team
        $stmt_verify = $conn->prepare("SELECT tournament_id FROM tournament_teams WHERE id = ? AND leader_user_id = ?");
        $stmt_verify->bind_param("ii", $team_id, $leader_id);
        $stmt_verify->execute();
        $team_info = $stmt_verify->get_result()->fetch_assoc();

        if (!$team_info) {
            throw new Exception("You are not the leader of this team or the team does not exist.");
        }
        $tournament_id = $team_info['tournament_id'];

        // 2. Find the invitation for the old user
        $stmt_old_invite = $conn->prepare("SELECT id, status FROM team_invitations WHERE team_id = ? AND invited_user_id = ?");
        $stmt_old_invite->bind_param("ii", $team_id, $old_user_id);
        $stmt_old_invite->execute();
        $old_invitation = $stmt_old_invite->get_result()->fetch_assoc();

        if (!$old_invitation) {
            throw new Exception("The player you are trying to replace was not found in your team's invitations.");
        }
        
        // You can only replace players who are 'pending' or 'rejected'
        if ($old_invitation['status'] === 'accepted') {
            throw new Exception("You cannot replace a player who has already accepted the invitation.");
        }
        
        // 3. Check if the new user is already in the tournament
        $stmt_check_new = $conn->prepare("SELECT id FROM participants WHERE user_id = ? AND tournament_id = ?");
        $stmt_check_new->bind_param("ii", $new_user_id, $tournament_id);
        $stmt_check_new->execute();
        if ($stmt_check_new->get_result()->num_rows > 0) {
            throw new Exception("This player has already joined the tournament.");
        }

        // 4. Delete the old invitation
        $stmt_delete = $conn->prepare("DELETE FROM team_invitations WHERE id = ?");
        $stmt_delete->bind_param("i", $old_invitation['id']);
        $stmt_delete->execute();

        // 5. Create a new invitation for the new user
        $stmt_new_invite = $conn->prepare("INSERT INTO team_invitations (team_id, invited_user_id) VALUES (?, ?)");
        $stmt_new_invite->bind_param("ii", $team_id, $new_user_id);
        $stmt_new_invite->execute();
        
        // 6. Log the replacement (optional but good practice)
        $stmt_log = $conn->prepare("INSERT INTO team_replacements (team_id, tournament_id, old_user_id, new_user_id, replaced_by_user_id) VALUES (?, ?, ?, ?, ?)");
        $stmt_log->bind_param("iiiii", $team_id, $tournament_id, $old_user_id, $new_user_id, $leader_id);
        $stmt_log->execute();
        
        $conn->commit();
        $response = ['success' => true, 'title' => 'Player Replaced!', 'message' => 'Invitation has been sent to the new player.'];

    } catch (Exception $e) {
        $conn->rollback();
        $response['message'] = $e->getMessage();
    }
}

echo json_encode($response);
?>