<?php
require_once 'db.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user_id = getCurrentUserId();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $food_id = intval($_POST['food_id'] ?? 0);
    
    if ($food_id <= 0) {
        $error = 'Invalid food item.';
    } else {
        // Check if food item exists and is available
        $check_query = "SELECT id, user_id, status FROM food_items WHERE id = ? AND status = 'available'";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("i", $food_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $error = 'Food item not available or already taken.';
        } else {
            $food = $result->fetch_assoc();
            
            // Check if user is trying to request their own food
            if ($food['user_id'] == $user_id) {
                $error = 'You cannot request your own food item.';
            } else {
                // Check if request already exists
                $existing_query = "SELECT id FROM requests WHERE food_id = ? AND requester_id = ? AND status IN ('pending', 'accepted')";
                $stmt2 = $conn->prepare($existing_query);
                $stmt2->bind_param("ii", $food_id, $user_id);
                $stmt2->execute();
                $existing_result = $stmt2->get_result();
                
                if ($existing_result->num_rows > 0) {
                    $error = 'You have already requested this food item.';
                } else {
                    // Create request
                    $donor_id = $food['user_id'];
                    $insert_query = "INSERT INTO requests (food_id, requester_id, donor_id, status) VALUES (?, ?, ?, 'pending')";
                    $stmt3 = $conn->prepare($insert_query);
                    $stmt3->bind_param("iii", $food_id, $user_id, $donor_id);
                    
                    if ($stmt3->execute()) {
                        $success = 'Food request sent successfully! The donor will be notified.';
                    } else {
                        $error = 'Failed to send request. Please try again.';
                    }
                    $stmt3->close();
                }
                $stmt2->close();
            }
        }
        $stmt->close();
    }
}

// Redirect back to dashboard or show message
if ($success || $error) {
    $_SESSION['request_message'] = $success ?: $error;
    $_SESSION['request_message_type'] = $success ? 'success' : 'error';
    header('Location: dashboard.php');
    exit;
}
?>

