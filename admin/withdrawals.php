<?php
require_once 'includes/header.php';

// Handle Approve/Reject actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $withdrawal_id = intval($_GET['id']);

    // UPDATED: Fetch the pending withdrawal from the 'withdrawals' table
    $stmt = $conn->prepare("SELECT user_id, amount FROM withdrawals WHERE id = ? AND status = 'pending'");
    $stmt->bind_param("i", $withdrawal_id);
    $stmt->execute();
    $withdrawal = $stmt->get_result()->fetch_assoc();

    if ($withdrawal) {
        $user_id = $withdrawal['user_id'];
        $amount = $withdrawal['amount'];
        
        $conn->begin_transaction();
        try {
            if ($action == 'approve') {
                // Action: Approve - Coins are already deducted. We just update the status.
                // UPDATED: Update the status in the 'withdrawals' table
                $update_stmt = $conn->prepare("UPDATE withdrawals SET status = 'approved' WHERE id = ?");
                $update_stmt->bind_param("i", $withdrawal_id);
                $update_stmt->execute();
            
            } elseif ($action == 'reject') {
                // Action: Reject
                // Step 1: Refund the coins back to the user's account.
                $refund_stmt = $conn->prepare("UPDATE users SET coins = coins + ? WHERE id = ?");
                $refund_stmt->bind_param("di", $amount, $user_id);
                $refund_stmt->execute();

                // Step 2: UPDATED: Update the status in the 'withdrawals' table
                $update_stmt = $conn->prepare("UPDATE withdrawals SET status = 'rejected' WHERE id = ?");
                $update_stmt->bind_param("i", $withdrawal_id);
                $update_stmt->execute();
            }
            
            $conn->commit();
        } catch (Exception $e) {
            $conn->rollback();
        }
    }
    
    header("Location: withdrawals.php");
    exit();
}
?>

<div class="page-header"><h1>Manage Withdrawals</h1></div>
<div class="card">
    <h2>All Withdrawal Requests</h2>
    <div class="table-container">
    <table>
        <thead>
    <tr>
        <th>User</th>
        <th>Details</th> <!-- "Phone" ki jagah "Details" likh diya hai -->
        <th>Amount</th>
        <th>Method</th>
        <th>Date</th>
        <th>Status</th>
        <th>Actions</th>
    </tr>
</thead>
        <tbody>
        <?php
        // The existing SQL query already fetches all columns from 'withdrawals' (w.*), so no change needed here.
        $sql = "SELECT w.*, u.name as user_name FROM withdrawals w JOIN users u ON w.user_id = u.id ORDER BY w.created_at DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0):
            while($row = $result->fetch_assoc()):
        ?>
                    <tr>
            <td><?php echo htmlspecialchars($row['user_name']); ?></td>
            
            <!-- YEH NAYI LOGIC HAI DETAILS DIKHANE KI -->
            <td class="withdrawal-details">
                <?php if ($row['method'] == 'diamonds'): ?>
                    <pre><?php echo htmlspecialchars($row['details']); ?></pre>
                <?php else: ?>
                    <strong>Name:</strong> <?php echo htmlspecialchars($row['name']); ?><br>
                    <strong>Phone:</strong> <?php echo htmlspecialchars($row['phone']); ?>
                <?php endif; ?>
            </td>

            <td><?php echo $row['amount']; ?> Coins</td>
            
            <!-- METHOD DIKHANE KA BEHTAR TAREEQA -->
            <td>
                <span class="status-<?php echo $row['status']; ?>">
                    <?php echo ucfirst(htmlspecialchars($row['method'])); ?>
                </span>
            </td>

            <td><?php echo date('d M, Y', strtotime($row['created_at'])); ?></td>
            <td><span class="status-<?php echo $row['status']; ?>"><?php echo ucfirst($row['status']); ?></span></td>
            <td class="action-links">
                <?php if ($row['status'] == 'pending'): ?>
                <a href="withdrawals.php?action=approve&id=<?php echo $row['id']; ?>" class="edit-link" onclick="return confirm('Approve this withdrawal request?');">Approve</a>
                <a href="withdrawals.php?action=reject&id=<?php echo $row['id']; ?>" class="delete-link" onclick="return confirm('Are you sure you want to REJECT this request? The user will be refunded.');">Reject</a>
                <?php else: echo '-'; endif; ?>
            </td>
        </tr>
        <?php
            endwhile;
        else:
        ?>
        <!-- CHANGE 3: Updated colspan to 7 to match the new number of columns -->
        <tr><td colspan="7" style="text-align:center;">No withdrawal requests.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once 'includes/footer.php'; ?>