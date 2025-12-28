<?php
require_once 'db.php';
if (!isLoggedIn()) die("Giriş yapmalısınız.");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tid = (int)$_POST['transaction_id'];
    $code = escape($_POST['code']);
    $donor_id = $_SESSION['user_id'];

    // Kodu ve ID'yi kontrol et
    $check = $conn->query("SELECT * FROM transactions WHERE id=$tid AND donor_id=$donor_id AND verification_code='$code' AND status='pending'");
    
    if ($check->num_rows > 0) {
        $trans = $check->fetch_assoc();
        $food_id = $trans['food_id'];
        
        // 1. Transaction tamamlandı yap
        $conn->query("UPDATE transactions SET status='completed', completed_at=NOW() WHERE id=$tid");
        
        // 2. Yemeği tamamlandı (arşiv) yap
        $conn->query("UPDATE food_items SET status='completed' WHERE id=$food_id");
        
        // Başarılı, geri dön
        header("Location: profile.php");
    } else {
        echo "<script>alert('Hatalı kod!'); window.location='profile.php';</script>";
    }
}
?>