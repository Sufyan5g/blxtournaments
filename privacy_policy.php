<?php 
// Ye file session aur database connection ke liye hai
// Apne project ke hisab se iska naam change kar lein (e.g., init.php)
include 'includes/init.php'; 

// Header include karein
include 'includes/header.php'; 
?>

<style>
    .privacy-container {
        max-width: 800px;
        margin: 20px auto;
        padding: 25px;
        background-color: #2c2c3e; /* Dark background */
        color: #f0f0f0; /* Light text */
        border-radius: 10px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.3);
    }
    .privacy-container h1, .privacy-container h2 {
        color: #4CAF50; /* Green accent for headings */
        border-bottom: 2px solid #4CAF50;
        padding-bottom: 10px;
        margin-bottom: 20px;
    }
    .privacy-container p, .privacy-container li {
        line-height: 1.6;
        font-size: 16px;
    }
    .privacy-container ul {
        list-style-type: disc;
        padding-left: 20px;
    }
    .warning {
        background-color: #ffc107;
        color: #333;
        padding: 15px;
        border-radius: 5px;
        border-left: 5px solid #d9a400;
        font-weight: bold;
    }
</style>

<div class="privacy-container">
    <h1>Privacy Policy for BLX Tournament</h1>
    <p><strong>Last updated:</strong> <?php echo date("F j, Y"); ?></p>

    <p>Welcome to BLX Tournament. We are committed to protecting your privacy. This Privacy Policy explains how we collect, use, and safeguard your information when you use our website and application.</p>

    <h2>1. Information We Collect</h2>
    <p>We may collect the following information:</p>
    <ul>
        <li><strong>Personal Data:</strong> Name, email address, User ID (UID), and other details you provide during registration.</li>
        <li><strong>Game Data:</strong> Your in-game level, stats, and tournament performance.</li>
        <li><strong>Transaction Data:</strong> Details about deposits, withdrawals, and payments.</li>
    </ul>

    <h2>2. How We Use Your Information</h2>
    <p>We use the information we collect to:</p>
    <ul>
        <li>Create and manage your account.</li>
        <li>Organize and operate tournaments.</li>
        <li>Process transactions and payments.</li>
        <li>Communicate with you about updates, support, and promotional offers.</li>
        <li>Ensure the security and integrity of our platform.</li>
    </ul>

    <h2>3. User Conduct and Rules</h2>
    <p>To maintain a fair and competitive environment for all players, you must adhere to the following rules:</p>
    
    <div class="warning">
        <p><strong>IMPORTANT WARNING:</strong> Sharing Tournament Room ID and Password with anyone who is not a registered participant in that specific match is strictly forbidden. Any player found sharing room credentials will face an immediate and permanent account ban. All funds, coins, or winnings in the account will be forfeited without any possibility of a refund.</p>
    </div>

    <ul>
        <li><strong>No Cheating:</strong> The use of hacks, scripts, third-party software, or any tool that gives an unfair advantage is strictly prohibited.</li>
        <li><strong>Account Security:</strong> You are responsible for keeping your account credentials secure. Do not share your password with anyone.</li>
        <li><strong>Respectful Conduct:</strong> Abusive language, harassment, or any form of toxicity towards other players or staff will not be tolerated and may result in a temporary or permanent ban.</li>
        <li><strong>One Account Per Player:</strong> Each player is allowed only one account. Creating multiple accounts to abuse the system will result in all associated accounts being banned.</li>
    </ul>

    <h2>4. Security of Your Information</h2>
    <p>We use administrative, technical, and physical security measures to help protect your personal information. While we have taken reasonable steps to secure the personal information you provide to us, please be aware that despite our efforts, no security measures are perfect or impenetrable.</p>

    <h2>5. Changes to This Privacy Policy</h2>
    <p>We may update this Privacy Policy from time to time. We will notify you of any changes by posting the new Privacy Policy on this page. You are advised to review this Privacy Policy periodically for any changes.</p>

    <h2>6. Contact Us</h2>
    <p>If you have any questions or concerns about this Privacy Policy, please contact us through our Customer Support section.</p>
</div>

<?php 
// Footer include karein
include 'includes/footer.php'; 
?>