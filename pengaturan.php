<?php
// Ambil status dan pesan dari URL. Ini penting agar notifikasi bisa ditampilkan.
$status = $_GET['status'] ?? '';
$message = $_GET['message'] ?? ''; // Ambil pesan dari URL juga

include_once __DIR__ . '/config.php';
if (!isset($conn) && isset($koneksi)) {
    $conn = $koneksi;
}

if (!function_exists('get_setting')) {
    function get_setting($conn, $name, $default = '') {
        // Pastikan koneksi database tersedia sebelum melakukan query
        if (!$conn) {
            return $default;
        }
        $name = mysqli_real_escape_string($conn, $name);
        $q = mysqli_query($conn, "SELECT setting_value FROM app_settings WHERE setting_name = '$name'");
        if ($q && mysqli_num_rows($q) > 0) {
            return mysqli_fetch_assoc($q)['setting_value'];
        }
        return $default;
    }
}

// Ambil semua pengaturan yang ada saat ini dari database
$settings = [];
if (isset($conn)) {
    $q_settings = mysqli_query($conn, "SELECT setting_name, setting_value FROM app_settings");
    if ($q_settings) {
        while ($row = mysqli_fetch_assoc($q_settings)) {
            $settings[$row['setting_name']] = $row['setting_value'];
        }
    }
}

// Inisialisasi variabel dengan nilai dari database atau nilai default
$company_name = $settings['company_name'] ?? 'METISYS INDONESIA';
$company_address = $settings['company_address'] ?? 'Jl. Contoh No. 123, Bekasi, Jawa Barat 17111';
$company_phone = $settings['company_phone'] ?? '(021) 8888-9999';
$company_email = $settings['company_email'] ?? 'info@metisys.co.id';
$company_logo = $settings['company_logo'] ?? 'metisys_logo.png'; // Default logo jika belum ada di DB
$lecturer_name = $settings['lecturer_name'] ?? 'Elkin Rilvani, S.Kom, M.Kom';
$lecturer_title = $settings['lecturer_title'] ?? 'Dosen Pengampu';
$lecturer_signature = $settings['lecturer_signature'] ?? ''; // Path tanda tangan digital


