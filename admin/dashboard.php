<?php
require_once 'includes/header.php';
$total_users = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'user'")->fetch_assoc()['count'];
$total_tournaments = $conn->query("SELECT COUNT(*) as count FROM tournaments")->fetch_assoc()['count'];
$pending_deposits = $conn->query("SELECT COUNT(*) as count FROM transactions WHERE type='deposit' AND status='pending'")->fetch_assoc()['count'];
$pending_withdrawals = $conn->query("SELECT COUNT(*) as count FROM transactions WHERE type='withdraw' AND status='pending'")->fetch_assoc()['count'];
?>
<div class="page-header"><h1>Dashboard</h1></div>
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem;">
    <div class="card"><h3>Total Users</h3><p style="font-size: 2rem; font-weight: 700;"><?php echo $total_users; ?></p></div>
    <div class="card"><h3>Tournaments</h3><p style="font-size: 2rem; font-weight: 700;"><?php echo $total_tournaments; ?></p></div>
    <div class="card"><h3>Pending Deposits</h3><p style="font-size: 2rem; font-weight: 700;"><?php echo $pending_deposits; ?></p></div>
    <div class="card"><h3>Pending Withdrawals</h3><p style="font-size: 2rem; font-weight: 700;"><?php echo $pending_withdrawals; ?></p></div>
</div>
<?php require_once 'includes/footer.php'; ?>