        </main>
    </div>

    <!-- ========= OLD MODAL FOR TRANSACTIONS (Kept as it is) ========= -->
    <div class="admin-modal" id="detailsModal">
        <!-- ... your existing transaction modal content ... -->
    </div>
    
    <!-- ========================================================= -->
    <!-- ========= USER DETAILS MODAL (Updated with UID fields) ========= -->
    <!-- ========================================================= -->
    <div class="admin-modal" id="userModal">
        <div class="admin-modal-content large">
            <div class="admin-modal-header">
                <h2 id="userModalTitle">User Details</h2>
                <span class="admin-modal-close" id="userModalClose">×</span>
            </div>
            <div id="userModalBody">
                <div id="userModalLoader" style="text-align:center; padding: 2rem;">Loading...</div>
                <div id="userModalContent" style="display:none;">
                    <div class="details-grid">
                        <div class="detail-item"><strong>User ID:</strong> <span id="modalUserId"></span></div>
                        <div class="detail-item"><strong>Name:</strong> <span id="modalUserName"></span></div>
                        
                        <!-- NEW: Added fields for UIDs -->
                        <div class="detail-item"><strong>Registered UID:</strong> <span id="modalUserUID" style="color:#4f46e5; font-weight:bold;"></span></div>
                        <div class="detail-item" id="tourneyUIDContainer" style="display:none;"><strong>Tournament UID:</strong> <span id="modalTourneyUID" style="color:#10b981; font-weight:bold;"></span></div>
                        
                        <div class="detail-item"><strong>Email:</strong> <span id="modalUserEmail"></span></div>
                        <div class="detail-item"><strong>Phone:</strong> <span id="modalUserPhone"></span></div>
                        <div class="detail-item"><strong>Coins:</strong> <span id="modalUserCoins"></span></div>
                        <div class="detail-item"><strong>Role:</strong> <span id="modalUserRole"></span></div>
                        <div class="detail-item full-width"><strong>Registered:</strong> <span id="modalUserRegistered"></span></div>
                    </div>
                    
                    <div class="manage-coins-section">
                        <h4>Manage Coins</h4>
                        <form id="deductCoinsForm">
                            <input type="hidden" name="user_id" id="formUserId">
                            <div class="form-group"><label>Deduct Amount</label><input type="number" name="deduct_amount" placeholder="e.g., 50" required></div>
                            <button type="submit" class="btn btn-danger">Deduct Coins</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>


<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // --- OLD SCRIPT FOR TRANSACTION MODAL ---
    // ... your existing script for this modal ...

    // --- SCRIPT FOR USER DETAILS MODAL (Updated) ---
    const userModal = document.getElementById('userModal');
    if (userModal) {
        const userModalClose = document.getElementById('userModalClose');
        const userModalLoader = document.getElementById('userModalLoader');
        const userModalContent = document.getElementById('userModalContent');
        
        document.body.addEventListener('click', function(e) {
            if (e.target.closest('.view-user-btn')) {
                const button = e.target.closest('.view-user-btn');
                const userId = button.dataset.userid;
                // UPDATED: Get the tournament-specific ID if it exists
                const tourneyId = button.dataset.tourneyid || null;
                openUserModal(userId, tourneyId);
            }
        });

        // UPDATED: Function now accepts a second parameter for tournament UID
        function openUserModal(userId, tourneyId) {
            userModal.style.display = 'flex';
            userModalLoader.style.display = 'block';
            userModalContent.style.display = 'none';

            // Hide tournament-specific UID field by default
            document.getElementById('tourneyUIDContainer').style.display = 'none';

            fetch(`get_user_details.php?user_id=${userId}`)
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        const user = result.data;
                        document.getElementById('modalUserId').textContent = user.id;
                        document.getElementById('modalUserName').textContent = user.name;
                        document.getElementById('modalUserEmail').textContent = user.email;
                        document.getElementById('modalUserPhone').textContent = user.phone || 'N/A';
                        document.getElementById('modalUserCoins').textContent = user.coins;
                        document.getElementById('modalUserRole').textContent = user.role;
                        document.getElementById('modalUserRegistered').textContent = user.created_at_formatted;
                        document.getElementById('formUserId').value = user.id;

                        // NEW: Populate UID fields
                        document.getElementById('modalUserUID').textContent = user.freefire_id || 'Not Set';
                        if (tourneyId) {
                            document.getElementById('modalTourneyUID').textContent = tourneyId;
                            document.getElementById('tourneyUIDContainer').style.display = 'block';
                        }
                        
                        userModalLoader.style.display = 'none';
                        userModalContent.style.display = 'block';
                    } else { /* ... error handling ... */ }
                })
                .catch(error => { /* ... error handling ... */ });
        }
        
        // --- Close actions and form submission (No changes needed here) ---
        userModalClose.addEventListener('click', () => userModal.style.display = 'none');
        userModal.addEventListener('click', (e) => { if (e.target === userModal) userModal.style.display = 'none'; });
        document.getElementById('deductCoinsForm').addEventListener('submit', function(e) { /* ... form submission logic ... */ });
    }
});
</script>
</body>
</html>