// --- PROSES SIMPAN PENGATURAN ---
if (isset($_POST['save_settings'])) {
    $status_redirect = 'error'; // Default ke error
    $message_redirect = 'Terjadi kesalahan saat menyimpan pengaturan.';

    // Pastikan koneksi database tersedia
    if (!$conn) {
        $message_redirect = 'Koneksi database tidak tersedia.';
        echo "<script>window.location.href='dashboard.php?section=pengaturan&status=$status_redirect&message=" . urlencode($message_redirect) . "';</script>";
        exit();
    }

    // Ambil data dari form dan sanitasi
    $new_company_name = mysqli_real_escape_string($conn, $_POST['company_name']);
    $new_company_address = mysqli_real_escape_string($conn, $_POST['company_address']);
    $new_company_phone = mysqli_real_escape_string($conn, $_POST['company_phone']);
    $new_company_email = mysqli_real_escape_string($conn, $_POST['company_email']);
    $new_lecturer_name = mysqli_real_escape_string($conn, $_POST['lecturer_name']);
    $new_lecturer_title = mysqli_real_escape_string($conn, $_POST['lecturer_title']);

    // Data yang akan diupdate ke tabel app_settings
    $updates = [
        'company_name' => $new_company_name,
        'company_address' => $new_company_address,
        'company_phone' => $new_company_phone,
        'company_email' => $new_company_email,
        'lecturer_name' => $new_lecturer_name,
        'lecturer_title' => $new_lecturer_title
    ];

    $all_success = true; // Flag untuk melacak apakah semua update berhasil
    $error_messages = [];

    // Lakukan update untuk setiap pengaturan teks
    foreach ($updates as $name => $value) {
        $q_update = mysqli_query($conn, "INSERT INTO app_settings (setting_name, setting_value) VALUES ('$name', '$value') ON DUPLICATE KEY UPDATE setting_value = '$value'");
        if (!$q_update) {
            $all_success = false;
            $error_messages[] = "Gagal memperbarui '$name': " . mysqli_error($conn);
        }
    }

    // Handle upload logo
    if (isset($_FILES['company_logo_file']) && $_FILES['company_logo_file']['error'] == UPLOAD_ERR_OK) {
        $target_dir = "images/"; // Folder tempat menyimpan logo (pastikan folder ini ada dan writable)
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $file_extension = pathinfo($_FILES['company_logo_file']['name'], PATHINFO_EXTENSION);
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (!in_array(strtolower($file_extension), $allowed_extensions)) {
            $all_success = false;
            $error_messages[] = "Ekstensi file tidak diizinkan. Hanya JPG, JPEG, PNG, GIF, WEBP.";
        } else {
            $new_logo_filename = "metisys_logo." . strtolower($file_extension);
            $target_file_path = $target_dir . $new_logo_filename;

            $check = getimagesize($_FILES['company_logo_file']['tmp_name']);
            if($check !== false) {
                $old_logo_name = get_setting($conn, 'company_logo');
                if (!empty($old_logo_name) && $old_logo_name != $new_logo_filename && file_exists($target_dir . $old_logo_name)) {
                    unlink($target_dir . $old_logo_name);
                }

                if (move_uploaded_file($_FILES['company_logo_file']['tmp_name'], $target_file_path)) {
                    $q_update_logo = mysqli_query($conn, "INSERT INTO app_settings (setting_name, setting_value) VALUES ('company_logo', '$new_logo_filename') ON DUPLICATE KEY UPDATE setting_value = '$new_logo_filename'");
                    if (!$q_update_logo) {
                        $all_success = false;
                        $error_messages[] = "Gagal update nama logo di database: " . mysqli_error($conn);
                    }
                } else {
                    $all_success = false;
                    $error_messages[] = "Gagal mengupload file logo ke server.";
                }
            } else {
                $all_success = false;
                $error_messages[] = "File yang diupload bukan gambar yang valid.";
            }
        }
    } elseif (isset($_FILES['company_logo_file']) && $_FILES['company_logo_file']['error'] != UPLOAD_ERR_NO_FILE) {
        $all_success = false;
        $error_messages[] = "Terjadi kesalahan upload file: Kode error " . $_FILES['company_logo_file']['error'];
    }

    // Handle digital signature upload
    if (isset($_POST['signature_data']) && !empty($_POST['signature_data'])) {
        $signature_data_raw = $_POST['signature_data'];
        // Hapus "data:image/png;base64," dari string
        $signature_data_base64 = str_replace('data:image/png;base64,', '', $signature_data_raw);
        $signature_image = base64_decode($signature_data_base64);

        $signature_dir = "uploads/signatures/"; // Folder untuk tanda tangan
        if (!is_dir($signature_dir)) {
            mkdir($signature_dir, 0777, true);
        }
        $signature_filename = "lecturer_signature.png"; // Nama file tetap
        $signature_filepath = $signature_dir . $signature_filename;

        if (file_put_contents($signature_filepath, $signature_image)) {
            $q_update_signature = mysqli_query($conn, "INSERT INTO app_settings (setting_name, setting_value) VALUES ('lecturer_signature', '$signature_filename') ON DUPLICATE KEY UPDATE setting_value = '$signature_filename'");
            if (!$q_update_signature) {
                $all_success = false;
                $error_messages[] = "Gagal update path tanda tangan di database: " . mysqli_error($conn);
            }
        } else {
            $all_success = false;
            $error_messages[] = "Gagal menyimpan file tanda tangan ke server.";
        }
    }


    if ($all_success) {
        // Menggunakan 'success' untuk status updated, persis seperti di data_latih.php
        $status_redirect = 'success';
        $message_redirect = 'Pengaturan berhasil diperbarui!';
    } else {
        $status_redirect = 'error';
        if (!empty($error_messages)) {
            $message_redirect = "Gagal menyimpan pengaturan: " . implode(" | ", $error_messages);
        } else {
            $message_redirect = 'Terjadi kesalahan tidak terduga saat menyimpan pengaturan.';
        }
    }
    echo "<script>window.location.href='dashboard.php?section=pengaturan&status=$status_redirect&message=" . urlencode($message_redirect) . "';</script>";
    exit();
}

