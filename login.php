<?php require_once 'db.php';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = escape($_POST['email']);
    $password = $_POST['password'];
    
    $result = $conn->query("SELECT * FROM users WHERE email='$email'");
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_type'] = $user['user_type'];
            $_SESSION['username'] = $user['username'];
            
            if($user['user_type'] == 'admin') header("Location: admin.php");
            else header("Location: dashboard.php");
            exit();
        } else { $error = "Incorrect password."; }
    } else { $error = "User not found."; }
}
?>
<!DOCTYPE html>
<html lang="en">
<head><title>Login - FoodShare</title><script src="https://cdn.tailwindcss.com"></script></head>
<body class="bg-gray-100 flex justify-center items-center min-h-screen">
    <div class="bg-white p-8 rounded shadow-md w-96">
        <h2 class="text-2xl font-bold text-center text-green-600 mb-6">Login</h2>
        <?php if(isset($error)) echo "<p class='text-red-500 mb-4 text-sm'>$error</p>"; ?>
        <form method="POST" class="space-y-4">
            <input type="email" name="email" placeholder="Email Address" required class="w-full border p-2 rounded">
            <input type="password" name="password" placeholder="Password" required class="w-full border p-2 rounded">
            <button type="submit" class="w-full bg-green-600 text-white py-2 rounded font-bold hover:bg-green-700">Login</button>
        </form>
        <div class="mt-4 text-center text-sm space-y-2">
            <div><a href="register.php" class="text-blue-500 hover:underline">Create an Account</a></div>
            <div><a href="forgot_password.php" class="text-gray-500 hover:underline">Forgot Password?</a></div>
        </div>
    </div>
</body>
</html>