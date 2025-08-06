document.addEventListener('DOMContentLoaded', function() {
    // ===================================
    // === All Element Selections (Ek Jagah Par) ===
    // ===================================
    const walletBtn = document.getElementById('walletBtn');
    const walletModal = document.getElementById('walletModal');
    const closeWalletModal = document.getElementById('closeWalletModal');
    const joinModal = document.getElementById('joinModal'); // Solo Join Modal
    const closeJoinModal = document.getElementById('closeJoinModal');
    const resultModal = document.getElementById('resultModal');
    const closeResultModal = document.getElementById('closeResultModal');
    const resultOkBtn = document.getElementById('resultOkBtn');
    const tournamentGrid = document.querySelector('.tournament-grid');
    const updatesModal = document.getElementById('updatesModal');
    const closeUpdatesModal = document.getElementById('closeUpdatesModal');
    const updatesContent = document.getElementById('updatesContent');
    const leaderboardBtn = document.getElementById('leaderboardBtn');
    const leaderboardModal = document.getElementById('leaderboardModal');
    const rewardsList = document.querySelector('.rewards-list');
    const withdrawalMethodSelect = document.getElementById('withdrawal_method');
    
    // Naye Team Modal ke elements
    const joinTeamModal = document.getElementById('joinTeamModal');
    const invitationModal = document.getElementById('invitationModal');


    // ===================================
    // === Universal Helper Functions ===
    // ===================================

    function handleFormSubmit(e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        let action = '';

        if (form.id === 'depositForm') action = '/process_deposit.php';
        else if (form.id === 'withdrawForm') action = '/process_withdraw.php';
        else if (form.id === 'joinForm') action = '/process_join.php';
        else if (form.id === 'joinTeamForm') action = '/create_team.php'; // Team form ke liye action

        if (action) {
            const modalToClose = form.closest('.modal');
            if (modalToClose) modalToClose.style.display = 'none';

            fetch(action, { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => { showResult(data); })
                .catch(error => { showResult({ success: false, title: 'Error', message: 'An unexpected network error occurred.' }); });
        }
    }

    function showResult(data) {
        if (!resultModal) return;
        const resultTitle = resultModal.querySelector('#resultTitle');
        const resultMessage = resultModal.querySelector('#resultMessage');
        const resultIcon = resultModal.querySelector('#resultIcon');
        
        if (resultTitle) resultTitle.textContent = data.title;
        if (resultMessage) resultMessage.textContent = data.message;
        if (resultIcon) {
            resultIcon.className = 'fas ' + (data.success ? 'fa-check-circle success' : 'fa-times-circle error');
        }
        
        resultModal.style.display = 'flex';
        
        if (resultOkBtn) {
            resultOkBtn.onclick = () => {
                resultModal.style.display = 'none';
                if (data.success) window.location.reload();
            };
        }
    }
    
function handleViewStatus(button) {
    if (!updatesModal || !updatesContent) return;
    const tournamentId = button.dataset.id;
    updatesContent.innerHTML = '<div class="leaderboard-loader"></div>';
    updatesModal.style.display = 'flex';

    fetch(`/get_team_status.php?tournament_id=${tournamentId}`)
        .then(response => {
             if (!response.ok) { throw new Error('Network response was not ok'); }
             return response.json();
        })
        .then(res => {
            if (!res.success) {
                updatesContent.innerHTML = `<p>${res.message || 'Could not load status.'}</p>`;
                return;
            }
            let html = '';
                        // === NAYA CODE SHURU: ROOM ID/PASSWORD WALA SECTION ===
            html += `
                <div class="room-copy-section">
                    <button class="copy-btn" data-type="id" data-tournament-id="${tournamentId}">
                        <i class="fas fa-door-open"></i> Room ID
                    </button>
                    <button class="copy-btn" data-type="pass" data-tournament-id="${tournamentId}">
                        <i class="fas fa-key"></i> Password
                    </button>
                </div>
            `;
            // === NAYA CODE KHATAM ===
            if (res.is_team) {
                const teamData = res.data;
                html += `<h3 class="team-status-title">Team: ${teamData.team_name}</h3>`;
                html += '<ul class="team-members-list">';
                teamData.members.forEach(member => {
                    const statusClass = member.status;
                    const leaderBadge = member.is_leader ? '<span class="leader-badge">👑 Leader</span>' : '';
                    let replaceBtn = '';
                    // Replace button sirf leader ko dikhega, aur sirf pending/rejected players ke liye
                    if (teamData.is_leader && !member.is_leader && (member.status === 'pending' || member.status === 'rejected')) {
                        replaceBtn = `<button class="replace-btn" data-team-id="${teamData.team_id}" data-old-user-id="${member.user_id}" data-old-user-name="${member.name}">Replace</button>`;
                    }
                    html += `<li class="team-member-item"><div class="member-info"><img src="/${member.avatar_url || 'uploads/avatars/default.png'}" alt="Avatar"><div><strong>${member.name} ${leaderBadge}</strong><small>UID: ${member.uid}</small></div></div><div class="member-status-container"><span class="status-pill ${statusClass}">${member.status}</span>${replaceBtn}</div></li>`;
                });
                html += '</ul>';
                if (teamData.updates && teamData.updates.length > 0) {
                    html += `<div class="team-updates-section"><h4><i class="fas fa-info-circle"></i> Tournament Updates</h4></div>`;
                    teamData.updates.forEach(update => {
                        html += `<div class="update-item"><p>${update.message}</p><span class="update-time">${update.time}</span></div>`;
                    });
                }
            } else {
                if (res.data && res.data.length > 0) {
                    res.data.forEach(update => {
                        html += `<div class="update-item"><p>${update.message}</p><span class="update-time">${update.time}</span></div>`;
                    });
                } else {
                    html += '<p>No updates have been posted for this tournament yet.</p>';
                }
            }
            updatesContent.innerHTML = html;
        })
        .catch(error => {
            updatesContent.innerHTML = '<p>Failed to load status. Please try again later.</p>';
            console.error('Error fetching status:', error);
        });
}

    // ===================================
    // === Specific Modal Logics ===
    // ===================================

    // Wallet Modal
    if (walletBtn && walletModal) {
        const tabs = walletModal.querySelectorAll('.tab');
        const tabContents = walletModal.querySelectorAll('.tab-content');
        const depositStep1 = document.getElementById('depositStep1');
        const depositStep2 = document.getElementById('depositStep2');
        const depositNextBtn = document.getElementById('depositNextBtn');
        const depositBackBtn = document.getElementById('depositBackBtn');
        const depositMethodSelect = document.getElementById('depositMethod');
        const paymentDetailsDiv = document.getElementById('paymentDetails');

        walletBtn.addEventListener('click', () => { walletModal.style.display = 'flex'; resetDepositForm(); });
        if(closeWalletModal) closeWalletModal.addEventListener('click', () => { walletModal.style.display = 'none'; });

        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                tabs.forEach(t => t.classList.remove('active'));
                tabContents.forEach(c => c.classList.remove('active'));
                tab.classList.add('active');
                document.getElementById(tab.dataset.tab).classList.add('active');
            });
        });
        
        if(depositMethodSelect) depositMethodSelect.addEventListener('change', () => { paymentDetailsDiv.style.display = depositMethodSelect.value ? 'block' : 'none'; });
        if(depositNextBtn) depositNextBtn.addEventListener('click', () => {
            if (walletModal.querySelector('[name="name"]').value && walletModal.querySelector('[name="phone"]').value && walletModal.querySelector('[name="amount"]').value && walletModal.querySelector('[name="method"]').value) {
                depositStep1.style.display = 'none';
                depositStep2.style.display = 'block';
            } else { alert('Please fill all fields in Step 1.'); }
        });
        if(depositBackBtn) depositBackBtn.addEventListener('click', () => { depositStep2.style.display = 'none'; depositStep1.style.display = 'block'; });
        
        function resetDepositForm() {
            depositStep1.style.display = 'block';
            depositStep2.style.display = 'none';
            document.getElementById('depositForm').reset();
            paymentDetailsDiv.style.display = 'none';
            document.getElementById('fileName').textContent = 'No file chosen';
        }

        document.getElementById('depositScreenshot').addEventListener('change', function() { document.getElementById('fileName').textContent = this.files.length > 0 ? this.files[0].name : 'No file chosen'; });
        document.getElementById('depositForm').addEventListener('submit', handleFormSubmit);
        document.getElementById('withdrawForm').addEventListener('submit', handleFormSubmit);
    }
    
    // Solo Join Modal
    if (joinModal && closeJoinModal) {
        closeJoinModal.addEventListener('click', () => joinModal.style.display = 'none');
        document.getElementById('joinForm').addEventListener('submit', handleFormSubmit);
    }
    
    // Updates Modal
    if (updatesModal && closeUpdatesModal) {
        closeUpdatesModal.addEventListener('click', () => updatesModal.style.display = 'none');
    }

    // Result Modal
    if (resultModal && closeResultModal) {
        closeResultModal.addEventListener('click', () => resultModal.style.display = 'none');
    }

    // Leaderboard Logic
    if (leaderboardBtn && leaderboardModal) {
        const closeLeaderboardModal = document.getElementById('closeLeaderboardModal');
        const leaderboardTabs = leaderboardModal.querySelectorAll('.leaderboard-tab-link');
        const periodTabsContainer = leaderboardModal.querySelector('.period-tabs');
        const periodTabs = leaderboardModal.querySelectorAll('.period-tab-btn');
        const dataContainer = leaderboardModal.querySelector('#leaderboard-data-container');
        let currentType = 'coins';
        let currentPeriod = 'all-time';

        leaderboardBtn.addEventListener('click', (e) => { e.preventDefault(); leaderboardModal.style.display = 'flex'; if(periodTabsContainer) periodTabsContainer.style.display = 'none'; fetchLeaderboardData('coins', 'all-time'); });
        if(closeLeaderboardModal) closeLeaderboardModal.addEventListener('click', () => { leaderboardModal.style.display = 'none'; });
        window.addEventListener('click', (event) => { if (event.target == leaderboardModal) { leaderboardModal.style.display = 'none'; } });

        if (leaderboardTabs) {
            leaderboardTabs.forEach(tab => {
                tab.addEventListener('click', (e) => {
                    e.preventDefault();
                    leaderboardTabs.forEach(t => t.classList.remove('active'));
                    tab.classList.add('active');
                    currentType = tab.dataset.type;
                    if (currentType === 'coins') { if(periodTabsContainer) periodTabsContainer.style.display = 'none'; fetchLeaderboardData('coins', 'all-time'); } 
                    else { if(periodTabsContainer) periodTabsContainer.style.display = 'flex'; fetchLeaderboardData('wins', 'all-time'); }
                });
            });
        }

        if(periodTabs) {
            periodTabs.forEach(tab => {
                tab.addEventListener('click', (e) => {
                    e.preventDefault();
                    periodTabs.forEach(t => t.classList.remove('active'));
                    tab.classList.add('active');
                    currentPeriod = tab.dataset.period;
                    if (currentType === 'wins') { fetchLeaderboardData('wins', currentPeriod); }
                });
            });
        }
        
        function fetchLeaderboardData(type, period) {
            if(!dataContainer) return;
            dataContainer.innerHTML = '<div class="leaderboard-loader"></div>';
            fetch(`/leaderboard_data.php?type=${type}&period=${period}`)
                .then(response => response.json())
                .then(result => {
                    if (result.success && result.data.length > 0) {
                        let html = '<ul class="leaderboard-list">';
                        const rankIcons = ['🥇', '🥈', '🥉'];
                        result.data.forEach((user, index) => {
                            const scoreValue = typeof user.value === 'string' ? user.value : parseInt(user.value).toLocaleString();
                            const scoreIcon = (type === 'coins') ? 'fas fa-coins' : 'fas fa-trophy';
                            const score = `<i class="${scoreIcon}"></i> ${scoreValue}`;
                            const rankDisplay = rankIcons[index] || `<span class="rank-number">${index + 1}</span>`;
                            const avatarHtml = `<div class="leaderboard-avatar-container"><img src="/${user.avatar || 'uploads/avatars/default.png'}" alt="Avatar" class="leaderboard-avatar">${user.badge ? `<img src="/${user.badge}" alt="Badge" class="leaderboard-badge-frame">` : ''}</div>`;
                            html += `<li class="leaderboard-item"><span class="leaderboard-rank">${rankDisplay}</span>${avatarHtml}<div class="leaderboard-user-info"><div class="leaderboard-user-name">${user.name}</div><div class="leaderboard-user-uid">UID: ${user.uid}</div></div><div class="leaderboard-score">${score}</div></li>`;
                        });
                        html += '</ul>';
                        dataContainer.innerHTML = html;
                    } else {
                        dataContainer.innerHTML = '<p class="no-leaderboard-data">No data available for this category yet.</p>';
                    }
                }).catch(error => { console.error('Leaderboard fetch error:', error); dataContainer.innerHTML = '<p class="no-leaderboard-data">Failed to load leaderboard.</p>'; });
        }
    }
    
    // Claim Reward Logic
    if (rewardsList) {
        rewardsList.addEventListener('click', function(e) {
            if (e.target.classList.contains('claim-btn') && !e.target.disabled) {
                const claimButton = e.target;
                const levelToClaim = claimButton.dataset.level;
                claimButton.disabled = true;
                claimButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                const formData = new FormData();
                formData.append('level', levelToClaim);
                fetch('/claim_reward.php', { method: 'POST', body: formData })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            claimButton.classList.add('claimed-btn');
                            claimButton.innerHTML = '<i class="fas fa-check"></i> Claimed';
                            const walletAmount = document.getElementById('walletAmount');
                            if (walletAmount) walletAmount.textContent = data.new_coin_total;
                            alert(data.message); 
                        } else {
                            alert('Error: ' + data.message);
                            claimButton.disabled = false;
                            claimButton.textContent = 'Claim';
                        }
                    })
                    .catch(error => { console.error('Claim Reward Error:', error); alert('An error occurred.'); claimButton.disabled = false; claimButton.textContent = 'Claim'; });
            }
        });
    }
    
    // Dynamic Withdrawal Form Logic
    if (withdrawalMethodSelect) {
        const cashFields = document.getElementById('cash-fields');
        const diamondFields = document.getElementById('diamond-fields');
        const diamondPackageSelect = document.getElementById('diamond_package');
        const packageCostSpan = document.getElementById('package-cost');
        const membershipBox = document.getElementById('membership-details-box');
        const diamondPrices = { '13_diamonds': 25, '35_diamonds': 55, '70_diamonds': 105, '140_diamonds': 210, '355_diamonds': 520, '713_diamonds': 1040, '1426_diamonds': 2080, '3565_diamonds': 5200, '7130_diamonds': 10400, '14260_diamonds': 20500, 'weekly_membership': 360, 'monthly_membership': 1550 };

        withdrawalMethodSelect.addEventListener('change', function() {
            if (this.value === 'diamonds') { if(cashFields) cashFields.style.display = 'none'; if(diamondFields) diamondFields.style.display = 'block'; } 
            else { if(cashFields) cashFields.style.display = 'block'; if(diamondFields) diamondFields.style.display = 'none'; }
            if (membershipBox) membershipBox.style.display = 'none';
        });
        
        if (diamondPackageSelect) {
            diamondPackageSelect.addEventListener('change', function() {
                const selectedPackage = this.value;
                const cost = diamondPrices[selectedPackage] || 0;
                if(packageCostSpan) packageCostSpan.textContent = cost;
                if (selectedPackage === 'weekly_membership') { membershipBox.innerHTML = '<div class="membership-info"><h4><i class="fas fa-star"></i> Weekly Membership</h4><p>Total 430 Diamonds (80 foran, phir 50 rozana 7 din tak).</p></div>'; membershipBox.style.display = 'block'; } 
                else if (selectedPackage === 'monthly_membership') { membershipBox.innerHTML = '<div class="membership-info"><h4><i class="fas fa-crown"></i> Monthly Membership</h4><p>Total 1900 Diamonds (400 foran, phir 50 rozana 30 din tak).</p></div>'; membershipBox.style.display = 'block'; } 
                else { membershipBox.style.display = 'none'; }
            });
        }
    }

    // =======================================================
    // === NAYA & FIXED: TEAM, INVITATION & CLICK LOGIC ===
    // =======================================================

    // Yeh variables Team Modal ke liye global rakhein taake inki values yaad rahen
    let selectedTeammates = new Map();
    let maxTeammates = 0;

    // Team Join Modal
    if (joinTeamModal) {
        const closeJoinTeamModal = document.getElementById('closeJoinTeamModal');
        const joinStep1 = document.getElementById('joinStep1');
        const joinStep2 = document.getElementById('joinStep2');
        const goToStep2Btn = document.getElementById('goToStep2');
        const backToStep1Btn = document.getElementById('backToStep1');
        const searchInput = document.getElementById('teammateSearchInput');
        const searchResultsDiv = document.getElementById('searchResults');
        const selectedList = document.getElementById('selectedTeammatesList');
        const joinTeamForm = document.getElementById('joinTeamForm');
        let searchTimeout;

        if (closeJoinTeamModal) closeJoinTeamModal.addEventListener('click', () => joinTeamModal.style.display = 'none');
        if (goToStep2Btn) goToStep2Btn.addEventListener('click', () => { 
            if (document.getElementById('team_name').value && document.getElementById('leader_ff_id').value) {
                joinStep1.style.display = 'none'; 
                joinStep2.style.display = 'block'; 
            } else { 
                alert('Please fill Team Name and your Free Fire ID.'); 
            } 
        });
        if (backToStep1Btn) backToStep1Btn.addEventListener('click', () => { joinStep2.style.display = 'none'; joinStep1.style.display = 'block'; });
        
        if (searchInput) {
            searchInput.addEventListener('keyup', () => {
                clearTimeout(searchTimeout);
                const query = searchInput.value;
                if (query.length < 2) { if (searchResultsDiv) searchResultsDiv.innerHTML = ''; return; }
                searchTimeout = setTimeout(() => {
                    const tournamentId = document.getElementById('team_modal_tournament_id').value;
                    fetch(`/search_users.php?q=${query}&tournament_id=${tournamentId}`)
                        .then(res => res.json())
                        .then(data => {
                            let html = '';
                            if (data.success && data.users.length > 0) {
                                data.users.forEach(user => {
                                    // Isse is NAYI line se replace karein
// Isse is NAYI line se replace karein
html += `<div class="search-result-item" data-id="${user.id}" data-name="${user.name}" data-uid="${user.uid}" data-has-coins="${user.has_enough_coins}">
            <img src="/${user.avatar_url || 'uploads/avatars/default.png'}" class="search-avatar">
            <div class="search-user-details">
                <strong>${user.name}</strong>
                <small>UID: ${user.uid}</small>
            </div>
            ${!user.has_enough_coins ? '<span class="no-coins-badge">Fee Not Paid</span>' : '<span class="has-coins-badge">Fee Paid</span>'}
        </div>`;
                                });
                            } else { html = '<p class="search-no-results">No users found.</p>'; }
                            if (searchResultsDiv) searchResultsDiv.innerHTML = html;
                        });
                }, 500);
            });
        }

        if (searchResultsDiv) {
            searchResultsDiv.addEventListener('click', e => {
                const item = e.target.closest('.search-result-item');
                if (item) {
                    const userId = item.dataset.id;
                    const hasCoins = item.dataset.hasCoins === 'true'; // Check karein 'true' string hai

                    if (selectedTeammates.size >= maxTeammates) { 
                        alert(`You can only select ${maxTeammates} teammate(s).`); 
                        return; 
                    }
                    
                    if (selectedTeammates.has(userId)) {
                        alert('This player is already selected.');
                        return;
                    }

                    if (!hasCoins) {
                        // Agar coins nahi hain, to sponsor wala popup dikhayein
                        const payModal = document.getElementById('payForTeammateModal');
                        if(payModal) {
                            payModal.querySelector('#teammateToPayFor').textContent = item.dataset.name;
                            payModal.style.display = 'flex';
                            
                            // Yes/No buttons par event listener lagayein
                            document.getElementById('confirmPayBtn').onclick = () => {
                                selectedTeammates.set(userId, { name: item.dataset.name, uid: item.dataset.uid, sponsored: true });
                                updateSelectedList();
                                searchInput.value = '';
                                searchResultsDiv.innerHTML = '';
                                payModal.style.display = 'none';
                            };
                            document.getElementById('cancelPayBtn').onclick = () => {
                                payModal.style.display = 'none';
                            };
                        }
                    } else {
                        // Agar coins hain, to direct add kar dein
                        selectedTeammates.set(userId, { name: item.dataset.name, uid: item.dataset.uid, sponsored: false });
                        updateSelectedList();
                        searchInput.value = '';
                        searchResultsDiv.innerHTML = '';
                    }
                }
            });
        }

function updateSelectedList() {
    if (selectedList) {
        selectedList.innerHTML = '';
        if (selectedTeammates.size === 0) { 
            selectedList.innerHTML = '<li>No teammates selected yet.</li>'; 
        } else { 
            selectedTeammates.forEach((data, id) => { 
                const sponsoredBadge = data.sponsored ? ' <span class="sponsored-badge">(You Pay)</span>' : '';
                const li = document.createElement('li'); 
                li.innerHTML = `<span>${data.name} (UID: ${data.uid})${sponsoredBadge}</span><input type="hidden" name="teammates[]" value="${id}"><button type="button" class="remove-teammate-btn" data-id="${id}">×</button>`; 
                selectedList.appendChild(li); 
            }); 
        }
    }
}

        if (selectedList) { 
            selectedList.addEventListener('click', e => { 
                if (e.target.classList.contains('remove-teammate-btn')) { 
                    const userId = e.target.dataset.id; 
                    selectedTeammates.delete(userId); 
                    updateSelectedList(); 
                } 
            }); 
        }
        
        if (joinTeamForm) {
    joinTeamForm.addEventListener('submit', function(e) {
        e.preventDefault();

        // Forced invitation check
        const requiredTeammates = maxTeammates;
        if (selectedTeammates.size !== requiredTeammates) {
            alert(`You must invite exactly ${requiredTeammates} teammate(s).`);
            return;
        }

        const formData = new FormData(this);
        const sponsored_ids = [];
        selectedTeammates.forEach((data, id) => {
            if (data.sponsored) {
                sponsored_ids.push(id);
            }
        });
        // Sponsored teammates ki IDs ko form data mein add karein
        formData.append('sponsored_teammates', JSON.stringify(sponsored_ids));
        
        joinTeamModal.style.display = 'none';
        // handleFormSubmit ko yahan call na karein, balki fetch direct use karein
        fetch('/create_team.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => showResult(data))
            .catch(err => showResult({ success: false, title: 'Error', message: 'A network error occurred.' }));
    });
}
    }

    // Invitation Modal
    if (invitationModal) {
        const acceptInviteBtn = document.getElementById('acceptInviteBtn');
        const rejectInviteBtn = document.getElementById('rejectInviteBtn');
        function handleInvitationResponse(action, invitationId, button) {
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            const formData = new FormData();
            formData.append('invitation_id', invitationId);
            formData.append('action', action);
            fetch('/manage_invitation.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => { invitationModal.style.display = 'none'; showResult(data); })
                .catch(err => { showResult({ success: false, title: 'Error', message: 'A network error occurred.' }); });
        }
        if (acceptInviteBtn) acceptInviteBtn.addEventListener('click', e => handleInvitationResponse('accept', e.target.dataset.id, e.target));
        if (rejectInviteBtn) rejectInviteBtn.addEventListener('click', e => handleInvitationResponse('reject', e.target.dataset.id, e.target));
    }
    

if (tournamentGrid) {
        tournamentGrid.addEventListener('click', function(e) {
            const joinBtn = e.target.closest('.join-btn');
            const viewStatusBtn = e.target.closest('.view-status-btn');

            if (viewStatusBtn) {
                handleViewStatus(viewStatusBtn);
                return;
            }

            if (joinBtn && !joinBtn.disabled) {
                const tournamentId = joinBtn.dataset.id;
                const matchType = joinBtn.dataset.matchType;
                
                if (typeof matchType === 'undefined' || matchType === 'solo') {
                    if (joinModal) {
                        document.getElementById('modal_tournament_id').value = tournamentId;
                        joinModal.style.display = 'flex';
                    }
                } else { // Duo ya Squad
                    if (joinTeamModal) {
                        document.getElementById('team_modal_tournament_id').value = tournamentId;
                        
                        // maxTeammates ki value yahan set hogi
                        if (matchType === 'duo') {
                            maxTeammates = 1;
                        } else if (matchType === 'squad') {
                            maxTeammates = 3;
                        }
                        
                        // Modal ko reset karein
                        const joinTeamForm = joinTeamModal.querySelector('#joinTeamForm');
                        const selectedList = joinTeamModal.querySelector('#selectedTeammatesList');
                        const searchResultsDiv = joinTeamModal.querySelector('#searchResults');
                        
                        if(joinTeamForm) joinTeamForm.reset();
                        if(selectedList) selectedList.innerHTML = '<li>No teammates selected yet.</li>';
                        if(searchResultsDiv) searchResultsDiv.innerHTML = '';
                        
                        joinTeamModal.querySelector('#joinStep2').style.display = 'none';
                        joinTeamModal.querySelector('#joinStep1').style.display = 'block';
                        
                        selectedTeammates.clear();
                        joinTeamModal.style.display = 'flex';
                    }
                }
            }
        });
    }
const replacePlayerModal = document.getElementById('replacePlayerModal');
if (replacePlayerModal) {
    const closeReplaceModal = document.getElementById('closeReplaceModal');
    const replaceSearchInput = document.getElementById('replaceSearchInput');
    const replaceSearchResults = document.getElementById('replaceSearchResults');
    let replaceSearchTimeout;

    // Modal kholne ke liye event listener (yeh updatesContent par lagega)
    if (updatesContent) {
        updatesContent.addEventListener('click', e => {
            if (e.target.classList.contains('replace-btn')) {
                const button = e.target;
                document.getElementById('replace_team_id').value = button.dataset.teamId;
                document.getElementById('replace_old_user_id').value = button.dataset.oldUserId;
                document.getElementById('replacingPlayerName').textContent = button.dataset.oldUserName;
                replacePlayerModal.style.display = 'flex';
            }
        });
    }

    if(closeReplaceModal) closeReplaceModal.addEventListener('click', () => {
        replacePlayerModal.style.display = 'none';
        replaceSearchInput.value = '';
        replaceSearchResults.innerHTML = '';
    });
    
    // Player search karne ki logic
    if (replaceSearchInput) {
        replaceSearchInput.addEventListener('keyup', () => {
            clearTimeout(replaceSearchTimeout);
            const query = replaceSearchInput.value;
            const teamId = document.getElementById('replace_team_id').value;
            const teamData = document.querySelector(`.replace-btn[data-team-id="${teamId}"]`);
            if (!teamData) return;
            const tournamentId = document.querySelector(`.view-status-btn[data-id]`).dataset.id; // Get ID from visible status button

            if (query.length < 2) { if (replaceSearchResults) replaceSearchResults.innerHTML = ''; return; }
            
            replaceSearchTimeout = setTimeout(() => {
                fetch(`/search_users.php?q=${query}&tournament_id=${tournamentId}`)
                    .then(res => res.json())
                    .then(data => {
                        let html = '';
                        if (data.success && data.users.length > 0) {
                            data.users.forEach(user => {
                                html += `<div class="search-result-item replace-new-player" data-new-user-id="${user.id}">
                                            <img src="/${user.avatar_url || 'uploads/avatars/default.png'}" class="search-avatar">
                                            <div class="search-user-details"><strong>${user.name}</strong><small>UID: ${user.uid}</small></div>
                                         </div>`;
                            });
                        } else { html = '<p class="search-no-results">No users found.</p>'; }
                        if (replaceSearchResults) replaceSearchResults.innerHTML = html;
                    });
            }, 500);
        });
    }

    // Naye player ko select karne par action
    if (replaceSearchResults) {
        replaceSearchResults.addEventListener('click', e => {
            const item = e.target.closest('.replace-new-player');
            if (item) {
                const newUserId = item.dataset.newUserId;
                const teamId = document.getElementById('replace_team_id').value;
                const oldUserId = document.getElementById('replace_old_user_id').value;

                const formData = new FormData();
                formData.append('team_id', teamId);
                formData.append('old_user_id', oldUserId);
                formData.append('new_user_id', newUserId);

                fetch('/replace_teammate.php', { method: 'POST', body: formData })
                    .then(res => res.json())
                    .then(data => {
                        replacePlayerModal.style.display = 'none';
                        showResult(data);
                        // Status view ko refresh karein
                        if(data.success && updatesModal.style.display === 'flex'){
                            const tourneyId = document.querySelector(`.view-status-btn[data-id]`).dataset.id;
                            document.querySelector(`.view-status-btn[data-id="${tourneyId}"]`).click();
                        }
                    });
            }
        });
    }
}

    // ===================================
    // === NAYA: ROOM ID/PASS COPY LOGIC ===
    // ===================================
    if (updatesContent) {
        updatesContent.addEventListener('click', function(e) {
            const copyBtn = e.target.closest('.copy-btn');
            if (copyBtn && !copyBtn.disabled) {
                const tournamentId = copyBtn.dataset.tournamentId;
                const type = copyBtn.dataset.type;
                const originalHTML = copyBtn.innerHTML; // InnerHTML save karein

                copyBtn.disabled = true;
                copyBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

                const formData = new FormData();
                formData.append('tournament_id', tournamentId);
                formData.append('type', type);

                fetch('/get_room_details.php', { method: 'POST', body: formData })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success && data.detail) {
                            navigator.clipboard.writeText(data.detail).then(() => {
                                copyBtn.innerHTML = '<i class="fas fa-check"></i> Copied!';
                            }).catch(() => {
                                 copyBtn.innerHTML = 'Copy Failed';
                            });
                        } else {
                            copyBtn.innerHTML = '<i class="fas fa-times"></i> Not Available';
                        }
                    })
                    .catch(() => { copyBtn.innerHTML = 'Error'; })
                    .finally(() => {
                        setTimeout(() => {
                            copyBtn.innerHTML = originalHTML; // Original HTML wapis set karein
                            copyBtn.disabled = false;
                        }, 2000);
                    });
            }
        });
    }
}); // END OF DOMContentLoaded
