<?php
// MUKAMMAL NAYA CODE - (SIRF TOP WINS MEIN WEEKLY/MONTHLY)
session_start();
require_once __DIR__ . '/includes/db.php';
date_default_timezone_set('Asia/Karachi'); // Timezone set karna ek achi practice hai

header('Content-Type: application/json');

$type = isset($_GET['type']) ? $_GET['type'] : 'coins';
$period = isset($_GET['period']) ? $_GET['period'] : 'all-time';

$leaderboard_data = [];
$sql = "";

// === YEH NAYA AUR ASLI LOGIC HAI ===
// Rule: Agar type 'coins' hai, to hamesha all-time data dikhao
if ($type === 'coins') {
    // Coins ke liye hamesha all-time query chalegi
    $sql = "SELECT id, name, correct_id_name, uid, coins, wins, avatar_url, level, privacy_settings FROM users ORDER BY coins DESC, id ASC LIMIT 3";

} else { // type === 'wins'
    // Sirf Wins ke liye weekly/monthly ka logic chalega
    if ($period === 'all-time') {
        // All-Time Wins
        $sql = "SELECT id, name, correct_id_name, uid, coins, wins, avatar_url, level, privacy_settings FROM users ORDER BY wins DESC, id ASC LIMIT 3";
    } else {
        // Weekly/Monthly Wins
        $interval = ($period === 'weekly') ? 'INTERVAL 7 DAY' : 'INTERVAL 1 MONTH';
        $sql = "
            SELECT u.id, u.name, u.correct_id_name, u.uid, u.avatar_url, u.level, u.privacy_settings, COUNT(w.id) as total_value
            FROM win_logs w
            JOIN users u ON w.user_id = u.id
            WHERE w.win_timestamp >= DATE_SUB(NOW(), $interval)
            GROUP BY u.id
            ORDER BY total_value DESC, u.id ASC
            LIMIT 3
        ";
    }
}

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'DB Error: ' . $conn->error]);
    exit();
}
$stmt->execute();
$result = $stmt->get_result();

// --- Baki ka data process karne wala code waisa hi hai ---
$badges_result = $conn->query("SELECT level_required, badge_image_url FROM level_badges ORDER BY level_required DESC");
$badges = [];
while ($row = $badges_result->fetch_assoc()) {
    $badges[$row['level_required']] = $row['badge_image_url'];
}

function get_badge_for_level($level, $all_badges) {
    foreach ($all_badges as $level_req => $badge_url) {
        if ($level >= $level_req) {
            return $badge_url;
        }
    }
    return null;
}

while ($row = $result->fetch_assoc()) {
    $settings = json_decode($row['privacy_settings'] ?? '[]', true) ?? [];
    $display_name = !empty(trim($row['correct_id_name'])) ? htmlspecialchars($row['correct_id_name']) : htmlspecialchars($row['name']);
    if (!empty($settings['hide_correct_id_name']) && !empty($settings['hide_name'])) { $display_name = '******'; }
    elseif (!empty($settings['hide_correct_id_name'])) { $display_name = htmlspecialchars($row['name']); }
    elseif (!empty($settings['hide_name'])) { $display_name = !empty(trim($row['correct_id_name'])) ? htmlspecialchars($row['correct_id_name']) : '******'; }

    $uid_display = !empty($settings['hide_uid']) ? '********' : htmlspecialchars($row['uid']);
    
    $value_to_show = 0;
    // Naya logic value dikhane ke liye
    if ($type === 'coins' || ($type === 'wins' && $period === 'all-time')) {
        $value_to_show = ($type === 'coins') ? (int)$row['coins'] : (int)$row['wins'];
        if ($type === 'coins' && !empty($settings['hide_coins'])) {
             $value_to_show = '****';
        }
    } else { // Sirf weekly/monthly wins ke liye
        $value_to_show = (int)$row['total_value'];
    }
    
    $badge_url = get_badge_for_level($row['level'], $badges);

    $leaderboard_data[] = [
        'name' => $display_name,
        'uid' => $uid_display,
        'value' => $value_to_show, // Generic 'value' key JS ke liye
        'avatar' => htmlspecialchars($row['avatar_url']),
        'badge' => $badge_url ? htmlspecialchars($badge_url) : null
    ];
}

$stmt->close();
echo json_encode(['success' => true, 'data' => $leaderboard_data]);
?>