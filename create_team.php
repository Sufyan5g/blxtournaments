<?php
// FILE: create_team.php (FINAL LOGIC WITH LUCKY TEAM)

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
    $tournament_id = intval($_POST['tournament_id']);
    $team_name = sanitize_input($_POST['team_name']);
    $leader_ff_id = sanitize_input($_POST['leader_ff_id']);
    $teammates = isset($_POST['teammates']) ? $_POST['teammates'] : [];
    $sponsored_teammates = isset($_POST['sponsored_teammates']) ? json_decode($_POST['sponsored_teammates'], true) : [];

    $conn->begin_transaction();
    try {
        // Tournament ki details get karein (entryFee, match_type, aur lucky_slots)
        $stmt_tourney = $conn->prepare("SELECT entryFee, match_type, lucky_slots FROM tournaments WHERE id = ? FOR UPDATE");
        $stmt_tourney->bind_param("i", $tournament_id);
        $stmt_tourney->execute();
        $tournament = $stmt_tourney->get_result()->fetch_assoc();

        if (!$tournament) throw new Exception("Tournament not found.");
        
        $required_teammates = ($tournament['match_type'] === 'duo' ? 1 : ($tournament['match_type'] === 'squad' ? 3 : 0));
        if (count($teammates) != $required_teammates) {
            throw new Exception("You must invite exactly {$required_teammates} teammate(s).");
        }

        $team_size = 1 + $required_teammates;
        $total_fee = $tournament['entryFee'] * $team_size;

        $stmt_user = $conn->prepare("SELECT coins FROM users WHERE id = ?");
        $stmt_user->bind_param("i", $leader_id);
        $stmt_user->execute();
        $leader = $stmt_user->get_result()->fetch_assoc();
        
        if ($leader['coins'] < $total_fee) {
            throw new Exception("Insufficient coins. You need {$total_fee} coins to pay for the entire team.");
        }

        // 1. Leader ke account se poori team ki fee kaat lein
        $stmt_deduct = $conn->prepare("UPDATE users SET coins = coins - ? WHERE id = ?");
        $stmt_deduct->bind_param("di", $total_fee, $leader_id);
        $stmt_deduct->execute();

        // === LUCKY TEAM LOGIC SHURU ===
        $is_lucky_team = 0;
        $lucky_slots_array = !empty($tournament['lucky_slots']) ? explode(',', $tournament['lucky_slots']) : [];

        if (!empty($lucky_slots_array)) {
            // Check karein ke is tournament mein pehle se kitni teams hain
            $count_stmt = $conn->prepare("SELECT COUNT(*) as team_count FROM tournament_teams WHERE tournament_id = ?");
            $count_stmt->bind_param("i", $tournament_id);
            $count_stmt->execute();
            $current_team_count = $count_stmt->get_result()->fetch_assoc()['team_count'];
            $this_team_slot = $current_team_count + 1;

            if (in_array($this_team_slot, $lucky_slots_array)) {
                $is_lucky_team = 1;
                // Team lucky hai, to leader ko poori fee wapis kar do!
                $refund_stmt = $conn->prepare("UPDATE users SET coins = coins + ? WHERE id = ?");
                $refund_stmt->bind_param("di", $total_fee, $leader_id);
                $refund_stmt->execute();
            }
        }
        // === LUCKY TEAM LOGIC KHATAM ===

        // 2. Team create karein (is_lucky_team column ke sath)
        $stmt_create_team = $conn->prepare("INSERT INTO tournament_teams (tournament_id, team_name, leader_user_id, is_lucky_team) VALUES (?, ?, ?, ?)");
        $stmt_create_team->bind_param("isii", $tournament_id, $team_name, $leader_id, $is_lucky_team);
        $stmt_create_team->execute();
        $team_id = $conn->insert_id;

        // 3. Leader ko participants mein add karein
        $stmt_add_leader = $conn->prepare("INSERT INTO participants (user_id, tournament_id, freefire_id, teamName, team_id, paid_by_user_id) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt_add_leader->bind_param("iissii", $leader_id, $tournament_id, $leader_ff_id, $team_name, $team_id, $leader_id);
        $stmt_add_leader->execute();
        
        // 4. Teammates ko invitations bhej dein
        $stmt_invite = $conn->prepare("INSERT INTO team_invitations (team_id, invited_user_id, is_sponsored) VALUES (?, ?, ?)");
        foreach ($teammates as $teammate_id_raw) {
            $teammate_id = intval($teammate_id_raw);
            $is_sponsored = in_array($teammate_id, $sponsored_teammates) ? 1 : 0;
            $stmt_invite->bind_param("iii", $team_id, $teammate_id, $is_sponsored);
            $stmt_invite->execute();
        }

        $conn->commit();

        if ($is_lucky_team) {
            $response = ['success' => true, 'title' => '🎉 Congratulations! 🎉', 'message' => "Your team is the Lucky Team! Your entry is FREE and {$total_fee} coins have been refunded."];
        } else {
            $response = ['success' => true, 'title' => 'Team Created!', 'message' => "Team registered successfully! {$total_fee} coins have been held for the full team."];
        }

    } catch (Exception $e) {
        $conn->rollback();
        $response['message'] = $e->getMessage();
    }
}

echo json_encode($response);
?>