<?php 
require_once 'db.php'; 
// Public listings logic
$sql = "SELECT f.*, u.username as donor_name FROM food_items f JOIN users u ON f.donor_id = u.id WHERE f.status = 'available' ORDER BY f.created_at DESC LIMIT 6";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>FoodShare - Waste Less, Share More</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        html { scroll-behavior: smooth; }
    </style>
</head>
<body class="bg-gray-50 font-sans">
    
    <nav class="bg-white/90 backdrop-blur-md fixed w-full z-50 shadow-sm">
        <div class="container mx-auto px-6 py-4 flex justify-between items-center">
            <a href="index.php" class="text-2xl font-bold text-green-700 tracking-tighter">FoodShare</a>
            <div class="space-x-6 text-sm font-medium">
                <a href="#about" class="text-gray-600 hover:text-green-600 transition">About Us</a>
                <a href="#browse" class="text-gray-600 hover:text-green-600 transition">Browse Food</a>
                <?php if(isLoggedIn()): ?>
                    <a href="dashboard.php" class="bg-green-600 text-white px-5 py-2 rounded-full hover:bg-green-700 transition shadow-md">Dashboard</a>
                <?php else: ?>
                    <a href="login.php" class="text-gray-700 hover:text-green-600">Login</a>
                    <a href="register.php" class="bg-green-600 text-white px-5 py-2 rounded-full hover:bg-green-700 transition shadow-md">Join Us</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="relative h-screen flex items-center justify-center bg-cover bg-center" style="background-image: url('https://images.unsplash.com/photo-1488459716781-31db52582fe9?q=80&w=2070&auto=format&fit=crop');">
        <div class="absolute inset-0 bg-black bg-opacity-60"></div>
        
        <div class="relative z-10 text-center text-white px-6 max-w-4xl">
            <h1 class="text-5xl md:text-7xl font-bold mb-6 leading-tight">Waste Less.<br><span class="text-green-400">Share More.</span></h1>
            <p class="text-xl md:text-2xl mb-10 text-gray-200 font-light">Join the movement to end food waste. Connect with your neighbors and share surplus food today.</p>
            <a href="#browse" class="bg-green-500 hover:bg-green-600 text-white font-bold py-4 px-10 rounded-full text-lg shadow-lg transform hover:scale-105 transition duration-300">
                Browse Available Food ↓
            </a>
        </div>
    </div>

    <div id="about" class="py-24 bg-white">
        <div class="container mx-auto px-6">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-800 mb-4">Who Are We?</h2>
                <div class="w-24 h-1 bg-green-500 mx-auto"></div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-12 text-center">
                <div class="p-8">
                    <div class="bg-green-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-6">
                        <span class="text-3xl">🌍</span>
                    </div>
                    <h3 class="text-xl font-bold mb-4">Our Mission</h3>
                    <p class="text-gray-600 leading-relaxed">
                        FoodShare is dedicated to reducing local food waste by connecting donors with surplus food to neighbors in need. We believe no good food should be thrown away.
                    </p>
                </div>
                <div class="p-8">
                    <div class="bg-green-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-6">
                        <span class="text-3xl">🤝</span>
                    </div>
                    <h3 class="text-xl font-bold mb-4">Community First</h3>
                    <p class="text-gray-600 leading-relaxed">
                        We are building a trust-based community where sharing is the norm. Our platform ensures safety through verification codes and a reputation system.
                    </p>
                </div>
                <div class="p-8">
                    <div class="bg-green-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-6">
                        <span class="text-3xl">🌱</span>
                    </div>
                    <h3 class="text-xl font-bold mb-4">Sustainable Future</h3>
                    <p class="text-gray-600 leading-relaxed">
                        Every meal shared is a step towards a greener planet. By preventing food waste, we reduce carbon footprints and support a circular economy.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div id="browse" class="py-20 bg-gray-100 border-t">
        <div class="container mx-auto px-6">
            <div class="flex justify-between items-end mb-12">
                <div>
                    <h2 class="text-3xl font-bold text-gray-800">Available Food Nearby</h2>
                    <p class="text-gray-500 mt-2">Check out the latest donations from your community.</p>
                </div>
                <?php if(!isLoggedIn()): ?>
                    <a href="register.php" class="hidden md:inline-block text-green-600 font-bold hover:underline">Sign up to claim food →</a>
                <?php endif; ?>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <?php while($item = $result->fetch_assoc()): ?>
                    <?php 
                        $img_path = "uploads/" . $item['image_path'];
                        if (!empty($item['image_path']) && file_exists($img_path)) {
                            $display = $img_path;
                        } else {
                            $display = "https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=400&q=80";
                        }
                    ?>
                    <div class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-xl transition duration-300 transform hover:-translate-y-1">
                        <img src="<?php echo $display; ?>" class="w-full h-48 object-cover">
                        <div class="p-6">
                            <h3 class="text-xl font-bold text-gray-800 mb-2"><?php echo htmlspecialchars($item['title']); ?></h3>
                            <div class="flex justify-between items-center text-sm text-gray-500 mb-4">
                                <span>📍 <?php echo htmlspecialchars($item['province']); ?></span>
                                <span class="text-green-600 font-semibold">Exp: <?php echo date("d.m", strtotime($item['expiry_date'])); ?></span>
                            </div>
                            <?php if(isLoggedIn()): ?>
                                <a href="dashboard.php" class="block w-full text-center bg-green-600 text-white py-2 rounded-lg font-bold hover:bg-green-700 transition">View Details</a>
                            <?php else: ?>
                                <a href="register.php" class="block w-full text-center bg-gray-800 text-white py-2 rounded-lg font-bold hover:bg-gray-900 transition">Register to Claim</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
    
    <footer class="bg-gray-900 text-gray-300 py-12">
        <div class="container mx-auto px-6">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="mb-6 md:mb-0">
                    <h2 class="text-2xl font-bold text-white mb-2">FoodShare</h2>
                    <p class="text-sm">Building a waste-free world, one meal at a time.</p>
                </div>
                <div class="flex space-x-8 text-sm">
                    <a href="#" class="hover:text-green-400 transition">Privacy Policy</a>
                    <a href="#" class="hover:text-green-400 transition">Terms of Service</a>
                    <a href="#" class="hover:text-green-400 transition">Contact Us</a>
                </div>
            </div>
            <div class="border-t border-gray-800 mt-8 pt-8 text-center text-xs text-gray-500">
                &copy; 2025 FoodShare Platform. All rights reserved.
            </div>
        </div>
    </footer>
</body>
</html>