// Ambil pengaturan terbaru setelah update (jika ada) untuk memastikan tampilan form up-to-date
$settings = [];
if (isset($conn)) {
    $q_settings = mysqli_query($conn, "SELECT setting_name, setting_value FROM app_settings");
    if ($q_settings) {
        while ($row = mysqli_fetch_assoc($q_settings)) {
            $settings[$row['setting_name']] = $row['setting_value'];
        }
    }
}
$company_name = $settings['company_name'] ?? 'METISYS INDONESIA';
$company_address = $settings['company_address'] ?? 'Jl. Contoh No. 123, Bekasi, Jawa Barat 17111';
$company_phone = $settings['company_phone'] ?? '(021) 8888-9999';
$company_email = $settings['company_email'] ?? 'info@metisys.co.id';
$company_logo = $settings['company_logo'] ?? 'metisys_logo.png';
$lecturer_name = $settings['lecturer_name'] ?? 'Elkin Rilvani, S.Kom, M.Kom';
$lecturer_title = $settings['lecturer_title'] ?? 'Dosen Pengampu';
$lecturer_signature = $settings['lecturer_signature'] ?? '';

?>

<!-- CSS khusus untuk pengaturan.php -->
<style>
    /* Variabel CSS (pastikan ini konsisten dengan style.css utama Anda) */
    :root {
        --primary-blue: #1a73e8;
        --dark-blue: #0d47a1;
        --white: #ffffff;
        --text-color: #333;
        --border-color: #e0e0e0;
        --shadow-light: rgba(0, 0, 0, 0.08);
        --shadow-medium: rgba(0, 0, 0, 0.15);
        --error-red: #e74a3b;
        --success-green: #1cc88a; /* Ini adalah warna default, tapi notifikasi akan menimpa */
    }

    .settings-container {
        width: 100%;
        box-sizing: border-box;
        padding: 5px;
        color: var(--text-color);
    }

    /* Notifikasi - PERSIS SEPERTI data_latih.php (Gambar 2) */
    .alert-notif {
        padding: 10px 15px; /* Sesuai gambar kedua */
        border-radius: 4px; /* Sesuai gambar kedua */
        margin-bottom: 20px;
        font-weight: normal; /* Diubah dari bold ke normal */
        transition: all 0.3s ease-in-out;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1); /* Bayangan lebih halus, sesuai gambar kedua */
        opacity: 1; /* Default opacity */
        display: flex;
        align-items: center;
        justify-content: space-between;
        border: 1px solid transparent; /* PENTING: border transparan di default alert-notif */
    }
    .alert-notif.alert-success {
        background: #d4edda; /* Warna hijau sangat terang, sesuai gambar kedua */
        color: #155724; /* Warna teks hijau gelap, sesuai gambar kedua */
        border-color: #c3e6cb; /* Warna border hijau muda, sesuai gambar kedua */
    }
    .alert-notif.alert-error { /* Untuk pesan error, disesuaikan agar konsisten */
        background: #f8d7da; /* Warna merah sangat terang */
        color: #721c24; /* Warna teks merah gelap */
        border-color: #f5c6cb; /* Warna border merah muda */
    }

    /* Close Button for Notification - PERSIS SEPERTI data_latih.php (Gambar 2) */
    .alert-notif span { /* Menggunakan span untuk tombol close */
        font-size: 1.5em;
        line-height: 1;
        cursor: pointer;
        padding: 0 5px;
        margin-left: 10px;
        /* Warna X akan mengikuti warna teks parent .alert-notif secara otomatis */
    }
    .alert-notif span:hover {
        opacity: 0.7; /* Sedikit transparan saat hover */
    }


    /* Card untuk Form */
    .card-modern {
        background: var(--white);
        padding: 25px;
        border-radius: 12px;
        border: 1px solid var(--border-color);
        margin-bottom: 25px;
        width: 100%;
        box-sizing: border-box;
        box-shadow: 0 4px 15px var(--shadow-light);
    }

    /* Form Layout */
    .settings-form {
        display: flex;
        flex-wrap: wrap;
        gap: 20px; /* Jarak antar kolom dan baris */
        width: 100%;
    }

    .form-group-settings {
        flex: 1 1 45%; /* Dua kolom per baris, dengan sedikit fleksibilitas */
        min-width: 300px; /* Lebar minimum agar tidak terlalu sempit */
    }

    .form-label {
        display: block;
        font-weight: bold;
        margin-bottom: 8px;
        color: #555;
    }

    .form-input-field {
        width: 100%;
        padding: 12px;
        border-radius: 8px;
        border: 1px solid #ccc;
        box-sizing: border-box;
        font-size: 1em;
        transition: all 0.3s ease;
    }

    .form-input-field:focus {
        border-color: var(--primary-blue);
        box-shadow: 0 0 0 3px rgba(26, 115, 232, 0.2);
        outline: none;
    }

    /* --- Tombol Aksi Utama (Simpan) --- */
    .btn-primary-action {
        background: linear-gradient(45deg, var(--primary-blue), var(--dark-blue));
        color: var(--white) !important;
        padding: 12px 25px;
        border-radius: 8px;
        border: none;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s ease;
        font-size: 1em;
        white-space: nowrap;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
        position: relative;
        overflow: hidden;
    }

    .btn-primary-action::before {
        content: "";
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.2);
        transform: skewX(-30deg);
        transition: all 0.5s ease;
    }

    .btn-primary-action:hover::before {
        left: 100%;
    }

    .btn-primary-action:hover {
        transform: translateY(-2px) scale(1.01);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
    }

    .logo-preview, .signature-preview {
        margin-top: 10px;
        max-width: 150px; /* Ukuran preview */
        height: auto;
        border: 1px solid #ddd;
        padding: 5px;
        background-color: #f9f9f9;
        border-radius: 5px;
    }

    /* Styling Canvas Tanda Tangan */
    #signatureCanvas {
        border: 1px solid #ccc;
        border-radius: 8px;
        background-color: #fcfcfc;
        cursor: crosshair;
        touch-action: none; /* Mencegah scrolling pada perangkat sentuh */
    }
    .signature-buttons {
        margin-top: 10px;
        display: flex;
        gap: 10px;
    }
    .signature-buttons .btn {
        padding: 8px 15px;
        border-radius: 5px;
        border: none;
        cursor: pointer;
        font-size: 0.9em;
    }
    .signature-buttons .btn-clear {
        background-color: #f44336;
        color: white;
    }
    .signature-buttons .btn-clear:hover {
        background-color: #d32f2f;
    }
    .signature-buttons .btn-save-sig {
        background-color: var(--primary-blue);
        color: white;
    }
    .signature-buttons .btn-save-sig:hover {
        background-color: var(--dark-blue);
    }

    /* Responsive Adjustments */
    @media (max-width: 768px) {
        .settings-form {
            flex-direction: column; /* Kolom menjadi satu di bawah di layar kecil */
            align-items: stretch;
        }
        .form-group-settings {
            flex: none; /* Matikan flex untuk kolom di mobile */
            width: 100%;
            min-width: unset;
        }
    }
