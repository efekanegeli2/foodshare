<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'foodshare');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8mb4
$conn->set_charset("utf8mb4");

// Session start
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Helper function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Helper function to check if user is admin
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

// Helper function to get current user ID
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

// Initialize database tables if they don't exist
function initDatabase($conn) {
    // Users table
    $sql_users = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        security_question VARCHAR(255) NOT NULL,
        security_answer VARCHAR(255) NOT NULL,
        role ENUM('user', 'admin') DEFAULT 'user',
        status ENUM('active', 'suspended') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    // Food items table
    $sql_food = "CREATE TABLE IF NOT EXISTS food_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        image_path VARCHAR(255),
        expiry_date DATE NOT NULL,
        pickup_location VARCHAR(255) NOT NULL,
        status ENUM('available', 'reserved', 'completed') DEFAULT 'available',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    // Requests table
    $sql_requests = "CREATE TABLE IF NOT EXISTS requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        food_id INT NOT NULL,
        requester_id INT NOT NULL,
        donor_id INT NOT NULL,
        status ENUM('pending', 'accepted', 'rejected', 'completed') DEFAULT 'pending',
        verification_code VARCHAR(10),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (food_id) REFERENCES food_items(id) ON DELETE CASCADE,
        FOREIGN KEY (requester_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (donor_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    // Reports table
    $sql_reports = "CREATE TABLE IF NOT EXISTS reports (
        id INT AUTO_INCREMENT PRIMARY KEY,
        reporter_id INT NOT NULL,
        reported_user_id INT NOT NULL,
        food_id INT,
        request_id INT,
        reason_category VARCHAR(100) NOT NULL,
        reason_details TEXT,
        status ENUM('pending', 'reviewed', 'resolved') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (reported_user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (food_id) REFERENCES food_items(id) ON DELETE SET NULL,
        FOREIGN KEY (request_id) REFERENCES requests(id) ON DELETE SET NULL
    )";
    
    $conn->query($sql_users);
    $conn->query($sql_food);
    $conn->query($sql_requests);
    $conn->query($sql_reports);
}

// Run initialization
initDatabase($conn);
?>

