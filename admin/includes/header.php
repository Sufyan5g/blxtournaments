<?php
require_once dirname(__DIR__, 2) . '/includes/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') { header("Location: /login.php"); exit(); }
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html><html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Admin Panel</title><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"><link rel="stylesheet" href="/assets/css/admin_style.css"></head>
<body>
<div class="sidebar">
    <div class="logo"><a href="dashboard.php">BLX Admin</a></div>
    <nav><ul>
        <li><a href="dashboard.php" class="<?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li><a href="tournaments.php" class="<?php echo ($current_page == 'tournaments.php') ? 'active' : ''; ?>"><i class="fas fa-trophy"></i> Tournaments</a></li>
        <li><a href="users.php" class="<?php echo ($current_page == 'users.php') ? 'active' : ''; ?>"><i class="fas fa-users"></i> Users</a></li>
        <li><a href="deposits.php" class="<?php echo ($current_page == 'deposits.php') ? 'active' : ''; ?>"><i class="fas fa-wallet"></i> Deposits</a></li>
        <li><a href="withdrawals.php" class="<?php echo ($current_page == 'withdrawals.php') ? 'active' : ''; ?>"><i class="fas fa-hand-holding-usd"></i> Withdrawals</a></li>
        
        <!-- === NAYA LINK YAHAN ADD KIYA GAYA HAI === -->
        <li><a href="game_settings.php" class="<?php echo ($current_page == 'game_settings.php') ? 'active' : ''; ?>"><i class="fas fa-cogs"></i> Game Settings</a></li>
        <li><a href="updates.php" class="<?php echo ($current_page == 'updates.php') ? 'active' : ''; ?>"><i class="fas fa-bullhorn"></i> Send Updates</a></li>
        
    </ul></nav>
</div>
<div class="main-content">
    <header class="header">
        <h2>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h2>
        <div><a href="/index.php" target="_blank">View Site</a> | <a href="logout.php">Logout</a></div>
    </header>
    <main class="page-content">
