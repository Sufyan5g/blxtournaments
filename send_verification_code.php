<?php
require_once __DIR__ . '/includes/db.php';
header('Content-Type: application/json');

if (!isset($_POST['email']) || empty($_POST['email'])) {
    echo json_encode(['success' => false, 'message' => 'Email is required.']);
    exit();
}

$email = sanitize_input($_POST['email']);

// =========================================================================
// NAYA LOGIC: Check karo ke email pehle se registered aur VERIFIED to nahi hai
// =========================================================================
$stmt_check = $conn->prepare("SELECT id FROM users WHERE email = ? AND is_verified = 1");
$stmt_check->bind_param("s", $email);
$stmt_check->execute();
$stmt_check->store_result();

if ($stmt_check->num_rows > 0) {
    // Agar user mil gaya jo pehle se verified hai
    echo json_encode([
        'success' => false,
        'status'  => 'warning', // Naya status bheja hai JavaScript ke liye
        'message' => 'This email address is already registered. Please login.'
    ]);
    $stmt_check->close();
    $conn->close();
    exit();
}
$stmt_check->close();
// =================== End of New Logic ==============================


// Agar email registered nahi hai, to purana logic chalao
$verification_code = rand(100000, 999999);

// Ab user ko ya to insert karo ya update karo (agar wo unverified hai)
$stmt = $conn->prepare("INSERT INTO users (email, verification_code, is_verified) VALUES (?, ?, 0) ON DUPLICATE KEY UPDATE verification_code = ?, is_verified = 0");
$stmt->bind_param("sss", $email, $verification_code, $verification_code);

if ($stmt->execute()) {
    // Email Bhejne ka Code (Brevo API) - Ismein koi change nahi
    $apiKey = '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';
    $emailData = [
        'sender' => ['name' => 'BLX Tournaments', 'email' => 'blxtournamentsotp@gmail.com'],
        'to' => [['email' => $email]],
        'subject' => 'Your Verification Code for BLX Tournaments',
        'htmlContent' => 'Hello,<br><br>Your verification code for BLX Tournaments is: <b>' . $verification_code . '</b>'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.brevo.com/v3/smtp/email");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($emailData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['accept: application/json', 'api-key: ' . $apiKey, 'content-type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpcode == 201) {
        echo json_encode(['success' => true, 'message' => 'Verification code has been sent to your email.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to send email. Please try again.']);
    }
    
} else {
    echo json_encode(['success' => false, 'message' => 'Database Error: Could not process request.']);
}

$stmt->close();
$conn->close();
?>