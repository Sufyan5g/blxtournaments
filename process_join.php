<?php
// === YEH NAYI LINE ADD KAREIN ===
ob_start(); // Start output buffering to catch any stray echoes or warnings

require_once 'includes/db.php';

// === YEH NAYI LINE ADD KAREIN ===
ob_clean(); // Clean (erase) the buffer, removing any warnings/echoes that happened during includes

header('Content-Type: application/json'); // Header ab aayega

$response = [ 'success' => false, 'title' => 'Error', 'message' => 'An unknown error occurred.' ];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = "You must be logged in to join.";
    echo json_encode($response);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tournament_id'])) {
    $user_id = $_SESSION['user_id'];
    $tournament_id = intval($_POST['tournament_id']);
    $freefire_id = sanitize_input($_POST['freefire_id']);
    $teamName = sanitize_input($_POST['teamName'] ?? '');

    if(empty($freefire_id)) {
        $response['message'] = "Free Fire ID cannot be empty.";
        echo json_encode($response);
        exit();
    }
    
    $conn->begin_transaction();
    try {
        // Get tournament details and lock the row
        $stmt_tourney = $conn->prepare("SELECT title, entryFee, maxSquads, lucky_slots FROM tournaments WHERE id = ? FOR UPDATE");
        $stmt_tourney->bind_param("i", $tournament_id);
        $stmt_tourney->execute();
        $tournament = $stmt_tourney->get_result()->fetch_assoc();

        // Get user coins and lock the row
        $stmt_user = $conn->prepare("SELECT coins FROM users WHERE id = ? FOR UPDATE");
        $stmt_user->bind_param("i", $user_id);
        $stmt_user->execute();
        $user = $stmt_user->get_result()->fetch_assoc();
        
        if (!$tournament || !$user) { throw new Exception("Invalid tournament or user data."); }

        // Check if user has already joined
        $stmt_check_join = $conn->prepare("SELECT id FROM participants WHERE user_id = ? AND tournament_id = ?");
        $stmt_check_join->bind_param("ii", $user_id, $tournament_id);
        $stmt_check_join->execute();
        if($stmt_check_join->get_result()->num_rows > 0) { throw new Exception("You have already joined this tournament."); }

        // Get current number of participants to determine the slot number
        $stmt_count = $conn->prepare("SELECT COUNT(*) as c FROM participants WHERE tournament_id = ?");
        $stmt_count->bind_param("i", $tournament_id);
        $stmt_count->execute();
        $current_participants = $stmt_count->get_result()->fetch_assoc()['c'];
        
        if($current_participants >= $tournament['maxSquads']) { throw new Exception("Sorry, this tournament is already full."); }
        
        // The new player's slot number will be...
        $join_slot_number = $current_participants + 1;
        
        // === LUCKY SLOT LOGIC ===
        $is_lucky_join = false;
        $entry_fee_to_pay = $tournament['entryFee'];

        if (!empty($tournament['lucky_slots'])) {
            $lucky_slots_array = explode(',', $tournament['lucky_slots']);
            // Check if current slot number is in the lucky list
            if (in_array($join_slot_number, $lucky_slots_array)) {
                $is_lucky_join = true;
                $entry_fee_to_pay = 0; // Free entry!
            }
        }
        
        // Check for coins only if it's not a free (lucky) join
        if (!$is_lucky_join && $user['coins'] < $entry_fee_to_pay) {
            throw new Exception("Insufficient coins. You need " . $entry_fee_to_pay . " coins to join.");
        }

        // Deduct coins if required
        if ($entry_fee_to_pay > 0) {
            $new_coins = $user['coins'] - $entry_fee_to_pay;
            $stmt_update_coins = $conn->prepare("UPDATE users SET coins = ? WHERE id = ?");
            $stmt_update_coins->bind_param("di", $new_coins, $user_id); // Use 'd' for double/decimal
            $stmt_update_coins->execute();
        }
        
        // Insert the participant with their join status
        $stmt_insert = $conn->prepare("INSERT INTO participants (user_id, tournament_id, freefire_id, teamName, join_slot_number, is_lucky_join, paid_by_user_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $is_lucky_join_int = $is_lucky_join ? 1 : 0;
        $stmt_insert->bind_param("iissiii", $user_id, $tournament_id, $freefire_id, $teamName, $join_slot_number, $is_lucky_join_int, $user_id); 
        $stmt_insert->execute();
        
        $conn->commit();

        if ($is_lucky_join) {
             $response = [
                'success' => true,
                'title' => '🎉 Congratulations! 🎉',
                'message' => 'You are a Lucky Player! Your entry is FREE for ' . htmlspecialchars($tournament['title']) . '!'
            ];
        } else {
            $response = [
                'success' => true,
                'title' => 'Tournament Joined!',
                'message' => 'Successfully joined ' . htmlspecialchars($tournament['title']) . '!'
            ];
        }
        
    } catch (Exception $e) {
        $conn->rollback();
        $response['message'] = $e->getMessage();
    }
}

// Final output
echo json_encode($response);

// === AUR YEH LINE ADD KAREIN ===
ob_end_flush(); // Send the output and turn off buffering
?>