<?php require_once 'db.php';
if (!isLoggedIn()) { header("Location: login.php"); exit(); }

$user_id = $_SESSION['user_id'];
$type = $_SESSION['user_type'];
$user = $conn->query("SELECT * FROM users WHERE id=$user_id")->fetch_assoc();

if ($type == 'donor') {
    $sql_active = "SELECT t.*, f.title, f.image_path, u.username as receiver_name FROM transactions t JOIN food_items f ON t.food_id = f.id JOIN users u ON t.receiver_id = u.id WHERE t.donor_id = $user_id AND t.status = 'pending'";
} else {
    $sql_active = "SELECT t.*, f.title, f.image_path, u.username as donor_name FROM transactions t JOIN food_items f ON t.food_id = f.id JOIN users u ON t.donor_id = u.id WHERE t.receiver_id = $user_id AND t.status = 'pending'";
}
$active_result = $conn->query($sql_active);

$sql_history = "SELECT t.*, f.title, u.username as other_party, f.image_path, (SELECT COUNT(*) FROM reports r WHERE r.transaction_id = t.id AND r.reporter_id = $user_id) as is_reported FROM transactions t JOIN food_items f ON t.food_id = f.id JOIN users u ON " . ($type == 'donor' ? "t.receiver_id = u.id" : "t.donor_id = u.id") . " WHERE t.status = 'completed' AND " . ($type == 'donor' ? "t.donor_id" : "t.receiver_id") . " = $user_id ORDER BY t.completed_at DESC";
$history_result = $conn->query($sql_history);
?>
<!DOCTYPE html>
<html lang="en">
<head><title>My Profile</title><script src="https://cdn.tailwindcss.com"></script></head>
<body class="bg-gray-50">
    <nav class="bg-green-600 text-white p-4 shadow mb-6">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-xl font-bold"><a href="dashboard.php">FoodShare</a></h1>
            <a href="logout.php" class="text-white hover:underline">Logout</a>
        </div>
    </nav>
    <div class="container mx-auto px-4 py-6">
        <div class="bg-white p-6 rounded shadow mb-8 flex justify-between items-center">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">Hello, <?php echo htmlspecialchars($user['username']); ?></h2>
                <p class="text-gray-600"><?php echo ucfirst($type); ?> Account</p>
            </div>
            <div class="text-right">
                <span class="block text-3xl font-bold text-green-600"><?php echo $user['reputation_score']; ?></span>
                <span class="text-xs text-gray-500">Trust Score</span>
            </div>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <div>
                <h3 class="text-xl font-bold mb-4 border-b pb-2">🚀 Active Transactions</h3>
                <?php if ($active_result->num_rows > 0): ?>
                    <div class="space-y-4">
                        <?php while($row = $active_result->fetch_assoc()): ?>
                            <div class="bg-white p-4 rounded shadow flex gap-4 border-l-4 border-yellow-400">
                                <?php $img = !empty($row['image_path']) ? "uploads/".$row['image_path'] : "https://via.placeholder.com/150"; ?>
                                <img src="<?php echo $img; ?>" class="w-24 h-24 object-cover rounded">
                                <div class="flex-1">
                                    <h4 class="font-bold text-lg"><?php echo htmlspecialchars($row['title']); ?></h4>
                                    <?php if ($type == 'donor'): ?>
                                        <p class="text-sm text-gray-600 mb-2">Receiver: <b><?php echo htmlspecialchars($row['receiver_name']); ?></b></p>
                                        <form action="verify_code.php" method="POST" class="mt-2">
                                            <input type="hidden" name="transaction_id" value="<?php echo $row['id']; ?>">
                                            <div class="flex gap-2">
                                                <input type="text" name="code" placeholder="6-Digit Code" required maxlength="6" class="border p-2 rounded w-full text-center tracking-widest">
                                                <button type="submit" class="bg-green-600 text-white px-4 rounded font-bold hover:bg-green-700">Confirm</button>
                                            </div>
                                        </form>
                                    <?php else: ?>
                                        <p class="text-sm text-gray-600 mb-2">Donor: <b><?php echo htmlspecialchars($row['donor_name']); ?></b></p>
                                        <div class="bg-gray-100 p-2 rounded text-center">
                                            <p class="text-xs text-gray-500">Verification Code:</p>
                                            <p class="text-2xl font-mono font-bold text-blue-600 tracking-widest"><?php echo $row['verification_code']; ?></p>
                                            <p class="text-xs text-red-500 mt-1">Show this to the donor.</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <p class="text-gray-500 italic">No active transactions.</p>
                <?php endif; ?>
            </div>
            <div>
                <h3 class="text-xl font-bold mb-4 border-b pb-2">📜 History & Reports</h3>
                <?php if ($history_result->num_rows > 0): ?>
                    <div class="space-y-4">
                        <?php while($hist = $history_result->fetch_assoc()): ?>
                            <div class="bg-white p-4 rounded shadow flex justify-between items-center opacity-75 hover:opacity-100 transition">
                                <div>
                                    <h4 class="font-bold"><?php echo htmlspecialchars($hist['title']); ?></h4>
                                    <p class="text-xs text-gray-500">User: <?php echo htmlspecialchars($hist['other_party']); ?></p>
                                    <p class="text-xs text-gray-400"><?php echo date("d.m.Y", strtotime($hist['completed_at'])); ?></p>
                                </div>
                                <div>
                                    <?php if ($hist['is_reported'] > 0): ?>
                                        <span class="text-xs bg-red-100 text-red-600 px-2 py-1 rounded">Reported</span>
                                    <?php else: ?>
                                        <a href="report_user.php?tid=<?php echo $hist['id']; ?>" class="bg-red-50 text-red-600 border border-red-200 px-3 py-1 rounded text-sm hover:bg-red-600 hover:text-white transition">⚠️ Report User</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <p class="text-gray-500 italic">No completed transactions yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>