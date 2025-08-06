<?php
require_once 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_join']) && isset($_SESSION['user_id'])) {
    $tournament_id = intval($_POST['tournament_id']);
    $user_id = $_SESSION['user_id'];
    $freefire_id = sanitize_input($_POST['freefire_id']);
    $teamName = sanitize_input($_POST['teamName']);

    if(empty($freefire_id)) {
        $_SESSION['error_message'] = "Free Fire ID is required to join.";
        header("Location: index.php");
        exit();
    }
    
    $conn->begin_transaction();
    try {
        // 1. Get tournament and user details
        $stmt = $conn->prepare("SELECT entryFee, maxSquads FROM tournaments WHERE id = ? FOR UPDATE");
        $stmt->bind_param("i", $tournament_id);
        $stmt->execute();
        $tournament = $stmt->get_result()->fetch_assoc();

        $stmt = $conn->prepare("SELECT coins FROM users WHERE id = ? FOR UPDATE");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        // 2. Check if tournament is full
        $stmt = $conn->prepare("SELECT COUNT(*) as c FROM participants WHERE tournament_id = ?");
        $stmt->bind_param("i", $tournament_id);
        $stmt->execute();
        $participants_count = $stmt->get_result()->fetch_assoc()['c'];
        
        if ($participants_count >= $tournament['maxSquads']) {
            throw new Exception("Sorry, this tournament is already full.");
        }
        
        // 3. Check if user has enough coins
        if ($user['coins'] < $tournament['entryFee']) {
            throw new Exception("You do not have enough coins to join.");
        }

        // 4. Deduct coins and add participant
        $new_coins = $user['coins'] - $tournament['entryFee'];
        $stmt_update = $conn->prepare("UPDATE users SET coins = ? WHERE id = ?");
        $stmt_update->bind_param("ii", $new_coins, $user_id);
        $stmt_update->execute();

        $stmt_insert = $conn->prepare("INSERT INTO participants (user_id, tournament_id, freefire_id, teamName) VALUES (?, ?, ?, ?)");
        $stmt_insert->bind_param("iiss", $user_id, $tournament_id, $freefire_id, $teamName);
        $stmt_insert->execute();

        $conn->commit();
        $_SESSION['success_title'] = "Tournament Joined!";
        $_SESSION['success_message'] = "You have successfully joined the tournament.";

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
    }
}

// Redirect back to the main page
header("Location: index.php");
exit();
?>