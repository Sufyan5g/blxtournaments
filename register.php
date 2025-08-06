<?php
// PHP logic ko HTML se pehle rakhna zaroori hai
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/header.php'; // Header ko upar le aayein

// Form submission ka logic yahan handle hoga (jab user 'Register' button dabata hai)
$errors = [];
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['password'])) { // Sirf register form submit hone par chale
    $name = sanitize_input($_POST['name']);
    $email = sanitize_input($_POST['email']);
    $freefire_id = sanitize_input($_POST['freefire_id']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $verification_code = sanitize_input($_POST['verification_code']);

    // Baaki ka validation
    if (empty($name) || empty($email) || empty($password) || empty($freefire_id) || empty($verification_code)) {
        $errors[] = "All fields, including verification code, are required.";
    }
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }
    // ... baaki ka validation logic ...

    if (empty($errors)) {
        // ... baaki ka registration logic ...
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND verification_code = ? AND is_verified = 0");
        $stmt->bind_param("ss", $email, $verification_code);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows == 1) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt->close();
            $stmt_update = $conn->prepare("UPDATE users SET name = ?, password = ?, freefire_id = ?, is_verified = 1, verification_code = NULL WHERE email = ?");
            $stmt_update->bind_param("ssss", $name, $hashed_password, $freefire_id, $email);
            if ($stmt_update->execute()) {
                header("Location: login.php?status=reg_success");
                exit();
            } else {
                $errors[] = "Something went wrong during final registration.";
            }
        } else {
            $errors[] = "Invalid verification code or email. Please click 'Get Code' again if needed.";
        }
    }
}
?>

<div class="form-container">
    <!-- NAYA: Heading ab center mein hai -->
    <h1 style="text-align: center;">Create an Account</h1>

    <!-- NAYA: Saare messages ab yahan form ke top par dikhenge -->
    <div id="form-messages" style="margin-bottom: 1rem;">
        <?php if (!empty($errors)): ?>
            <div class="message-box error">
                <?php foreach ($errors as $error) echo "<p>$error</p>"; ?>
            </div>
        <?php endif; ?>
    </div>

    <form action="register.php" method="POST" id="registerForm">
        <div class="form-group">
            <label for="name">Correct ID Name</label>
            <input type="text" name="name" required>
        </div>
        
        <div class="form-group">
            <label for="email">Email Address</label>
            <!-- NAYA: Email aur Button ko ek hi line mein laane ke liye input-group -->
            <div class="input-group">
                <input type="email" name="email" id="email" placeholder="Enter your email" required>
                <!-- NAYA: Button ab input ke saath hai -->
                <button type="button" id="getCodeBtn" class="submit-btn">Get Code</button>
            </div>
        </div>

        <!-- Verification box ab shuru mein chupa rahega -->
        <div class="form-group" id="verify-box" style="display:none;">
            <label for="verification_code">Verification Code</label>
            <input type="text" name="verification_code" id="verification_code" placeholder="Enter code from email">
        </div>
        
        <div class="form-group">
            <label for="freefire_id">Free Fire UID</label>
            <input type="text" name="freefire_id" required>
        </div>
        
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" name="password" required>
        </div>

        <div class="form-group">
            <label for="confirm_password">Confirm Password</label>
            <input type="password" name="confirm_password" required>
        </div>

        <button type="submit" class="submit-btn">Register</button>
    </form>
    <p class="message">Already have an account? <a href="login.php">Login here</a></p>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function(){
    $('#getCodeBtn').on('click', function(){
        var email = $('#email').val();
        var btn = $(this);
        // NAYA: Messages dikhane wala div ab form ke top par hai
        var messageContainer = $('#form-messages');

        // Purane messages saaf karo
        messageContainer.html('');

        if(email === "") {
            // Error message form ke top par dikhao
            var errorHtml = '<div class="message-box error"><p>Please enter your email address first.</p></div>';
            messageContainer.html(errorHtml).slideDown();
            return;
        }

        btn.prop('disabled', true).text('Sending...');

        $.ajax({
            url: 'send_verification_code.php',
            type: 'POST',
            data: { email: email },
            dataType: 'json',
            success: function(response){
                var messageClass = '';
                var messageText = '';

                if(response.success){
                    // SUCCESS (Green Message)
                    $('#verify-box').slideDown();
                    messageClass = 'success';
                    messageText = response.message;
                    
                    // Countdown Timer
                    var seconds = 60;
                    btn.text('Resend in ' + seconds + 's');
                    var countdown = setInterval(function() {
                        seconds--;
                        btn.text('Resend in ' + seconds + 's');
                        if (seconds <= 0) {
                            clearInterval(countdown);
                            btn.prop('disabled', false).text('Get Code');
                        }
                    }, 1000);

                } else {
                    // Yahan check karo ke error hai ya warning
                    if(response.status === 'warning') {
                        // WARNING (Yellow Message)
                        messageClass = 'warning';
                    } else {
                        // ERROR (Red Message)
                        messageClass = 'error';
                    }
                    messageText = response.message;
                    btn.prop('disabled', false).text('Get Code');
                }
                
                // Final message ko top par dikhao
                var messageHtml = '<div class="message-box ' + messageClass + '"><p>' + messageText + '</p></div>';
                messageContainer.html(messageHtml).slideDown();

            },
            error: function(){
                // Server/Network Error (Red Message)
                var errorHtml = '<div class="message-box error"><p>An error occurred. Please try again.</p></div>';
                messageContainer.html(errorHtml).slideDown();
                btn.prop('disabled', false).text('Get Code');
            }
        });
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>