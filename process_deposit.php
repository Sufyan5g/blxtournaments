<?php
// Note: Changed this line to include init.php which likely starts the session
require_once 'includes/init.php'; // Using init.php as it's common for session_start()
require_once 'includes/db.php';
header('Content-Type: application/json');

$response = ['success' => false, 'title' => 'Error', 'message' => 'An unknown error occurred.'];

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    
    // Sanitize all POST data (Assuming sanitize_input function exists in one of the included files)
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
    $method = isset($_POST['method']) ? trim($_POST['method']) : '';
    $transaction_id = isset($_POST['transaction_id']) ? trim($_POST['transaction_id']) : '';

    // Basic validation
    if (empty($name) || empty($phone) || empty($amount) || empty($method) || empty($transaction_id)) {
        $response['message'] = "All fields are required.";
        echo json_encode($response);
        exit();
    }
    
    // <<< START: NEW CODE TO CHECK FOR DUPLICATE TRANSACTION ID >>>
    
    // Prepare a statement to check if the transaction ID already exists
    $check_stmt = $conn->prepare("SELECT id FROM deposits WHERE transaction_id = ?");
    
    if ($check_stmt === false) {
        $response['message'] = "Database prepare error (check): " . $conn->error;
        echo json_encode($response);
        exit();
    }
    
    $check_stmt->bind_param("s", $transaction_id);
    $check_stmt->execute();
    $check_stmt->store_result(); // Important to get num_rows

    if ($check_stmt->num_rows > 0) {
        // The transaction ID already exists, send back an error
        $response['success'] = false;
        $response['title']   = 'Duplicate Entry';
        $response['message'] = 'Transaction ID Already Used.';
        $check_stmt->close();
        echo json_encode($response);
        exit();
    }
    
    $check_stmt->close();

    // <<< END: NEW CODE >>>


    if (isset($_FILES['screenshot']) && $_FILES['screenshot']['error'] == 0) {
        $target_dir = "uploads/screenshots/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0755, true);
        }
        
        // Sanitize the filename to prevent security issues
        $original_filename = basename($_FILES["screenshot"]["name"]);
        $safe_filename = preg_replace("/[^a-zA-Z0-9\._-]/", "", $original_filename);
        $file_name = uniqid() . '-' . $safe_filename;
        $target_file = $target_dir . $file_name;

        // Check if file is a real image
        $check = getimagesize($_FILES["screenshot"]["tmp_name"]);
        if($check === false) {
            $response['message'] = "File is not a valid image.";
            echo json_encode($response);
            exit();
        }

        if (move_uploaded_file($_FILES["screenshot"]["tmp_name"], $target_file)) {
            // Insert into the 'deposits' table
            $stmt = $conn->prepare("INSERT INTO deposits (user_id, name, phone, amount, method, transaction_id, screenshot, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
            
            if ($stmt === false) {
                $response['message'] = "Database prepare error (insert): " . $conn->error;
            } else {
                $stmt->bind_param("isssdss", $user_id, $name, $phone, $amount, $method, $transaction_id, $target_file);
                if ($stmt->execute()) {
                    $response = ['success' => true, 'title' => 'Deposit Pending', 'message' => 'Your deposit request is under verification. We will update your balance soon.'];
                } else {
                    $response['message'] = "Database execution error: " . $stmt->error;
                }
                $stmt->close();
            }
        } else {
            $response['message'] = "Error uploading file.";
        }
    } else {
        $response['message'] = "Screenshot is required.";
    }
} else {
    $response['title'] = 'Login Required';
    $response['message'] = "You must be logged in to make a deposit.";
}

echo json_encode($response);
?>