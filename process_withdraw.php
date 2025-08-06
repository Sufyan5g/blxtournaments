<?php
require_once 'includes/db.php';
header('Content-Type: application/json');

// Default response
$response = ['success' => false, 'title' => 'Error', 'message' => 'An unknown error occurred.'];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = "You must be logged in to make a withdrawal.";
    echo json_encode($response);
    exit();
}

$user_id = $_SESSION['user_id'];
$method = sanitize_input($_POST['method'] ?? '');

// Transaction shuru karein taake data safe rahe
$conn->begin_transaction();

try {
    // Pehle se variables define kar lein
    $name = null;
    $phone = null;
    $cost = 0;
    $details = null;

    // Condition 1: Agar method cash hai
    if ($method === 'easypaisa' || $method === 'jazzcash') {
        $name = sanitize_input($_POST['name'] ?? '');
        $phone = sanitize_input($_POST['phone'] ?? '');
        $amount = filter_input(INPUT_POST, 'withdraw_amount', FILTER_VALIDATE_FLOAT);

        if (empty($name) || empty($phone) || !$amount || $amount <= 0) {
            throw new Exception("Please fill all cash withdrawal fields correctly.");
        }
        $cost = $amount; // Cash mein amount hi cost hai

    // Condition 2: Agar method diamonds hai
    } elseif ($method === 'diamonds') {
        $prices = [
            '13_diamonds' => 25, '35_diamonds' => 55, '70_diamonds' => 105, '140_diamonds' => 210,
            '355_diamonds' => 520, '713_diamonds' => 1040, '1426_diamonds' => 2080, '3565_diamonds' => 5200,
            '7130_diamonds' => 10400, '14260_diamonds' => 20500, 'weekly_membership' => 360, 'monthly_membership' => 1550
        ];

        $package = sanitize_input($_POST['diamond_package'] ?? '');
        $uid = sanitize_input($_POST['uid'] ?? '');
        $ign = sanitize_input($_POST['ign'] ?? '');

        if (empty($package)) {
            throw new Exception("Please select a package.");
        }
        if (empty($uid)) {
            throw new Exception("Please enter your Player UID.");
        }
        if (!isset($prices[$package])) {
            throw new Exception("Invalid package selected. Please refresh the page.");
        }
        
        $cost = $prices[$package];
        $details = "Package: " . str_replace('_', ' ', $package) . "\nUID: " . $uid . "\nIGN: " . $ign;

    } else {
        throw new Exception("Please select a valid withdrawal method.");
    }
    
    // Ab common logic jo dono ke liye chalegi
    if ($cost <= 0) {
        throw new Exception("Invalid amount or cost calculated.");
    }

    // User ke coins check karein
    $stmt_check = $conn->prepare("SELECT coins FROM users WHERE id = ? FOR UPDATE");
    $stmt_check->bind_param("i", $user_id);
    $stmt_check->execute();
    $user = $stmt_check->get_result()->fetch_assoc();

    if (!$user || $cost > $user['coins']) {
        throw new Exception("Not enough coins. Required: $cost, You have: " . ($user['coins'] ?? 0));
    }

    // Coins kaat lein
    $stmt_deduct = $conn->prepare("UPDATE users SET coins = coins - ? WHERE id = ?");
    $stmt_deduct->bind_param("di", $cost, $user_id);
    $stmt_deduct->execute();
    
    // Universal INSERT query jo dono ke liye kaam karegi
    $stmt_insert = $conn->prepare("INSERT INTO withdrawals (user_id, name, phone, amount, method, details, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
    $stmt_insert->bind_param("issdss", $user_id, $name, $phone, $cost, $method, $details);
    if(!$stmt_insert->execute()){
         throw new Exception("Database insert failed: " . $stmt_insert->error);
    }

    // Agar sab theek hai to save karein
    $conn->commit();
    
    $response = ['success' => true, 'title' => 'Request Submitted', 'message' => 'Your withdrawal request has been submitted.'];

} catch (Exception $e) {
    // Agar koi ghalti ho to sab kuch wapas kar dein
    $conn->rollback();
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>