<?php
require_once 'db.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';

// Handle admin actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'suspend_user') {
        $user_id = intval($_POST['user_id'] ?? 0);
        $update_query = "UPDATE users SET status = 'suspended' WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("i", $user_id);
        
        if ($stmt->execute()) {
            $success = 'User suspended successfully.';
        } else {
            $error = 'Failed to suspend user.';
        }
        $stmt->close();
    } elseif ($action === 'activate_user') {
        $user_id = intval($_POST['user_id'] ?? 0);
        $update_query = "UPDATE users SET status = 'active' WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("i", $user_id);
        
        if ($stmt->execute()) {
            $success = 'User activated successfully.';
        } else {
            $error = 'Failed to activate user.';
        }
        $stmt->close();
    } elseif ($action === 'delete_user') {
        $user_id = intval($_POST['user_id'] ?? 0);
        
        // Prevent admin from deleting themselves
        if ($user_id == getCurrentUserId()) {
            $error = 'You cannot delete your own account.';
        } else {
            $delete_query = "DELETE FROM users WHERE id = ?";
            $stmt = $conn->prepare($delete_query);
            $stmt->bind_param("i", $user_id);
            
            if ($stmt->execute()) {
                $success = 'User deleted successfully.';
            } else {
                $error = 'Failed to delete user.';
            }
            $stmt->close();
        }
    } elseif ($action === 'delete_food') {
        $food_id = intval($_POST['food_id'] ?? 0);
        $delete_query = "DELETE FROM food_items WHERE id = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param("i", $food_id);
        
        if ($stmt->execute()) {
            $success = 'Food item deleted successfully.';
        } else {
            $error = 'Failed to delete food item.';
        }
        $stmt->close();
    } elseif ($action === 'update_report_status') {
        $report_id = intval($_POST['report_id'] ?? 0);
        $status = $_POST['status'] ?? '';
        
        if (in_array($status, ['pending', 'reviewed', 'resolved'])) {
            $update_query = "UPDATE reports SET status = ? WHERE id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("si", $status, $report_id);
            
            if ($stmt->execute()) {
                $success = 'Report status updated successfully.';
            } else {
                $error = 'Failed to update report status.';
            }
            $stmt->close();
        }
    }
    
    // Reload page to show updated data
    if ($success || $error) {
        header('Location: admin.php');
        exit;
    }
}

// Get all users
$users_query = "SELECT id, username, email, role, status, created_at FROM users ORDER BY created_at DESC";
$users_result = $conn->query($users_query);

// Get all food items
$food_query = "SELECT fi.*, u.username FROM food_items fi JOIN users u ON fi.user_id = u.id ORDER BY fi.created_at DESC";
$food_result = $conn->query($food_query);

// Get all reports
$reports_query = "SELECT r.*, 
                  u_reporter.username as reporter_username, 
                  u_reported.username as reported_username,
                  fi.title as food_title
                  FROM reports r
                  JOIN users u_reporter ON r.reporter_id = u_reporter.id
                  JOIN users u_reported ON r.reported_user_id = u_reported.id
                  LEFT JOIN food_items fi ON r.food_id = fi.id
                  ORDER BY r.created_at DESC";
