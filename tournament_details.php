<?php
require_once 'includes/header.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<div class='container'><p>Invalid Tournament ID.</p></div>";
    require_once 'includes/footer.php';
    exit();
}

$tournament_id = intval($_GET['id']);

$stmt = $conn->prepare("SELECT * FROM tournaments WHERE id = ?");
$stmt->bind_param("i", $tournament_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    echo "<div class='container'><p>Tournament not found.</p></div>";
    require_once 'includes/footer.php';
    exit();
}
$tournament = $result->fetch_assoc();

// === NEW: Prepare lucky slot count for display ===
$lucky_slots_count = 0;
if (!empty($tournament['lucky_slots'])) {
    $lucky_slots_arr = explode(',', $tournament['lucky_slots']);
    $lucky_slots_count = count($lucky_slots_arr);
}
?>

<div class="tournament-details-container">
    <div class="details-header">
        <h1><?php echo htmlspecialchars($tournament['title']); ?></h1>
        <p class="tournament-status-badge status-<?php echo htmlspecialchars($tournament['status']); ?>">
            <?php echo ucfirst(htmlspecialchars($tournament['status'])); ?>
        </p>
    </div>

    <div class="details-body">
        <div class="info-grid">
            <div class="info-box"><i class="fas fa-coins"></i><span>Prize Pool</span><strong><?php echo htmlspecialchars($tournament['prizePool']); ?> Coins</strong></div>
            <div class="info-box"><i class="fas fa-money-bill-wave"></i><span>Entry Fee</span><strong><?php echo htmlspecialchars($tournament['entryFee']); ?> Coins</strong></div>
            <div class="info-box"><i class="fas fa-users"></i><span>Mode</span><strong><?php echo htmlspecialchars($tournament['type']); ?></strong></div>
            
            <!-- Map Name Display (Fixed) -->
            <div class="info-box"><i class="fas fa-map-marked-alt"></i><span>Map</span><strong><?php echo htmlspecialchars($tournament['map_name'] ?: 'TBA'); ?></strong></div>
            
            <!-- Lucky Slots Count Display (New) -->
            <?php if ($lucky_slots_count > 0): ?>
            <div class="info-box"><i class="fas fa-gift"></i><span>🎉 Lucky Slots</span><strong><?php echo $lucky_slots_count; ?></strong></div>
            <?php endif; ?>
            
            <div class="info-box"><i class="fas fa-clock"></i><span>Starts At</span><strong><?php echo date('d M, h:i A', strtotime($tournament['startTime'])); ?></strong></div>
        </div>

        <div class="details-tabs">
            <button class="tab-link active" data-tab="overview">Overview</button>
            <button class="tab-link" data-tab="participants">Participants</button>
            <button class="tab-link" data-tab="results">Results</button>
        </div>

        <div class="tab-content active" id="overview">
            <div class="details-section"><h3><i class="fas fa-gavel"></i> Rules</h3><div class="rules-content"><?php echo !empty($tournament['rules']) ? nl2br(htmlspecialchars($tournament['rules'])) : '<p>Rules not posted yet.</p>'; ?></div></div>
            <div class="details-section"><h3><i class="fas fa-trophy"></i> Prize Distribution</h3><div class="rules-content"><?php echo !empty($tournament['prize_details']) ? nl2br(htmlspecialchars($tournament['prize_details'])) : '<p>Prize details not available.</p>'; ?></div></div>
        </div>

        <div class="tab-content" id="participants">
            <div class="details-section"><h3><i class="fas fa-users"></i> Registered Players</h3>
                <ul class="participant-list">
                    <?php
                        // Updated to show slot number
                        $p_stmt = $conn->prepare("SELECT u.name, p.join_slot_number FROM participants p JOIN users u ON p.user_id = u.id WHERE p.tournament_id = ? ORDER BY p.join_slot_number ASC");
                        $p_stmt->bind_param("i", $tournament_id); $p_stmt->execute(); $p_result = $p_stmt->get_result();
                        if ($p_result->num_rows > 0) {
                            while($p = $p_result->fetch_assoc()) { 
                                echo '<li><span class="slot-number">#' . htmlspecialchars($p['join_slot_number']) . '</span> <i class="fas fa-user-shield"></i> ' . htmlspecialchars($p['name']) . '</li>'; 
                            }
                        } else { 
                            echo '<p>No players have joined yet.</p>'; 
                        }
                    ?>
                </ul>
            </div>
        </div>

        <div class="tab-content" id="results">
            <div class="details-section"><h3><i class="fas fa-poll"></i> Final Standings</h3>
                <?php
                    $res_stmt = $conn->prepare("SELECT r.*, u.name as user_name FROM tournament_results r JOIN users u ON r.user_id = u.id WHERE r.tournament_id = ? ORDER BY r.rank ASC");
                    $res_stmt->bind_param("i", $tournament_id); $res_stmt->execute(); $res_result = $res_stmt->get_result();
                ?>
                <?php if ($res_result->num_rows > 0): ?>
                    <table class="results-table"><thead><tr><th>Rank</th><th>Player</th><th>Kills</th><th>Prize Won</th></tr></thead>
                        <tbody><?php while($row = $res_result->fetch_assoc()): ?>
                            <tr><td class="rank-cell">#<?php echo htmlspecialchars($row['rank']); ?></td><td><?php echo htmlspecialchars($row['user_name']); ?></td><td><?php echo htmlspecialchars($row['kills']); ?></td><td><i class="fas fa-coins"></i> <?php echo htmlspecialchars($row['prize_won']); ?></td></tr>
                        <?php endwhile; ?></tbody></table>
                <?php else: echo '<p>Results have not been posted yet.</p>'; endif; ?>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.details-tabs .tab-link').forEach(tab => {
        tab.addEventListener('click', function() {
            document.querySelectorAll('.details-tabs .tab-link').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            document.querySelectorAll('.details-body .tab-content').forEach(c => c.classList.remove('active'));
            document.getElementById(this.dataset.tab).classList.add('active');
        });
    });
});
</script>
<?php require_once 'includes/footer.php'; ?>