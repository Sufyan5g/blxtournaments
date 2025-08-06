<?php
// Yeh file AJAX requests ke liye hai, isliye header/footer nahi chahiye
require_once dirname(__DIR__) . '/includes/db.php'; 

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$search = $_GET['search'] ?? '';
if (empty($search)) {
    echo json_encode([]);
    exit();
}

$searchTerm = "%" . $search . "%";
// Hum 'avatar_url' ko IFNULL se check kar rahe hain taake agar woh khali ho to default image ka path bhejain
$stmt = $conn->prepare("SELECT id, name, uid, IFNULL(avatar_url, 'assets/images/default_avatar.png') as avatar_url FROM users WHERE name LIKE ? OR uid LIKE ? LIMIT 10");
$stmt->bind_param("ss", $searchTerm, $searchTerm);
$stmt->execute();
$result = $stmt->get_result();
$users = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

echo json_encode($users);
?>