$reports_result = $conn->query($reports_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - FoodShare</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="dashboard.php" class="text-2xl font-bold text-green-600">FoodShare</a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="dashboard.php" class="text-gray-700 hover:text-green-600 px-3 py-2 rounded-md text-sm font-medium">Dashboard</a>
                    <a href="profile.php" class="text-gray-700 hover:text-green-600 px-3 py-2 rounded-md text-sm font-medium">Profile</a>
                    <a href="logout.php" class="bg-red-500 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-red-600">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-8">Admin Panel</h1>
        
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <!-- Users Section -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">Users Management</h2>
            
            <?php if ($users_result && $users_result->num_rows > 0): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Username</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php while ($user = $users_result->fetch_assoc()): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo ucfirst($user['role']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $user['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                            <?php echo ucfirst($user['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                        <?php if ($user['status'] === 'active'): ?>
                                            <form method="POST" action="" class="inline">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <input type="hidden" name="action" value="suspend_user">
                                                <button type="submit" class="text-yellow-600 hover:text-yellow-900">Suspend</button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" action="" class="inline">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <input type="hidden" name="action" value="activate_user">
                                                <button type="submit" class="text-green-600 hover:text-green-900">Activate</button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <?php if ($user['id'] != getCurrentUserId()): ?>
                                            <form method="POST" action="" class="inline" onsubmit="return confirm('Are you sure you want to delete this user? This action cannot be undone.');">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <input type="hidden" name="action" value="delete_user">
                                                <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-gray-500">No users found.</p>
            <?php endif; ?>
        </div>
        
        <!-- Food Items Section -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">Food Items Management</h2>
            
            <?php if ($food_result && $food_result->num_rows > 0): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Donor</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expiry Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php while ($food = $food_result->fetch_assoc()): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($food['title']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($food['username']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php 
                                            echo $food['status'] === 'available' ? 'bg-green-100 text-green-800' : 
                                                ($food['status'] === 'reserved' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800'); 
                                        ?>">
                                            <?php echo ucfirst($food['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo date('M d, Y', strtotime($food['expiry_date'])); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <form method="POST" action="" class="inline" onsubmit="return confirm('Are you sure you want to delete this food item? This action cannot be undone.');">
                                            <input type="hidden" name="food_id" value="<?php echo $food['id']; ?>">
                                            <input type="hidden" name="action" value="delete_food">
                                            <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-gray-500">No food items found.</p>
            <?php endif; ?>
        </div>
        
        <!-- Reports Section -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">Reports Management</h2>
            
            <?php if ($reports_result && $reports_result->num_rows > 0): ?>
                <div class="space-y-4">
                    <?php while ($report = $reports_result->fetch_assoc()): ?>
                        <div class="border border-gray-200 rounded-lg p-4">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <p class="text-sm text-gray-600">
                                        <span class="font-medium">Reporter:</span> <?php echo htmlspecialchars($report['reporter_username']); ?>
                                    </p>
                                    <p class="text-sm text-gray-600">
                                        <span class="font-medium">Reported User:</span> <?php echo htmlspecialchars($report['reported_username']); ?>
                                    </p>
                                    <?php if ($report['food_title']): ?>
                                        <p class="text-sm text-gray-600">
                                            <span class="font-medium">Food Item:</span> <?php echo htmlspecialchars($report['food_title']); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php 
                                    echo $report['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                        ($report['status'] === 'reviewed' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'); 
                                ?>">
                                    <?php echo ucfirst($report['status']); ?>
                                </span>
                            </div>
                            
                            <p class="text-sm font-medium text-gray-700 mb-1">Category: <?php echo htmlspecialchars($report['reason_category']); ?></p>
                            <p class="text-sm text-gray-600 mb-3"><?php echo htmlspecialchars($report['reason_details']); ?></p>
                            
                            <div class="flex space-x-2">
                                <form method="POST" action="" class="inline">
                                    <input type="hidden" name="report_id" value="<?php echo $report['id']; ?>">
                                    <input type="hidden" name="action" value="update_report_status">
                                    <input type="hidden" name="status" value="reviewed">
                                    <button type="submit" class="text-blue-600 hover:text-blue-900 text-sm">Mark as Reviewed</button>
                                </form>
                                <form method="POST" action="" class="inline">
                                    <input type="hidden" name="report_id" value="<?php echo $report['id']; ?>">
                                    <input type="hidden" name="action" value="update_report_status">
                                    <input type="hidden" name="status" value="resolved">
                                    <button type="submit" class="text-green-600 hover:text-green-900 text-sm">Mark as Resolved</button>
                                </form>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p class="text-gray-500">No reports found.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

