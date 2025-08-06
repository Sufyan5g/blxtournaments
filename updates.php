<?php 
require_once 'includes/header.php'; 
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

$user_id = $_SESSION['user_id'];

// Get the logged-in user's registration date to filter old updates
$stmt_user_date = $conn->prepare("SELECT created_at FROM users WHERE id = ?");
$stmt_user_date->bind_param("i", $user_id);
$stmt_user_date->execute();
$user_info = $stmt_user_date->get_result()->fetch_assoc();
$user_registration_date = $user_info['created_at'];
$stmt_user_date->close();


/**
 * Function to process the [user] shortcode and generate a styled profile card.
 */
function process_user_shortcode($matches) {
    global $conn;
    $user_id_to_show = intval($matches[1]);

    if ($user_id_to_show > 0) {
        $stmt = $conn->prepare("
            SELECT u.name, u.uid, u.avatar_url, u.level, b.badge_image_url
            FROM users u
            LEFT JOIN level_badges b ON b.level_required = (
                SELECT MAX(level_required) 
                FROM level_badges 
                WHERE level_required <= u.level
            )
            WHERE u.id = ?
        ");
        $stmt->bind_param("i", $user_id_to_show);
        $stmt->execute();
        $user_data = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user_data) {
            $avatar_url = htmlspecialchars($user_data['avatar_url']);
            $badge_url = !empty($user_data['badge_image_url']) ? htmlspecialchars($user_data['badge_image_url']) : '';
            
            $player_card_html = '
<div class="embedded-user-card">
    <div class="avatar-frame-container">
        <img src="/' . $avatar_url . '" class="avatar-image">
        ' . (!empty($badge_url) ? '<img src="/' . $badge_url . '" class="badge-overlay">' : '') . '
    </div>
    <div class="user-info-text">
        <strong>' . htmlspecialchars($user_data['name']) . '</strong>
        <div class="user-meta">
            <span>UID: ' . htmlspecialchars($user_data['uid']) . '</span>
            <span class="desktop-only-separator"> | </span>
            <span>Level: ' . htmlspecialchars($user_data['level']) . '</span>
        </div>
    </div>
</div>';

            return $player_card_html;
        }
    }
    return '';
}

// Fetch relevant updates for the user
$stmt = $conn->prepare("
    SELECT u.id, u.title, u.content, u.created_at, r.id as read_status
    FROM updates u
    LEFT JOIN user_updates_read_status r ON u.id = r.update_id AND r.user_id = ?
    WHERE (u.target_user_id IS NULL) AND (u.created_at >= ?)
    ORDER BY u.created_at DESC
");
$stmt->bind_param("is", $user_id, $user_registration_date);
$stmt->execute();
$updates = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Mark updates as read (logic remains the same)
// ... [Your existing code to mark updates as read] ...
if(count($updates) > 0){
    $unread_updates_ids = [];
    foreach ($updates as $update) {
        if (!$update['read_status']) {
            $unread_updates_ids[] = $update['id'];
        }
    }
    if (!empty($unread_updates_ids)) {
        $stmt_mark_read = $conn->prepare("INSERT INTO user_updates_read_status (user_id, update_id) VALUES (?, ?)");
        foreach ($unread_updates_ids as $update_id) {
            $stmt_mark_read->bind_param("ii", $user_id, $update_id);
            $stmt_mark_read->execute();
        }
        $stmt_mark_read->close();
    }
}
?>
<style>
/* Naye, behtar styles jo screenshot se match karte hain */
.update-container {
    max-width: 800px;
    margin: 20px auto;
    padding: 15px;
}

.update-item {
    background-color: #2c2c3e;
    margin-bottom: 20px;
    border-radius: 12px;
    overflow: hidden;
    border: 1px solid #4a4a6a;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}

.update-title {
    background-color: #4a4a6a;
    padding: 12px 20px;
    font-weight: bold;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.update-title .title-text {
    font-size: 1.1em;
    color: #fff;
}

.update-title .date {
    font-size: 0.8em;
    color: #ccc;
}

/* Content ab hamesha nazar aayega */
.update-content {
    padding: 20px;
    display: block; /* Hamesha visible rakhein */
    line-height: 1.6;
    color: #f0f0f0;
}

/* Player card ke styles pehle se theek hain, unko nahi chherna */
.embedded-user-card {
    background: #1e1e2f;
    border: 1px solid #4a4a6a;
    border-radius: 8px;
    padding: 15px;
    margin: 15px 0;
    display: flex;
    align-items: center;
    gap: 15px;
}

.avatar-frame-container {
    position: relative;
    width: 60px; /* Thora sa adjust kiya gaya hai screenshot ke hisab se */
    height: 60px;
    flex-shrink: 0;
}

.avatar-image {
    position: absolute;
    top: 50%;
    left: 50%;
    width: 48%; /* Aapke pehle wale code ke hisab se behtar sizing */
    height: 48%;
    transform: translate(-50%, -50%);
    border-radius: 50%;
    object-fit: cover;
    z-index: 1;
}

.badge-overlay {
    position: absolute;
    top: -5px; /* Thori si fine-tuning */
    left: -5px;
    width: 70px;
    height: 70px;
    pointer-events: none;
    z-index: 2;
}

.user-info-text strong {
    font-size: 1.1em;
    color: #fff;
}

.user-meta {
    font-size: 0.9em;
    color: #ccc;
}
</style>

<div class="update-container">
    <h1 style="text-align: center; color: var(--text-highlight); margin-bottom: 30px;">Updates & Notifications</h1>

    <?php if (empty($updates)): ?>
        <p style="text-align: center; color: #aaa;">No new updates available for you.</p>
    <?php else: ?>
        <?php foreach ($updates as $update): 
            $processed_content = preg_replace_callback('/\[user id="(\d+)"\]/', 'process_user_shortcode', $update['content']);
        ?>
            <div class="update-item">
                <div class="update-title">
                    <span><i class="fas fa-bullhorn"></i> <?php echo htmlspecialchars($update['title']); ?></span>
                    <span class="date"><?php echo date("d M, Y", strtotime($update['created_at'])); ?></span>
                </div>
                <div class="update-content">
                    <?php echo $processed_content; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
document.querySelectorAll('.update-title').forEach(title => {
    title.addEventListener('click', () => {
        const content = title.nextElementSibling;
        const isVisible = content.style.display === 'block';
        document.querySelectorAll('.update-content').forEach(c => c.style.display = 'none');
        content.style.display = isVisible ? 'none' : 'block';
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>