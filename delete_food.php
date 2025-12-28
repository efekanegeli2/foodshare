<?php require_once 'db.php';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isLoggedIn()) {
    $fid = (int)$_POST['food_id'];
    $uid = $_SESSION['user_id'];
    // Sadece admin veya ilanı açan silebilir
    $sql = "DELETE FROM food_items WHERE id=$fid AND (donor_id=$uid OR " . (isAdmin() ? "1=1" : "1=0") . ")";
    $conn->query($sql);
    header("Location: dashboard.php");
}
?>