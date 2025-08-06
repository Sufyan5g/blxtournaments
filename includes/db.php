<?php
if (session_status() == PHP_SESSION_NONE) { session_start(); }
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', 'root'); // Apna MariaDB password yahan daalein
define('DB_NAME', 'blx_db');
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }
$conn->set_charset("utf8mb4");
function sanitize_input($data) { global $conn; $data = trim($data); $data = stripslashes($data); $data = htmlspecialchars($data); return $conn->real_escape_string($data); }
?>
