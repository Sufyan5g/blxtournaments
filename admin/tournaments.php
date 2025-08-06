<?php
require_once __DIR__ . '/../includes/db.php';

// === NAYA CODE SHURU: PARTICIPANT KO KICK KARNA AUR REFUND KARNA ===
if (isset($_GET['action']) && $_GET['action'] === 'kick_participant') {
    $tournament_id_kick = intval($_GET['tournament_id']);
    $user_id_kick = intval($_GET['user_id']);

    if ($tournament_id_kick > 0 && $user_id_kick > 0) {
        $conn->begin_transaction();
        try {
            // Tournament ki entry fee get karein
            $tourney_stmt = $conn->prepare("SELECT entryFee FROM tournaments WHERE id = ?");
            $tourney_stmt->bind_param("i", $tournament_id_kick);
            $tourney_stmt->execute();
            $tournament = $tourney_stmt->get_result()->fetch_assoc();
            $entry_fee_to_refund = $tournament ? $tournament['entryFee'] : 0;
            $tourney_stmt->close();

            // Participant ko tournament se delete karein
            $delete_stmt = $conn->prepare("DELETE FROM participants WHERE tournament_id = ? AND user_id = ?");
            $delete_stmt->bind_param("ii", $tournament_id_kick, $user_id_kick);
            $affected_rows = $delete_stmt->execute() ? $delete_stmt->affected_rows : 0;
            $delete_stmt->close();

            // Agar participant delete hua hai to hi refund karein
            if ($affected_rows > 0 && $entry_fee_to_refund > 0) {
                $refund_stmt = $conn->prepare("UPDATE users SET coins = coins + ? WHERE id = ?");
                $refund_stmt->bind_param("ii", $entry_fee_to_refund, $user_id_kick);
                $refund_stmt->execute();
                $refund_stmt->close();
            }

            $conn->commit();
        } catch (Exception $e) {
            $conn->rollback();
            // Optional: error log kar sakte hain error_log($e->getMessage());
        }
    }
    // Wapis usi tournament section par redirect karein
    header("Location: tournaments.php#" . $tournament_id_kick);
    exit();
}
// === NAYA CODE KHATAM ===

