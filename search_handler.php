<?php
// search_handler.php
header('Content-Type: application/json'); // Memberitahu browser bahwa responsnya adalah JSON

// Sertakan file konfigurasi database Anda
// Pastikan path ini benar relatif terhadap lokasi search_handler.php
require_once 'config.php';

$response = ['success' => false, 'message' => ''];

if (isset($_GET['q']) && !empty($_GET['q'])) {
    $search_query = $_GET['q'];

    // Gunakan prepared statement untuk mencegah SQL injection
    // Mencari nama menu yang mengandung kata kunci pencarian (case-insensitive)
    // Mengecualikan 'login' dan 'logout' dari hasil pencarian
    $stmt = $conn->prepare("SELECT url FROM menu_items WHERE LOWER(name) LIKE LOWER(?) AND LOWER(name) NOT IN ('login', 'logout') LIMIT 1");

    if ($stmt) {
        $param_search = '%' . $search_query . '%';
        $stmt->bind_param("s", $param_search);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $response['success'] = true;
            $response['url'] = $row['url'];
        } else {
            // Pesan disesuaikan untuk notifikasi kustom
            $response['message'] = 'Maaf, halaman atau fitur "' . htmlspecialchars($search_query) . '" tidak ditemukan.';
        }
        $stmt->close();
    } else {
        $response['message'] = 'Gagal menyiapkan statement: ' . $conn->error;
    }
} else {
    $response['message'] = 'Silakan masukkan kata kunci pencarian.'; // Pesan disesuaikan
}

$conn->close();
echo json_encode($response);
?>
