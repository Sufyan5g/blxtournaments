<?php require_once 'includes/header.php'; ?>

<!-- ============== MODALS (POPUPS) ============== -->
<!-- WALLET MODAL -->
<div class="modal" id="walletModal">
    <div class="modal-content">
        <span class="close-btn" id="closeWalletModal">×</span>
        <h2 class="modal-title"><i class="fas fa-wallet"></i> Wallet</h2>
        <div class="tab-container">
            <div class="tab active" data-tab="deposit-tab"><i class="fas fa-arrow-down"></i> Deposit</div>
            <div class="tab" data-tab="withdraw-tab"><i class="fas fa-arrow-up"></i> Withdraw</div>
        </div>
        <!-- DEPOSIT TAB -->
        <div class="tab-content active" id="deposit-tab">
            <form id="depositForm">
                <div class="step active" id="depositStep1">
                    <div class="form-group"><label>Your Name</label><input type="text" name="name" placeholder="Enter your full name" required></div>
                    <div class="form-group"><label>Phone Number</label><input type="tel" name="phone" placeholder="03XX-XXXXXXX" required></div>
                    <div class="form-group"><label>Amount (₹)</label><input type="number" name="amount" placeholder="Enter amount to deposit" required></div>
                    <div class="form-group">
                        <label>Payment Method</label>
                        <select name="method" id="depositMethod" required>
                            <option value="">Select a method</option>
                            <option value="easypaisa">EasyPaisa</option>
                            <option value="jazzcash">JazzCash</option>
                        </select>
                    </div>
                    <div class="payment-details" id="paymentDetails" style="display: none;">
                        <h4>Payment Instructions</h4>
                        <p>Please send payment to:</p>
                        <div class="account-details">
                            <p><strong>Account Number:</strong> <span>03450069480</span></p>
                            <p><strong>Name:</strong> <span>Ammara Manzoor</span></p>
                        </div>
                    </div>
                    <button type="button" class="submit-btn" id="depositNextBtn">Next <i class="fas fa-arrow-right"></i></button>
                </div>
                <div class="step" id="depositStep2" style="display:none">
                    <div class="form-group"><label><i class="fas fa-receipt"></i> Transaction ID</label><input type="text" name="transaction_id" placeholder="Enter transaction ID" required></div>
                    <div class="form-group">
                        <label><i class="fas fa-camera"></i> Upload Screenshot</label>
                        <input type="file" name="screenshot" id="depositScreenshot" accept="image/*" style="display: none;" required>
                        <label for="depositScreenshot" class="file-upload-label"><i class="fas fa-upload"></i> Choose File</label>
                        <span id="fileName" class="file-name">No file chosen</span>
                    </div>
                    <div class="button-group">
                        <button type="button" class="back-btn" id="depositBackBtn"><i class="fas fa-arrow-left"></i> Back</button>
                        <button type="submit" class="submit-btn">Confirm Deposit</button>
                    </div>
                </div>
            </form>
        </div>
        <!-- WITHDRAW TAB -->
        <!-- WITHDRAW TAB -->
<div class="tab-content" id="withdraw-tab">
     <form id="withdrawForm">
        <!-- Naya Dropdown Method Select Karne Ke Liye -->
        <div class="form-group">
            <label>Withdrawal Method</label>
            <select name="method" id="withdrawal_method" required>
    <option value="">-- Select Method --</option> <!-- Yeh user ke liye behtar hai -->
    <option value="easypaisa">EasyPaisa</option>
    <option value="jazzcash">JazzCash</option>
    <option value="diamonds">Diamonds</option>
</select>
        </div>

        <!-- Fields for EasyPaisa/JazzCash (Yeh pehle se tha, bas div mein daal diya hai) -->
        <div id="cash-fields">
            <div class="form-group"><label>Your Name</label><input type="text" name="name" placeholder="Enter your full name"></div>
            <div class="form-group"><label>Phone Number</label><input type="tel" name="phone" placeholder="03XX-XXXXXXX"></div>
            <div class="form-group"><label>Amount (Coins)</label><input type="number" name="withdraw_amount" placeholder="Enter amount to withdraw"></div>
        </div>

        <!-- Naye Fields Diamonds Ke Liye (Yeh shuru mein chupe rahenge) -->
        <div id="diamond-fields" style="display: none;">
            <div class="form-group">
                <label for="uid">Player UID</label>
                <input type="text" name="uid" placeholder="Enter your game UID">
            </div>
            <div class="form-group">
                <label for="ign">Correct In-Game Name</label>
                <input type="text" name="ign" placeholder="Verify karne ke liye game ka naam">
            </div>
            <div class="form-group">
                <label for="diamond_package">Select Package</label>
                <!-- Yeh naya div yahan paste karein -->
