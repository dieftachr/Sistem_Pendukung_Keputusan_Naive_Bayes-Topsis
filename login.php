<?php
session_start();
require_once 'config.php'; // Pastikan file config.php ada dan berisi koneksi database

// Jika user sudah login, arahkan ke dashboard
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("Location: dashboard.php");
    exit();
}

$error_message = '';
$success_message = '';
$show_register_form = false; // Flag untuk mengontrol form mana yang aktif di JS

// Variabel untuk menyimpan nilai input yang sebelumnya diisi (agar tidak hilang saat error)
$reg_username_value = '';
$reg_email_value = '';

// Menangani pesan error/sukses dari URL (untuk initial page load atau redirects)
if (isset($_GET['error'])) {
    if ($_GET['error'] == 'emptyfields') {
        $error_message = 'Nama pengguna/Email dan Kata Sandi harus diisi!';
    } elseif ($_GET['error'] == 'passwordwrong') {
        $error_message = 'Kata Sandi salah.';
    } elseif ($_GET['error'] == 'usernotfound') {
        $error_message = 'Nama pengguna atau Email tidak ditemukan.';
    } elseif ($_GET['error'] == 'dberror') {
        $error_message = 'Terjadi kesalahan database. Silakan coba lagi.';
    } elseif ($_GET['error'] == 'dberror_prepare') {
        $error_message = 'Terjadi kesalahan saat menyiapkan query database.';
    } elseif ($_GET['error'] == 'dberror_invalid_hash') {
        $error_message = 'Terjadi kesalahan pada format kata sandi tersimpan. Silakan hubungi administrator.';
    } elseif ($_GET['error'] == 'userexists') {
        $error_message = 'Nama pengguna atau Email sudah terdaftar.';
        $show_register_form = true; // Aktifkan form register jika error terkait register
    } elseif ($_GET['error'] == 'invalidemail') {
        $error_message = 'Format email tidak valid.';
        $show_register_form = true;
    } elseif ($_GET['error'] == 'passwordmismatch') {
        $error_message = 'Konfirmasi kata sandi tidak cocok.';
        $show_register_form = true;
    } elseif (isset($_POST['password']) && strlen($_POST['password']) < 6) { // Perbaikan: tambahkan isset()
        $error_message = 'Kata sandi minimal 6 karakter.';
        $show_register_form = true;
    } elseif ($_GET['error'] == 'emptyfields_register') {
        $error_message = 'Semua kolom harus diisi untuk pendaftaran!';
        $show_register_form = true;
    }
}
if (isset($_GET['reset']) && $_GET['reset'] == 'success') {
    $success_message = 'Kata sandi Anda berhasil direset! Silakan masuk.';
}
if (isset($_GET['registration']) && $_GET['registration'] == 'success') {
    $success_message = 'Pendaftaran berhasil! Silakan masuk.';
}

// Logika untuk memproses LOGIN
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login_submit'])) {
    $username_email = trim($_POST["username_email"]);
    $password = trim($_POST["password"]);

    if (empty($username_email) || empty($password)) {
        $error_message = 'Nama pengguna/Email dan Kata Sandi harus diisi!';
    } else {
        // Query untuk autentikasi dari tabel users (tanpa avatar_url)
        $stmt = $conn->prepare("SELECT id, username, email, password FROM users WHERE username = ? OR email = ?");
        if ($stmt === false) {
            $error_message = 'Terjadi kesalahan saat menyiapkan query database.';
        } else {
            $stmt->bind_param("ss", $username_email, $username_email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows == 1) {
                $stmt->bind_result($id, $username, $email, $hashed_password);
                $stmt->fetch();
                $stmt->close(); // Tutup statement pertama setelah fetch

                $hashed_password_for_verification = (string) $hashed_password;

                if (password_verify($password, $hashed_password_for_verification)) {
                    $_SESSION["loggedin"] = true;
                    $_SESSION["id"] = $id;
                    $_SESSION["username"] = $username;
                    $_SESSION["email"] = $email;

                    // --- PENTING: Ambil avatar_url dari tabel profiles ---
                    $stmt_profile = $conn->prepare("SELECT avatar_url FROM profiles WHERE user_id = ?");
                    if ($stmt_profile === false) {
                        // Jika gagal menyiapkan query profil, set avatar default
                        error_log("Failed to prepare profile query for user_id: " . $id . " - " . $conn->error);
                        $_SESSION["avatar_url"] = 'uploads/avatars/default_avatar.png';
                    } else {
                        $stmt_profile->bind_param("i", $id);
                        $stmt_profile->execute();
                        $stmt_profile->bind_result($avatar_url_from_db);
                        $stmt_profile->fetch();
                        $stmt_profile->close();

                        // Set $_SESSION["avatar_url"] dengan nilai dari database atau default
                        $_SESSION["avatar_url"] = $avatar_url_from_db ?: 'uploads/avatars/default_avatar.png';
                    }

                    header("Location: dashboard.php");
                    exit();
                } else {
                    $error_message = 'Kata Sandi salah.';
                }
            } else {
                $error_message = 'Nama pengguna atau Email tidak ditemukan.';
            }
            // $stmt->close(); // Sudah ditutup di dalam if ($stmt->num_rows == 1)
        }
    }
}

