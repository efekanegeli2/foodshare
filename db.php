<?php
session_start();
$host = 'localhost';
$dbname = 'foodshare_db'; // phpMyAdmin'deki isimle aynı olmalı
$username = 'root';
$password = ''; 

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) { die("Bağlantı Hatası: " . $conn->connect_error); }
$conn->set_charset("utf8mb4");

function escape($string) { global $conn; return $conn->real_escape_string(trim($string)); }
function isLoggedIn() { return isset($_SESSION['user_id']); }
function isAdmin() { return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin'; }

// Upload klasörü yoksa oluştur (Garanti olsun)
if (!file_exists('uploads')) { mkdir('uploads', 0777, true); }
?>