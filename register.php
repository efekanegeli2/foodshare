<?php
require_once 'db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $security_question = trim($_POST['security_question'] ?? '');
    $security_answer = trim($_POST['security_answer'] ?? '');
    
    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($security_question) || empty($security_answer)) {
        $error = 'All fields are required.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } else {
        // Check if username or email already exists
        $check_query = "SELECT id FROM users WHERE username = ? OR email = ?";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = 'Username or email already exists.';
        } else {
            // Hash password and security answer
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $hashed_answer = password_hash(strtolower($security_answer), PASSWORD_DEFAULT);
            
            // Insert user
            $insert_query = "INSERT INTO users (username, email, password, security_question, security_answer) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param("sssss", $username, $email, $hashed_password, $security_question, $hashed_answer);
            
            if ($stmt->execute()) {
                $success = 'Registration successful! You can now <a href="login.php" class="text-green-600 underline">login</a>.';
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - FoodShare</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8 bg-white p-8 rounded-lg shadow-lg">
            <div>
                <h2 class="text-center text-3xl font-extrabold text-gray-900">Create Account</h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    Already have an account? <a href="login.php" class="font-medium text-green-600 hover:text-green-500">Login here</a>
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
            
            <form class="mt-8 space-y-6" method="POST" action="">
                <div class="space-y-4">
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
                        <input id="username" name="username" type="text" required 
                               class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500"
                               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                    </div>
                    
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                        <input id="email" name="email" type="email" required 
                               class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500"
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                    
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                        <input id="password" name="password" type="password" required 
                               class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                    </div>
                    
                    <div>
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirm Password</label>
                        <input id="confirm_password" name="confirm_password" type="password" required 
                               class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                    </div>
                    
                    <div>
                        <label for="security_question" class="block text-sm font-medium text-gray-700">Security Question</label>
                        <input id="security_question" name="security_question" type="text" required 
                               class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500"
                               placeholder="e.g., What city were you born in?"
                               value="<?php echo htmlspecialchars($_POST['security_question'] ?? ''); ?>">
                    </div>
                    
                    <div>
                        <label for="security_answer" class="block text-sm font-medium text-gray-700">Security Answer</label>
                        <input id="security_answer" name="security_answer" type="text" required 
                               class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500"
                               placeholder="Your answer to the security question">
                    </div>
                </div>
                
                <div>
                    <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        Register
                    </button>
                </div>
            </form>
            
            <div class="text-center">
                <a href="index.php" class="text-sm text-gray-600 hover:text-green-600">Back to Home</a>
            </div>
        </div>
    </div>
</body>
</html>