// === FINAL & MUKAMMAL CANCEL TOURNAMENT LOGIC (HANDLES PENDING INVITATIONS) ===
if (isset($_GET['action']) && $_GET['action'] === 'suspend') {
    $tournament_id_to_cancel = intval($_GET['id']);
    if ($tournament_id_to_cancel > 0) {
        $conn->begin_transaction();
        try {
            // 1. Tournament ki details get karein (status aur entryFee)
            $tourney_stmt = $conn->prepare("SELECT status, entryFee FROM tournaments WHERE id = ? FOR UPDATE");
            $tourney_stmt->bind_param("i", $tournament_id_to_cancel);
            $tourney_stmt->execute();
            $tournament_details = $tourney_stmt->get_result()->fetch_assoc();
            $tourney_stmt->close();

            // Sirf 'upcoming' tournaments hi cancel ho sakte hain
            if ($tournament_details && $tournament_details['status'] === 'upcoming') {
                $entry_fee_to_refund = $tournament_details['entryFee'];
                $refunds_to_process = [];

                // =========================================================================
                // PART A: UN USERS KE LIYE REFUND JINHONE JOIN KAR LIYA THA (PARTICIPANTS)
                // =========================================================================
                $participants_stmt = $conn->prepare("SELECT user_id, paid_by_user_id FROM participants WHERE tournament_id = ?");
                $participants_stmt->bind_param("i", $tournament_id_to_cancel);
                $participants_stmt->execute();
                $participants = $participants_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                $participants_stmt->close();
                
                if ($entry_fee_to_refund > 0) {
                    foreach ($participants as $participant) {
                        $user_to_refund = $participant['paid_by_user_id'] ?? $participant['user_id'];
                        if ($user_to_refund > 0) {
                            $refunds_to_process[$user_to_refund] = ($refunds_to_process[$user_to_refund] ?? 0) + $entry_fee_to_refund;
                        }
                    }
                }

                // =========================================================================
                // PART B: UN INVITATIONS KE LIYE REFUND JO PENDING HAIN
                // =========================================================================
                $pending_invites_stmt = $conn->prepare("
                    SELECT tt.leader_user_id 
                    FROM team_invitations ti
                    JOIN tournament_teams tt ON ti.team_id = tt.id
                    WHERE tt.tournament_id = ? AND ti.status = 'pending'
                ");
                $pending_invites_stmt->bind_param("i", $tournament_id_to_cancel);
                $pending_invites_stmt->execute();
                $pending_invites_result = $pending_invites_stmt->get_result();
                
                if ($entry_fee_to_refund > 0) {
                    while ($row = $pending_invites_result->fetch_assoc()) {
                        $leader_to_refund = $row['leader_user_id'];
                        if ($leader_to_refund > 0) {
                            $refunds_to_process[$leader_to_refund] = ($refunds_to_process[$leader_to_refund] ?? 0) + $entry_fee_to_refund;
                        }
                    }
                }
                $pending_invites_stmt->close();

                // =========================================================================
                // PART C: SAB REFUNDS KO EK SAATH PROCESS KAREIN
                // =========================================================================
                if (!empty($refunds_to_process)) {
                    $refund_stmt = $conn->prepare("UPDATE users SET coins = coins + ? WHERE id = ?");
                    foreach ($refunds_to_process as $user_id => $total_amount) {
                        $refund_stmt->bind_param("di", $total_amount, $user_id);
                        $refund_stmt->execute();
                    }
                    $refund_stmt->close();
                }

                // =========================================================================
                // PART D: TOURNAMENT SE SAARA DATA CLEANUP KAREIN
                // =========================================================================
                // Sabhi participants ko delete karein
                $conn->query("DELETE FROM participants WHERE tournament_id = $tournament_id_to_cancel");
                // Sabhi invitations ko delete karein
                $conn->query("DELETE FROM team_invitations WHERE team_id IN (SELECT id FROM tournament_teams WHERE tournament_id = $tournament_id_to_cancel)");
                // Sabhi teams ko delete karein
                $conn->query("DELETE FROM tournament_teams WHERE tournament_id = $tournament_id_to_cancel");

                // Tournament ka status 'canceled' karein
                $status_update_stmt = $conn->prepare("UPDATE tournaments SET status = 'canceled' WHERE id = ?");
                $status_update_stmt->bind_param("i", $tournament_id_to_cancel);
                $status_update_stmt->execute();
                $status_update_stmt->close();
                
                $conn->commit();
            }
        } catch (Exception $e) {
            $conn->rollback();
            error_log("Tournament cancel failed for ID $tournament_id_to_cancel: " . $e->getMessage());
        }
    }
    header("Location: tournaments.php");
    exit();
}

// === ADD/UPDATE TOURNAMENT LOGIC ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_tournament'])) {
    // ... (upar ka saara variable code waisa hi rahega)
    $title = sanitize_input($_POST['title']);
    $type = sanitize_input($_POST['type']);
    $match_type = sanitize_input($_POST['match_type']); // Yeh line zaroori hai
    $entryFee = intval($_POST['entryFee']);
    $prizePool = intval($_POST['prizePool']);
    $returnAmount = intval($_POST['return_amount']);
    $perKillPrice = intval($_POST['per_kill_price']);
    $maxSquads = intval($_POST['maxSquads']);
    $startTime = sanitize_input($_POST['startTime']);
    $map_name = sanitize_input($_POST['map_name']);
    $rules = sanitize_input($_POST['rules']);
    $prize_details = sanitize_input($_POST['prize_details']);
    $tournament_id = intval($_POST['tournament_id']);
    $lucky_prize = intval($_POST['lucky_prize']);
    $lucky_slots_input = isset($_POST['lucky_slots']) ? $_POST['lucky_slots'] : [];
    $lucky_slots_clean = array_filter(array_map('intval', $lucky_slots_input));
    $lucky_slots_db = !empty($lucky_slots_clean) ? implode(',', $lucky_slots_clean) : null;

    if ($tournament_id > 0) {
        // YEH UPDATE WALI QUERY HAI - ISKO THEEK KAREIN
        $stmt = $conn->prepare("UPDATE tournaments SET title=?, type=?, match_type=?, entryFee=?, prizePool=?, return_amount=?, per_kill_price=?, maxSquads=?, startTime=?, map_name=?, rules=?, prize_details=?, lucky_prize=?, lucky_slots=? WHERE id=?");
        $stmt->bind_param("sssiiiiisssissi", $title, $type, $match_type, $entryFee, $prizePool, $returnAmount, $perKillPrice, $maxSquads, $startTime, $map_name, $rules, $prize_details, $lucky_prize, $lucky_slots_db, $tournament_id);
    } else {
        // YEH INSERT WALI QUERY HAI - ISKO BHI THEEK KAREIN
        $stmt = $conn->prepare("INSERT INTO tournaments (title, type, match_type, entryFee, prizePool, return_amount, per_kill_price, maxSquads, startTime, map_name, rules, prize_details, lucky_prize, lucky_slots) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssiiiiissssis", $title, $type, $match_type, $entryFee, $prizePool, $returnAmount, $perKillPrice, $maxSquads, $startTime, $map_name, $rules, $prize_details, $lucky_prize, $lucky_slots_db);
    }
    
    if ($stmt && $stmt->execute()) {
        header("Location: tournaments.php");
        exit();
    } else {
        $_SESSION['error_message'] = "Error: " . ($stmt ? $stmt->error : $conn->error);
        header("Location: tournaments.php");
        exit();
    }
}

