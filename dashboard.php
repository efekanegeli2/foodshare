<?php require_once 'db.php';
if (!isLoggedIn()) { header("Location: login.php"); exit(); }

// FİLTRE VERİLERİNİ VERİTABANINDAN ÇEK (Sadece ilanı olan şehirler gelsin)
$cities_query = $conn->query("SELECT DISTINCT province FROM food_items WHERE status='available' ORDER BY province ASC");
$districts_query = $conn->query("SELECT DISTINCT district FROM food_items WHERE status='available' ORDER BY district ASC");

// Filtreleme Seçimleri
$filter_prov = $_GET['province'] ?? '';
$filter_dist = $_GET['district'] ?? '';

$sql = "SELECT f.*, u.username as donor_name FROM food_items f JOIN users u ON f.donor_id = u.id WHERE f.status='available'";
if($filter_prov) $sql .= " AND f.province = '" . escape($filter_prov) . "'";
if($filter_dist) $sql .= " AND f.district = '" . escape($filter_dist) . "'";
$sql .= " ORDER BY f.created_at DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head><title>Dashboard - FoodShare</title><script src="https://cdn.tailwindcss.com"></script></head>
<body class="bg-gray-50">
    <nav class="bg-green-600 text-white p-4 shadow mb-6">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-xl font-bold"><a href="index.php">FoodShare</a></h1>
            <div class="flex items-center space-x-4">
                <?php if(isAdmin()): ?>
                    <a href="admin.php" class="bg-red-500 hover:bg-red-700 text-white px-3 py-1 rounded font-bold text-sm">Admin Panel</a>
                <?php endif; ?>
                <?php if($_SESSION['user_type'] == 'donor'): ?>
                    <a href="add_food.php" class="bg-white text-green-600 px-4 py-2 rounded font-bold hover:bg-gray-100">+ Donate Food</a>
                <?php endif; ?>
                <a href="profile.php" class="hover:underline font-semibold">My Profile</a>
                <a href="logout.php" class="bg-green-800 px-3 py-1 rounded text-sm hover:bg-green-900">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4">
        
        <form class="bg-white p-4 rounded shadow mb-6 flex flex-col md:flex-row gap-4 items-end">
            <div class="w-full">
                <label class="block text-xs font-bold text-gray-500 mb-1">Filter by City</label>
                <select name="province" class="w-full border p-2 rounded bg-gray-50">
                    <option value="">All Cities</option>
                    <?php while($c = $cities_query->fetch_assoc()): ?>
                        <option value="<?php echo htmlspecialchars($c['province']); ?>" <?php echo ($filter_prov == $c['province']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($c['province']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="w-full">
                <label class="block text-xs font-bold text-gray-500 mb-1">Filter by District</label>
                <select name="district" class="w-full border p-2 rounded bg-gray-50">
                    <option value="">All Districts</option>
                    <?php while($d = $districts_query->fetch_assoc()): ?>
                        <option value="<?php echo htmlspecialchars($d['district']); ?>" <?php echo ($filter_dist == $d['district']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($d['district']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <button type="submit" class="bg-gray-800 text-white px-6 py-2 rounded font-bold hover:bg-gray-900">Filter</button>
            <a href="dashboard.php" class="bg-gray-200 text-gray-700 px-4 py-2 rounded text-center flex items-center justify-center">Reset</a>
        </form>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <?php if ($result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                    <?php 
                        $img_path = "uploads/" . $row['image_path'];
                        if (!empty($row['image_path']) && file_exists($img_path)) {
                            $display_img = $img_path;
                        } else {
                            $display_img = "https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=400&q=80"; 
                        }
                    ?>
                    <div class="bg-white rounded-lg shadow-lg overflow-hidden border border-gray-200 hover:shadow-xl transition">
                        <div class="h-48 w-full bg-gray-200 relative">
                            <img src="<?php echo $display_img; ?>" class="w-full h-full object-cover">
                            <span class="absolute top-2 right-2 bg-green-100 text-green-800 text-xs font-bold px-2 py-1 rounded shadow">
                                Exp: <?php echo date("d.m.Y", strtotime($row['expiry_date'])); ?>
                            </span>
                        </div>
                        <div class="p-4">
                            <h3 class="font-bold text-xl text-gray-800 mb-2"><?php echo htmlspecialchars($row['title']); ?></h3>
                            <p class="text-gray-600 text-sm h-12 overflow-hidden mb-3"><?php echo htmlspecialchars($row['description']); ?></p>
                            <div class="border-t pt-2 text-sm text-gray-500 space-y-1">
                                <p class="flex items-center">👤 <span class="ml-1 font-semibold"><?php echo htmlspecialchars($row['donor_name']); ?></span></p>
                                <p class="flex items-center">📍 <span class="ml-1"><?php echo htmlspecialchars($row['province']) . " / " . htmlspecialchars($row['district']); ?></span></p>
                            </div>
                            <div class="mt-4">
                                <?php if($_SESSION['user_type'] == 'receiver'): ?>
                                    <form action="request_food.php" method="POST">
                                        <input type="hidden" name="food_id" value="<?php echo $row['id']; ?>">
                                        <button type="submit" class="w-full bg-green-600 text-white py-2 rounded font-bold hover:bg-green-700">Request Food</button>
                                    </form>
                                <?php elseif($_SESSION['user_id'] == $row['donor_id'] || isAdmin()): ?>
                                    <form action="delete_food.php" method="POST" onsubmit="return confirm('Delete this listing?');">
                                        <input type="hidden" name="food_id" value="<?php echo $row['id']; ?>">
                                        <button type="submit" class="w-full bg-red-500 text-white py-2 rounded font-bold hover:bg-red-600">Delete Listing</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-span-3 text-center py-10 text-gray-500"><p class="text-xl">No food listings found.</p></div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>