<div id="membership-details-box" style="display:none; margin-top: 15px;"></div>
                <select name="diamond_package" id="diamond_package">
                    <option value="">-- Package Select Karein --</option>
                    <option value="13_diamonds">13 Diamonds</option>
                    <option value="35_diamonds">35 Diamonds</option>
                    <option value="70_diamonds">70 Diamonds</option>
                    <option value="140_diamonds">140 Diamonds</option>
                    <option value="355_diamonds">355 Diamonds</option>
                    <option value="713_diamonds">713 Diamonds</option>
                    <option value="1426_diamonds">1,426 Diamonds</option>
                    <option value="3565_diamonds">3,565 Diamonds</option>
                    <option value="7130_diamonds">7,130 Diamonds</option>
                    <option value="14260_diamonds">14,260 Diamonds</option>
                    <option value="weekly_membership">Weekly Membership</option>
                    <option value="monthly_membership">Monthly Membership</option>
                </select>
            </div>
            <div class="form-group">
                <p style="font-size: 16px; color: #00bcd4;">Cost: <span id="package-cost" style="font-weight: bold;">0</span> Coins</p>
            </div>
        </div>
        
        <button type="submit" class="submit-btn">Request Withdrawal</button>
    </form>
</div>
    </div>
</div>

<!-- JOIN TOURNAMENT MODAL -->
<div class="modal" id="joinModal">
    <div class="modal-content">
        <span class="close-btn" id="closeJoinModal">×</span>
        <h2 class="modal-title"><i class="fas fa-gamepad"></i> Join Tournament</h2>
        <form id="joinForm">
            <input type="hidden" name="tournament_id" id="modal_tournament_id">
            <div id="modalTournamentInfo" class="tournament-details"></div>
            <div class="form-group"><label for="freefire_id">Your Free Fire ID</label><input type="text" name="freefire_id" id="freefire_id" required></div>
            <div class="form-group"><label for="teamName">Team Name (Optional)</label><input type="text" name="teamName" id="teamName"></div>
            <button type="submit" class="submit-btn"><i class="fas fa-check-circle"></i> Confirm & Join</button>
        </form>
    </div>
</div>

<!-- SUCCESS/RESULT MODAL -->
<div class="modal" id="resultModal">
     <div class="modal-content success-content">
        <span class="close-btn" id="closeResultModal">×</span>
        <div class="success-icon"><i id="resultIcon" class="fas fa-check-circle"></i></div>
        <h2 id="resultTitle" class="modal-title">Success</h2>
        <p id="resultMessage">Your action was successful.</p>
        <button class="submit-btn" id="resultOkBtn">OK</button>
    </div>
</div>

<!-- TOURNAMENT UPDATES MODAL -->
<div class="modal" id="updatesModal">
    <div class="modal-content">
        <span class="close-btn" id="closeUpdatesModal">×</span>
        <h2 class="modal-title"><i class="fas fa-info-circle"></i> Tournament Updates</h2>
        <div id="updatesContent" class="updates-container">
            <p>Loading...</p>
        </div>
    </div>
</div>


<!-- Main Page Content -->
<div class="hero">
    <h1>Free Fire Tournaments</h1>
    <p>Join tournaments and win real cash prizes!</p>
</div>

