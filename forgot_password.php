<?php
session_start();
require_once 'config.php'; // Pastikan file config.php ada dan berisi koneksi database

$error_message = '';
$success_message = '';
$show_reset_form = false;
$user_email_for_reset = ''; // Untuk menyimpan email yang sudah divalidasi

// --- Logika untuk memproses input Email ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['email_submit'])) {
    $email = trim($_POST["email"]);

    if (empty($email)) {
        $error_message = 'Silakan masukkan alamat email Anda.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Format email tidak valid.';
    } else {
        // Cek apakah email terdaftar di database
        $stmt = $conn->prepare("SELECT id, email FROM users WHERE email = ?");
        if ($stmt === false) {
            $error_message = 'Terjadi kesalahan database. Silakan coba lagi.';
        } else {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows == 1) {
                // Email ditemukan, tampilkan form reset password
                $show_reset_form = true;
                $user_email_for_reset = $email; // Simpan email untuk proses reset selanjutnya
            } else {
                $error_message = 'Email tidak terdaftar.'; // Pesan umum untuk keamanan
            }
            $stmt->close();
        }
    }
}

// --- Logika untuk memproses Reset Password ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reset_password_submit'])) {
    $email = trim($_POST["reset_email"]); // Ambil email dari hidden field
    $new_password = trim($_POST["new_password"]);
    $confirm_password = trim($_POST["confirm_password"]);

    // Setelah submit reset, kita harus tetap menampilkan form reset jika ada error
    // Jadi, kita perlu memvalidasi ulang email atau mengambilnya dari hidden field
    if (empty($email)) { // Ini seharusnya tidak terjadi jika hidden field diisi dengan benar
        $error_message = 'Email tidak valid untuk proses reset.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Format email tidak valid.';
    } elseif (empty($new_password) || empty($confirm_password)) {
        $error_message = 'Kata sandi baru dan konfirmasi harus diisi.';
        $show_reset_form = true; // Tetap tampilkan form reset
        $user_email_for_reset = $email;
    } elseif ($new_password !== $confirm_password) {
        $error_message = 'Kata sandi baru dan konfirmasi tidak cocok.';
        $show_reset_form = true; // Tetap tampilkan form reset
        $user_email_for_reset = $email;
    } elseif (strlen($new_password) < 6) {
        $error_message = 'Kata sandi minimal 6 karakter.';
        $show_reset_form = true; // Tetap tampilkan form reset
        $user_email_for_reset = $email;
    } else {
        // Hash password baru
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        // Update password di database
        $stmt_update = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
        if ($stmt_update === false) {
            $error_message = 'Terjadi kesalahan database saat memperbarui kata sandi.';
            $show_reset_form = true; // Tetap tampilkan form reset
            $user_email_for_reset = $email;
        } else {
            $stmt_update->bind_param("ss", $hashed_password, $email);
            if ($stmt_update->execute()) {
                $success_message = 'Kata sandi Anda berhasil direset. Silakan masuk dengan kata sandi baru Anda.';
                // Setelah sukses, kembali ke form email atau redirect ke login
                header("Location: login.php?reset=success");
                exit();
            } else {
                $error_message = 'Terjadi kesalahan saat memperbarui kata sandi.';
                $show_reset_form = true; // Tetap tampilkan form reset
                $user_email_for_reset = $email;
            }
            $stmt_update->close();
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
    <title>Reset Kata Sandi - Metisys</title>
    <link rel="stylesheet" href="CSS/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-left">
            <h1 class="welcome-title">Reset Kata Sandi Anda</h1>
            <p class="auth-left-subtitle">Kami akan membantu Anda mendapatkan kembali akses ke akun Metisys Anda.</p>
            <!-- Tidak ada logo animasi di sini -->
        </div>

        <div class="auth-right">
            <div class="auth-form-card">
                <?php if (!empty($error_message)): ?>
                    <p class="error-message"><?php echo $error_message; ?></p>
                <?php endif; ?>
                <?php if (!empty($success_message)): ?>
                    <p class="success-message"><?php echo $success_message; ?></p>
                <?php endif; ?>

                <?php if ($show_reset_form): ?>
                    <!-- Form Reset Password -->
                    <form action="forgot_password.php" method="POST" class="auth-form" id="resetPasswordForm">
                        <p class="form-description">Masukkan kata sandi baru Anda.</p>
                        <input type="hidden" name="reset_email" value="<?php echo htmlspecialchars($user_email_for_reset); ?>">
                        <div class="input-group">
                            <label for="new_password">Kata Sandi Baru</label>
                            <input type="password" id="new_password" name="new_password" placeholder="Kata Sandi Baru" required>
                        </div>
                        <div class="input-group">
                            <label for="confirm_password">Konfirmasi Kata Sandi Baru</label>
                            <input type="password" id="confirm_password" name="confirm_password" placeholder="Konfirmasi Kata Sandi Baru" required>
                        </div>
                        <button type="submit" name="reset_password_submit" class="gradient-button">Reset Kata Sandi</button>
                    </form>
                <?php else: ?>
                    <!-- Form Input Email -->
                    <form action="forgot_password.php" method="POST" class="auth-form" id="emailInputForm">
                        <p class="form-description">Masukkan alamat email terdaftar Anda untuk melanjutkan.</p>
                        <div class="input-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" placeholder="Alamat Email Anda" required>
                        </div>
                        <button type="submit" name="email_submit" class="gradient-button">Lanjutkan</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
