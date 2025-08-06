<?php
// FILE: get_team_status.php
require_once 'includes/db.php';
header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'An error occurred.'];

if (!isset($_SESSION['user_id']) || !isset($_GET['tournament_id'])) {
    $response['message'] = 'Invalid request.';
    echo json_encode($response);
    exit();
}

$user_id = $_SESSION['user_id'];
$tournament_id = intval($_GET['tournament_id']);

// Check if this tournament is a team-based match
$tourney_stmt = $conn->prepare("SELECT match_type FROM tournaments WHERE id = ?");
$tourney_stmt->bind_param("i", $tournament_id);
$tourney_stmt->execute();
$tournament = $tourney_stmt->get_result()->fetch_assoc();

if (!$tournament || $tournament['match_type'] == 'solo') {
    // Agar solo hai, to purane updates wala system chalao
    $update_stmt = $conn->prepare("SELECT message, created_at FROM tournament_updates WHERE tournament_id = ? ORDER BY created_at DESC");
    $update_stmt->bind_param("i", $tournament_id);
    $update_stmt->execute();
    $updates = $update_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $formatted_updates = [];
    foreach($updates as $update) {
        $formatted_updates[] = [
            'message' => nl2br(htmlspecialchars($update['message'])),
            'time' => date('d M, h:i A', strtotime($update['created_at']))
        ];
    }
    
    $response = [
        'success' => true,
        'is_team' => false,
        'data' => $formatted_updates
    ];
    echo json_encode($response);
    exit();
}

// Team-based logic starts here
$team_data = null;

// Find the team this user belongs to in this tournament
$team_id_stmt = $conn->prepare("
    SELECT tt.id, tt.leader_user_id, tt.team_name
    FROM tournament_teams tt
    LEFT JOIN team_invitations ti ON tt.id = ti.team_id
    WHERE tt.tournament_id = ? 
    AND (tt.leader_user_id = ? OR (ti.invited_user_id = ? AND ti.status = 'accepted'))
    GROUP BY tt.id
");
$team_id_stmt->bind_param("iii", $tournament_id, $user_id, $user_id);
$team_id_stmt->execute();
$team_result = $team_id_stmt->get_result()->fetch_assoc();

if (!$team_result) {
    $response['message'] = 'You are not part of any team in this tournament.';
    echo json_encode($response);
    exit();
}

$team_id = $team_result['id'];
$team_data = [
    'team_id' => $team_id,
    'team_name' => htmlspecialchars($team_result['team_name']),
    'is_leader' => ($user_id == $team_result['leader_user_id']),
    'members' => [],
    'updates' => []
];

// Get all team members (leader + accepted invitations)
$members_stmt = $conn->prepare("
    (SELECT u.id as user_id, u.name, u.uid, u.avatar_url, 'accepted' as status, 1 as is_leader
     FROM users u
     JOIN tournament_teams tt ON u.id = tt.leader_user_id
     WHERE tt.id = ?)
    UNION
    (SELECT u.id as user_id, u.name, u.uid, u.avatar_url, ti.status, 0 as is_leader
     FROM users u
     JOIN team_invitations ti ON u.id = ti.invited_user_id
     WHERE ti.team_id = ?)
    ORDER BY is_leader DESC, name ASC
");
$members_stmt->bind_param("ii", $team_id, $team_id);
$members_stmt->execute();
$members_result = $members_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

foreach ($members_result as $member) {
    $team_data['members'][] = [
        'user_id' => $member['user_id'],
        'name' => htmlspecialchars($member['name']),
        'uid' => htmlspecialchars($member['uid']),
        'avatar_url' => htmlspecialchars($member['avatar_url']),
        'status' => $member['status'], // pending, accepted, rejected
        'is_leader' => $member['is_leader'] == 1
    ];
}

// Get general updates for this tournament
$update_stmt = $conn->prepare("SELECT message, created_at FROM tournament_updates WHERE tournament_id = ? ORDER BY created_at DESC");
$update_stmt->bind_param("i", $tournament_id);
$update_stmt->execute();
$updates = $update_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

foreach($updates as $update) {
    $team_data['updates'][] = [
        'message' => nl2br(htmlspecialchars($update['message'])),
        'time' => date('d M, h:i A', strtotime($update['created_at']))
    ];
}

$response = [
    'success' => true,
    'is_team' => true,
    'data' => $team_data
];

echo json_encode($response);
?>