<!-- === YEH TOURNAMENT DIKHANE WALA ASLI SECTION HAI === -->
<div class="tournament-grid">
    <?php
    // Get all tournaments the current user has joined
    $joined_tournaments = [];
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        $stmt_joined = $conn->prepare("SELECT tournament_id FROM participants WHERE user_id = ?");
        $stmt_joined->bind_param("i", $user_id);
        $stmt_joined->execute();
        $result_joined = $stmt_joined->get_result();
        while ($row_joined = $result_joined->fetch_assoc()) {
            $joined_tournaments[] = $row_joined['tournament_id'];
        }
    }

    // Query to get all upcoming tournaments along with the number of participants
    $sql = "SELECT t.*, (SELECT COUNT(DISTINCT team_id) FROM participants p WHERE p.tournament_id = t.id AND p.team_id IS NOT NULL) AS currentTeams, (SELECT COUNT(*) FROM participants p WHERE p.tournament_id = t.id AND p.team_id IS NULL) as currentSolos FROM tournaments t WHERE t.status = 'upcoming' ORDER BY t.startTime ASC";
    $result = $conn->query($sql);

    // Check if there are any tournaments
    if ($result->num_rows > 0):
        // Loop through each tournament and display it
        while($row = $result->fetch_assoc()):
            $isFull = ($row['currentSquads'] >= $row['maxSquads']);
            $has_joined = in_array($row['id'], $joined_tournaments);
    ?>
        <div class="tournament-card">
            <div class="card-header"><?php echo htmlspecialchars($row['title']); ?></div>
            <div class="card-body">
                <span class="tournament-type"><?php echo htmlspecialchars($row['type']); ?></span>
                <div class="tournament-info">
                    <p><span>Entry:</span> <span><?php echo $row['entryFee']; ?> coins</span></p>
                    <p><span>Prize:</span> <span><?php echo $row['prizePool']; ?> coins</span></p>
                    
                    <?php if ($row['per_kill_price'] > 0): ?>
                        <p><span>Per Kill:</span> <span><?php echo htmlspecialchars($row['per_kill_price']); ?> coins</span></p>
                    <?php endif; ?>
                    
                    <?php if (!empty($row['map_name'])): ?>
                        <p><span>Map:</span> <span><?php echo htmlspecialchars($row['map_name']); ?></span></p>
                    <?php endif; ?>

                    <?php 
                    if (!empty($row['lucky_slots'])) {
                        $lucky_slots_array = array_filter(explode(',', $row['lucky_slots']));
                        $lucky_slots_count = count($lucky_slots_array);
                        if ($lucky_slots_count > 0) {
                            echo '<p><span>🎉 Lucky Players:</span> <span>' . $lucky_slots_count . '</span></p>';
                        }
                    }
                    ?>
                    
                    <?php
    if ($row['match_type'] == 'solo') {
        $current_slots = $row['currentSolos'];
        $max_slots = $row['maxSquads'];
        $slot_text = 'Players';
    } else {
        $current_slots = $row['currentTeams'];
        $max_slots = $row['maxSquads'];
        $slot_text = 'Teams';
    }
?>
<p><span>Slots:</span> <span><?php echo $current_slots; ?>/<?php echo $max_slots; ?> <span class="squads-status"><?php echo $slot_text; ?></span></span></p>
                    <p><span>Starts:</span> <span><?php echo date('d M, Y h:i A', strtotime($row['startTime'])); ?></span></p>
                </div>

                <!-- DYNAMIC BUTTON LOGIC -->
<?php if ($has_joined): ?>
    <button class="view-status-btn" data-id="<?php echo $row['id']; ?>">
        <i class="fas fa-eye"></i> View Status
    </button>
<?php else: ?>
    <button class="join-btn" 
            data-id="<?php echo $row['id']; ?>" 
            data-match-type="<?php echo htmlspecialchars($row['match_type']); // <-- YEH NAYI LINE HAI ?>"
            <?php if($isFull) echo 'disabled style="background-color: #4A5568;"'; ?>>
        <?php if ($isFull): ?>
            <i class="fas fa-lock"></i> Slots Full
        <?php else: ?>
            <i class="fas fa-gamepad"></i> Join Tournament
        <?php endif; ?>
    </button>
<?php endif; ?>
            </div>
        </div>
    <?php 
        endwhile; // End of the while loop
    else: 
        // Agar koi tournament na mile to yeh message dikhao
        echo "<p style='text-align:center; grid-column: 1 / -1;'>No upcoming tournaments found.</p>"; 
    endif; 
    ?>
</div> <!-- End of tournament-grid -->

<!-- =================================== -->
<!-- === NAYA: TEAM JOIN MODAL SHURU === -->
<!-- =================================== -->
<div id="joinTeamModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" id="closeJoinTeamModal">&times;</span>
        
        <form id="joinTeamForm">
            <input type="hidden" name="tournament_id" id="team_modal_tournament_id">
            
            <!-- Step 1: Team Name and Leader's FF ID -->
            <div id="joinStep1">
                <h3 class="modal-title"><i class="fas fa-users"></i> Create Your Team</h3>
                <div class="form-group">
                    <label for="team_name">Team Name</label>
                    <input type="text" id="team_name" name="team_name" placeholder="Enter your team name" required>
                </div>
                <div class="form-group">
                    <label for="leader_ff_id">Your Free Fire ID</label>
                    <input type="text" id="leader_ff_id" name="leader_ff_id" placeholder="Enter your in-game ID" value="<?php echo htmlspecialchars($_SESSION['user_freefire_id'] ?? ''); ?>" required>
                </div>
                <button type="button" id="goToStep2" class="submit-btn">Next &rarr;</button>
            </div>

            <!-- Step 2: Invite Teammates -->
            <div id="joinStep2" style="display:none;">
                <h3 class="modal-title"><i class="fas fa-user-plus"></i> Invite Teammates</h3>
                <div class="form-group">
                    <label for="teammateSearchInput">Search by Name or UID</label>
                    <div class="search-wrapper">
                        <input type="text" id="teammateSearchInput" placeholder="Search for players...">
                        <i class="fas fa-search search-icon"></i>
                    </div>
                    <div id="searchResults" class="search-results-container"></div>
                </div>
                
                <h4>Selected Teammates:</h4>
                <ul id="selectedTeammatesList" class="selected-teammates-list">
                    <li>No teammates selected yet.</li>
                </ul>
                
                <div class="modal-actions" style="display: flex; justify-content: space-between; margin-top: 20px;">
                    <button type="button" id="backToStep1" class="btn btn-secondary">&larr; Back</button>
                    <button type="submit" class="btn btn-success">Confirm & Join</button>
                </div>
            </div>
        </form>
    </div>
