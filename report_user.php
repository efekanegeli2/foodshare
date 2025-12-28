<?php require_once 'db.php';
if (!isLoggedIn()) header("Location: login.php");

$tid = (int)$_GET['tid'];
$user_id = $_SESSION['user_id'];
$sql = "SELECT t.*, u_donor.username as donor_name, u_receiver.username as receiver_name FROM transactions t JOIN users u_donor ON t.donor_id = u_donor.id JOIN users u_receiver ON t.receiver_id = u_receiver.id WHERE t.id = $tid AND (t.donor_id = $user_id OR t.receiver_id = $user_id)";
$res = $conn->query($sql);
if ($res->num_rows == 0) die("Transaction not found.");
$trans = $res->fetch_assoc();
$reported_id = ($user_id == $trans['donor_id']) ? $trans['receiver_id'] : $trans['donor_id'];
$reported_name = ($user_id == $trans['donor_id']) ? $trans['receiver_name'] : $trans['donor_name'];
$my_role = ($user_id == $trans['donor_id']) ? 'donor' : 'receiver';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $cat = escape($_POST['category']);
    $det = escape($_POST['details']);
    $conn->query("INSERT INTO reports (reporter_id, reported_user_id, transaction_id, reason_category, reason_details) VALUES ($user_id, $reported_id, $tid, '$cat', '$det')");
    echo "<script>alert('Report submitted successfully.'); window.location='profile.php';</script>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head><title>Report User</title><script src="https://cdn.tailwindcss.com"></script></head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded shadow-lg w-full max-w-md">
        <h2 class="text-xl font-bold text-red-600 mb-4">⚠️ Report User</h2>
        <p class="mb-4 text-gray-700">Reporting: <b><?php echo $reported_name; ?></b></p>
        <form method="POST">
            <label class="block mb-2 font-bold text-sm">Reason:</label>
            <select name="category" class="w-full border p-2 rounded mb-4" required>
                <?php if ($my_role == 'donor'): ?>
                    <option value="no_show">Receiver did not show up</option>
                    <option value="no_code">Refused to give code</option>
                    <option value="rude">Rude Behavior</option>
                    <option value="other">Other</option>
                <?php else: ?>
                    <option value="spoiled">Food was spoiled/expired</option>
                    <option value="hygiene">Hygiene Issue</option>
                    <option value="fake_listing">Misleading Content</option>
                    <option value="no_show">Donor did not show up</option>
                    <option value="other">Other</option>
                <?php endif; ?>
            </select>
            <label class="block mb-2 font-bold text-sm">Details (Optional):</label>
            <textarea name="details" class="w-full border p-2 rounded mb-4 h-24" placeholder="Please describe the issue..."></textarea>
            <div class="flex justify-between">
                <a href="profile.php" class="text-gray-500 py-2">Cancel</a>
                <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded font-bold hover:bg-red-700">Submit Report</button>
            </div>
        </form>
    </div>
</body>
</html>