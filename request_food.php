<?php
require_once 'db.php';
if (!isLoggedIn() || $_SESSION['user_type'] != 'receiver') die("Yetkisiz erişim.");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $food_id = (int)$_POST['food_id'];
    $receiver_id = $_SESSION['user_id'];
    
    // Yemek bilgilerini al
    $food = $conn->query("SELECT * FROM food_items WHERE id=$food_id AND status='available'")->fetch_assoc();
    if (!$food) die("Bu yemek artık uygun değil.");

    // 6 Haneli Kod Üret
    $code = rand(100000, 999999);
    $donor_id = $food['donor_id'];

    // Transaction oluştur
    $sql = "INSERT INTO transactions (food_id, donor_id, receiver_id, verification_code, status) 
            VALUES ($food_id, $donor_id, $receiver_id, '$code', 'pending')";
    
    if ($conn->query($sql)) {
        // Yemeğin durumunu 'reserved' yap
        $conn->query("UPDATE food_items SET status='reserved' WHERE id=$food_id");
        header("Location: profile.php"); // Profile yönlendir, kodu orada görecek
    } else {
        echo "Hata: " . $conn->error;
    }
}
?>