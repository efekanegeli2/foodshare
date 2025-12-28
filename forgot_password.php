<?php 
require_once 'db.php';
$step = 1; 
$error = ''; 
$success = '';

$questions = [
    1 => "What is the name of your primary school teacher?",
    2 => "What is the name of your first pet?",
    3 => "In which city were you born?",
    4 => "What is your favorite movie?"
];

if (isset($_POST['check_email'])) {
    $email = escape($_POST['email']);
    $result = $conn->query("SELECT id, security_question FROM users WHERE email = '$email'");
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $_SESSION['reset_user_id'] = $user['id'];
        $_SESSION['reset_q_id'] = $user['security_question'];
        $step = 2;
    } else { 
        $error = "Email not found."; 
    }
}

if (isset($_POST['reset_pass'])) {
    $uid = $_SESSION['reset_user_id'];
    $answer = trim($_POST['security_answer']);
    $new_pass = $_POST['new_password'];
    
    $user_data = $conn->query("SELECT security_answer FROM users WHERE id = $uid")->fetch_assoc();
    
    if ($user_data && password_verify($answer, $user_data['security_answer'])) {
        $new_pass_hash = password_hash($new_pass, PASSWORD_DEFAULT);
        $conn->query("UPDATE users SET password = '$new_pass_hash' WHERE id = $uid");
        $success = "Password changed successfully! Redirecting to login...";
        unset($_SESSION['reset_user_id']); 
        unset($_SESSION['reset_q_id']);
        header("refresh:2;url=login.php");
    } else { 
        $error = "Incorrect answer to security question."; 
        $step = 2; 
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Forgot Password</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded-lg shadow-md w-96">
        <h2 class="text-2xl font-bold text-center text-green-600 mb-6">Reset Password</h2>
        
        <?php if ($error): ?>
            <div class='bg-red-100 text-red-700 p-3 rounded mb-4 text-sm text-center'>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class='bg-green-100 text-green-700 p-3 rounded mb-4 text-sm text-center'>
                <?php echo $success; ?>
            </div>
        <?php else: ?>
            
            <?php if ($step == 1): ?>
                <form method="POST">
                    <p class="text-gray-600 text-sm mb-4 text-center">Enter your email to find your account.</p>
                    <input type="email" name="email" required placeholder="Email Address" class="w-full px-3 py-2 border rounded mb-4">
                    <button type="submit" name="check_email" class="w-full bg-green-600 text-white font-bold py-2 px-4 rounded hover:bg-green-700 transition">Continue</button>
                </form>
            <?php elseif ($step == 2): ?>
                <form method="POST">
                    <div class="bg-blue-50 p-3 rounded border border-blue-200 mb-4">
                        <p class="text-xs text-gray-500 uppercase font-bold">Security Question:</p>
                        <p class="text-gray-800 font-semibold text-lg">
                            <?php echo isset($questions[$_SESSION['reset_q_id']]) ? $questions[$_SESSION['reset_q_id']] : "Unknown Question"; ?>
                        </p>
                    </div>
                    <input type="text" name="security_answer" placeholder="Your Answer" required class="w-full px-3 py-2 border rounded mb-4">
                    <input type="password" name="new_password" placeholder="New Password" required class="w-full px-3 py-2 border rounded mb-4">
                    <button type="submit" name="reset_pass" class="w-full bg-green-600 text-white font-bold py-2 px-4 rounded hover:bg-green-700 transition">Change Password</button>
                </form>
            <?php endif; ?>

        <?php endif; ?>
        
        <div class="mt-4 text-center">
            <a href="login.php" class="text-sm text-gray-500 hover:text-green-600">Back to Login</a>
        </div>
    </div>
</body>
</html>