if (isset($_GET['delete'])) {
    $id_to_delete = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM tournaments WHERE id = ?");
    $stmt->bind_param("i", $id_to_delete);
    $stmt->execute();
    header("Location: tournaments.php");
    exit();
}

if (isset($_POST['save_kills'])) {
    $tournament_id = intval($_POST['tournament_id']);
    $participant_user_id = intval($_POST['participant_user_id']);
    $new_kills = intval($_POST['total_kills']);
    $tourney_stmt = $conn->prepare("SELECT per_kill_price FROM tournaments WHERE id = ?");
    $tourney_stmt->bind_param("i", $tournament_id);
    $tourney_stmt->execute();
    $tournament = $tourney_stmt->get_result()->fetch_assoc();
    $per_kill_price = $tournament ? $tournament['per_kill_price'] : 0;
    if ($per_kill_price > 0) {
        $conn->begin_transaction();
        try {
            $part_stmt = $conn->prepare("SELECT kills FROM participants WHERE tournament_id = ? AND user_id = ?");
            $part_stmt->bind_param("ii", $tournament_id, $participant_user_id);
            $part_stmt->execute();
            $participant = $part_stmt->get_result()->fetch_assoc();
            $old_kills = $participant ? $participant['kills'] : 0;
            $kill_difference = $new_kills - $old_kills;
            $coins_to_award = $kill_difference * $per_kill_price;
            if ($coins_to_award != 0) {
                $user_stmt = $conn->prepare("UPDATE users SET coins = coins + ? WHERE id = ?");
                $user_stmt->bind_param("ii", $coins_to_award, $participant_user_id);
                $user_stmt->execute();
            }
            $update_kills_stmt = $conn->prepare("UPDATE participants SET kills = ? WHERE tournament_id = ? AND user_id = ?");
            $update_kills_stmt->bind_param("iii", $new_kills, $tournament_id, $participant_user_id);
            $update_kills_stmt->execute();
            $conn->commit();
        } catch (Exception $e) { $conn->rollback(); }
    }
    header("Location: tournaments.php#" . $tournament_id);
    exit();
}
// =======================================================
// === FINAL & MUKAMMAL WINNER LOGIC (SOLO AUR TEAM) ===
// =======================================================

// SOLO WINNER LOGIC
if (isset($_POST['declare_winner'])) {
    $tournament_id = intval($_POST['tournament_id']);
    $winner_user_id = intval($_POST['winner_user_id']);

    $stmt_tourney = $conn->prepare("SELECT prizePool, return_amount, lucky_prize FROM tournaments WHERE id = ? AND status = 'upcoming'");
    $stmt_tourney->bind_param("i", $tournament_id);
    $stmt_tourney->execute();
    $tournament = $stmt_tourney->get_result()->fetch_assoc();

    $stmt_winner_part = $conn->prepare("SELECT is_lucky_join FROM participants WHERE tournament_id = ? AND user_id = ?");
    $stmt_winner_part->bind_param("ii", $tournament_id, $winner_user_id);
    $stmt_winner_part->execute();
    $winner_participant = $stmt_winner_part->get_result()->fetch_assoc();

    if ($tournament && $winner_participant) {
        $prize_to_award = ($winner_participant['is_lucky_join'] == 1 && $tournament['lucky_prize'] > 0) ? $tournament['lucky_prize'] : $tournament['prizePool'];
        $returnAmount = $tournament['return_amount'];
        
        $conn->begin_transaction();
        try {
            if ($returnAmount > 0) {
                $p_stmt = $conn->prepare("SELECT user_id FROM participants WHERE tournament_id = ? AND user_id != ?");
                $p_stmt->bind_param("ii", $tournament_id, $winner_user_id);
                $p_stmt->execute();
                $losing_participants = $p_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                if (count($losing_participants) > 0) {
                    $refund_stmt = $conn->prepare("UPDATE users SET coins = coins + ? WHERE id = ?");
                    foreach ($losing_participants as $participant) {
                        $refund_stmt->bind_param("ii", $returnAmount, $participant['user_id']);
                        $refund_stmt->execute();
                    }
                }
            }

            $winner_stmt = $conn->prepare("UPDATE users SET coins = coins + ?, wins = wins + 1 WHERE id = ?");
            $winner_stmt->bind_param("ii", $prize_to_award, $winner_user_id);
            $winner_stmt->execute();
            
            $log_win_stmt = $conn->prepare("INSERT INTO win_logs (user_id) VALUES (?)");
            $log_win_stmt->bind_param("i", $winner_user_id);
            $log_win_stmt->execute();

            $status_stmt = $conn->prepare("UPDATE tournaments SET status = 'completed' WHERE id = ?");
            $status_stmt->bind_param("i", $tournament_id);
            $status_stmt->execute();
            
            // Sab participants ko XP award karna
            $xp_to_award = 10;
            $all_participants_stmt = $conn->prepare("SELECT user_id FROM participants WHERE tournament_id = ?");
            $all_participants_stmt->bind_param("i", $tournament_id);
            $all_participants_stmt->execute();
            $all_participants_result = $all_participants_stmt->get_result();
            if ($all_participants_result->num_rows > 0) {
                $award_xp_stmt = $conn->prepare("UPDATE users SET xp = xp + ? WHERE id = ?");
                while ($participant_row = $all_participants_result->fetch_assoc()) {
                    $award_xp_stmt->bind_param("ii", $xp_to_award, $participant_row['user_id']);
                    $award_xp_stmt->execute();
                }
            }
            
            $conn->commit();
        } catch (Exception $e) { $conn->rollback(); }
    }
    header("Location: tournaments.php"); 
    exit();
}

