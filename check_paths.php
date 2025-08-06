<?php
// Error display ko ON karein taake humein sab kuch nazar aaye
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Path Checking...</h1>";

// --- Pehli file ko check karte hain ---
echo "<p>Checking for: 'includes/db.php'...</p>";
require_once __DIR__ . '/includes/db.php';
echo "<p style='color:green;'>SUCCESS: db.php found!</p>";

// --- Doosri file ko check karte hain ---
echo "<p>Checking for: 'includes/mailer/Exception.php'...</p>";
require_once __DIR__ . '/includes/mailer/Exception.php';
echo "<p style='color:green;'>SUCCESS: Exception.php found!</p>";

// --- Teesri file ko check karte hain ---
echo "<p>Checking for: 'includes/mailer/PHPMailer.php'...</p>";
require_once __DIR__ . '/includes/mailer/PHPMailer.php';
echo "<p style='color:green;'>SUCCESS: PHPMailer.php found!</p>";

// --- Chauthi file ko check karte hain ---
echo "<p>Checking for: 'includes/mailer/SMTP.php'...</p>";
require_once __DIR__ . '/includes/mailer/SMTP.php';
echo "<p style='color:green;'>SUCCESS: SMTP.php found!</p>";

echo "<hr><h2><b style='color:blue;'>All files included successfully! Your paths are CORRECT.</b></h2>";

?>