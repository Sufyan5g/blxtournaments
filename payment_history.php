<?php
require_once 'includes/header.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$transactions = [];

// --- FETCH DEPOSITS WITH ERROR CHECKING ---
$stmt_dep = $conn->prepare("SELECT amount, status, created_at FROM deposits WHERE user_id = ?");
// This check is crucial. If prepare fails, it will tell you why.
if ($stmt_dep === false) {
    die("Error preparing the deposits query: " . htmlspecialchars($conn->error));
}
$stmt_dep->bind_param("i", $user_id);
$stmt_dep->execute();
$deposits = $stmt_dep->get_result();
while ($row = $deposits->fetch_assoc()) {
    $transactions[] = [
        'type' => 'Deposit',
        'amount' => $row['amount'],
        'status' => ucfirst($row['status']),
        'date' => strtotime($row['created_at'])
    ];
}
$stmt_dep->close();

// --- FETCH WITHDRAWALS WITH ERROR CHECKING ---
$stmt_wd = $conn->prepare("SELECT amount, status, created_at FROM withdrawals WHERE user_id = ?");
// This check is crucial.
if ($stmt_wd === false) {
    die("Error preparing the withdrawals query: " . htmlspecialchars($conn->error));
}
$stmt_wd->bind_param("i", $user_id);
$stmt_wd->execute();
$withdrawals = $stmt_wd->get_result();
while ($row = $withdrawals->fetch_assoc()) {
    $transactions[] = [
        'type' => 'Withdrawal',
        'amount' => $row['amount'],
        'status' => ucfirst($row['status']),
        'date' => strtotime($row['created_at'])
    ];
}
$stmt_wd->close();

// Sort all transactions by date in descending order
if (!empty($transactions)) {
    usort($transactions, function($a, $b) {
        return $b['date'] - $a['date'];
    });
}
?>

<div class="container">
    <div class="page-title-container">
        <h1 class="page-title">Payment History</h1>
        <a href="/profile.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Profile</a>
    </div>

    <div class="history-table-container">
        <?php if (!empty($transactions)): ?>
            <table class="history-table">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Amount (Coins/₹)</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
    <?php foreach ($transactions as $tx): ?>
        <tr class="tx-<?php echo strtolower($tx['type']); ?> status-<?php echo strtolower($tx['status']); ?>">
            
            <!-- Yeh naye `data-label` attributes add kiye gaye hain -->
            <td data-label="Type">
                <i class="fas <?php echo ($tx['type'] == 'Deposit') ? 'fa-arrow-down' : 'fa-arrow-up'; ?>"></i>
                <?php echo htmlspecialchars($tx['type']); ?>
            </td>
            <td data-label="Amount"><?php echo htmlspecialchars($tx['amount']); ?></td>
            <td data-label="Status"><span class="status-badge"><?php echo htmlspecialchars($tx['status']); ?></span></td>
            <td data-label="Date"><?php echo date('d M, Y h:i A', $tx['date']); ?></td>
            
        </tr>
    <?php endforeach; ?>
</tbody>
            </table>
        <?php else: ?>
            <p class="no-history">You have no transaction history yet.</p>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>