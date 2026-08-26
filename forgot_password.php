<?php
require_once 'db.php';

$step = $_GET['step'] ?? '1'; // 1: email, 2: security question, 3: new password
$error = '';
$success = '';
$email = '';
$security_question = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step === '1') {
        // Step 1: Get email
        $email = trim($_POST['email'] ?? '');
        
        if (empty($email)) {
            $error = 'Email is required.';
        } else {
            $query = "SELECT id, security_question FROM users WHERE email = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                $_SESSION['reset_email'] = $email;
                $_SESSION['reset_user_id'] = $user['id'];
                $security_question = $user['security_question'];
                $step = '2';
            } else {
                $error = 'Email not found.';
            }
            $stmt->close();
        }
    } elseif ($step === '2') {
        // Step 2: Verify security answer
        $email = $_SESSION['reset_email'] ?? '';
        $security_answer = trim($_POST['security_answer'] ?? '');
        
        if (empty($security_answer)) {
            $error = 'Security answer is required.';
        } else {
            $query = "SELECT security_answer FROM users WHERE email = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                if (password_verify(strtolower($security_answer), $user['security_answer'])) {
                    $step = '3';
                } else {
                    $error = 'Incorrect security answer.';
                }
            } else {
                $error = 'Error verifying security answer.';
            }
            $stmt->close();
        }
    } elseif ($step === '3') {
        // Step 3: Set new password
        $email = $_SESSION['reset_email'] ?? '';
        $user_id = $_SESSION['reset_user_id'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($new_password) || empty($confirm_password)) {
            $error = 'Both password fields are required.';
        } elseif ($new_password !== $confirm_password) {
            $error = 'Passwords do not match.';
        } elseif (strlen($new_password) < 6) {
            $error = 'Password must be at least 6 characters long.';
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $query = "UPDATE users SET password = ? WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("si", $hashed_password, $user_id);
            
            if ($stmt->execute()) {
                unset($_SESSION['reset_email']);
                unset($_SESSION['reset_user_id']);
                $success = 'Password reset successfully! You can now <a href="login.php" class="text-green-600 underline">login</a> with your new password.';
                $step = '1';
            } else {
                $error = 'Failed to reset password. Please try again.';
            }
            $stmt->close();
        }
    }
}

// Get security question if in step 2
if ($step === '2' && isset($_SESSION['reset_email'])) {
    $email = $_SESSION['reset_email'];
    $query = "SELECT security_question FROM users WHERE email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $security_question = $user['security_question'];
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - FoodShare</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8 bg-white p-8 rounded-lg shadow-lg">
            <div>
                <h2 class="text-center text-3xl font-extrabold text-gray-900">Password Recovery</h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    Follow the steps to reset your password
                </p>
            </div>
            
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($step === '1'): ?>
                <!-- Step 1: Enter Email -->
                <form class="mt-8 space-y-6" method="POST" action="?step=1">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Enter your email address</label>
                        <input id="email" name="email" type="email" required 
                               class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500"
                               value="<?php echo htmlspecialchars($email); ?>">
                    </div>
                    
                    <div>
                        <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            Continue
                        </button>
                    </div>
                </form>
                
            <?php elseif ($step === '2'): ?>
                <!-- Step 2: Security Question -->
                <form class="mt-8 space-y-6" method="POST" action="?step=2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Your Security Question:</label>
                        <div class="bg-gray-100 p-4 rounded-md mb-4">
                            <p class="text-gray-800 font-medium"><?php echo htmlspecialchars($security_question); ?></p>
                        </div>
                        
                        <label for="security_answer" class="block text-sm font-medium text-gray-700">Your Answer</label>
                        <input id="security_answer" name="security_answer" type="text" required 
                               class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                    </div>
                    
                    <div>
                        <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            Verify Answer
                        </button>
                    </div>
                </form>
                
            <?php elseif ($step === '3'): ?>
                <!-- Step 3: New Password -->
                <form class="mt-8 space-y-6" method="POST" action="?step=3">
                    <div class="space-y-4">
                        <div>
                            <label for="new_password" class="block text-sm font-medium text-gray-700">New Password</label>
                            <input id="new_password" name="new_password" type="password" required 
                                   class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                        </div>
                        
                        <div>
                            <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                            <input id="confirm_password" name="confirm_password" type="password" required 
                                   class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                        </div>
                    </div>
                    
                    <div>
                        <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            Reset Password
                        </button>
                    </div>
                </form>
            <?php endif; ?>
            
            <div class="text-center">
                <a href="login.php" class="text-sm text-gray-600 hover:text-green-600">Back to Login</a>
            </div>
        </div>
    </div>
</body>
</html>

