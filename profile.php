<?php
require_once 'includes/header.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

// === NAYA CODE SHURU: UPDATES KO "READ" MARK KARNE KA LOGIC ===
$user_id_for_updates = $_SESSION['user_id'];
// Woh updates jo user ke liye hain ya sab ke liye hain, aur abhi tak read nahi huin
$unread_stmt = $conn->prepare("
    SELECT u.id FROM updates u
    LEFT JOIN user_updates_read_status r ON u.id = r.update_id AND r.user_id = ?
    WHERE (u.target_user_id = ? OR u.target_user_id IS NULL) AND r.id IS NULL
");
if ($unread_stmt) {
    $unread_stmt->bind_param("ii", $user_id_for_updates, $user_id_for_updates);
    $unread_stmt->execute();
    $unread_updates = $unread_stmt->get_result();

    if ($unread_updates->num_rows > 0) {
        $mark_read_stmt = $conn->prepare("INSERT INTO user_updates_read_status (user_id, update_id) VALUES (?, ?)");
        if ($mark_read_stmt) {
            while ($update = $unread_updates->fetch_assoc()) {
                $mark_read_stmt->bind_param("ii", $user_id_for_updates, $update['id']);
                $mark_read_stmt->execute();
            }
            $mark_read_stmt->close();
        }
    }
    $unread_stmt->close();
}
// === NAYA CODE KHATAM ===

$user_id = $_SESSION['user_id'];
$errors = []; 
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $name = sanitize_input($_POST['name']);
    $phone = sanitize_input($_POST['phone']);
    $uid = sanitize_input($_POST['uid']);
    $correct_id_name = sanitize_input($_POST['correct_id_name']);
    
    $privacy_settings = [
        'hide_coins' => isset($_POST['hide_coins']) ? 1 : 0,
        'hide_name' => isset($_POST['hide_name']) ? 1 : 0,
        'hide_correct_id_name' => isset($_POST['hide_correct_id_name']) ? 1 : 0,
        'hide_uid' => isset($_POST['hide_uid']) ? 1 : 0,
    ];
    $privacy_settings_json = json_encode($privacy_settings);

    $avatar_path = null;
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == UPLOAD_ERR_OK) {
        $target_dir = "uploads/avatars/";
        if (!is_dir($target_dir)) { mkdir($target_dir, 0755, true); }
        
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        $file_ext = strtolower(pathinfo($_FILES["avatar"]["name"], PATHINFO_EXTENSION));
        if (in_array($file_ext, $allowed_types) && $_FILES["avatar"]["size"] < 2000000) {
            $new_file_name = uniqid('avatar_', true) . '.' . $file_ext;
            $target_file = $target_dir . $new_file_name;
            if (move_uploaded_file($_FILES["avatar"]["tmp_name"], $target_file)) {
                $avatar_path = $target_file;
            } else { $errors[] = "Error: Could not move the uploaded file."; }
        } else { $errors[] = "Invalid file type or size (Max 2MB)."; }
    }

    if (empty($name) || empty($phone)) { $errors[] = "Name and Phone Number cannot be empty."; }
    if (!preg_match('/^03\d{9}$/', $phone)) { $errors[] = "Please enter a valid Pakistani phone number."; }

    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE users SET name=?, phone=?, uid=?, correct_id_name=?, privacy_settings=? WHERE id=?");
        $stmt->bind_param("sssssi", $name, $phone, $uid, $correct_id_name, $privacy_settings_json, $user_id);
        $stmt->execute();
        $stmt->close();

        if ($avatar_path) {
            $stmt_avatar = $conn->prepare("UPDATE users SET avatar_url = ? WHERE id = ?");
            $stmt_avatar->bind_param("si", $avatar_path, $user_id);
            $stmt_avatar->execute();
            $stmt_avatar->close();
        }
        $success = "Profile updated successfully!";
        $_SESSION['user_name'] = $name;
    }
}

// Fetch all necessary data for the page
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

$user_privacy_settings = json_decode($user['privacy_settings'], true) ?? [];

// Fetch current equipped badge
$current_badge = null;
$stmt_badge = $conn->prepare("SELECT badge_name, badge_image_url FROM level_badges WHERE level_required <= ? ORDER BY level_required DESC LIMIT 1");
$stmt_badge->bind_param("i", $user['level']);
$stmt_badge->execute();
$badge_result = $stmt_badge->get_result();
if ($badge_result->num_rows > 0) { $current_badge = $badge_result->fetch_assoc(); }
$stmt_badge->close();

// Fetch ALL badges for the carousel
$all_badges_result = $conn->query("SELECT * FROM level_badges ORDER BY level_required ASC");
$all_badges = $all_badges_result->fetch_all(MYSQLI_ASSOC);
$current_badge_index = 0; // Default index
foreach ($all_badges as $index => $badge) {
    if ($current_badge && $badge['badge_image_url'] == $current_badge['badge_image_url']) {
        $current_badge_index = $index;
        break;
    }
}

