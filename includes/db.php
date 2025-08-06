<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Render.com par, humein PostgreSQL istemal karna hai
// Ye code check karega ke Environment Variables set hain ya nahi

// Database credentials ko Environment Variables se hasil karein
$db_host = getenv('DB_HOST');
$db_port = getenv('DB_PORT'); // PostgreSQL port istemal karta hai
$db_name = getenv('DB_NAME');
$db_user = getenv('DB_USER');
$db_pass = getenv('DB_PASS');

// Agar environment variables nahi milte (local development ke liye), to fallback values use karein
if (empty($db_host)) {
    // Ye hissa aapke local computer (XAMPP/WAMP) par chalne ke liye hai
    $db_host = '127.0.0.1';
    $db_user = 'root';
    $db_pass = ''; // Apna local password yahan daalein
    $db_name = 'blx_db';
    
    // MySQLi connection for local
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($conn->connect_error) {
        die("Local DB Connection failed: " . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");

} else {
    // Ye hissa Render.com par chalne ke liye hai
    // PostgreSQL ke liye PDO istemal karna behtar hai

    // PostgreSQL DSN (Data Source Name)
    $dsn = "pgsql:host={$db_host};port={$db_port};dbname={$db_name};user={$db_user};password={$db_pass}";
    
    try {
        // PDO object banayein
        $conn = new PDO($dsn);
        // Error mode ko exception par set karein
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("Render DB Connection failed: " . $e->getMessage());
    }
}

// Sanitize function ko update karna hoga kyunki ab hum PDO aur MySQLi dono istemal kar sakte hain
function sanitize_input($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    
    // Agar local par MySQLi hai to real_escape_string use karein
    if (is_a($conn, 'mysqli')) {
        return $conn->real_escape_string($data);
    }
    // PDO ke liye, hum prepared statements use karenge, isliye yahan sirf basic sanitization kafi hai
    return $data;
}

?>
