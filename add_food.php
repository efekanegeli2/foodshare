<?php
require_once 'db.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';

// Ensure uploads directory exists
if (!file_exists('uploads')) {
    mkdir('uploads', 0777, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $expiry_date = $_POST['expiry_date'] ?? '';
    $pickup_location = trim($_POST['pickup_location'] ?? '');
    $user_id = getCurrentUserId();
    
    // Validation
    if (empty($title) || empty($expiry_date) || empty($pickup_location)) {
        $error = 'Title, expiry date, and pickup location are required.';
    } else {
        // Validate expiry date (should be in the future)
        $expiry_timestamp = strtotime($expiry_date);
        $today_timestamp = strtotime('today');
        
        if ($expiry_timestamp < $today_timestamp) {
            $error = 'Expiry date must be today or in the future.';
        } else {
            $image_path = null;
            
            // Handle image upload
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['image'];
                $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                $max_size = 5 * 1024 * 1024; // 5MB
                
                if (!in_array($file['type'], $allowed_types)) {
                    $error = 'Invalid file type. Only JPEG, PNG, and GIF are allowed.';
                } elseif ($file['size'] > $max_size) {
                    $error = 'File size exceeds 5MB limit.';
                } else {
                    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $filename = uniqid('food_', true) . '.' . $extension;
                    $target_path = 'uploads/' . $filename;
                    
                    if (move_uploaded_file($file['tmp_name'], $target_path)) {
                        $image_path = $filename; // Store only filename in DB
                    } else {
                        $error = 'Failed to upload image.';
                    }
                }
            }
            
            if (empty($error)) {
                // Insert food item
                $query = "INSERT INTO food_items (user_id, title, description, image_path, expiry_date, pickup_location) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("isssss", $user_id, $title, $description, $image_path, $expiry_date, $pickup_location);
                
                if ($stmt->execute()) {
                    $success = 'Food item added successfully!';
                    // Clear form
                    $_POST = [];
                } else {
                    $error = 'Failed to add food item. Please try again.';
                }
                $stmt->close();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Food - FoodShare</title>
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
            <h2 class="text-3xl font-bold text-gray-900 mb-6">Add Food Item</h2>
            
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
            
            <form method="POST" action="" enctype="multipart/form-data" class="space-y-6">
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700">Title *</label>
                    <input id="title" name="title" type="text" required 
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500"
                           value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>">
                </div>
                
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea id="description" name="description" rows="4" 
                              class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                </div>
                
                <div>
                    <label for="image" class="block text-sm font-medium text-gray-700">Food Image</label>
                    <input id="image" name="image" type="file" accept="image/jpeg,image/jpg,image/png,image/gif" 
                           class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100">
                    <p class="mt-1 text-sm text-gray-500">Max size: 5MB. Formats: JPEG, PNG, GIF</p>
                </div>
                
                <div>
                    <label for="expiry_date" class="block text-sm font-medium text-gray-700">Expiry Date *</label>
                    <input id="expiry_date" name="expiry_date" type="date" required 
                           min="<?php echo date('Y-m-d'); ?>"
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500"
                           value="<?php echo htmlspecialchars($_POST['expiry_date'] ?? ''); ?>">
                </div>
                
                <div>
                    <label for="pickup_location" class="block text-sm font-medium text-gray-700">Pickup Location *</label>
                    <input id="pickup_location" name="pickup_location" type="text" required 
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500"
                           placeholder="e.g., 123 Main St, City, State"
                           value="<?php echo htmlspecialchars($_POST['pickup_location'] ?? ''); ?>">
                </div>
                
                <div class="flex space-x-4">
                    <button type="submit" class="flex-1 bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        Add Food Item
                    </button>
                    <a href="dashboard.php" class="flex-1 bg-gray-300 text-gray-700 py-2 px-4 rounded-md hover:bg-gray-400 text-center">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>

