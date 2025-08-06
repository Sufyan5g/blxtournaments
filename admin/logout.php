<?php
session_start();
session_unset();
session_destroy();
header("Location: /login.php"); // Logout ke baad login page par bhejo
exit();
?>