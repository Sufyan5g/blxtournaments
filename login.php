<?php
// Sabse pehle, db.php ko include karein aur session start karein
require_once __DIR__ . '/includes/db.php';

// --- YAHAN SARA PHP LOGIC PEHLE AAYEGA ---

// 1. Check karein ki user pehle se logged in hai ya nahi
if(isset($_SESSION['user_id'])){
    header("Location: index.php");
    exit();
}

$errors = [];
$success_message = ''; // Success message ke liye variable

// NEW: Check for registration success message
if (isset($_GET['status']) && $_GET['status'] == 'reg_success') {
    $success_message = "Registration Successful! You can now log in.";
}


// 2. Check karein ki form submit hua hai ya nahi
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $errors[] = "Both fields are required.";
    } else {
        // IMPORTANT: We only allow verified users to log in
        $stmt = $conn->prepare("SELECT id, name, password, role FROM users WHERE email = ? AND is_verified = 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                // Password sahi hai, session start karein
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role'];
                
                // Role ke hisaab se redirect karein
                if ($user['role'] == 'admin') {
                    header("Location: /admin/dashboard.php");
                } else {
                    header("Location: /index.php");
                }
                exit();
            } else {
                $errors[] = "Incorrect email or password, or account not verified.";
            }
        } else {
            $errors[] = "Incorrect email or password, or account not verified.";
        }
        $stmt->close();
    }
}

// --- AB HTML CODE SHURU HOGA ---
require_once __DIR__ . '/includes/header.php';
?>

<div class="form-container">
    <h1 style="text-align: center;">Login</h1>

    <?php if (!empty($errors)): ?>
        <div class="error">
            <?php foreach ($errors as $error): ?>
                <p><?php echo $error; ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($success_message)): ?>
    <!-- NAYA PROFESSIONAL MESSAGE BOX -->
    <div class="message-box-global success">
        <div class="message-content">
            <i class="fas fa-check-circle"></i> <!-- Khoobsurat tick icon -->
            <p><?php echo $success_message; ?></p>
        </div>
    </div>
<?php endif; ?>

    <form action="login.php" method="POST">
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" name="email" id="email" required>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" name="password" id="password" required>
        </div>
        <button type="submit" class="submit-btn">Login</button>
    </form>
    <p class="message">Don't have an account? <a href="register.php">Register here</a></p>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const globalMessageBox = document.querySelector('.message-box-global');
    
    // Agar message box page par mojood hai
    if (globalMessageBox) {
        // 5 second ke baad isko fade out kar ke hata do
        setTimeout(() => {
            globalMessageBox.style.opacity = '0';
            // Fade out ke baad isko DOM se bhi nikal do taake jagah na le
            setTimeout(() => {
                globalMessageBox.remove();
            }, 500); // 0.5 second ka fade-out transition
        }, 5000); // 5000 milliseconds = 5 seconds

        // User kahin bhi click kare to bhi message band ho jaye
        globalMessageBox.addEventListener('click', function() {
            this.style.opacity = '0';
            setTimeout(() => {
                this.remove();
            }, 500);
        });
    }
});
</script>

<?php
require_once __DIR__ . '/includes/footer.php'; 
?>