// XP Calculation
$xp_per_level = 100;
$next_level_xp_target = $user['level'] * $xp_per_level;
$xp_for_current_level = ($user['level'] - 1) * $xp_per_level;
$xp_earned_in_level = $user['xp'] - $xp_for_current_level;
$xp_needed_for_level = $next_level_xp_target - $xp_for_current_level;
$progress_percentage = ($xp_needed_for_level > 0) ? ($xp_earned_in_level / $xp_needed_for_level) * 100 : 100;

// Fetch rewards
$rewards_result = $conn->query("SELECT * FROM level_rewards ORDER BY level_milestone ASC");
$rewards = $rewards_result->fetch_all(MYSQLI_ASSOC);
?>

<div class="container"> <!-- Renamed from form-container for clarity -->
    <!-- Profile Header with Interactive Badge Carousel -->
    <div class="profile-header-new">
        <div class="badge-carousel-container">
            <div class="profile-avatar-wrapper">
                 <img src="/<?php echo htmlspecialchars($user['avatar_url']); ?>" alt="Profile Avatar" class="profile-avatar-bg">
            </div>
            <div class="badge-carousel-overlay">
                <div class="badge-carousel-track">
                    <?php foreach ($all_badges as $badge): ?>
                        <?php $is_locked = $user['level'] < $badge['level_required']; ?>
                        <div class="badge-item <?php if($is_locked) echo 'locked'; ?>" 
                             data-badge-name="<?php echo htmlspecialchars($badge['badge_name']); ?>"
                             data-level-required="<?php echo $badge['level_required']; ?>"
                             data-is-locked="<?php echo $is_locked ? 'true' : 'false'; ?>">
                            <img src="/<?php echo htmlspecialchars($badge['badge_image_url']); ?>" class="avatar-frame">
                            <?php if($is_locked): ?>
                                <div class="lock-overlay">
                                    <i class="fas fa-lock"></i>
                                    <span>LOCKED</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <button class="carousel-arrow prev" id="prevBadgeBtn"><i class="fas fa-chevron-left"></i></button>
            <button class="carousel-arrow next" id="nextBadgeBtn"><i class="fas fa-chevron-right"></i></button>
        </div>
        <div class="profile-info-text">
            <h2><?php echo htmlspecialchars ($user['name']); ?></h2>
            <p id="badgeInfoText">Level <?php echo $user['level']; ?> - <?php echo htmlspecialchars($current_badge['badge_name'] ?? 'Newbie'); ?></p>
        </div>
    </div>
    
    <!-- Display Success/Error Messages -->
    <?php if (!empty($errors)): ?><div class="error" style="background-color: #c0392b; color: white; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;"><?php foreach ($errors as $error) echo "<p>$error</p>"; ?></div><?php endif; ?>
    <?php if ($success): ?><div class="success" style="background-color: #27ae60; color: white; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;"><p><?php echo $success; ?></p></div><?php endif; ?>

    <!-- Start of Form and Profile Cards -->
    <h1 style="text-align: center; margin-bottom: 2rem; color: var(--text-highlight);">Edit Profile</h1>
    <form action="profile.php" method="POST" class="profile-form" enctype="multipart/form-data">
        <div class="profile-section-card">
            <h3><i class="fas fa-user-edit"></i> Edit Information</h3>
            <div class="form-group"><label>Correct ID Name</label><input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required></div>
            <div class="form-group"><label>Email (Cannot be changed)</label><input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled></div>
            <div class="form-group"><label>Phone Number</label><input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" required></div>
            <div class="form-group"><label>UID</label><input type="text" name="uid" placeholder="Enter your game UID" value="<?php echo htmlspecialchars($user['uid'] ?? ''); ?>"></div>
        </div>
        <div class="profile-section-card">
            <h3><i class="fas fa-camera-retro"></i> Change Profile Picture</h3>
            <input type="file" name="avatar" id="avatar" accept="image/*" style="display: none;">
            <label for="avatar" class="file-upload-label"><i class="fas fa-upload"></i><span>Choose a new photo</span></label>
        </div>
        <div class="profile-section-card">
            <h3><i class="fas fa-user-secret"></i> Privacy Settings</h3>
            <p>Select which information you want to hide from others on the Leaderboard.</p>
            <div class="privacy-options">
                <label class="custom-checkbox-label"><input type="checkbox" name="hide_coins" value="1" <?php echo !empty($user_privacy_settings['hide_coins']) ? 'checked' : ''; ?>><span class="checkbox-visual"></span> Hide Coins</label>
                <label class="custom-checkbox-label"><input type="checkbox" name="hide_name" value="1" <?php echo !empty($user_privacy_settings['hide_name']) ? 'checked' : ''; ?>><span class="checkbox-visual"></span> Hide Correct ID Name</label>
                <label class="custom-checkbox-label"><input type="checkbox" name="hide_uid" value="1" <?php echo !empty($user_privacy_settings['hide_uid']) ? 'checked' : ''; ?>><span class="checkbox-visual"></span> Hide UID</label>
            </div>
        </div>
        <button type="submit" name="update_profile" class="update-profile-btn"><i class="fas fa-sync-alt"></i> Update Profile</button>
    </form>
    
    <!-- Player Progression Card (Outside the form) -->
    <div class="profile-section-card">
        <h3><i class="fas fa-chart-line"></i> Player Progression</h3>
        <div class="xp-info">
            <span class="level">Level <?php echo $user['level']; ?></span>
            <span class="xp-text">XP: <?php echo $xp_earned_in_level; ?> / <?php echo $xp_needed_for_level; ?></span>
        </div>
        <div class="xp-bar-container"><div class="xp-bar-progress" style="width: <?php echo $progress_percentage; ?>%;"></div></div>
        <h4><i class="fas fa-gift"></i> Level Rewards</h4>
        <div class="rewards-list">
            <?php foreach ($rewards as $reward): 
                $can_claim = ($user['level'] >= $reward['level_milestone'] && $user['last_reward_claimed_level'] < $reward['level_milestone']);
                $is_claimed = ($user['last_reward_claimed_level'] >= $reward['level_milestone']);
            ?>
            <div class="reward-item">
                <div class="reward-info">Level <?php echo $reward['level_milestone']; ?><span class="prize"><i class="fas fa-coins"></i> <?php echo $reward['reward_coins']; ?></span></div>
                <?php if ($is_claimed): ?><button class="reward-btn claimed" disabled><i class="fas fa-check"></i> Claimed</button>
                <?php elseif ($can_claim): ?><button class="reward-btn" data-level="<?php echo $reward['level_milestone']; ?>">Claim</button>
                <?php else: ?><button class="reward-btn locked" disabled><i class="fas fa-lock"></i> Locked</button><?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Final action buttons -->
