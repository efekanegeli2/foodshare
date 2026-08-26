<?php
require_once 'db.php';

// Get all available food items for public view
$public_food_query = "SELECT fi.*, u.username, u.email 
                      FROM food_items fi 
                      JOIN users u ON fi.user_id = u.id 
                      WHERE fi.status = 'available' 
                      ORDER BY fi.created_at DESC 
                      LIMIT 20";
$public_food_result = $conn->query($public_food_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FoodShare - Food Waste Sharing Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .hero-gradient {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <h1 class="text-2xl font-bold text-green-600">FoodShare</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <?php if (isLoggedIn()): ?>
                        <a href="dashboard.php" class="text-gray-700 hover:text-green-600 px-3 py-2 rounded-md text-sm font-medium">Dashboard</a>
                        <a href="profile.php" class="text-gray-700 hover:text-green-600 px-3 py-2 rounded-md text-sm font-medium">Profile</a>
                        <a href="logout.php" class="bg-red-500 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-red-600">Logout</a>
                    <?php else: ?>
                        <a href="login.php" class="text-gray-700 hover:text-green-600 px-3 py-2 rounded-md text-sm font-medium">Login</a>
                        <a href="register.php" class="bg-green-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-green-700">Register</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-gradient text-white py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-5xl md:text-6xl font-bold mb-6">Food Waste Sharing Platform</h1>
            <p class="text-xl md:text-2xl mb-8 text-green-100">Connect with your community to share food and reduce waste</p>
            <a href="#food-grid" class="inline-block bg-white text-green-600 px-8 py-4 rounded-lg text-lg font-semibold hover:bg-green-50 transition duration-300 shadow-lg">
                Browse Food
            </a>
        </div>
    </section>

    <!-- Public Food Grid -->
    <section id="food-grid" class="py-16 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-gray-800 mb-8 text-center">Available Food Items</h2>
            
            <?php if ($public_food_result && $public_food_result->num_rows > 0): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php while ($item = $public_food_result->fetch_assoc()): ?>
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
                                
                                <?php if (isLoggedIn()): ?>
                                    <form method="POST" action="request_food.php">
                                        <input type="hidden" name="food_id" value="<?php echo $item['id']; ?>">
                                        <button type="submit" class="w-full bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700 transition duration-300">
                                            Request Food
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <a href="register.php" class="block w-full bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700 transition duration-300 text-center">
                                        Request Food (Login Required)
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-12">
                    <p class="text-gray-500 text-lg">No food items available at the moment. Check back later!</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <p>&copy; <?php echo date('Y'); ?> FoodShare Platform. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>

