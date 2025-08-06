<?php
// admin/index.php
require_once dirname(__DIR__, 1) . '/includes/db.php';
// ... baaki ka code ...

// Handle All POST Requests on this page
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    
    // Handle Join Tournament
    if (isset($_POST['confirm_join'])) {
        // ... (Join logic is here) ...
    }
    // Handle Deposit
    elseif (isset($_POST['confirm_deposit'])) {
        $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
        $transaction_id = sanitize_input($_POST['transaction_id']);
        if (isset($_FILES['screenshot']) && $_FILES['screenshot']['error'] == 0) {
            $target_dir = "uploads/screenshots/";
            if (!is_dir($target_dir)) { mkdir($target_dir, 0755, true); }
            $file_name = uniqid() . '-' . basename($_FILES["screenshot"]["name"]);
            $target_file = $target_dir . $file_name;
            if (move_uploaded_file($_FILES["screenshot"]["tmp_name"], $target_file)) {
                $stmt = $conn->prepare("INSERT INTO transactions (user_id, type, amount, status, transaction_id_user, screenshot_path) VALUES (?, 'deposit', ?, 'pending', ?, ?)");
                $stmt->bind_param("idss", $user_id, $amount, $transaction_id, $target_file);
                if ($stmt->execute()) {
                    $_SESSION['success_title'] = "Deposit Pending";
                    $_SESSION['success_message'] = "Your deposit request is under verification.";
                }
            }
        }
    }
    // Handle Withdraw
    elseif (isset($_POST['request_withdraw'])) {
        // ... (Withdraw logic is here) ...
    }
    header("Location: index.php"); // Redirect after processing
    exit();
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- ======================= -->
<!-- MODALS (POPUPS) SECTION -->
<!-- ======================= -->

<!-- Wallet Modal -->
<div class="modal" id="walletModal">
    <div class="modal-content">
        <span class="close-btn">×</span>
        <h2><i class="fas fa-wallet"></i> Wallet</h2>
        <div class="tab-container">
            <div class="tab active" data-tab="deposit"><i class="fas fa-arrow-down"></i> Deposit</div>
            <div class="tab" data-tab="withdraw"><i class="fas fa-arrow-up"></i> Withdraw</div>
        </div>
        <div class="tab-content active" id="deposit">
            <div class="step" id="depositStep1">
                <div class="form-group"><label>Your Name</label><input type="text" id="d_name" required></div>
                <div class="form-group"><label>Phone Number</label><input type="tel" id="d_phone" placeholder="03XX-XXXXXXX" required></div>
                <div class="form-group"><label>Amount (₹)</label><input type="number" id="d_amount" required></div>
                <div class="form-group"><label>Payment Method</label><select id="d_method" required><option value="">Select a method</option><option value="easypaisa">EasyPaisa</option><option value="jazzcash">JazzCash</option></select></div>
                <div class="payment-details" style="display: none;"><p>Send payment to: <strong>03450069480 (M.Sufyan)</strong></p></div>
                <button type="button" class="submit-btn" id="depositNextBtn">Next →</button>
            </div>
            <div class="step" id="depositStep2" style="display:none;">
                <form action="index.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="amount" id="final_amount">
                    <div class="form-group"><label><i class="fas fa-receipt"></i> Transaction ID</label><input type="text" name="transaction_id" required></div>
                    <div class="form-group"><label><i class="fas fa-camera"></i> Screenshot</label><input type="file" name="screenshot" id="screenshot" accept="image/*" style="display:none" required><label for="screenshot" class="file-upload-label"><i class="fas fa-upload"></i> Choose File</label><span id="fileName" class="file-name">No file chosen</span></div>
                    <div class="button-group"><button type="button" class="back-btn" id="depositBackBtn">← Back</button><button type="submit" name="confirm_deposit" class="submit-btn">Confirm</button></div>
                </form>
            </div>
        </div>
        <div class="tab-content" id="withdraw">
            <form action="index.php" method="POST">
                <div class="form-group"><label>Your Name</label><input type="text" name="withdraw_name" required></div>
                <div class="form-group"><label>Phone Number</label><input type="tel" name="withdraw_phone" required></div>
                <div class="form-group"><label>Amount (Coins)</label><input type="number" name="withdraw_amount" required></div>
                <div class="form-group"><label>Method</label><select name="withdraw_method" required><option value="">Select</option><option value="easypaisa">EasyPaisa</option><option value="jazzcash">JazzCash</option></select></div>
                <button type="submit" name="request_withdraw" class="submit-btn">Request Withdrawal</button>
            </form>
        </div>
    </div>
</div>

<!-- Join Tournament Modal -->
<div class="modal" id="joinModal">
    <div class="modal-content">
        <span class="close-btn">×</span>
        <h2 id="modalTournamentTitle">Join Tournament</h2>
        <form id="joinForm" action="index.php" method="POST">
            <input type="hidden" name="tournament_id" id="modal_tournament_id">
            <div class="form-group"><label for="freefire_id"><i class="fas fa-id-card"></i> Free Fire ID</label><input type="text" name="freefire_id" id="freefire_id" required></div>
            <div class="form-group"><label for="teamName"><i class="fas fa-users"></i> Team Name (Optional)</label><input type="text" name="teamName" id="teamName"></div>
            <div class="payment-summary" id="joinPaymentSummary"></div>
            <button type="submit" name="confirm_join" class="submit-btn"><i class="fas fa-check-circle"></i> Confirm & Join</button>
        </form>
    </div>
</div>

<!-- Success/Pending Modal -->
<div class="modal" id="successModal">
    <div class="modal-content success-content">
        <span class="close-btn">×</span>
        <div class="success-icon"><i class="fas fa-check-circle"></i></div>
        <h2 id="successTitle">Success</h2>
        <p id="successMessage"></p>
        <button class="submit-btn" id="successOkBtn">OK</button>
    </div>
</div>

<!-- MAIN PAGE CONTENT -->
<div class="hero"><h1>Free Fire Tournaments</h1><p>Join tournaments and win real cash prizes!</p></div>
<?php if (isset($_SESSION['success_message'])) { /* ... */ } ?>
<div class="tournament-grid">
    <?php
    $result = $conn->query("SELECT * FROM tournaments WHERE status = 'upcoming'");
    if ($result && $result->num_rows > 0):
        while($row = $result->fetch_assoc()):
            // ... (HTML for tournament cards is the same as before) ...
    ?>
            <div class="tournament-card">
                <!-- ... -->
            </div>
    <?php
        endwhile;
    endif;
    ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
