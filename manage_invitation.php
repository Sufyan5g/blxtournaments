<?php
// FILE: manage_invitation.php (FINAL LOGIC WITH LUCKY TEAM NOTIFICATION)

require_once 'includes/db.php';
header('Content-Type: application/json');

$response = ['success' => false, 'title' => 'Error', 'message' => 'An unknown error occurred.'];

if (!isset($_SESSION['user_id']) || !isset($_POST['invitation_id']) || !isset($_POST['action'])) {
    $response['message'] = 'Invalid request.';
    echo json_encode($response);
    exit();
}

$user_id = $_SESSION['user_id']; // Invited user
$invitation_id = intval($_POST['invitation_id']);
$action = $_POST['action'];

$conn->begin_transaction();
try {
    // === NAYI TABDEELI: `tt.is_lucky_team` ko bhi get karein ===
    $stmt = $conn->prepare("
        SELECT 
            ti.*, 
            tt.leader_user_id, 
            tt.tournament_id, 
            tt.team_name, 
            tt.is_lucky_team 
        FROM team_invitations ti 
        JOIN tournament_teams tt ON ti.team_id = tt.id 
        WHERE ti.id = ? AND ti.invited_user_id = ? AND ti.status = 'pending'
    ");
    $stmt->bind_param("ii", $invitation_id, $user_id);
    $stmt->execute();
    $invitation = $stmt->get_result()->fetch_assoc();

    if (!$invitation) {
        throw new Exception("Invitation not found or already responded to.");
    }
    
    $team_id = $invitation['team_id'];
    $leader_id = $invitation['leader_user_id'];
    $tournament_id = $invitation['tournament_id'];
    $team_name = $invitation['team_name'];
    $is_sponsored = $invitation['is_sponsored'];
    $is_lucky_team = $invitation['is_lucky_team']; // Naya variable

    if ($action === 'accept') {
        
        // === NAYI TABDEELI: Message aur Title ke liye naye variables ===
        $message = '';
        $response_title = 'Invitation Accepted!';

        if ($is_lucky_team == 1) {
            // Agar team lucky hai, to sab se pehle yeh message set karo
            $response_title = '🎉 Lucky Join! 🎉';
            $message = "Welcome to the Lucky Team! Your entry is completely free as your team has secured a lucky slot.";
        
        } elseif ($is_sponsored == 1) {
            // Normal sponsored player
            $message = "You have joined the team. Your leader has paid your fee!";
        
        } else {
            // Normal non-sponsored player: fee deduct aur leader ko refund
            $tourney_stmt = $conn->prepare("SELECT entryFee FROM tournaments WHERE id = ?");
            $tourney_stmt->bind_param("i", $tournament_id);
            $tourney_stmt->execute();
            $entry_fee = $tourney_stmt->get_result()->fetch_assoc()['entryFee'];

            $teammate_stmt = $conn->prepare("SELECT coins FROM users WHERE id = ?");
            $teammate_stmt->bind_param("i", $user_id);
            $teammate_stmt->execute();
            $teammate = $teammate_stmt->get_result()->fetch_assoc();
            
            if ($teammate['coins'] < $entry_fee) {
                throw new Exception("You do not have enough coins ({$entry_fee}) to accept this invitation.");
            }
            
            // Fee kaat kar leader ko wapis karo
            $conn->query("UPDATE users SET coins = coins - $entry_fee WHERE id = $user_id");
            $conn->query("UPDATE users SET coins = coins + $entry_fee WHERE id = $leader_id");
            
            $message = "You have joined the team! {$entry_fee} coins deducted.";
        }

        // --- Participant add karne ka logic waisa hi rahega ---
        $user_ff_stmt = $conn->prepare("SELECT freefire_id FROM users WHERE id = ?");
        $user_ff_stmt->bind_param("i", $user_id);
        $user_ff_stmt->execute();
        $user_ff_id = $user_ff_stmt->get_result()->fetch_assoc()['freefire_id'];

        if(empty($user_ff_id)){
            throw new Exception("Please update your Game ID in your profile before accepting.");
        }

        $paid_by = ($is_sponsored == 1 || $is_lucky_team == 1) ? $leader_id : $user_id;
        $add_part_stmt = $conn->prepare("INSERT INTO participants (user_id, tournament_id, freefire_id, teamName, team_id, paid_by_user_id) VALUES (?, ?, ?, ?, ?, ?)");
        $add_part_stmt->bind_param("iissii", $user_id, $tournament_id, $user_ff_id, $team_name, $team_id, $paid_by);
        $add_part_stmt->execute();

        // Invitation status update karo
        $update_stmt = $conn->prepare("UPDATE team_invitations SET status = 'accepted', responded_at = NOW() WHERE id = ?");
        $update_stmt->bind_param("i", $invitation_id);
        $update_stmt->execute();
        
        $response = ['success' => true, 'title' => $response_title, 'message' => $message];

    } elseif ($action === 'reject') {
        // Reject wala logic waisa hi rahega
        if ($is_sponsored == 0) {
            $tourney_stmt = $conn->prepare("SELECT entryFee FROM tournaments WHERE id = ?");
            $tourney_stmt->bind_param("i", $tournament_id);
            $tourney_stmt->execute();
            $entry_fee = $tourney_stmt->get_result()->fetch_assoc()['entryFee'];

            $stmt_refund_leader = $conn->prepare("UPDATE users SET coins = coins + ? WHERE id = ?");
            $stmt_refund_leader->bind_param("di", $entry_fee, $leader_id);
            $stmt_refund_leader->execute();
        }

        $update_stmt = $conn->prepare("UPDATE team_invitations SET status = 'rejected', responded_at = NOW() WHERE id = ?");
        $update_stmt->bind_param("i", $invitation_id);
        $update_stmt->execute();
        
        $response = ['success' => true, 'title' => 'Invitation Rejected', 'message' => 'You have rejected the team invitation.'];
    }

    $conn->commit();
} catch (Exception $e) {
    $conn->rollback();
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>