<?php
// Tampilkan semua error PHP untuk debugging. Hapus ini di lingkungan produksi.
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

// Sertakan file konfigurasi untuk koneksi database
require_once 'config.php';

// Cek apakah user sudah login
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: login.php");
    exit();
}

// Ambil user_id, username, dan email dari session
$user_id = $_SESSION["id"];
$username = htmlspecialchars($_SESSION["username"]); // Untuk tampilan di navbar/sidebar
$user_email_from_session = $_SESSION["email"];
$avatar_url_from_session = $_SESSION["avatar_url"] ?? 'uploads/avatars/default_avatar.png'; // Ambil avatar dari sesi, default jika tidak ada

// --- Bagian Penanganan Permintaan AJAX KHUSUS PROFIL ---
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    header('Content-Type: application/json');

    $action = $_POST['action'] ?? null;
    if (!$action) {
        $input = file_get_contents('php://input');
        $data_json = json_decode($input, true);
        $action = $data_json['action'] ?? null;
    }

    $response = ["success" => false, "message" => "Aksi tidak dikenal."];

    switch ($action) {
        case 'get_profile':
            $stmt = $conn->prepare("SELECT u.email, p.* FROM users u LEFT JOIN profiles p ON u.id = p.user_id WHERE u.id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $profile_data = $result->fetch_assoc();
                // Jika tidak ada entri profil untuk pengguna, buat yang default
                if (is_null($profile_data['user_id'])) {
                    $insert_stmt = $conn->prepare("INSERT INTO profiles (user_id, avatar_url) VALUES (?, ?)");
                    $default_avatar = 'uploads/avatars/default_avatar.png';
                    $insert_stmt->bind_param("is", $user_id, $default_avatar);
                    $insert_stmt->execute();
                    $insert_stmt->close();
                    // Ambil kembali data setelah penyisipan
                    $stmt->execute();
                    $profile_data = $stmt->get_result()->fetch_assoc();
                }
                $profile_data['email'] = $user_email_from_session;
                // Pastikan avatar_url tidak pernah kosong, gunakan default jika kosong
                if (empty($profile_data['avatar_url'])) {
                    $profile_data['avatar_url'] = 'uploads/avatars/default_avatar.png';
                }

                $response = ["success" => true, "data" => $profile_data];
            } else {
                // Kasus ini seharusnya tidak terjadi jika pengguna sudah login, tetapi sebagai cadangan
                $response = ["success" => false, "message" => "Data profil tidak ditemukan untuk user ID ini."];
            }
            $stmt->close();
            break;

        case 'update_profile':
            $full_name = $_POST['full_name'] ?? null;
            $title = $_POST['title'] ?? null;
            $bio = $_POST['bio'] ?? null;
            $phone = $_POST['phone'] ?? null;
            $address = $_POST['address'] ?? null;
            $date_of_birth = $_POST['date_of_birth'] ?? null;
            $gender = $_POST['gender'] ?? null;
            $occupation = $_POST['occupation'] ?? null;
            $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
            $two_factor_auth = isset($_POST['two_factor_auth']) ? 1 : 0;
            $timezone = $_POST['timezone'] ?? null;

            $stmt = $conn->prepare("UPDATE profiles SET full_name=?, title=?, bio=?, phone=?, address=?, date_of_birth=?, gender=?, occupation=?, email_notifications=?, two_factor_auth=?, timezone=? WHERE user_id=?");
            $stmt->bind_param("sssssssiisis",
                $full_name, $title, $bio, $phone, $address, $date_of_birth,
                $gender, $occupation, $email_notifications, $two_factor_auth, $timezone, $user_id
            );

            if ($stmt->execute()) {
                $response = ["success" => true, "message" => "Profil berhasil diperbarui."];
            } else {
                $response = ["success" => false, "message" => "Gagal memperbarui profil: " . $stmt->error];
            }
            $stmt->close();
            break;

        case 'change_password':
            $input = file_get_contents('php://input');
            $data_json = json_decode($input, true);

            $current_password = $data_json['current_password'] ?? '';
            $new_password = $data_json['new_password'] ?? '';

            if (empty($current_password) || empty($new_password)) {
                $response = ["success" => false, "message" => "Kata sandi saat ini dan kata sandi baru tidak boleh kosong."];
            } elseif (strlen($new_password) < 6) {
                $response = ["success" => false, "message" => "Kata sandi baru minimal 6 karakter."];
            } else {
                $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $stmt->store_result();

                if ($stmt->num_rows == 1) {
                    $stmt->bind_result($hashed_password);
                    $stmt->fetch();

                    if (password_verify($current_password, $hashed_password)) {
                        $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                        $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                        $update_stmt->bind_param("si", $new_hashed_password, $user_id);

                        if ($update_stmt->execute()) {
                            $response = ["success" => true, "message" => "Kata sandi berhasil diubah."];
                        } else {
                            $response = ["success" => false, "message" => "Terjadi kesalahan saat memperbarui kata sandi: " . $conn->error];
                        }
                        $update_stmt->close();
                    } else {
                        $response = ["success" => false, "message" => "Kata sandi saat ini salah."];
                    }
                } else {
                    $response = ["success" => false, "message" => "Pengguna tidak ditemukan."];
                }
                $stmt->close();
            }
            break;

        case 'upload_avatar':
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == UPLOAD_ERR_OK) {
                $target_dir = "uploads/avatars/";
                if (!is_dir($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }

                $imageFileType = strtolower(pathinfo($_FILES["avatar"]["name"], PATHINFO_EXTENSION));
                $new_file_name = "avatar_" . $user_id . "_" . time() . "." . $imageFileType;
                $target_file = $target_dir . $new_file_name;

                $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
                if (!in_array($imageFileType, $allowed_types)) {
                    $response = ["success" => false, "message" => "Hanya file JPG, JPEG, PNG & GIF yang diizinkan."];
                } else {
                    if (move_uploaded_file($_FILES["avatar"]["tmp_name"], $target_file)) {
                        $avatar_url_db = $target_file;
                        $stmt = $conn->prepare("UPDATE profiles SET avatar_url = ? WHERE user_id = ?");
                        $stmt->bind_param("si", $avatar_url_db, $user_id);
                        if ($stmt->execute()) {
                            // Perbarui sesi avatar agar navbar juga terupdate di halaman lain
                            $_SESSION["avatar_url"] = $avatar_url_db;
                            $response = ["success" => true, "message" => "Avatar berhasil diunggah.", "avatar_url" => $avatar_url_db];
                        } else {
                            $response = ["success" => false, "message" => "Gagal memperbarui URL avatar di database."];
                        }
                        $stmt->close();
                    } else {
                        $response = ["success" => false, "message" => "Gagal mengunggah file."];
                    }
                }
            } else {
                $response = ["success" => false, "message" => "Tidak ada file yang diunggah atau terjadi kesalahan: " . ($_FILES['avatar']['error'] ?? 'Tidak diketahui')];
            }
            break;

        case 'delete_account':
            $confirmation_text = $data_json['confirmation_text'] ?? '';

            if ($confirmation_text !== "HAPUS AKUN SAYA") {
                $response = ["success" => false, "message" => "Teks konfirmasi tidak sesuai."];
                break;
            }

            // Mulai transaksi untuk memastikan kedua penghapusan berhasil atau gagal bersamaan
            $conn->begin_transaction();

            try {
                // 1. Hapus data profil pengguna terlebih dahulu
                // Ini mengasumsikan tabel 'profiles' memiliki kolom 'user_id' yang merupakan foreign key ke 'users.id'
                // Jika ada tabel terkait lainnya, mereka mungkin perlu dihapus terlebih dahulu atau memiliki ON DELETE CASCADE yang diatur.
                $stmt_profile = $conn->prepare("DELETE FROM profiles WHERE user_id = ?");
                $stmt_profile->bind_param("i", $user_id);
                $stmt_profile->execute();
                $stmt_profile->close();

                // 2. Hapus pengguna dari tabel users
                $stmt_user = $conn->prepare("DELETE FROM users WHERE id = ?");
                $stmt_user->bind_param("i", $user_id);
                $stmt_user->execute();
                $stmt_user->close();

                $conn->commit(); // Commit transaksi jika keduanya berhasil

                // Hancurkan sesi setelah penghapusan berhasil
                session_unset();
                session_destroy();

                $response = ["success" => true, "message" => "Akun berhasil dihapus. Anda akan diarahkan ke halaman login."];

            } catch (mysqli_sql_exception $e) {
                $conn->rollback(); // Rollback jika terjadi kesalahan
                $response = ["success" => false, "message" => "Gagal menghapus akun: " . $e->getMessage()];
            }
            break;

        default:
            $response = ["success" => false, "message" => "Aksi tidak dikenal."];
            break;
    }

    echo json_encode($response);
    $conn->close();
    exit();
}