// Logika untuk memproses REGISTER
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register_submit'])) {
    $reg_username_value = trim($_POST["username"]);
    $reg_email_value = trim($_POST["email"]);
    $password = trim($_POST["password"]);
    $confirm_password = trim($_POST["confirm_password"]);

    if (empty($reg_username_value) || empty($reg_email_value) || empty($password) || empty($confirm_password)) {
        $error_message = 'Semua kolom harus diisi untuk pendaftaran!';
        $show_register_form = true;
    } elseif (!filter_var($reg_email_value, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Format email tidak valid.';
        $show_register_form = true;
    } elseif ($password !== $confirm_password) {
        $error_message = 'Konfirmasi kata sandi tidak cocok.';
        $show_register_form = true;
    } elseif (strlen($password) < 6) {
        $error_message = 'Kata sandi minimal 6 karakter.';
        $show_register_form = true;
    } else {
        // Cek apakah username atau email sudah ada di tabel users
        $stmt_check = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        if ($stmt_check === false) {
            $error_message = 'Terjadi kesalahan database.';
            $show_register_form = true;
        } else {
            $stmt_check->bind_param("ss", $reg_username_value, $reg_email_value);
            $stmt_check->execute();
            $stmt_check->store_result();
            if ($stmt_check->num_rows > 0) {
                $error_message = 'Nama pengguna atau Email sudah terdaftar.';
                $show_register_form = true;
            } else {
                $stmt_check->close(); // Tutup statement pengecekan

                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Masukkan data dasar ke tabel users
                $stmt_insert_user = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
                if ($stmt_insert_user === false) {
                    $error_message = 'Terjadi kesalahan database saat mendaftar pengguna.';
                    $show_register_form = true;
                } else {
                    $stmt_insert_user->bind_param("sss", $reg_username_value, $reg_email_value, $hashed_password);
                    if ($stmt_insert_user->execute()) {
                        $new_user_id = $stmt_insert_user->insert_id; // Dapatkan ID pengguna yang baru dibuat
                        $stmt_insert_user->close();

                        // --- PENTING: Masukkan entri default ke tabel profiles ---
                        $default_avatar_url = 'uploads/avatars/default_avatar.png';
                        // Anda bisa menambahkan kolom default lainnya di sini jika ada (misal: full_name, bio, dll.)
                        $stmt_insert_profile = $conn->prepare("INSERT INTO profiles (user_id, avatar_url) VALUES (?, ?)");
                        if ($stmt_insert_profile === false) {
                            // Jika gagal membuat profil, log error dan lanjutkan.
                            // Pertimbangkan untuk melakukan rollback user jika profil wajib.
                            error_log("Failed to prepare profile insert statement for user_id: " . $new_user_id . " - " . $conn->error);
                            header("Location: login.php?registration=success_partial"); // Indikasikan sukses tapi ada isu
                            exit();
                        } else {
                            $stmt_insert_profile->bind_param("is", $new_user_id, $default_avatar_url);
                            if ($stmt_insert_profile->execute()) {
                                $stmt_insert_profile->close();
                                header("Location: login.php?registration=success");
                                exit();
                            } else {
                                error_log("Failed to execute profile insert statement for user_id: " . $new_user_id . " - " . $conn->error);
                                header("Location: login.php?registration=success_partial");
                                exit();
                            }
                        }
                    } else {
                        $error_message = 'Terjadi kesalahan saat mendaftar pengguna.';
                        $show_register_form = true;
                        $stmt_insert_user->close();
                    }
                }
            }
        }
    }
}

