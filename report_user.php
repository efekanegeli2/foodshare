<?php
require_once 'db.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$reporter_id = getCurrentUserId();
$reported_user_id = intval($_GET['user_id'] ?? 0);
$request_id = intval($_GET['request_id'] ?? 0);
$food_id = intval($_GET['food_id'] ?? 0);

$error = '';
$success = '';

// Validate that we have a reported user
if ($reported_user_id <= 0) {
    $error = 'Invalid user to report.';
}

// Get reported user info
$reported_user = null;
if ($reported_user_id > 0) {
    $user_query = "SELECT username, email FROM users WHERE id = ?";
    $stmt = $conn->prepare($user_query);
    $stmt->bind_param("i", $reported_user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $reported_user = $result->fetch_assoc();
    } else {
        $error = 'User not found.';
    }
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reason_category = trim($_POST['reason_category'] ?? '');
    $reason_details = trim($_POST['reason_details'] ?? '');
    
    if (empty($reason_category)) {
        $error = 'Please select a reason category.';
    } elseif (empty($reason_details)) {
        $error = 'Please provide details about the issue.';
    } else {
        // Insert report
        $insert_query = "INSERT INTO reports (reporter_id, reported_user_id, food_id, request_id, reason_category, reason_details) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_query);
        
        $food_id_value = $food_id > 0 ? $food_id : null;
        $request_id_value = $request_id > 0 ? $request_id : null;
        
        $stmt->bind_param("iiiiss", $reporter_id, $reported_user_id, $food_id_value, $request_id_value, $reason_category, $reason_details);
        
        if ($stmt->execute()) {
            $success = 'Report submitted successfully. Our team will review it.';
        } else {
            $error = 'Failed to submit report. Please try again.';
        }
        $stmt->close();
    }
}

$reason_categories = [
    'spoil' => 'Food was spoiled or expired',
    'quality' => 'Food quality was poor',
    'behavior' => 'Inappropriate behavior',
    'no_show' => 'User did not show up',
    'fraud' => 'Suspected fraud or scam',
    'other' => 'Other (please specify)'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report User - FoodShare</title>
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
                    <a href="profile.php" class="text-gray-700 hover:text-green-600 px-3 py-2 rounded-md text-sm font-medium">Profile</a>
                    <a href="logout.php" class="bg-red-500 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-red-600">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-3xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <h2 class="text-3xl font-bold text-gray-900 mb-6">Report User</h2>
            
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
                    <?php echo htmlspecialchars($success); ?>
                    <div class="mt-4">
                        <a href="profile.php" class="text-green-600 hover:underline">Return to Profile</a>
                    </div>
                </div>
            <?php else: ?>
                <?php if ($reported_user): ?>
                    <div class="mb-6 p-4 bg-gray-100 rounded-lg">
                        <p class="text-gray-700">
                            <span class="font-medium">Reporting:</span> <?php echo htmlspecialchars($reported_user['username']); ?> (<?php echo htmlspecialchars($reported_user['email']); ?>)
                        </p>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" class="space-y-6">
                    <div>
                        <label for="reason_category" class="block text-sm font-medium text-gray-700 mb-2">Reason Category *</label>
                        <select id="reason_category" name="reason_category" required 
                                class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500">
                            <option value="">Select a reason...</option>
                            <?php foreach ($reason_categories as $key => $label): ?>
                                <option value="<?php echo $key; ?>" <?php echo (isset($_POST['reason_category']) && $_POST['reason_category'] === $key) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label for="reason_details" class="block text-sm font-medium text-gray-700 mb-2">Details *</label>
                        <textarea id="reason_details" name="reason_details" rows="6" required 
                                  class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500"
                                  placeholder="Please provide detailed information about the issue..."><?php echo htmlspecialchars($_POST['reason_details'] ?? ''); ?></textarea>
                        <p class="mt-1 text-sm text-gray-500">Please be as specific as possible to help us investigate the issue.</p>
                    </div>
                    
                    <div class="flex space-x-4">
                        <button type="submit" class="flex-1 bg-red-600 text-white py-2 px-4 rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                            Submit Report
                        </button>
                        <a href="profile.php" class="flex-1 bg-gray-300 text-gray-700 py-2 px-4 rounded-md hover:bg-gray-400 text-center">
                            Cancel
                        </a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

