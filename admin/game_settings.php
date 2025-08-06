<?php
// NAYI FILE: admin/game_settings.php
require_once 'includes/header.php';

// --- HANDLE FORM SUBMISSIONS ---
// Update Level Rewards
if (isset($_POST['update_rewards'])) {
    $levels = $_POST['level_milestone'];
    $coins = $_POST['reward_coins'];

    // Clear existing rewards
    $conn->query("TRUNCATE TABLE level_rewards");

    // Insert new rewards
    $stmt = $conn->prepare("INSERT INTO level_rewards (level_milestone, reward_coins) VALUES (?, ?)");
    foreach ($levels as $index => $level) {
        if (!empty($level) && !empty($coins[$index])) {
            $level_val = intval($level);
            $coin_val = intval($coins[$index]);
            $stmt->bind_param("ii", $level_val, $coin_val);
            $stmt->execute();
        }
    }
    echo "<div class='alert alert-success'>Level rewards updated successfully!</div>";
}
?>

<div class="page-header"><h1>Game & Level Settings</h1></div>

<!-- Level Rewards Management Card -->
<div class="card">
    <h2>Manage Level Rewards</h2>
    <div class="table-container">
    <p>Set how many coins a user gets when they reach a specific level. You can add or remove rows as needed.</p>
    <form action="game_settings.php" method="POST">
        <table id="rewards-table">
            <thead>
                <tr>
                    <th>Level Milestone</th>
                    <th>Reward (Coins)</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $rewards_result = $conn->query("SELECT * FROM level_rewards ORDER BY level_milestone ASC");
                while ($reward = $rewards_result->fetch_assoc()):
                ?>
                <tr>
                    <td><input type="number" name="level_milestone[]" value="<?php echo $reward['level_milestone']; ?>" required></td>
                    <td><input type="number" name="reward_coins[]" value="<?php echo $reward['reward_coins']; ?>" required></td>
                    <td><button type="button" class="btn btn-danger remove-row-btn">Remove</button></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        </div>
        <button type="button" id="add-reward-row" class="btn">Add New Reward Level</button>
        <hr>
        <button type="submit" name="update_rewards" class="btn btn-primary">Save All Rewards</button>
    </form>
</div>

<!-- XP Settings Card (Future Expansion) -->
<div class="card">
    <h2>XP System Settings</h2>
    <div class="table-container">
    <p>Current setting: <strong>100 XP is required for each level up.</strong></p>
    <p>The amount of XP awarded for joining, winning, or participating in a tournament is currently set directly in the code for stability. If you need to change these values, please contact the developer.</p>
    <ul>
        <li>XP for joining: <strong>10 XP</strong> (in `process_join.php`)</li>
        <li>XP for participation: <strong>20 XP</strong> (in `admin/tournaments.php`)</li>
        <li>XP for winning: <strong>50 XP</strong> (in `admin/tournaments.php`)</li>
    </ul>
</div>


<!-- JavaScript for adding/removing rows -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('add-reward-row').addEventListener('click', function() {
        const tableBody = document.querySelector('#rewards-table tbody');
        const newRow = `
            <tr>
                <td><input type="number" name="level_milestone[]" required></td>
                <td><input type="number" name="reward_coins[]" required></td>
                <td><button type="button" class="btn btn-danger remove-row-btn">Remove</button></td>
            </tr>
        `;
        tableBody.insertAdjacentHTML('beforeend', newRow);
    });

    document.querySelector('#rewards-table').addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('remove-row-btn')) {
            e.target.closest('tr').remove();
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>