<?php require_once 'db.php';
if (!isLoggedIn() || $_SESSION['user_type'] != 'donor') { header("Location: dashboard.php"); exit(); }

$min_date = date('Y-m-d', strtotime('+2 days'));

// Klasör Kontrolü (Otomatik Oluşturma)
if (!is_dir('uploads')) {
    mkdir('uploads', 0777, true);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = escape($_POST['title']);
    $desc = escape($_POST['description']);
    $expiry = escape($_POST['expiry_date']);
    $donor_id = $_SESSION['user_id'];
    
    $u = $conn->query("SELECT province, district FROM users WHERE id=$donor_id")->fetch_assoc();
    $prov = $u['province'];
    $dist = $u['district'];

    // RESİM İŞLEMLERİ
    $image_path = "";
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $filename = uniqid("food_", true) . "." . $ext;
            $destination = "uploads/" . $filename;
            
            // Yüklemeyi Dene
            if (move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
                $image_path = $filename;
            } else {
                // HATA VARSA DUR VE GÖSTER
                die("<h1 style='color:red; text-align:center; margin-top:50px;'>HATA: Resim 'uploads' klasörüne taşınamadı!<br>Lütfen klasör izinlerini kontrol edin.</h1>");
            }
        } else {
             die("<h1 style='color:red;'>HATA: Geçersiz dosya türü. Sadece JPG, PNG, GIF yükleyiniz.</h1>");
        }
    } else {
        // Resim seçilmediyse veya yükleme hatası varsa
        if(isset($_FILES['image']['error']) && $_FILES['image']['error'] != 4) {
             die("HATA KODU: " . $_FILES['image']['error']);
        }
    }

    $sql = "INSERT INTO food_items (donor_id, title, description, image_path, province, district, expiry_date) 
            VALUES ($donor_id, '$title', '$desc', '$image_path', '$prov', '$dist', '$expiry')";
    
    if ($conn->query($sql)) { header("Location: dashboard.php"); }
    else { echo "Database Error: " . $conn->error; }
}
?>
<!DOCTYPE html>
<html lang="en">
<head><title>Add Food</title><script src="https://cdn.tailwindcss.com"></script></head>
<body class="bg-gray-50 p-8">
    <div class="max-w-lg mx-auto bg-white p-6 rounded shadow">
        <h2 class="text-xl font-bold mb-4">Donate Food</h2>
        
        <form method="POST" enctype="multipart/form-data" class="space-y-4">
            <div>
                <label class="block text-sm text-gray-600">Title</label>
                <input type="text" name="title" required class="w-full border p-2 rounded">
            </div>
            <div>
                <label class="block text-sm text-gray-600">Description</label>
                <textarea name="description" required class="w-full border p-2 rounded"></textarea>
            </div>
            <div>
                <label class="block text-sm text-gray-600">Expiry Date</label>
                <input type="date" name="expiry_date" min="<?php echo $min_date; ?>" required class="w-full border p-2 rounded">
            </div>
            <div class="bg-yellow-50 p-3 rounded border border-yellow-200">
                <label class="block text-sm text-gray-600 font-bold mb-1">Food Image</label>
                <input type="file" name="image" required accept="image/*" class="w-full border p-2 rounded bg-white">
                <p class="text-xs text-gray-500 mt-1">* Required (JPG, PNG)</p>
            </div>
            <button type="submit" class="w-full bg-green-600 text-white py-2 rounded font-bold hover:bg-green-700">Post Donation</button>
        </form>
        <a href="dashboard.php" class="block text-center mt-4 text-gray-500">Cancel</a>
    </div>
</body>
</html>