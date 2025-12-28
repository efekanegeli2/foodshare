<?php require_once 'db.php';
if (!isAdmin()) { header("Location: index.php"); exit(); }

// --- İŞLEMLER ---
if (isset($_POST['delete_user'])) {
    $id = (int)$_POST['user_id'];
    $conn->query("DELETE FROM users WHERE id=$id");
}
if (isset($_POST['delete_food'])) {
    $id = (int)$_POST['food_id'];
    $conn->query("DELETE FROM food_items WHERE id=$id");
}
if (isset($_POST['dismiss_report'])) {
    $rid = (int)$_POST['report_id'];
    $conn->query("UPDATE reports SET status='dismissed' WHERE id=$rid");
}
if (isset($_POST['punish_user'])) {
    $rid = (int)$_POST['report_id'];
    $reported_uid = (int)$_POST['reported_user_id'];
    $conn->query("UPDATE reports SET status='resolved' WHERE id=$rid");
    $conn->query("UPDATE users SET reputation_score = reputation_score - 1.0 WHERE id=$reported_uid");
}

// --- VERİLERİ ÇEKME ---
$users = $conn->query("SELECT * FROM users WHERE user_type != 'admin'");
// EKSİK OLAN İLANLAR SORGUSU GERİ GELDİ:
$foods = $conn->query("SELECT f.*, u.username FROM food_items f JOIN users u ON f.donor_id = u.id ORDER BY f.created_at DESC");
$reports = $conn->query("SELECT r.*, u1.username AS reporter_name, u2.username AS reported_name, u2.reputation_score AS current_score FROM reports r JOIN users u1 ON r.reporter_id = u1.id JOIN users u2 ON r.reported_user_id = u2.id WHERE r.status = 'pending_review' ORDER BY r.created_at DESC");

// --- SEBEP DÜZELTME SÖZLÜĞÜ (Mapping) ---
// Artık 'no_show' yazmayacak, buradaki düzgün İngilizce metin yazacak.
$reason_map = [
    'no_show'       => 'User did not show up (Buluşmaya Gelmedi)',
    'no_code'       => 'Refused to give code (Kodu Vermedi)',
    'rude'          => 'Rude Behavior (Kaba Davranış)',
    'spoiled'       => 'Spoiled/Expired Food (Bozuk Gıda)',
    'hygiene'       => 'Hygiene Issue (Hijyen Sorunu)',
    'fake_listing'  => 'Misleading Content (Yanıltıcı İlan)',
    'money_request' => 'Requested Money (Para İstedi)',
    'other'         => 'Other Reason (Diğer)'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Admin Panel - FoodShare</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="container mx-auto">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Admin Panel</h1>
            <a href="dashboard.php" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Back to Dashboard</a>
        </div>
        
        <div class="bg-white p-6 rounded shadow mb-8 border-l-4 border-red-500">
            <h2 class="text-xl font-bold mb-4 text-red-600">⚠️ Pending Reports</h2>
            <?php if ($reports->num_rows > 0): ?>
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-red-50 text-red-800">
                            <th class="p-3 border">Reporter</th>
                            <th class="p-3 border">Reported User</th>
                            <th class="p-3 border">Reason</th>
                            <th class="p-3 border">Details</th>
                            <th class="p-3 border">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($rep = $reports->fetch_assoc()): ?>
                        <tr class="border-b hover:bg-gray-50">
                            <td class="p-3"><?php echo htmlspecialchars($rep['reporter_name']); ?></td>
                            <td class="p-3">
                                <b><?php echo htmlspecialchars($rep['reported_name']); ?></b>
                                <br><span class="text-xs text-gray-500">Score: <?php echo $rep['current_score']; ?></span>
                            </td>
                            <td class="p-3 font-semibold text-gray-800">
                                <?php echo isset($reason_map[$rep['reason_category']]) ? $reason_map[$rep['reason_category']] : $rep['reason_category']; ?>
                            </td>
                            <td class="p-3 text-sm text-gray-600 italic"><?php echo htmlspecialchars($rep['reason_details']); ?></td>
                            <td class="p-3 flex space-x-2">
                                <form method="POST">
                                    <input type="hidden" name="report_id" value="<?php echo $rep['id']; ?>">
                                    <button type="submit" name="dismiss_report" class="bg-gray-500 text-white px-3 py-1 rounded text-xs hover:bg-gray-600">Dismiss</button>
                                </form>
                                <form method="POST" onsubmit="return confirm('Reduce score by 1.0?');">
                                    <input type="hidden" name="report_id" value="<?php echo $rep['id']; ?>">
                                    <input type="hidden" name="reported_user_id" value="<?php echo $rep['reported_user_id']; ?>">
                                    <button type="submit" name="punish_user" class="bg-red-600 text-white px-3 py-1 rounded text-xs hover:bg-red-700">Punish (-1 Pt)</button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-gray-500 italic">No pending reports.</p>
            <?php endif; ?>
        </div>

        <div class="bg-white p-6 rounded shadow mb-8">
            <h2 class="text-xl font-bold mb-4 text-gray-700">User List</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead><tr class="border-b bg-gray-50"><th>ID</th><th>User</th><th>Type</th><th>Score</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php while($u = $users->fetch_assoc()): ?>
                        <tr class="border-b">
                            <td class="p-2"><?php echo $u['id']; ?></td>
                            <td class="p-2"><?php echo htmlspecialchars($u['username']); ?></td>
                            <td class="p-2"><span class="bg-blue-100 text-blue-800 px-2 rounded text-xs"><?php echo $u['user_type']; ?></span></td>
                            <td class="p-2 font-bold <?php echo ($u['reputation_score'] < 2.0) ? 'text-red-600' : 'text-green-600'; ?>"><?php echo $u['reputation_score']; ?></td>
                            <td class="p-2">
                                <form method="POST" onsubmit="return confirm('Delete user?');"><input type="hidden" name="user_id" value="<?php echo $u['id']; ?>"><button type="submit" name="delete_user" class="text-red-600 font-bold text-sm">Delete</button></form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white p-6 rounded shadow">
            <h2 class="text-xl font-bold mb-4 text-gray-700">Active Food Listings</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead><tr class="border-b bg-gray-50"><th>ID</th><th>Image</th><th>Title</th><th>Donor</th><th>Status</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php while($f = $foods->fetch_assoc()): ?>
                        <tr class="border-b">
                            <td class="p-2"><?php echo $f['id']; ?></td>
                            <td class="p-2">
                                <?php if($f['image_path'] && file_exists('uploads/'.$f['image_path'])): ?>
                                    <img src="uploads/<?php echo $f['image_path']; ?>" class="w-10 h-10 object-cover rounded">
                                <?php else: ?>
                                    <span class="text-xs text-gray-400">No Img</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-2"><?php echo htmlspecialchars($f['title']); ?></td>
                            <td class="p-2"><?php echo htmlspecialchars($f['username']); ?></td>
                            <td class="p-2 text-xs uppercase"><?php echo $f['status']; ?></td>
                            <td class="p-2">
                                <form method="POST" onsubmit="return confirm('Delete listing?');">
                                    <input type="hidden" name="food_id" value="<?php echo $f['id']; ?>">
                                    <button type="submit" name="delete_food" class="text-red-600 font-bold text-sm">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>