// ========== CHANGE: UPDATED TEAM WINNER LOGIC START ==========
// TEAM WINNER LOGIC (LUCKY TEAM SUPPORT KE SATH)
if (isset($_POST['declare_team_winner'])) {
    $tournament_id = intval($_POST['tournament_id']);
    $winning_team_id = intval($_POST['winning_team_id']);

    // Tournament ki prize details (prizePool aur lucky_prize) get karein
    $stmt_tourney = $conn->prepare("SELECT prizePool, lucky_prize FROM tournaments WHERE id = ? AND status = 'upcoming'");
    $stmt_tourney->bind_param("i", $tournament_id);
    $stmt_tourney->execute();
    $tournament = $stmt_tourney->get_result()->fetch_assoc();

    // Check karein ke jeetne wali team lucky thi ya nahi
    $stmt_team = $conn->prepare("SELECT is_lucky_team FROM tournament_teams WHERE id = ?");
    $stmt_team->bind_param("i", $winning_team_id);
    $stmt_team->execute();
    $winning_team_details = $stmt_team->get_result()->fetch_assoc();
    $is_lucky_winner = ($winning_team_details && $winning_team_details['is_lucky_team'] == 1);

    // Jeetne wali team ke members get karein
    $stmt_winners = $conn->prepare("SELECT user_id FROM participants WHERE tournament_id = ? AND team_id = ?");
    $stmt_winners->bind_param("ii", $tournament_id, $winning_team_id);
    $stmt_winners->execute();
    $winners_result = $stmt_winners->get_result();
    $winning_members = $winners_result->fetch_all(MYSQLI_ASSOC);
    $winner_count = count($winning_members);

    if ($tournament && $winner_count > 0) {
        
        // Decide karein ke konsa prize dena hai
        $total_prize = ($is_lucky_winner && $tournament['lucky_prize'] > 0) ? $tournament['lucky_prize'] : $tournament['prizePool'];
        $prize_per_member = floor($total_prize / $winner_count);
        
        $conn->begin_transaction();
        try {
            $winner_stmt = $conn->prepare("UPDATE users SET coins = coins + ?, wins = wins + 1 WHERE id = ?");
            $log_win_stmt = $conn->prepare("INSERT INTO win_logs (user_id) VALUES (?)");

            foreach ($winning_members as $member) {
                $member_id = $member['user_id'];
                $winner_stmt->bind_param("ii", $prize_per_member, $member_id);
                $winner_stmt->execute();
                $log_win_stmt->bind_param("i", $member_id);
                $log_win_stmt->execute();
            }

            // Baqi ka code waisa hi rahega (status update, xp award, etc.)
            $status_stmt = $conn->prepare("UPDATE tournaments SET status = 'completed' WHERE id = ?");
            $status_stmt->bind_param("i", $tournament_id);
            $status_stmt->execute();
            
            $xp_to_award = 10;
            $all_participants_stmt = $conn->prepare("SELECT user_id FROM participants WHERE tournament_id = ?");
            $all_participants_stmt->bind_param("i", $tournament_id);
            $all_participants_stmt->execute();
            $all_participants_result = $all_participants_stmt->get_result();
            if ($all_participants_result->num_rows > 0) {
                $award_xp_stmt = $conn->prepare("UPDATE users SET xp = xp + ? WHERE id = ?");
                while ($participant_row = $all_participants_result->fetch_assoc()) {
                    $award_xp_stmt->bind_param("ii", $xp_to_award, $participant_row['user_id']);
                    $award_xp_stmt->execute();
                }
            }
            
            $conn->commit();
        } catch (Exception $e) { $conn->rollback(); }
    }
    header("Location: tournaments.php"); 
    exit();
}
// ========== CHANGE: UPDATED TEAM WINNER LOGIC END ==========


if (isset($_POST['send_update'])) {
    $tournament_id = intval($_POST['tournament_id']);
    $update_text = sanitize_input($_POST['update_message']);
    if (!empty($update_text)) {
        $stmt = $conn->prepare("INSERT INTO tournament_updates (tournament_id, message) VALUES (?, ?)");
        if ($stmt) {
            $stmt->bind_param("is", $tournament_id, $update_text);
            $stmt->execute();
            $stmt->close();
        }
    }
    header("Location: tournaments.php");
    exit();
}

