<?php
require_once 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['message'] = "You must be logged in.";
    $_SESSION['message_type'] = "error";
    header("Location: /login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

// Handle Deposit
if (isset($_POST['confirm_deposit'])) {
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
    $transaction_id = sanitize_input($_POST['transaction_id']);
    
    if (isset($_FILES['screenshot']) && $_FILES['screenshot']['error'] == 0) {
        $target_dir = "uploads/screenshots/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0755, true);
        $file_name = uniqid() . '-' . basename($_FILES["screenshot"]["name"]);
        $target_file = $target_dir . $file_name;
        
        if (move_uploaded_file($_FILES["screenshot"]["tmp_name"], $target_file)) {
            $stmt = $conn->prepare("INSERT INTO transactions (user_id, type, amount, status, transaction_id_user, screenshot_path) VALUES (?, 'deposit', ?, 'pending', ?, ?)");
            $stmt->bind_param("idss", $user_id, $amount, $transaction_id, $target_file);
            if ($stmt->execute()) {
                $_SESSION['message'] = "Deposit request submitted and is pending verification.";
                $_SESSION['message_type'] = "success";
            } else {
                $_SESSION['message'] = "Database error."; $_SESSION['message_type'] = "error";
            }
        } else {
            $_SESSION['message'] = "Error uploading screenshot."; $_SESSION['message_type'] = "error";
        }
    } else {
        $_SESSION['message'] = "Screenshot is required."; $_SESSION['message_type'] = "error";
    }
}

// Handle Withdraw
if (isset($_POST['request_withdraw'])) {
    $amount = filter_input(INPUT_POST, 'withdraw_amount', FILTER_VALIDATE_FLOAT);
    $result = $conn->query("SELECT coins FROM users WHERE id = $user_id");
    $current_coins = $result->fetch_assoc()['coins'];

    if ($amount > 0 && $amount <= $current_coins) {
        $stmt = $conn->prepare("INSERT INTO transactions (user_id, type, amount, status) VALUES (?, 'withdraw', ?, 'pending')");
        $stmt->bind_param("id", $user_id, $amount);
        if ($stmt->execute()) {
             $_SESSION['message'] = "Withdrawal request submitted and is pending approval."; $_SESSION['message_type'] = "success";
        } else {
             $_SESSION['message'] = "Database error."; $_SESSION['message_type'] = "error";
        }
    } else {
         $_SESSION['message'] = "Invalid withdrawal amount or insufficient balance."; $_SESSION['message_type'] = "error";
    }
}

header("Location: /index.php");
exit();
?>