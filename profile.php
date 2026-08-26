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

// Handle verification code submission (for donors)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_code'])) {
    $verification_code = trim($_POST['verification_code'] ?? '');
    $request_id = intval($_POST['request_id'] ?? 0);
    
    if (empty($verification_code) || $request_id <= 0) {
        $error = 'Invalid verification code or request.';
    } else {
        // Verify code and update request status
        $verify_query = "UPDATE requests SET status = 'completed' WHERE id = ? AND donor_id = ? AND verification_code = ? AND status = 'accepted'";
        $stmt = $conn->prepare($verify_query);
        $stmt->bind_param("iis", $request_id, $user_id, $verification_code);
        
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            // Update food item status
            $food_update = "UPDATE food_items fi 
                           JOIN requests r ON fi.id = r.food_id 
                           SET fi.status = 'completed' 
                           WHERE r.id = ?";
            $stmt2 = $conn->prepare($food_update);
            $stmt2->bind_param("i", $request_id);
            $stmt2->execute();
            $stmt2->close();
            
            $success = 'Verification code accepted! Transaction completed.';
        } else {
            $error = 'Invalid verification code or request already completed.';
        }
        $stmt->close();
    }
}

// Get user's food listings
$listings_query = "SELECT * FROM food_items WHERE user_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($listings_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$listings_result = $stmt->get_result();

// Get incoming requests (for user's food items)
$incoming_requests_query = "SELECT r.*, fi.title, fi.image_path, u.username, u.email 
                            FROM requests r 
                            JOIN food_items fi ON r.food_id = fi.id 
                            JOIN users u ON r.requester_id = u.id 
                            WHERE r.donor_id = ? AND r.status IN ('pending', 'accepted')
                            ORDER BY r.created_at DESC";
$stmt2 = $conn->prepare($incoming_requests_query);
$stmt2->bind_param("i", $user_id);
$stmt2->execute();
$incoming_requests_result = $stmt2->get_result();

// Get outgoing requests (user's requests)
$outgoing_requests_query = "SELECT r.*, fi.title, fi.image_path, u.username, u.email 
                            FROM requests r 
                            JOIN food_items fi ON r.food_id = fi.id 
                            JOIN users u ON r.donor_id = u.id 
                            WHERE r.requester_id = ? AND r.status IN ('pending', 'accepted', 'completed')
                            ORDER BY r.created_at DESC";
$stmt3 = $conn->prepare($outgoing_requests_query);
$stmt3->bind_param("i", $user_id);
$stmt3->execute();
$outgoing_requests_result = $stmt3->get_result();

// Get transaction history (completed transactions)
$history_query = "SELECT r.*, fi.title, fi.image_path, 
                  CASE 
                      WHEN r.requester_id = ? THEN u_donor.username
                      ELSE u_requester.username
                  END as other_user,
                  CASE 
                      WHEN r.requester_id = ? THEN u_donor.id
                      ELSE u_requester.id
                  END as other_user_id
                  FROM requests r 
                  JOIN food_items fi ON r.food_id = fi.id 
                  JOIN users u_donor ON r.donor_id = u_donor.id 
                  JOIN users u_requester ON r.requester_id = u_requester.id 
                  WHERE (r.requester_id = ? OR r.donor_id = ?) AND r.status = 'completed'
                  ORDER BY r.created_at DESC";
$stmt4 = $conn->prepare($history_query);
$stmt4->bind_param("iiii", $user_id, $user_id, $user_id, $user_id);
$stmt4->execute();
$history_result = $stmt4->get_result();

