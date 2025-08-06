<?php
// Is file ka kaam sirf session start karna aur database connect karna hai.
// Yeh hamesha sabse pehle chalegi.

// 1. Start the session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 2. Include the database connection file
// __DIR__ se path ki galti kabhi nahi hogi.
require_once __DIR__ . '/db.php';
?>