// --- Akhir Bagian Penanganan Permintaan AJAX ---
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Pengguna - Metisys</title>
    <link rel="stylesheet" href="CSS/style.css">
    <!-- Font Awesome untuk ikon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- CSS khusus untuk profil -->
    <link rel="stylesheet" href="CSS/profile-style.css">
</head>
<body>
    <div class="dashboard-wrapper sidebar-collapsed">
        <?php include_once 'sidebar.php'; ?>

        <div class="main-content">
            <?php
            // Pass variabel untuk navbar
            $username = $_SESSION["username"];
            $user_avatar_url = $_SESSION["avatar_url"] ?? 'uploads/avatars/default_avatar.png';
            include_once 'navbar.php';
            ?>

            <!-- Page Content (Profile Specific) -->
            <div class="page-content">
                <div class="profile-container">
                    <div class="profile-header-section">
                        <div class="profile-avatar-wrapper">
                            <img src="uploads/avatars/default_avatar.png" alt="User Avatar" class="profile-avatar" id="profile-display-avatar">
                            <button class="edit-avatar-btn" id="edit-avatar-button"><i class="fas fa-camera"></i></button>
                            <input type="file" id="avatar-upload-input" accept="image/*" style="display: none;">
                        </div>
                        <div class="profile-info-main">
                            <h1 class="profile-name" id="profile-display-name">Loading...</h1>
                            <p class="profile-title" id="profile-display-title">Loading...</i></p>
                            <p class="profile-bio" id="profile-display-bio">Loading...</p>
                            <div class="profile-actions">
                                <button class="btn-primary-glow" id="edit-profile-btn"><i class="fas fa-edit"></i> Edit Profil Lengkap</button>
                                <button class="btn-secondary-outline" id="change-password-btn"><i class="fas fa-key"></i> Ganti Password</button>
                            </div>
                        </div>
                    </div>

                    <div class="profile-grid">
                        <!-- Data Kontak -->
                        <div class="profile-card data-card" data-section="contact">
                            <div class="card-header">
                                <h2><i class="fas fa-address-book"></i> Data Kontak</h2>
                                <button class="edit-section-btn" data-section="contact"><i class="fas fa-pen"></i></button>
                            </div>
                            <div class="card-content view-mode">
                                <p><strong>Email:</strong> <span id="display-email">Loading...</span></p>
                                <p><strong>Telepon:</strong> <span id="display-phone">Loading...</span></p>
                                <p><strong>Alamat:</strong> <span id="display-address">Loading...</span></p>
                            </div>
                            <form class="card-content edit-mode hidden" data-section="contact">
                                <div class="input-group">
                                    <label for="edit-email">Email</label>
                                    <input type="email" id="edit-email" name="email" value="" readonly>
                                </div>
                                <div class="input-group">
                                    <label for="edit-phone">Telepon</label>
                                    <input type="text" id="edit-phone" name="phone" value="">
                                </div>
                                <div class="input-group">
                                    <label for="edit-address">Alamat</label>
                                    <input type="text" id="edit-address" name="address" value="">
                                </div>
                                <div class="form-actions">
                                    <button type="submit" class="btn-save-inline">Simpan</button>
                                    <button type="button" class="btn-cancel-inline">Batal</button>
                                </div>
                            </form>
                        </div>

                        <!-- Detail Pribadi -->
                        <div class="profile-card data-card" data-section="personal">
                            <div class="card-header">
                                <h2><i class="fas fa-user-circle"></i> Detail Pribadi</h2>
                                <button class="edit-section-btn" data-section="personal"><i class="fas fa-pen"></i></button>
                            </div>
                            <div class="card-content view-mode">
                                <p><strong>Tanggal Lahir:</strong> <span id="display-dob">Loading...</span></p>
                                <p><strong>Jenis Kelamin:</strong> <span id="display-gender">Loading...</span></p>
                                <p><strong>Pekerjaan:</strong> <span id="display-occupation">Loading...</span></p>
                            </div>
                            <form class="card-content edit-mode hidden" data-section="personal">
                                <div class="input-group">
                                    <label for="edit-dob">Tanggal Lahir</label>
                                    <div class="date-input-container">
                                        <input type="date" id="edit-dob" name="date_of_birth" value="">
                                        <span class="input-icon" data-target="edit-dob">
                                            <i class="fas fa-calendar-alt"></i>
                                        </span>
                                    </div>
                                </div>
                                <div class="input-group">
                                    <label for="edit-gender">Jenis Kelamin</label>
                                    <select id="edit-gender" name="gender">
                                        <option value="">Pilih</option>
                                        <option value="Pria">Pria</option>
                                        <option value="Wanita">Wanita</option>
                                        <option value="Lainnya">Lainnya</option>
                                    </select>
                                </div>
                                <div class="input-group">
                                    <label for="edit-occupation">Pekerjaan</label>
                                    <input type="text" id="edit-occupation" name="occupation" value="">
                                </div>
                                <div class="form-actions">
                                    <button type="submit" class="btn-save-inline">Simpan</button>
                                    <button type="button" class="btn-cancel-inline">Batal</button>
                                </div>
                            </form>
                        </div>

                        <!-- Preferensi & Keamanan -->
                        <div class="profile-card data-card" data-section="security">
                            <div class="card-header">
                                <h2><i class="fas fa-shield-alt"></i> Preferensi & Keamanan</h2>
                                <button class="edit-section-btn" data-section="security"><i class="fas fa-pen"></i></button>
                            </div>
                            <div class="card-content view-mode">
                                <p><strong>Notifikasi Email:</strong> <span id="display-email-notif">Loading...</span></p>
                                <p><strong>Autentikasi 2FA:</strong> <span id="display-2fa">Loading...</span></p>
                                <p><strong>Zona Waktu:</strong> <span id="display-timezone">Loading...</span></p>
                            </div>
                            <form class="card-content edit-mode hidden" data-section="security">
                                <div class="input-group checkbox-group">
                                    <input type="checkbox" id="edit-email-notif" name="email_notifications">
                                    <label for="edit-email-notif">Notifikasi Email</label>
                                </div>
                                <div class="input-group checkbox-group">
                                    <input type="checkbox" id="edit-2fa" name="two_factor_auth">
                                    <label for="edit-2fa">Autentikasi Dua Faktor (2FA)</label>
                                </div>
                                <div class="input-group">
                                    <label for="edit-timezone">Zona Waktu</label>
                                    <select id="edit-timezone" name="timezone">
                                        <option value="Asia/Jakarta">Asia/Jakarta</option>
                                        <option value="Asia/Singapore">Asia/Singapore</option>
                                        <option value="America/New_York">America/New_York</option>
                                    </select>
                                </div>
                                <div class="form-actions">
                                    <button type="submit" class="btn-save-inline">Simpan</button>
                                    <button type="button" class="btn-cancel-inline">Batal</button>
                                </div>
                            </form>
                        </div>

                        <!-- Aktivitas Terbaru -->
                        <div class="profile-card activity-log-card">
                            <div class="card-header">
                                <h2><i class="fas fa-history"></i> Aktivitas Terbaru</h2>
                            </div>
                            <div class="card-content">
                                <ul class="activity-list">
                                    <li><span class="activity-time">2025-12-22 10:30</span> - Login berhasil dari IP 192.168.1.10</li>
                                    <li><span class="activity-time">2025-12-21 15:45</span> - Mengubah pengaturan profil</li>
                                    <li><span class="activity-time">2025-12-20 09:10</span> - Mengakses modul SPK Fuzzy</li>
                                    <li><span class="activity-time">2025-12-19 11:20</span> - Mengganti password</li>
                                </ul>
                                <button class="btn-view-all-activity">Lihat Semua Aktivitas <i class="fas fa-arrow-right"></i></button>
                            </div>
                        </div>
                    </div>

                    <div class="profile-danger-zone">
                        <h2><i class="fas fa-exclamation-triangle"></i> Zona Berbahaya</h2>
                        <p>Tindakan di bawah ini tidak dapat diurungkan dan akan memiliki konsekuensi permanen.</p>
                        <button class="btn-delete-account" id="delete-account-btn"><i class="fas fa-trash-alt"></i> Hapus Akun</button>
                    </div>
                </div> <!-- Tutup .profile-container -->
            </div> <!-- Tutup .page-content -->
        </div> <!-- Tutup .main-content -->
    </div> <!-- TUTUP .dashboard-wrapper DI SINI -->

    <!-- MODAL-MODAL ANDA HARUS BERADA DI SINI, SEBAGAI ANAK LANGSUNG DARI BODY -->

    <!-- Global Edit Profile Modal -->
    <div class="modal-overlay" id="global-edit-profile-modal">
        <div class="modal-content">
            <h2 class="modal-title">Edit Profil Lengkap</h2>
            <form id="global-profile-edit-form">
                <div class="input-group">
                    <label for="global-edit-name">Nama Lengkap</label>
                    <input type="text" id="global-edit-name" name="full_name" value="">
                </div>
                <div class="input-group">
                    <label for="global-edit-title">Jabatan/Gelar</label>
                    <input type="text" id="global-edit-title" name="title" value="">
                </div>
                <div class="input-group">
                    <label for="global-edit-bio">Bio Singkat</label>
                    <textarea id="global-edit-bio" name="bio" rows="4"></textarea>
                </div>
                <hr class="modal-separator">
                <h3>Data Kontak</h3>
                <div class="input-group">
                    <label for="global-edit-email">Email</label>
                    <input type="email" id="global-edit-email" name="email" value="" readonly>
                </div>
                <div class="input-group">
                    <label for="global-edit-phone">Telepon</label>
                    <input type="text" id="global-edit-phone" name="phone" value="">
                </div>
                <div class="input-group">
                    <label for="global-edit-address">Alamat</label>
                    <input type="text" id="global-edit-address" name="address" value="">
                </div>
                <hr class="modal-separator">
                <h3>Detail Pribadi</h3>
                <div class="input-group">
                    <label for="global-edit-dob">Tanggal Lahir</label>
                    <div class="date-input-container">
                        <input type="date" id="global-edit-dob" name="date_of_birth" value="">
                        <span class="input-icon" data-target="global-edit-dob">
                            <i class="fas fa-calendar-alt"></i>
                        </span>
                    </div>
                </div>
                <div class="input-group">
                    <label for="global-edit-gender">Jenis Kelamin</label>
                    <select id="global-edit-gender" name="gender">
                        <option value="">Pilih</option>
                        <option value="Pria">Pria</option>
                        <option value="Wanita">Wanita</option>
                        <option value="Lainnya">Lainnya</option>
                    </select>
                </div>
                <div class="input-group">
                    <label for="global-edit-occupation">Pekerjaan</label>
                    <input type="text" id="global-edit-occupation" name="occupation" value="">
                </div>
                <hr class="modal-separator">
                <h3>Preferensi & Keamanan</h3>
                <div class="input-group checkbox-group">
                    <input type="checkbox" id="global-edit-email-notif" name="email_notifications">
                    <label for="global-edit-email-notif">Notifikasi Email</label>
                </div>
                <div class="input-group checkbox-group">
                    <input type="checkbox" id="global-edit-2fa" name="two_factor_auth">
                    <label for="global-edit-2fa">Autentikasi Dua Faktor (2FA)</label>
                </div>
                <div class="input-group">
                    <label for="global-edit-timezone">Zona Waktu</label>
                    <select id="global-edit-timezone" name="timezone">
                        <option value="Asia/Jakarta">Asia/Jakarta</option>
                        <option value="Asia/Singapore">Asia/Singapore</option>
                        <option value="America/New_York">America/New_York</option>
                    </select>
                </div>
                <div class="modal-actions">
                    <button type="submit" class="btn-primary-glow">Simpan Perubahan</button>
                    <button type="button" class="btn-cancel-modal">Batal</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Account Confirmation Modal -->
    <div class="modal-overlay" id="delete-account-modal">
        <div class="modal-content danger-modal">
            <h2 class="modal-title"><i class="fas fa-exclamation-triangle"></i> Konfirmasi Hapus Akun</h2>
            <p class="modal-message">Anda akan menghapus akun Anda secara permanen. Tindakan ini tidak dapat diurungkan.</p>
            <p class="modal-message">Mohon ketik "<strong style="color: var(--error-red);">HAPUS AKUN SAYA</strong>" untuk melanjutkan.</p>
            <div class="input-group">
                <input type="text" id="delete-confirm-input" placeholder="Ketik 'HAPUS AKUN SAYA'">
            </div>
            <div class="modal-actions">
                <button class="btn-delete-confirm" id="confirm-delete-btn" disabled>Hapus Akun</button>
                <button type="button" class="btn-cancel-modal">Batal</button>
            </div>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div class="modal-overlay" id="change-password-modal">
        <div class="modal-content">
            <h2 class="modal-title"><i class="fas fa-key"></i> Ganti Kata Sandi</h2>
            <form id="change-password-form">
                <div id="change-password-error-message" class="error-message hidden"></div>
                <div id="change-password-success-message" class="success-message hidden"></div>

                <div class="input-group">
                    <label for="current-password">Kata Sandi Saat Ini</label>
                    <div class="password-input-container">
                        <input type="password" id="current-password" name="current_password" placeholder="Masukkan kata sandi Anda saat ini" required>
                        <span class="toggle-password" data-target="current-password">
                            <i class="fas fa-eye"></i>
                        </span>
                    </div>
                </div>
                <div class="input-group">
                    <label for="new-password">Kata Sandi Baru</label>
                    <div class="password-input-container">
                        <input type="password" id="new-password" name="new_password" placeholder="Masukkan kata sandi baru (min. 6 karakter)" required>
                        <span class="toggle-password" data-target="new-password">
                            <i class="fas fa-eye"></i>
                        </span>
                    </div>
                </div>
                <div class="input-group">
                    <label for="confirm-new-password">Konfirmasi Kata Sandi Baru</label>
                    <div class="password-input-container">
                        <input type="password" id="confirm-new-password" name="confirm_new_password" placeholder="Konfirmasi kata sandi baru Anda" required>
                        <span class="toggle-password" data-target="confirm-new-password">
                            <i class="fas fa-eye"></i>
                        </span>
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="submit" class="btn-primary-glow">Simpan Kata Sandi Baru</button>
                    <button type="button" class="btn-cancel-modal">Batal</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Logout Confirmation Modal (Global) -->
    <div id="logoutModal" class="logout-modal-overlay">
        <div class="logout-modal-content">
            <h2 class="logout-modal-title">Konfirmasi Keluar</h2>
            <p class="logout-modal-message">Apakah Anda yakin ingin keluar dari akun Anda?</p>
            <div class="logout-modal-actions">
                <button id="confirmLogoutBtn" class="gradient-button">Ya, Keluar</button>
                <button id="cancelLogoutBtn" class="cancel-button">Batal</button>
            </div>
        </div>
    </div>

    <!-- Script JS khusus profil -->
    <script src="JS/profile-script.js"></script>
</body>
</html>