// Handle accept/reject requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $request_id = intval($_POST['request_id'] ?? 0);
    $action = $_POST['action'];
    
    if ($action === 'accept') {
        // Generate verification code
        $verification_code = substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 6);
        
        $update_query = "UPDATE requests SET status = 'accepted', verification_code = ? WHERE id = ? AND donor_id = ?";
        $stmt5 = $conn->prepare($update_query);
        $stmt5->bind_param("sii", $verification_code, $request_id, $user_id);
        
        if ($stmt5->execute()) {
            // Update food item status
            $food_update = "UPDATE food_items fi 
                           JOIN requests r ON fi.id = r.food_id 
                           SET fi.status = 'reserved' 
                           WHERE r.id = ?";
            $stmt6 = $conn->prepare($food_update);
            $stmt6->bind_param("i", $request_id);
            $stmt6->execute();
            $stmt6->close();
            
            $success = 'Request accepted! Verification code generated.';
        } else {
            $error = 'Failed to accept request.';
        }
        $stmt5->close();
    } elseif ($action === 'reject') {
        $update_query = "UPDATE requests SET status = 'rejected' WHERE id = ? AND donor_id = ?";
        $stmt7 = $conn->prepare($update_query);
        $stmt7->bind_param("ii", $request_id, $user_id);
        
        if ($stmt7->execute()) {
            $success = 'Request rejected.';
        } else {
            $error = 'Failed to reject request.';
        }
        $stmt7->close();
    }
    
    // Reload page to show updated data
    if ($success || $error) {
        header('Location: profile.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - FoodShare</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="dashboard.php" class="text-2xl font-bold text-green-600">FoodShare</a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="dashboard.php" class="text-gray-700 hover:text-green-600 px-3 py-2 rounded-md text-sm font-medium">Dashboard</a>
                    <a href="add_food.php" class="bg-green-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-green-700">Add Food</a>
                    <?php if (isAdmin()): ?>
                        <a href="admin.php" class="text-gray-700 hover:text-green-600 px-3 py-2 rounded-md text-sm font-medium">Admin</a>
                    <?php endif; ?>
                    <a href="logout.php" class="bg-red-500 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-red-600">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-8">My Profile</h1>
        
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <!-- My Listings -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">My Listings</h2>
            
            <?php if ($listings_result && $listings_result->num_rows > 0): ?>
                <div class="space-y-4">
                    <?php while ($listing = $listings_result->fetch_assoc()): ?>
                        <div class="border border-gray-200 rounded-lg p-4 flex items-center space-x-4">
                            <?php if ($listing['image_path']): ?>
                                <img src="uploads/<?php echo htmlspecialchars($listing['image_path']); ?>" 
                                     alt="<?php echo htmlspecialchars($listing['title']); ?>" 
                                     class="w-24 h-24 object-cover rounded">
                            <?php else: ?>
                                <div class="w-24 h-24 bg-gray-200 rounded flex items-center justify-center">
                                    <span class="text-gray-400 text-xs">No Image</span>
                                </div>
                            <?php endif; ?>
                            
                            <div class="flex-1">
                                <h3 class="font-semibold text-lg"><?php echo htmlspecialchars($listing['title']); ?></h3>
                                <p class="text-sm text-gray-600">Status: <span class="font-medium"><?php echo ucfirst($listing['status']); ?></span></p>
                                <p class="text-sm text-gray-600">Expires: <?php echo date('M d, Y', strtotime($listing['expiry_date'])); ?></p>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p class="text-gray-500">You haven't listed any food items yet. <a href="add_food.php" class="text-green-600 hover:underline">Add one now</a>!</p>
            <?php endif; ?>
        </div>
        
        <!-- Incoming Requests -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">Incoming Requests</h2>
            
            <?php if ($incoming_requests_result && $incoming_requests_result->num_rows > 0): ?>
                <div class="space-y-4">
                    <?php while ($request = $incoming_requests_result->fetch_assoc()): ?>
                        <div class="border border-gray-200 rounded-lg p-4">
                            <div class="flex items-center space-x-4 mb-4">
                                <?php if ($request['image_path']): ?>
                                    <img src="uploads/<?php echo htmlspecialchars($request['image_path']); ?>" 
                                         alt="<?php echo htmlspecialchars($request['title']); ?>" 
                                         class="w-24 h-24 object-cover rounded">
                                <?php else: ?>
                                    <div class="w-24 h-24 bg-gray-200 rounded flex items-center justify-center">
                                        <span class="text-gray-400 text-xs">No Image</span>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="flex-1">
                                    <h3 class="font-semibold text-lg"><?php echo htmlspecialchars($request['title']); ?></h3>
                                    <p class="text-sm text-gray-600">Requested by: <span class="font-medium"><?php echo htmlspecialchars($request['username']); ?></span></p>
                                    <p class="text-sm text-gray-600">Status: <span class="font-medium"><?php echo ucfirst($request['status']); ?></span></p>
                                    <?php if ($request['status'] === 'accepted' && $request['verification_code']): ?>
                                        <p class="text-sm text-green-600 font-semibold mt-2">Verification Code: <?php echo htmlspecialchars($request['verification_code']); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <?php if ($request['status'] === 'pending'): ?>
                                <div class="flex space-x-2">
                                    <form method="POST" action="" class="inline">
                                        <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                        <input type="hidden" name="action" value="accept">
                                        <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">Accept</button>
                                    </form>
                                    <form method="POST" action="" class="inline">
                                        <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700">Reject</button>
                                    </form>
                                </div>
                            <?php elseif ($request['status'] === 'accepted'): ?>
                                <form method="POST" action="" class="mt-4">
                                    <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                    <div class="flex space-x-2">
                                        <input type="text" name="verification_code" placeholder="Enter verification code" 
                                               class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500" required>
                                        <button type="submit" name="verify_code" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">Verify & Complete</button>
                                    </div>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p class="text-gray-500">No incoming requests at the moment.</p>
            <?php endif; ?>
        </div>
        
        <!-- My Requests -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">My Requests</h2>
            
            <?php if ($outgoing_requests_result && $outgoing_requests_result->num_rows > 0): ?>
                <div class="space-y-4">
                    <?php while ($request = $outgoing_requests_result->fetch_assoc()): ?>
                        <div class="border border-gray-200 rounded-lg p-4 flex items-center space-x-4">
                            <?php if ($request['image_path']): ?>
                                <img src="uploads/<?php echo htmlspecialchars($request['image_path']); ?>" 
                                     alt="<?php echo htmlspecialchars($request['title']); ?>" 
                                     class="w-24 h-24 object-cover rounded">
                            <?php else: ?>
                                <div class="w-24 h-24 bg-gray-200 rounded flex items-center justify-center">
                                    <span class="text-gray-400 text-xs">No Image</span>
                                </div>
                            <?php endif; ?>
                            
                            <div class="flex-1">
                                <h3 class="font-semibold text-lg"><?php echo htmlspecialchars($request['title']); ?></h3>
                                <p class="text-sm text-gray-600">Donor: <span class="font-medium"><?php echo htmlspecialchars($request['username']); ?></span></p>
                                <p class="text-sm text-gray-600">Status: <span class="font-medium"><?php echo ucfirst($request['status']); ?></span></p>
                                <?php if ($request['status'] === 'accepted' && $request['verification_code']): ?>
                                    <p class="text-sm text-green-600 font-semibold mt-2">Your Verification Code: <?php echo htmlspecialchars($request['verification_code']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p class="text-gray-500">You haven't made any requests yet.</p>
            <?php endif; ?>
        </div>
        
        <!-- Transaction History -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">Transaction History</h2>
            
            <?php if ($history_result && $history_result->num_rows > 0): ?>
                <div class="space-y-4">
                    <?php while ($transaction = $history_result->fetch_assoc()): ?>
                        <div class="border border-gray-200 rounded-lg p-4 flex items-center justify-between">
                            <div class="flex items-center space-x-4 flex-1">
                                <?php if ($transaction['image_path']): ?>
                                    <img src="uploads/<?php echo htmlspecialchars($transaction['image_path']); ?>" 
                                         alt="<?php echo htmlspecialchars($transaction['title']); ?>" 
                                         class="w-24 h-24 object-cover rounded">
                                <?php else: ?>
                                    <div class="w-24 h-24 bg-gray-200 rounded flex items-center justify-center">
                                        <span class="text-gray-400 text-xs">No Image</span>
                                    </div>
                                <?php endif; ?>
                                
                                <div>
                                    <h3 class="font-semibold text-lg"><?php echo htmlspecialchars($transaction['title']); ?></h3>
                                    <p class="text-sm text-gray-600">With: <span class="font-medium"><?php echo htmlspecialchars($transaction['other_user']); ?></span></p>
                                    <p class="text-sm text-gray-600">Completed: <?php echo date('M d, Y', strtotime($transaction['created_at'])); ?></p>
                                </div>
                            </div>
                            
                            <div>
                                <a href="report_user.php?user_id=<?php echo $transaction['other_user_id']; ?>&request_id=<?php echo $transaction['id']; ?>&food_id=<?php echo $transaction['food_id']; ?>" 
                                   class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700">
                                    Report User
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p class="text-gray-500">No completed transactions yet.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

