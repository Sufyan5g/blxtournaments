<?php
require_once 'includes/header.php';

// --- HANDLE APPROVAL/REJECTION LOGIC AT THE TOP ---
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $deposit_id = intval($_GET['id']);

    // UPDATED: Fetch from the 'deposits' table
    $stmt = $conn->prepare("SELECT user_id, amount FROM deposits WHERE id = ? AND status = 'pending'");
    $stmt->bind_param("i", $deposit_id);
    $stmt->execute();
    $deposit = $stmt->get_result()->fetch_assoc();

    if ($deposit) {
        $user_id = $deposit['user_id'];
        $amount = $deposit['amount']; // This is the amount to be added as coins

        if ($action == 'approve') {
            $conn->begin_transaction();
            try {
                // 1. Add coins to the user's account
                $stmt_update_user = $conn->prepare("UPDATE users SET coins = coins + ? WHERE id = ?");
                $stmt_update_user->bind_param("di", $amount, $user_id);
                $stmt_update_user->execute();
                
                // 2. UPDATED: Update the status in the 'deposits' table
                $stmt_update_trans = $conn->prepare("UPDATE deposits SET status = 'approved' WHERE id = ?");
                $stmt_update_trans->bind_param("i", $deposit_id);
                $stmt_update_trans->execute();

                $conn->commit();
            } catch (Exception $e) {
                $conn->rollback();
                $_SESSION['error_message'] = "An error occurred while approving the deposit.";
            }
        } elseif ($action == 'reject') {
            // UPDATED: Update the status in the 'deposits' table
            $stmt_reject = $conn->prepare("UPDATE deposits SET status = 'rejected' WHERE id = ?");
            $stmt_reject->bind_param("i", $deposit_id);
            $stmt_reject->execute();
        }
    }
    
    header("Location: deposits.php");
    exit();
}
?>

<div class="page-header">
    <h1>Manage Deposits</h1>
</div>

<div class="card">
    <h2>Pending Deposit Requests</h2>
    <div class="table-container">
    <table>
        <thead>
            <tr>
                <th>User</th>
                <th>Phone</th>
                <th>Amount (₹)</th>
                <th>TID</th>
                <th>Screenshot</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // UPDATED: Fetch from the 'deposits' table and join with 'users'
            $sql = "SELECT d.*, u.name as user_name FROM deposits d JOIN users u ON d.user_id = u.id WHERE d.status = 'pending' ORDER BY d.created_at ASC";
            $result = $conn->query($sql);
            if ($result->num_rows > 0):
                while($row = $result->fetch_assoc()):
            ?>
            <tr>
                <td><?php echo htmlspecialchars($row['user_name']); ?></td>
                <td><?php echo htmlspecialchars($row['phone']); ?></td>
                <td><?php echo $row['amount']; ?></td>
                <td><?php echo htmlspecialchars($row['transaction_id']); ?></td>
                <td><a href="/<?php echo $row['screenshot']; ?>" target="_blank" class="btn btn-primary">View</a></td>
                <td><?php echo date('d M, Y', strtotime($row['created_at'])); ?></td>
                <td class="action-links">
                    <a href="deposits.php?action=approve&id=<?php echo $row['id']; ?>" class="btn btn-success" onclick="return confirm('Approve this deposit? Coins will be added to user account.');">Approve</a>
                    <a href="deposits.php?action=reject&id=<?php echo $row['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to reject this deposit?');">Reject</a>
                </td>
            </tr>
            <?php endwhile; else: ?>
            <tr><td colspan="7" style="text-align:center;">No pending deposit requests.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</div>

<div class="card" style="margin-top: 2rem;">
    <h2>Completed Deposits (Last 10)</h2>
    <div class="table-container">
    <table>
        <thead>
            <tr>
                <th>User</th>
                <th>Amount (₹)</th>
                <th>Status</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // UPDATED: Fetch from the 'deposits' table
            $sql = "SELECT d.*, u.name as user_name FROM deposits d JOIN users u ON d.user_id = u.id WHERE d.status != 'pending' ORDER BY d.created_at DESC LIMIT 10";
            $result = $conn->query($sql);
            if ($result->num_rows > 0):
                while($row = $result->fetch_assoc()):
            ?>
            <tr>
                <td><?php echo htmlspecialchars($row['user_name']); ?></td>
                <td><?php echo $row['amount']; ?></td>
                <td>
                    <span class="status-<?php echo strtolower($row['status']); ?>">
                        <?php echo ucfirst($row['status']); ?>
                    </span>
                </td>
                <td><?php echo date('d M, Y', strtotime($row['created_at'])); ?></td>
            </tr>
            <?php endwhile; else: ?>
            <tr><td colspan="4" style="text-align:center;">No completed deposit history.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</div>
<?php require_once 'includes/footer.php'; ?>