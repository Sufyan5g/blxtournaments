<?php require_once __DIR__ . '/db.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BLX Tournaments</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- === YEH LINK THEEK KIYA GAYA HAI === -->
    <!-- Aapke file structure ke mutabik, yeh path hona chahiye -->
    <link rel="icon" type="uploads/png" href="uploads/favicon.png">
    <link rel="stylesheet" href="assets/css/style.css"> 
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <header>
        <!-- =========== PROFESSIONAL HEADER LAYOUT =========== -->
        <nav class="new-header">
            <!-- Left Side -->
            <!-- Left Side -->
<div class="header-left">
    <?php if (isset($_SESSION['user_id'])): 
        // === NOTIFICATION DOT LOGIC START ===
        $user_id_for_dot = $_SESSION['user_id'];
        $unread_stmt = $conn->prepare("
            SELECT COUNT(*) as unread_count FROM updates u
            LEFT JOIN user_updates_read_status r ON u.id = r.update_id AND r.user_id = ?
            WHERE (u.target_user_id = ? OR u.target_user_id IS NULL) AND r.id IS NULL
        ");
        $unread_stmt->bind_param("ii", $user_id_for_dot, $user_id_for_dot);
        $unread_stmt->execute();
        $has_unread = $unread_stmt->get_result()->fetch_assoc()['unread_count'] > 0;
        $unread_stmt->close();
        // === NOTIFICATION DOT LOGIC END ===
    ?>
        <a href="/profile.php" class="profile-link profile-btn" style="position: relative;">
            <i class="fas fa-user"></i> Profile
            <?php if ($has_unread): ?>
                <span style="position: absolute; top: 5px; right: 5px; height: 10px; width: 10px; background-color: #e74c3c; border-radius: 50%; border: 2px solid #2c3e50;"></span>
            <?php endif; ?>
        </a>
    <?php else: ?>
        <a href="/login.php" class="profile-link profile-btn">
            <i class="fas fa-sign-in-alt"></i> Login
        </a>
    <?php endif; ?>
</div>

            <!-- Center (Logo and Leaderboard) -->
            <!-- Center (Sirf Logo) -->
<div class="header-center">
    <a href="/index.php" class="logo-link"><i class="fas fa-trophy"></i> BLX TOURNAMENTS</a>
</div>

<!-- Right Side (Leaderboard aur Coins Wallet) -->
<div class="header-right">
     <?php if (isset($_SESSION['user_id'])): ?>
        <!-- Buttons ko group karne ke liye ek extra div -->
        <div class="header-actions">
            <a href="#" id="leaderboardBtn" class="profile-link profile-btn leaderboard-nav-btn">
                <i class="fas fa-star"></i> Leaderboard
            </a>
            <?php
                $userId = $_SESSION['user_id'];
                $stmt = $conn->prepare("SELECT coins FROM users WHERE id = ?");
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $result = $stmt->get_result();
                $userCoins = $result->num_rows > 0 ? $result->fetch_assoc()['coins'] : 0;
                $stmt->close();
            ?>
            <button class="wallet-btn" id="walletBtn">
                <i class="fas fa-wallet"></i> 
                <span id="walletAmount"><?php echo $userCoins; ?></span> Coins
            </button>
        </div>
    <?php else: ?>
        <a href="/register.php" class="profile-link profile-btn register-btn">
           Register
        </a>
    <?php endif; ?>
</div>
        </nav>
    </header>

    <!-- ======== LEADERBOARD MODAL (POPUP) ======== -->
    <div id="leaderboardModal" class="modal">
        <div class="modal-content leaderboard-modal-content">
            <span class="close-btn" id="closeLeaderboardModal">×</span>
            <div class="leaderboard-header">
                <h2>🏆 Leaderboard</h2>
                <?php if (isset($_SESSION['user_id'])): ?>
                <a href="/profile.php" title="Leaderboard Settings" class="leaderboard-settings-icon">
                    <i class="fas fa-cog"></i>
                </a>
                <?php endif; ?>
            </div>
            
            <div class="leaderboard-tabs">
                <button class="leaderboard-tab-link active" data-type="coins">
                    <i class="fas fa-coins"></i> Top Coins
                </button>
                <button class="leaderboard-tab-link" data-type="wins">
                    <i class="fas fa-trophy"></i> Top Wins
                </button>
            </div>
            <div class="period-tabs" style="display: none;">
    <button class="period-tab-btn" data-period="weekly">Weekly</button>
    <button class="period-tab-btn" data-period="monthly">Monthly</button>
    <button class="period-tab-btn active" data-period="all-time">All-Time</button>
</div>
            <div id="leaderboard-data-container">
                <div class="leaderboard-loader"></div>
            </div>
        </div>
    </div>
    <!-- ============================================= -->

    <main class="container">