</style>

<div class="settings-container">
    <!-- NOTIFIKASI - PERSIS SEPERTI data_latih.php (Gambar 2) -->
    <?php if ($status == 'success'): ?>
        <div class="alert-notif alert-success">
            <?= htmlspecialchars($message) ?>
            <span onclick="this.parentElement.style.display='none'" style="cursor:pointer">&times;</span>
        </div>
    <?php elseif ($status == 'error'): ?>
        <div class="alert-notif alert-error">
            <?= htmlspecialchars($message) ?>
            <span onclick="this.parentElement.style.display='none'" style="cursor:pointer">&times;</span>
        </div>
    <?php endif; ?>
    <!-- AKHIR NOTIFIKASI -->

    <div class="card-modern">
        <h4 class="mb-4">Pengaturan Umum Aplikasi</h4>
        <!-- Form harus memiliki enctype="multipart/form-data" untuk upload file -->
        <form action="" method="POST" enctype="multipart/form-data" class="settings-form" id="settingsForm">
            <!-- Bagian Informasi Perusahaan -->
            <div class="form-group-settings">
                <label for="company_name" class="form-label">Nama Perusahaan</label>
                <input type="text" name="company_name" id="company_name" class="form-input-field" value="<?= htmlspecialchars($company_name) ?>" required>
            </div>
            <div class="form-group-settings">
                <label for="company_address" class="form-label">Alamat Perusahaan</label>
                <input type="text" name="company_address" id="company_address" class="form-input-field" value="<?= htmlspecialchars($company_address) ?>" required>
            </div>
            <div class="form-group-settings">
                <label for="company_phone" class="form-label">Telepon Perusahaan</label>
                <input type="text" name="company_phone" id="company_phone" class="form-input-field" value="<?= htmlspecialchars($company_phone) ?>">
            </div>
            <div class="form-group-settings">
                <label for="company_email" class="form-label">Email Perusahaan</label>
                <input type="email" name="company_email" id="company_email" class="form-input-field" value="<?= htmlspecialchars($company_email) ?>">
            </div>

            <!-- Bagian Upload Logo -->
            <div class="form-group-settings">
                <label for="company_logo_file" class="form-label">Logo Perusahaan (PNG, JPG, GIF, WEBP)</label>
                <input type="file" name="company_logo_file" id="company_logo_file" class="form-input-field">
                <?php
                // Tampilkan preview logo jika ada dan file-nya ditemukan
                $logo_path = 'images/' . $company_logo;
                if (!empty($company_logo) && file_exists($logo_path)):
                    // Tambahkan timestamp sebagai cache-buster untuk logo
                    $cache_buster_logo = time();
                ?>
                    <p class="mt-2">Logo saat ini:</p>
                    <img src="<?= htmlspecialchars($logo_path) ?>?t=<?= $cache_buster_logo ?>" alt="Logo Perusahaan" class="logo-preview">
                <?php else: ?>
                    <p class="mt-2 text-muted">Belum ada logo terupload atau file tidak ditemukan.</p>
                <?php endif; ?>
            </div>

            <!-- Bagian Pengaturan Tanda Tangan Laporan -->
            <div class="form-group-settings">
                <label for="lecturer_name" class="form-label">Nama Penanggung Jawab (Laporan)</label>
                <input type="text" name="lecturer_name" id="lecturer_name" class="form-input-field" value="<?= htmlspecialchars($lecturer_name) ?>" required>
            </div>
            <div class="form-group-settings">
                <label for="lecturer_title" class="form-label">Jabatan Penanggung Jawab (Laporan)</label>
                <input type="text" name="lecturer_title" id="lecturer_title" class="form-input-field" value="<?= htmlspecialchars($lecturer_title) ?>" required>
            </div>

            <!-- Bagian Tanda Tangan Digital -->
            <div class="form-group-settings" style="flex: 1 1 100%;"> <!-- Ambil lebar penuh untuk tanda tangan -->
                <label class="form-label">Tanda Tangan Digital Penanggung Jawab</label>
                <canvas id="signatureCanvas" width="400" height="150"></canvas>
                <div class="signature-buttons">
                    <button type="button" id="clearSignature" class="btn btn-clear"><i class="fas fa-eraser"></i> Hapus Tanda Tangan</button>
                    <button type="button" id="saveSignature" class="btn btn-save-sig"><i class="fas fa-save"></i> Gunakan Tanda Tangan Ini</button>
                </div>
                <input type="hidden" name="signature_data" id="signatureData">
                <?php
                $signature_path = 'uploads/signatures/' . $lecturer_signature;
                if (!empty($lecturer_signature) && file_exists($signature_path)):
                    // Tambahkan timestamp sebagai cache-buster
                    $cache_buster = time();
                ?>
                    <p class="mt-2">Tanda tangan saat ini:</p>
                    <img src="<?= htmlspecialchars($signature_path) ?>?t=<?= $cache_buster ?>" alt="Tanda Tangan Digital" class="signature-preview">
                <?php else: ?>
                    <p class="mt-2 text-muted">Belum ada tanda tangan digital tersimpan.</p>
                <?php endif; ?>
            </div>


            <!-- Tombol Simpan -->
            <div class="form-group-settings" style="flex: 1 1 100%;"> <!-- Ambil lebar penuh untuk tombol -->
                <button type="submit" name="save_settings" class="btn-primary-action">
                    <i class="fas fa-save"></i> Simpan Pengaturan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // --- SCRIPT PENTING: PENGHAPUS PARAMETER URL (TETAP DIJAGA) ---
    // Ini harus dijalankan SEGERA untuk mencegah notifikasi muncul lagi saat navigasi.
    (function() {
        const url = new URL(window.location.href);
        const statusParam = url.searchParams.get('status');
        const messageParam = url.searchParams.get('message');

        if (statusParam || messageParam) {
            url.searchParams.delete('status');
            url.searchParams.delete('message');
            const newUrl = url.toString();

            if (history.replaceState) {
                history.replaceState(null, '', newUrl);
            } else {
                // Fallback for older browsers (will cause a full page reload)
                window.location.replace(newUrl);
            }
        }
    })();

    // --- SCRIPT PENTING: PENYEMBUNYI NOTIFIKASI OTOMATIS (PERSIS SEPERTI data_latih.php) ---
    document.addEventListener('DOMContentLoaded', function() {
        const notifications = document.querySelectorAll('.alert-notif');
        notifications.forEach(function(notif) {
            setTimeout(function() {
                notif.style.opacity = '0'; // Mulai transisi fade out
                setTimeout(function() {
                    notif.style.display = 'none'; // Sembunyikan elemen setelah transisi selesai
                }, 500); // Waktu transisi menghilang (PERSIS SEPERTI data_latih.php)
            }, 3000); // Notifikasi akan muncul selama 3 detik sebelum mulai fade out
        });
    });

    // --- Script untuk Tanda Tangan Digital (tidak berubah, karena tidak terkait notifikasi) ---
    document.addEventListener('DOMContentLoaded', function() {
        const canvas = document.getElementById('signatureCanvas');
        const ctx = canvas.getContext('2d');
        const clearButton = document.getElementById('clearSignature');
        const saveButton = document.getElementById('saveSignature');
        const signatureDataInput = document.getElementById('signatureData');
        const form = document.getElementById('settingsForm');

        if (!canvas) {
            console.error("Canvas element with ID 'signatureCanvas' not found.");
            return;
        }

        let drawing = false;
        let lastX = 0;
        let lastY = 0;

        // Atur gaya default untuk menggambar
        ctx.lineWidth = 2;
        ctx.lineCap = 'round';
        ctx.strokeStyle = '#000'; // Warna tinta hitam

        // Fungsi untuk memulai menggambar
        function startDrawing(e) {
            drawing = true;
            [lastX, lastY] = getMousePos(e);
            e.preventDefault(); // Mencegah scrolling pada perangkat sentuh
        }

        // Fungsi untuk menggambar
        function draw(e) {
            if (!drawing) return;
            e.preventDefault(); // Mencegah scrolling pada perangkat sentuh

            let [currentX, currentY] = getMousePos(e);

            ctx.beginPath();
            ctx.moveTo(lastX, lastY);
            ctx.lineTo(currentX, currentY);
            ctx.stroke();

            [lastX, lastY] = [currentX, currentY];
        }

        // Fungsi untuk menghentikan menggambar
        function stopDrawing() {
            drawing = false;
        }

        // Fungsi untuk mendapatkan posisi mouse/sentuh relatif terhadap canvas
        function getMousePos(e) {
            const rect = canvas.getBoundingClientRect();
            let clientX, clientY;

            if (e.touches) { // Touch event
                clientX = e.touches[0].clientX;
                clientY = e.touches[0].clientY;
            } else { // Mouse event
                clientX = e.clientX;
                clientY = e.clientY;
            }

            return [clientX - rect.left, clientY - rect.top];
        }

        // Event Listeners untuk mouse
        canvas.addEventListener('mousedown', startDrawing);
        canvas.addEventListener('mousemove', draw);
        canvas.addEventListener('mouseup', stopDrawing);
        canvas.addEventListener('mouseout', stopDrawing); // Berhenti menggambar jika kursor keluar canvas

        // Event Listeners untuk touch (perangkat sentuh)
        canvas.addEventListener('touchstart', startDrawing);
        canvas.addEventListener('touchmove', draw);
        canvas.addEventListener('touchend', stopDrawing);
        canvas.addEventListener('touchcancel', stopDrawing);

        // Tombol Hapus Tanda Tangan
        if (clearButton) {
            clearButton.addEventListener('click', function() {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                if (signatureDataInput) signatureDataInput.value = ''; // Hapus juga data dari hidden input
            });
        } else {
            console.warn("Clear signature button with ID 'clearSignature' not found.");
        }


        // Tombol Gunakan Tanda Tangan Ini (menyimpan data ke hidden input)
        if (saveButton) {
            saveButton.addEventListener('click', function() {
                if (isCanvasBlank(canvas)) {
                    alert('Silakan gambar tanda tangan terlebih dahulu.');
                    return;
                }
                // Konversi canvas ke data URL (Base64 PNG)
                const signatureDataURL = canvas.toDataURL('image/png');
                if (signatureDataInput) signatureDataInput.value = signatureDataURL;
                alert('Tanda tangan berhasil diambil. Jangan lupa klik "Simpan Pengaturan" untuk menyimpannya ke database.');
            });
        } else {
            console.warn("Save signature button with ID 'saveSignature' not found.");
        }


        // Fungsi untuk mengecek apakah canvas kosong
        function isCanvasBlank(canvas) {
            const blank = document.createElement('canvas');
            blank.width = canvas.width;
            blank.height = canvas.height;
            // Menggambar satu piksel transparan ke blank canvas untuk memastikan toDataURL() tidak mengembalikan string kosong
            // Ini adalah workaround untuk beberapa browser yang mungkin mengembalikan string kosong untuk canvas kosong
            blank.getContext('2d').clearRect(0, 0, blank.width, blank.height);
            return canvas.toDataURL() === blank.toDataURL();
        }
    });
</script>
