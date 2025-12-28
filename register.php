<?php require_once 'db.php'; 
$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user = escape($_POST['username']);
    $email = escape($_POST['email']);
    $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $type = escape($_POST['user_type']);
    $prov = escape($_POST['province']);
    $dist = escape($_POST['district']);
    $sq_id = (int)$_POST['security_question'];
    $sq_ans = password_hash(trim($_POST['security_answer']), PASSWORD_DEFAULT);

    $check = $conn->query("SELECT id FROM users WHERE email='$email' OR username='$user'");
    if($check->num_rows > 0) { $error = "Username or Email already exists."; }
    else {
        $sql = "INSERT INTO users (username, email, password, user_type, province, district, security_question, security_answer) 
                VALUES ('$user', '$email', '$pass', '$type', '$prov', '$dist', $sq_id, '$sq_ans')";
        if($conn->query($sql)) { header("Location: login.php"); exit(); } 
        else { $error = "Error: " . $conn->error; }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head><title>Register - FoodShare</title><script src="https://cdn.tailwindcss.com"></script></head>
<body class="bg-gray-100 flex justify-center items-center min-h-screen py-10">
    <div class="bg-white p-8 rounded shadow-md w-full max-w-md">
        <h2 class="text-2xl font-bold text-center text-green-600 mb-6">Create Account</h2>
        <?php if($error) echo "<p class='text-red-500 mb-4 text-sm'>$error</p>"; ?>
        <form method="POST" class="space-y-4">
            <input type="text" name="username" placeholder="Username" required class="w-full border p-2 rounded">
            <input type="email" name="email" placeholder="Email Address" required class="w-full border p-2 rounded">
            <input type="password" name="password" placeholder="Password" required class="w-full border p-2 rounded">
            
            <div class="grid grid-cols-2 gap-2">
                <input type="text" name="province" placeholder="City (e.g. London)" required class="border p-2 rounded">
                <input type="text" name="district" placeholder="District" required class="border p-2 rounded">
            </div>

            <label class="block text-sm text-gray-600 font-bold">Account Type</label>
            <select name="user_type" class="w-full border p-2 rounded">
                <option value="receiver">Receiver (I need food)</option>
                <option value="donor">Donor (I want to share)</option>
            </select>

            <div class="bg-gray-50 p-3 rounded border">
                <label class="block text-sm font-bold text-gray-700 mb-1">Security Question (For Recovery)</label>
                <select name="security_question" class="w-full border p-2 rounded mb-2">
                    <option value="1">What is the name of your primary school teacher?</option>
                    <option value="2">What is the name of your first pet?</option>
                    <option value="3">In which city were you born?</option>
                    <option value="4">What is your favorite movie?</option>
                </select>
                <input type="text" name="security_answer" placeholder="Your Answer" required class="w-full border p-2 rounded">
            </div>

            <button type="submit" class="w-full bg-green-600 text-white py-2 rounded font-bold hover:bg-green-700">Sign Up</button>
        </form>
        <p class="text-center mt-4 text-sm">Already have an account? <a href="login.php" class="text-green-600 font-bold">Login</a></p>
    </div>
</body>
</html>