<div class="profile-actions">
    <?php
        // === NOTIFICATION DOT LOGIC FOR BUTTON ===
        $unread_check_stmt = $conn->prepare("
            SELECT COUNT(*) as unread_count FROM updates u
            LEFT JOIN user_updates_read_status r ON u.id = r.update_id AND r.user_id = ?
            WHERE (u.target_user_id = ? OR u.target_user_id IS NULL) AND r.id IS NULL
        ");
        $unread_check_stmt->bind_param("ii", $user_id, $user_id);
        $unread_check_stmt->execute();
        $unread_count = $unread_check_stmt->get_result()->fetch_assoc()['unread_count'];
        $unread_check_stmt->close();
    ?>
    <a href="/payment_history.php" class="logout-link-btn history-btn">Payment History</a>
    
    <!-- === NAYA UPDATE BUTTON === -->
    <a href="/updates.php" class="logout-link-btn" style="background-color: #3498db; position: relative;">
        <i class="fas fa-bell"></i> Updates
        <?php if ($unread_count > 0): ?>
            <span style="position: absolute; top: 5px; right: 5px; height: 12px; width: 12px; background-color: #e74c3c; border-radius: 50%; border: 2px solid #3498db;"></span>
        <?php endif; ?>
    </a>

    <!-- === NAYA PRIVACY POLICY BUTTON === -->
    <a href="/privacy_policy.php" class="logout-link-btn" style="background-color: #95a5a6;">
        <i class="fas fa-shield-alt"></i> Privacy Policy
    </a>

    <a href="/uploads/download.apk" class="logout-link-btn download-btn">
        <i class="fas fa-download"></i> Download App
    </a>
    <a href="https://wa.me/923450069480?text=Hello%2C%20I%20need%20Help." class="logout-link-btn support-btn" target="_blank">Customer Support</a>
    
    <a href="/logout.php" class="logout-link-btn">Logout</a>
</div>
</div>

<!-- ========================================================= -->
<!-- ==        JAVASCRIPT FOR BADGE CAROUSEL                == -->
<!-- ========================================================= -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const track = document.querySelector('.badge-carousel-track');
    if (!track) return;

    const badges = document.querySelectorAll('.badge-item');
    const nextBtn = document.getElementById('nextBadgeBtn');
    const prevBtn = document.getElementById('prevBadgeBtn');
    const badgeInfoText = document.getElementById('badgeInfoText');

    let currentIndex = <?php echo $current_badge_index; ?>;
    const totalBadges = badges.length;

    function updateCarousel() {
        if (badges.length > 0) {
            track.style.transform = `translateX(-${currentIndex * 100}%)`;
            
            const currentBadge = badges[currentIndex];
            const badgeName = currentBadge.dataset.badgeName;
            const levelRequired = currentBadge.dataset.levelRequired;
            const isLocked = currentBadge.dataset.isLocked === 'true';

            if (isLocked) {
                badgeInfoText.textContent = `Locked (Requires Level ${levelRequired})`;
                badgeInfoText.classList.add('locked-text');
            } else {
                badgeInfoText.textContent = `Level <?php echo $user['level']; ?> - ${badgeName}`;
                badgeInfoText.classList.remove('locked-text');
            }
        }
    }

    nextBtn.addEventListener('click', () => {
        currentIndex = (currentIndex + 1) % totalBadges;
        updateCarousel();
    });

    prevBtn.addEventListener('click', () => {
        currentIndex = (currentIndex - 1 + totalBadges) % totalBadges;
        updateCarousel();
    });

    // Initial call to set the carousel to the correct position on page load
    updateCarousel();
});
</script>

<?php require_once 'includes/footer.php'; ?>