<?php
require_once 'db.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user_id = getCurrentUserId();

// Check for request messages
$request_message = $_SESSION['request_message'] ?? '';
$request_message_type = $_SESSION['request_message_type'] ?? '';
unset($_SESSION['request_message']);
unset($_SESSION['request_message_type']);

// Get available food items (excluding user's own items)
$food_query = "SELECT fi.*, u.username, u.email 
               FROM food_items fi 
               JOIN users u ON fi.user_id = u.id 
               WHERE fi.status = 'available' AND fi.user_id != ?
               ORDER BY fi.created_at DESC";
$stmt = $conn->prepare($food_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$food_result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - FoodShare</title>
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
                    <a href="add_food.php" class="bg-green-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-green-700">Add Food</a>
                    <a href="profile.php" class="text-gray-700 hover:text-green-600 px-3 py-2 rounded-md text-sm font-medium">Profile</a>
                    <?php if (isAdmin()): ?>
                        <a href="admin.php" class="text-gray-700 hover:text-green-600 px-3 py-2 rounded-md text-sm font-medium">Admin</a>
                    <?php endif; ?>
                    <a href="logout.php" class="bg-red-500 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-red-600">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
            <p class="text-gray-600 mt-2">Browse available food items from your community</p>
        </div>
        
        <?php if ($request_message): ?>
            <div class="mb-4 <?php echo $request_message_type === 'success' ? 'bg-green-100 border-green-400 text-green-700' : 'bg-red-100 border-red-400 text-red-700'; ?> border px-4 py-3 rounded relative">
                <?php echo htmlspecialchars($request_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($food_result && $food_result->num_rows > 0): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php while ($item = $food_result->fetch_assoc()): ?>
                    <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition duration-300">
                        <?php if ($item['image_path']): ?>
                            <img src="uploads/<?php echo htmlspecialchars($item['image_path']); ?>" 
                                 alt="<?php echo htmlspecialchars($item['title']); ?>" 
                                 class="w-full h-48 object-cover">
                        <?php else: ?>
                            <div class="w-full h-48 bg-gray-200 flex items-center justify-center">
                                <span class="text-gray-400">No Image</span>
                            </div>
                        <?php endif; ?>
                        
                        <div class="p-6">
                            <h3 class="text-xl font-semibold text-gray-800 mb-2"><?php echo htmlspecialchars($item['title']); ?></h3>
                            <p class="text-gray-600 mb-4 line-clamp-2"><?php echo htmlspecialchars($item['description']); ?></p>
                            
                            <div class="space-y-2 mb-4">
                                <p class="text-sm text-gray-500">
                                    <span class="font-medium">Expires:</span> 
                                    <?php echo date('M d, Y', strtotime($item['expiry_date'])); ?>
                                </p>
                                <p class="text-sm text-gray-500">
                                    <span class="font-medium">Location:</span> 
                                    <?php echo htmlspecialchars($item['pickup_location']); ?>
                                </p>
                                <p class="text-sm text-gray-500">
                                    <span class="font-medium">Donor:</span> 
                                    <?php echo htmlspecialchars($item['username']); ?>
                                </p>
                            </div>
                            
                            <form method="POST" action="request_food.php">
                                <input type="hidden" name="food_id" value="<?php echo $item['id']; ?>">
                                <button type="submit" class="w-full bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700 transition duration-300">
                                    Request Food
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-12 bg-white rounded-lg shadow-md">
                <p class="text-gray-500 text-lg">No food items available at the moment. Be the first to <a href="add_food.php" class="text-green-600 hover:underline">add food</a>!</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