// Tutup koneksi database jika sudah tidak diperlukan
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk / Daftar - Metisys</title>
    <link rel="stylesheet" href="CSS/style.css">
    <!-- Font Awesome untuk ikon (jika diperlukan) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-left">
            <h1 class="welcome-title">Selamat Datang di Metisys</h1>
            <div class="logo-animation-wrapper">
                <!-- Pastikan path ke logo Anda benar -->
                <img src="images/metisys_logo.png" alt="Metisys Logo" class="animated-logo">
            </div>
        </div>

        <div class="auth-right">
            <div class="auth-form-card">
                <div class="auth-toggle">
                    <button class="toggle-btn active" id="loginToggle">Masuk</button>
                    <button class="toggle-btn" id="registerToggle">Daftar</button>
                </div>

                <?php if (!empty($error_message)): ?>
                    <p class="error-message"><?php echo $error_message; ?></p>
                <?php endif; ?>
                <?php if (!empty($success_message)): ?>
                    <p class="success-message"><?php echo $success_message; ?></p>
                <?php endif; ?>

                <!-- Login Form -->
                <form action="login.php" method="POST" class="auth-form" id="loginForm">
                    <p class="form-description">Masuk untuk melanjutkan perjalanan Anda.</p>
                    <div class="input-group">
                        <label for="login_username_email">Email / Nama Pengguna</label>
                        <input type="text" id="login_username_email" name="username_email" placeholder="Email atau Nama Pengguna" required>
                    </div>
                    <div class="input-group">
                        <label for="login_password">Kata Sandi</label>
                        <div class="password-input-container">
                            <input type="password" id="login_password" name="password" placeholder="Kata Sandi" required>
                            <span class="toggle-password" data-target="login_password">
                                <i class="fas fa-eye"></i>
                            </span>
                        </div>
                    </div>
                    <p class="forgot-password-link"><a href="forgot_password.php">Lupa kata sandi?</a></p>
                    <button type="submit" name="login_submit" class="gradient-button">Masuk</button>
                </form>

                <!-- Register Form -->
                <form action="login.php" method="POST" class="auth-form hidden" id="registerForm">
                    <p class="form-description">Buat akun Anda untuk memulai.</p>
                    <div class="input-group">
                        <label for="register_username">Nama Pengguna</label>
                        <input type="text" id="register_username" name="username" placeholder="Nama Pengguna" value="<?php echo htmlspecialchars($reg_username_value); ?>" required>
                    </div>
                    <div class="input-group">
                        <label for="register_email">Email</label>
                        <input type="email" id="register_email" name="email" placeholder="Alamat Email" value="<?php echo htmlspecialchars($reg_email_value); ?>" required>
                    </div>
                    <div class="input-group">
                        <label for="register_password">Kata Sandi</label>
                        <div class="password-input-container">
                            <input type="password" id="register_password" name="password" placeholder="Buat Kata Sandi" required>
                            <span class="toggle-password" data-target="register_password">
                                <i class="fas fa-eye"></i>
                            </span>
                        </div>
                    </div>
                    <div class="input-group">
                        <label for="register_confirm_password">Konfirmasi Kata Sandi</label>
                        <div class="password-input-container">
                            <input type="password" id="register_confirm_password" name="confirm_password" placeholder="Konfirmasi Kata Sandi" required>
                            <span class="toggle-password" data-target="register_confirm_password">
                                <i class="fas fa-eye"></i>
                            </span>
                        </div>
                    </div>
                    <button type="submit" name="register_submit" class="gradient-button">Daftar</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const loginToggle = document.getElementById('loginToggle');
            const registerToggle = document.getElementById('registerToggle');
            const loginForm = document.getElementById('loginForm');
            const registerForm = document.getElementById('registerForm');

            function showForm(formToShow, formToHide, activeBtn, inactiveBtn) {
                formToShow.classList.remove('hidden');
                formToHide.classList.add('hidden');
                activeBtn.classList.add('active');
                inactiveBtn.classList.remove('active');
            }

            // Check URL parameters to determine which form to show initially
            const urlParams = new URLSearchParams(window.location.search);

            // Prioritaskan success message untuk registration, tampilkan form login
            if (urlParams.has('registration') && urlParams.get('registration') === 'success') {
                showForm(loginForm, registerForm, loginToggle, registerToggle);
            }
            // Jika ada flag PHP untuk menampilkan form register (dari POST error)
            else if (<?php echo json_encode($show_register_form); ?>) {
                showForm(registerForm, loginForm, registerToggle, loginToggle);
            }
            // Jika ada error dari GET parameter (misalnya dari redirect)
            else if (urlParams.has('error')) {
                const errorType = urlParams.get('error');
                // Daftar error yang seharusnya mengaktifkan form register
                if (['userexists', 'invalidemail', 'passwordmismatch', 'shortpassword', 'emptyfields_register', 'dberror_prepare', 'dberror'].includes(errorType)) {
                    showForm(registerForm, loginForm, registerToggle, loginToggle);
                } else {
                    showForm(loginForm, registerForm, loginToggle, registerToggle); // Default ke login untuk error lainnya
                }
            }
            // Jika tidak ada kondisi di atas, form login sudah aktif secara default.

            // Event listeners untuk tombol toggle
            loginToggle.addEventListener('click', function() {
                showForm(loginForm, registerForm, loginToggle, registerToggle);
            });

            registerToggle.addEventListener('click', function() {
                showForm(registerForm, loginForm, registerToggle, loginToggle);
            });

            // Logic for password visibility toggle
            document.querySelectorAll('.toggle-password').forEach(function(toggle) {
                toggle.addEventListener('click', function() {
                    const targetId = this.dataset.target;
                    const passwordInput = document.getElementById(targetId);
                    const icon = this.querySelector('i');

                    if (passwordInput.type === 'password') {
                        passwordInput.type = 'text';
                        icon.classList.remove('fa-eye');
                        icon.classList.add('fa-eye-slash');
                    } else {
                        passwordInput.type = 'password';
                        icon.classList.remove('fa-eye-slash');
                        icon.classList.add('fa-eye');
                    }
                });
            });
        });
    </script>
</body>
</html>