require_once 'includes/header.php';
$edit_mode = false;
$tournament_data = ['id' => '', 'title' => '', 'type' => '', 'match_type' => 'solo', 'entryFee' => '', 'prizePool' => '', 'return_amount' => '0', 'per_kill_price' => '0', 'maxSquads' => '', 'startTime' => '', 'map_name' => '', 'rules' => '', 'prize_details' => '', 'lucky_prize' => '0', 'lucky_slots' => ''];
if (isset($_GET['edit'])) {
    $edit_mode = true;
    $id_to_edit = intval($_GET['edit']);
    $result = $conn->query("SELECT * FROM tournaments WHERE id = $id_to_edit");
    if ($result->num_rows > 0) {
        $tournament_data = $result->fetch_assoc();
        $tournament_data['startTime'] = date('Y-m-d\TH:i', strtotime($tournament_data['startTime']));
    }
}
?>

<div class="page-header"><h1>Manage Tournaments</h1></div>

<div class="card">
    <h2><?php echo $edit_mode ? 'Edit Tournament' : 'Add New Tournament'; ?></h2>
    <form action="tournaments.php" method="POST" style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
        <input type="hidden" name="tournament_id" value="<?php echo htmlspecialchars($tournament_data['id']); ?>">
        
        <!-- Basic Info -->
        <div class="form-group"><label>Title</label><input type="text" name="title" value="<?php echo htmlspecialchars($tournament_data['title']); ?>" required></div>
        <div class="form-group"><label>Type (e.g., FreeFire, BGMI)</label><input type="text" name="type" value="<?php echo htmlspecialchars($tournament_data['type']); ?>" required></div>
        
        <div class="form-group">
            <label>Match Type</label>
            <select name="match_type" required>
                <option value="solo" <?php if(isset($tournament_data['match_type']) && $tournament_data['match_type'] == 'solo') echo 'selected'; ?>>Solo</option>
                <option value="duo" <?php if(isset($tournament_data['match_type']) && $tournament_data['match_type'] == 'duo') echo 'selected'; ?>>Duo</option>
                <option value="squad" <?php if(isset($tournament_data['match_type']) && $tournament_data['match_type'] == 'squad') echo 'selected'; ?>>Squad</option>
            </select>
        </div>
        
        <div class="form-group"><label>Max Players</label><input type="number" name="maxSquads" value="<?php echo htmlspecialchars($tournament_data['maxSquads']); ?>" required></div>
        <div class="form-group"><label>Map Name</label><input type="text" name="map_name" value="<?php echo htmlspecialchars($tournament_data['map_name'] ?? ''); ?>"></div>

        <!-- Prize Info -->
        <div class="form-group"><label>Entry Fee</label><input type="number" name="entryFee" value="<?php echo htmlspecialchars($tournament_data['entryFee']); ?>" required></div>
        <div class="form-group"><label>Prize Pool</label><input type="number" name="prizePool" value="<?php echo htmlspecialchars($tournament_data['prizePool']); ?>" required></div>
        <div class="form-group"><label>Per Kill Price</label><input type="number" name="per_kill_price" value="<?php echo htmlspecialchars($tournament_data['per_kill_price']); ?>" required></div>
        <div class="form-group"><label>Return Amount (if lose)</label><input type="number" name="return_amount" value="<?php echo htmlspecialchars($tournament_data['return_amount']); ?>" required></div>
        
        <!-- === NEW: Lucky Slot Fields === -->
        <div class="form-group">
            <label>Lucky Slot Prize</label>
            <input type="number" name="lucky_prize" value="<?php echo htmlspecialchars($tournament_data['lucky_prize'] ?? '0'); ?>" required>
        </div>
        <div class="form-group" style="grid-column: 1 / -1;">
            <!-- ========== CHANGE: UPDATED LABEL ========== -->
            <label>Lucky Team Slot Numbers</label>
            <div id="lucky-slots-container">
                <?php 
                $lucky_slots_arr = !empty($tournament_data['lucky_slots']) ? explode(',', $tournament_data['lucky_slots']) : [];
                if (!empty($lucky_slots_arr)):
                    foreach ($lucky_slots_arr as $slot):
                ?>
                <div class="lucky-slot-input" style="display:flex; gap:10px; margin-bottom:5px;">
                    <input type="number" name="lucky_slots[]" placeholder="Enter slot number (e.g., 17)" value="<?php echo htmlspecialchars($slot); ?>" min="1">
                    <button type="button" class="btn btn-danger remove-slot-btn" style="padding: 5px 10px;">Remove</button>
                </div>
                <?php 
                    endforeach;
                else: // Show one empty box if none are set
                ?>
                <div class="lucky-slot-input" style="display:flex; gap:10px; margin-bottom:5px;">
                    <input type="number" name="lucky_slots[]" placeholder="Enter slot number (e.g., 17)" min="1">
                    <button type="button" class="btn btn-danger remove-slot-btn" style="padding: 5px 10px;">Remove</button>
                </div>
                <?php endif; ?>
            </div>
            <button type="button" id="add-more-slots" class="btn btn-secondary" style="margin-top:10px;">Add More Slots</button>
        </div>
        <!-- === END of Lucky Slot Fields === -->
        
        <!-- Other Details -->
        <div class="form-group" style="grid-column: 1 / -1;"><label>Start Time</label><input type="datetime-local" name="startTime" value="<?php echo $tournament_data['startTime']; ?>" required></div>
        <div class="form-group" style="grid-column: 1 / -1;"><label>Rules</label><textarea name="rules" rows="4"><?php echo htmlspecialchars($tournament_data['rules'] ?? ''); ?></textarea></div>
        <div class="form-group" style="grid-column: 1 / -1;"><label>Prize Distribution</label><textarea name="prize_details" rows="4"><?php echo htmlspecialchars($tournament_data['prize_details'] ?? ''); ?></textarea></div>
        
        <button type="submit" name="save_tournament" class="btn btn-primary" style="grid-column: 1 / -1;"><?php echo $edit_mode ? 'Update Tournament' : 'Save Tournament'; ?></button>
    </form>