</div>
<!-- === TEAM JOIN MODAL KHATAM === -->

<!-- ======================================= -->
<!-- === NAYA: INVITATION MODAL SHURU === -->
<!-- ======================================= -->
<?php
// Check for pending invitations for the logged-in user
if (isset($_SESSION['user_id'])) {
    // Pehle se chal rahi team invitations check karein
    $inv_stmt = $conn->prepare("
        SELECT ti.id, u.name as leader_name, tt.team_name, t.title as tournament_title
        FROM team_invitations ti
        JOIN tournament_teams tt ON ti.team_id = tt.id
        JOIN users u ON tt.leader_user_id = u.id
        JOIN tournaments t ON tt.tournament_id = t.id
        WHERE ti.invited_user_id = ? AND ti.status = 'pending'
        LIMIT 1
    ");
    $inv_stmt->bind_param("i", $_SESSION['user_id']);
    $inv_stmt->execute();
    $invitation_result = $inv_stmt->get_result();
    if ($invitation = $invitation_result->fetch_assoc()):
?>
<div id="invitationModal" class="modal" style="display:flex;">
    <div class="modal-content">
        <h3 class="modal-title"><i class="fas fa-envelope"></i> Team Invitation</h3>
        <p>
            <strong><?php echo htmlspecialchars($invitation['leader_name']); ?></strong> has invited you to join team 
            <strong>"<?php echo htmlspecialchars($invitation['team_name']); ?>"</strong> 
            for the tournament 
            <strong>"<?php echo htmlspecialchars($invitation['tournament_title']); ?>"</strong>.
        </p>
        <div class="modal-actions" style="display: flex; justify-content: flex-end; gap: 10px;">
            <button id="rejectInviteBtn" class="btn btn-danger" data-id="<?php echo $invitation['id']; ?>">Reject</button>
            <button id="acceptInviteBtn" class="btn btn-success" data-id="<?php echo $invitation['id']; ?>">Accept</button>
        </div>
    </div>
</div>
<?php 
    endif;
    $inv_stmt->close();
}
?>
<!-- === INVITATION MODAL KHATAM === -->
<!-- =================================== -->
<!-- === NAYA: REPLACE PLAYER MODAL === -->
<!-- =================================== -->
<div id="replacePlayerModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" id="closeReplaceModal">&times;</span>
        <h3 class="modal-title"><i class="fas fa-random"></i> Replace Player</h3>
        <p>You are replacing: <strong id="replacingPlayerName">Player Name</strong></p>
        
        <form id="replacePlayerForm" onsubmit="return false;"> <!-- Form submit hone se rokein -->
            <input type="hidden" name="team_id" id="replace_team_id">
            <input type="hidden" name="old_user_id" id="replace_old_user_id">
            
            <div class="form-group">
                <label for="replaceSearchInput">Search for a new player</label>
                <div class="search-wrapper">
                    <input type="text" id="replaceSearchInput" placeholder="Search by Name or UID...">
                    <i class="fas fa-search search-icon"></i>
                </div>
                <div id="replaceSearchResults" class="search-results-container"></div>
            </div>
        </form>
    </div>
</div>
<!-- ============================================= -->
<!-- === NAYA: PAY FOR TEAMMATE MODAL (SPONSOR) === -->
<!-- ============================================= -->
<div id="payForTeammateModal" class="modal">
    <div class="modal-content">
        <h3 class="modal-title"><i class="fas fa-coins"></i> Pay Entry Fee?</h3>
        <p>
            Player <strong id="teammateToPayFor">Player Name</strong> does not have enough coins to join.
        </p>
        <p>Do you want to pay their entry fee from your account?</p>
        <div class="modal-actions" style="display: flex; justify-content: center; gap: 15px;">
            <button id="cancelPayBtn" class="btn btn-danger">No</button>
            <button id="confirmPayBtn" class="btn btn-success">Yes, Pay Fee</button>
        </div>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>