</div>

<div class="card">
    <h2>All Tournaments</h2>
    <?php
    $sql = "SELECT t.*, (SELECT COUNT(*) FROM participants p WHERE p.tournament_id = t.id) AS current_participants FROM tournaments t ORDER BY t.startTime DESC";
    $tournaments_result = $conn->query($sql);
    while($tournament = $tournaments_result->fetch_assoc()):
    ?>
    <div id="<?php echo $tournament['id']; ?>" style="border:1px solid #e2e8f0; padding:1.5rem; margin-bottom:1.5rem; border-radius:8px;">
        <div style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h3><?php echo htmlspecialchars($tournament['title']); ?> (Status: <?php echo ucfirst($tournament['status']); ?>)</h3>
            <div class="action-links">
                <?php if($tournament['status'] == 'upcoming'): ?>
                    <a href="tournaments.php?edit=<?php echo $tournament['id']; ?>" class="btn" style="background-color: #f59e0b;">Edit</a>
                    <a href="tournaments.php?action=suspend&id=<?php echo $tournament['id']; ?>" class="btn" style="background-color: #64748b;" onclick="return confirm('This will CANCEL the tournament and REFUND all entry fees. Are you sure?');">Cancel</a>
                <?php endif; ?>
                <a href="tournaments.php?delete=<?php echo $tournament['id']; ?>" class="btn btn-danger" onclick="return confirm('WARNING: This will permanently delete the tournament WITHOUT refunding any fees. Use \'Cancel\' to refund. Are you sure?');">Delete</a>
            </div>
        </div>
        
        <div class="update-form-container" style="margin-top: 1rem; border-top: 1px solid #eee; padding-top: 1rem;">
            <h4>Send Update to Participants</h4>
            <form action="tournaments.php" method="POST" style="display:flex; gap:1rem;">
                <input type="hidden" name="tournament_id" value="<?php echo $tournament['id']; ?>">
                <input type="text" name="update_message" placeholder="e.g., Room ID & Pass: 12345 / pass123" required style="flex-grow:1; padding: 0.5rem;">
                <button type="submit" name="send_update" class="btn btn-primary">Send</button>
            </form>
        </div>
        <div class="room-details-form-container" style="margin-top: 1rem; border-top: 1px solid #eee; padding-top: 1rem;">
            <h4>Set Room ID & Password</h4>
            <form action="/update_room_details.php" method="POST" style="display:flex; gap:1rem; align-items:center;">
                <input type="hidden" name="tournament_id" value="<?php echo $tournament['id']; ?>">
                <div class="form-group" style="margin:0;">
                    <label style="display:block; margin-bottom: 5px;">Room ID</label>
                    <input type="text" name="room_id" placeholder="Enter Room ID" value="<?php echo htmlspecialchars($tournament['room_id'] ?? ''); ?>" required>
                </div>
                <div class="form-group" style="margin:0;">
                    <label style="display:block; margin-bottom: 5px;">Password</label>
                    <input type="text" name="room_password" placeholder="Enter Password" value="<?php echo htmlspecialchars($tournament['room_password'] ?? ''); ?>" required>
                </div>
                <button type="submit" name="update_room_details" class="btn btn-primary" style="align-self: flex-end;">Set Details</button>
            </form>
        </div>

        <!-- =============================================== -->
        <!-- YAHAN SE NAYA AUR BEHTAR PARTICIPANTS CODE SHURU -->
        <!-- =============================================== -->

        <h4 style="margin-top: 1.5rem;">Participants (<?php echo $tournament['current_participants']; ?>)</h4>

        <?php
        // YEH QUERY AB LOOP KE BAHAR HAI (BEHTAR PERFORMANCE KE LIYE)
        // Pehle se hi un sab users ki list le lein jinhone details copy ki hain
        $copied_stmt = $conn->prepare("SELECT user_id FROM room_details_copied WHERE tournament_id = ?");
        $copied_stmt->bind_param("i", $tournament['id']);
        $copied_stmt->execute();
        $copied_result = $copied_stmt->get_result();
        $copied_status = [];
        while($row_copied = $copied_result->fetch_assoc()) {
            $copied_status[$row_copied['user_id']] = true;
        }
        $copied_stmt->close();
        ?>

        <!-- CHECK KAREIN KE TOURNAMENT SOLO HAI YA TEAM WALA -->
        <?php if (isset($tournament['match_type']) && $tournament['match_type'] != 'solo'): ?>
            
            <!-- AGAR TEAM WALA HAI TO YEH DIKHAYEIN -->
            <?php
            $teams_stmt = $conn->prepare("
                SELECT tt.id as team_id, tt.team_name, u.name as leader_name, tt.is_lucky_team
                FROM tournament_teams tt
                JOIN users u ON tt.leader_user_id = u.id
                WHERE tt.tournament_id = ? ORDER BY tt.team_name
            ");
            $teams_stmt->bind_param("i", $tournament['id']);
            $teams_stmt->execute();
            $teams_result = $teams_stmt->get_result();

            if ($teams_result && $teams_result->num_rows > 0):
                while ($team = $teams_result->fetch_assoc()):
            ?>
                    <div class="team-card" style="border: 1px solid #e2e8f0; padding: 1rem; margin-bottom: 1rem; border-radius: 8px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 0.5rem; margin-bottom: 0.5rem;">
                            <h5 style="margin: 0;">
                                Team: <strong><?php echo htmlspecialchars($team['team_name']); ?></strong> 
                                (Leader: <?php echo htmlspecialchars($team['leader_name']); ?>)
                                <?php if ($team['is_lucky_team']): ?>
                                    <span style="color: #f59e0b; font-weight: bold;"> (Lucky Team ✨)</span>
                                <?php endif; ?>
                            </h5>
                            <?php if($tournament['status'] == 'upcoming'): ?>
                            <form action="tournaments.php" method="POST" style="margin:0;">
                                <input type="hidden" name="tournament_id" value="<?php echo $tournament['id']; ?>">
                                <input type="hidden" name="winning_team_id" value="<?php echo $team['team_id']; ?>">
                                <button type="submit" name="declare_team_winner" class="btn btn-success" onclick="return confirm('Declare TEAM <?php echo htmlspecialchars($team['team_name']); ?> as the winner?');">Declare Team Winner</button>
                            </form>
                            <?php endif; ?>
                        </div>
                        
                        <table>
                            <thead>
                                <tr>
                                    <th>Player</th>
                                    <th>UID</th>
                                    <th>Paid By</th>
                                    <th>Status</th>
                                    <th>Kills</th>
                                    <th>Copied Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $members_stmt = $conn->prepare("
                                    SELECT p.user_id, p.kills, u.name, u.uid, paid_by.name as paid_by_name, 
                                           IF(p.user_id = tt.leader_user_id, 'Leader', ti.status) as member_status
                                    FROM participants p
                                    JOIN users u ON p.user_id = u.id
                                    JOIN tournament_teams tt ON p.team_id = tt.id
                                    LEFT JOIN team_invitations ti ON p.user_id = ti.invited_user_id AND ti.team_id = tt.id
                                    LEFT JOIN users paid_by ON p.paid_by_user_id = paid_by.id
                                    WHERE p.team_id = ?
                                ");
                                $members_stmt->bind_param("i", $team['team_id']);
                                $members_stmt->execute();
                                $members_result = $members_stmt->get_result();
                                if ($members_result):
                                    while ($member = $members_result->fetch_assoc()):
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($member['name']); ?></td>
                                    <td><?php echo htmlspecialchars($member['uid']); ?></td>
                                    <td><?php echo htmlspecialchars($member['paid_by_name'] ?: 'Self'); ?></td>
                                    <td>
                                        <?php 
                                            $status = htmlspecialchars($member['member_status'] ?? 'pending');
                                            $color = 'orange';
                                            if ($status == 'Leader' || strtolower($status) == 'accepted') $color = 'green';
                                            if (strtolower($status) == 'rejected') $color = 'red';
                                            echo "<span style='color: {$color}; text-transform: capitalize;'>{$status}</span>";
                                        ?>
                                    </td>
                                    <td>
                                        <?php if ($tournament['status'] == 'upcoming' && $tournament['per_kill_price'] > 0): ?>
                                            <form action="tournaments.php" method="POST" style="display:flex; gap:5px; margin:0;">
                                                <input type="hidden" name="tournament_id" value="<?php echo $tournament['id']; ?>">
                                                <input type="hidden" name="participant_user_id" value="<?php echo $member['user_id']; ?>">
                                                <input type="number" name="total_kills" value="<?php echo htmlspecialchars($member['kills'] ?? 0); ?>" style="width: 60px;" min="0">
                                                <button type="submit" name="save_kills" class="btn" style="padding: 2px 8px;">Save</button>
                                            </form>
                                        <?php else: ?>
                                            <span><?php echo htmlspecialchars($member['kills'] ?? 0); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="copied-status-cell" style="text-align: center;">
                                        <?php if (isset($copied_status[$member['user_id']])): ?>
                                            <i class="fas fa-check-circle" style="color: green;" title="Copied"></i>
                                        <?php else: ?>
                                            <i class="fas fa-hourglass-half" style="color: orange;" title="Waiting"></i>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; $members_stmt->close(); endif; ?>
                            </tbody>
                        </table>
                    </div>
            <?php 
                endwhile; $teams_stmt->close();
            else:
                echo "<p>No teams have joined this tournament yet.</p>";
            endif;
            ?>

        <?php else: ?>

            <!-- AGAR SOLO WALA HAI TO PURANA CODE DIKHAYEIN -->
            <?php if($tournament['current_participants'] > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>User Name (UID)</th>
                        <th>Team Name</th>
                        <th>Is Lucky?</th>
                        <th>Kills</th>
                        <th>Copied Details</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $p_stmt = $conn->prepare("SELECT p.user_id, p.teamName, p.is_lucky_join, p.kills, u.name as user_name, u.uid FROM participants p JOIN users u ON p.user_id = u.id WHERE p.tournament_id = ?");
                    $p_stmt->bind_param("i", $tournament['id']);
                    $p_stmt->execute();
                    $participants_result = $p_stmt->get_result();
                    while($participant = $participants_result->fetch_assoc()):
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($participant['user_name']); ?> (<?php echo htmlspecialchars($participant['uid']); ?>)</td>
                        <td><?php echo htmlspecialchars($participant['teamName'] ?: 'N/A'); ?></td>
                        <td><?php echo $participant['is_lucky_join'] ? '✔️ Yes' : 'No'; ?></td>
                        <td>
                            <?php if ($tournament['status'] == 'upcoming' && $tournament['per_kill_price'] > 0): ?>
                                <form action="tournaments.php" method="POST" style="display:flex; gap:5px; margin:0;">
                                    <input type="hidden" name="tournament_id" value="<?php echo $tournament['id']; ?>">
                                    <input type="hidden" name="participant_user_id" value="<?php echo $participant['user_id']; ?>">
                                    <input type="number" name="total_kills" value="<?php echo htmlspecialchars($participant['kills'] ?? 0); ?>" style="width: 60px;" min="0">
                                    <button type="submit" name="save_kills" class="btn" style="padding: 2px 8px;">Save</button>
                                </form>
                            <?php else: ?>
                                 <span><?php echo htmlspecialchars($participant['kills'] ?? 0); ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="copied-status-cell" style="text-align: center;">
                            <?php if (isset($copied_status[$participant['user_id']])): ?>
                                <i class="fas fa-check-circle" style="color: green;" title="Copied"></i>
                            <?php else: ?>
                                <i class="fas fa-hourglass-half" style="color: orange;" title="Waiting"></i>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form action="tournaments.php" method="POST" style="margin:0; display: inline-block;">
                                <input type="hidden" name="tournament_id" value="<?php echo $tournament['id']; ?>">
                                <input type="hidden" name="winner_user_id" value="<?php echo $participant['user_id']; ?>">
                                <button type="submit" name="declare_winner" class="btn btn-success" onclick="return confirm('Declare this user as winner?');">Declare Winner</button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; $p_stmt->close(); ?>
                </tbody>
            </table>
            <?php else: echo "<p>No one has joined this tournament yet.</p>"; endif; ?>

        <?php endif; ?>

        <!-- ============================================= -->
        <!-- YAHAN PAR NAYA PARTICIPANTS CODE KHATAM HOTA HAI -->
        <!-- ============================================= -->
        
    </div>
    <?php endwhile; ?>
</div>

<!-- === NEW JavaScript for "Add More" Button === -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('lucky-slots-container');
    
    document.getElementById('add-more-slots').addEventListener('click', function() {
        const newSlot = document.createElement('div');
        newSlot.className = 'lucky-slot-input';
        newSlot.style.cssText = 'display:flex; gap:10px; margin-bottom:5px;';
        newSlot.innerHTML = `
            <input type="number" name="lucky_slots[]" placeholder="Enter slot number" min="1">
            <button type="button" class="btn btn-danger remove-slot-btn" style="padding: 5px 10px;">Remove</button>
        `;
        container.appendChild(newSlot);
    });

    container.addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('remove-slot-btn')) {
            e.target.closest('.lucky-slot-input').remove();
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>