<?php
// navbar.php
// Pastikan session_start() sudah dipanggil di file utama yang meng-include ini
// Jika tidak, panggil di sini:
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Inisialisasi variabel default untuk ditampilkan
$display_username = "Tamu";
$display_avatar_url = "uploads/avatars/default_avatar.png"; // Default avatar

// --- Bagian Pengambilan Username ---
// Prioritaskan variabel $username yang mungkin di-pass dari file utama
if (isset($username)) {
    $display_username = htmlspecialchars($username);
} elseif (isset($_SESSION["username"])) { // Jika ada di session
    $display_username = htmlspecialchars($_SESSION["username"]);
}

// --- Bagian Pengambilan Avatar URL ---
// Prioritaskan variabel $user_avatar_url yang mungkin di-pass dari file utama
if (isset($user_avatar_url)) {
    $display_avatar_url = htmlspecialchars($user_avatar_url);
} elseif (isset($_SESSION["avatar_url"])) { // Jika ada di session
    $display_avatar_url = htmlspecialchars($_SESSION["avatar_url"]);
}

// Tambahan: Pastikan path avatar_url tidak kosong atau null dari DB/session
// Jika avatar_url dari DB/session kosong, gunakan default
if (empty($display_avatar_url) || $display_avatar_url === "uploads/avatars/default_avatar.png") {
    $display_avatar_url = "uploads/avatars/default_avatar.png";
}

?>
<nav class="navbar">
    <div class="navbar-left">
        <!-- Tombol Hamburger untuk membuka/menutup sidebar -->
        <button class="hamburger-btn" id="hamburgerBtn">
            <i class="fas fa-bars"></i>
        </button>
        <!-- Judul halaman yang dinamis, akan diubah oleh JavaScript -->
        <span class="navbar-page-title" id="navbarPageTitle">Dashboard</span>
    </div>
    <div class="navbar-center">
        <!-- Search Bar di tengah navbar -->
        <div class="search-bar">
            <!-- HANYA MENAMBAHKAN ID PADA INPUT DAN BUTTON INI -->
            <input type="text" id="searchInput" placeholder="Cari menu, data, atau fitur..." class="search-input">
            <button class="search-button" id="searchButton"><i class="fas fa-search"></i></button>
        </div>
    </div>
    <div class="navbar-right">
        <!-- Dropdown Profil Pengguna -->
        <div class="user-profile-dropdown" id="userProfileDropdown">
            <div class="user-info-trigger">
                <!-- Avatar pengguna yang dinamis -->
                <img src="<?php echo $display_avatar_url; ?>" alt="User Avatar" class="user-avatar" id="navbar-user-avatar">
                <!-- Nama pengguna yang dinamis -->
                <span class="user-display">Halo, <?php echo $display_username; ?></span>
                <!-- Ikon panah dropdown -->
                <i class="fas fa-caret-down dropdown-arrow"></i>
            </div>
            <!-- Menu dropdown yang tersembunyi secara default -->
            <div class="dropdown-menu">
                <!-- Tautan ke halaman profil pengguna -->
                <a href="profile.php" class="dropdown-item"><i class="fas fa-user-circle"></i> Profil User</a>
                <!-- Tautan untuk logout, akan memicu modal konfirmasi -->
                <a href="#" class="logout-trigger dropdown-item"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </div>
</nav>

<!-- JAVASCRIPT UNTUK FUNGSI PENCARIAN DAN MODAL -->
<!-- Tempatkan ini di bagian paling bawah file navbar.php -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const searchButton = document.getElementById('searchButton');

    // Elemen modal (pastikan ini ada di file utama/layout Anda)
    const notificationModal = document.getElementById('notificationModal');
    const notificationMessage = document.getElementById('notificationMessage');
    const closeModalBtn = document.getElementById('closeModalBtn');
    const modalOkBtn = document.getElementById('modalOkBtn');

    // Fungsi untuk menampilkan modal
    function showNotificationModal(message) {
        if (notificationModal && notificationMessage) { // Pastikan elemen modal ada
            notificationMessage.textContent = message;
            notificationModal.classList.add('show');
        } else {
            alert(message); // Fallback jika modal tidak ditemukan
        }
    }

    // Fungsi untuk menyembunyikan modal
    function hideNotificationModal() {
        if (notificationModal) {
            notificationModal.classList.remove('show');
        }
    }

    // Event listener untuk tombol tutup modal (X)
    if (closeModalBtn) closeModalBtn.addEventListener('click', hideNotificationModal);

    // Event listener untuk tombol OK di modal
    if (modalOkBtn) modalOkBtn.addEventListener('click', hideNotificationModal);

    // Event listener untuk menutup modal jika mengklik di luar konten modal
    if (notificationModal) {
        notificationModal.addEventListener('click', function(event) {
            if (event.target === notificationModal) { // Hanya jika mengklik overlay, bukan konten modal
                hideNotificationModal();
            }
        });
    }


    function performSearch() {
        const query = searchInput.value.trim(); // Ambil nilai input dan hapus spasi di awal/akhir
        if (query.length > 0) {
            // Kirim permintaan AJAX ke search_handler.php
            // Pastikan 'search_handler.php' berada di direktori yang sama
            // atau sesuaikan path-nya jika berbeda.
            fetch('search_handler.php?q=' + encodeURIComponent(query))
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok ' + response.statusText);
                    }
                    return response.json(); // Parse respons sebagai JSON
                })
                .then(data => {
                    if (data.success && data.url) {
                        // Jika pencarian berhasil dan URL ditemukan, arahkan pengguna
                        window.location.href = data.url;
                    } else {
                        // Tampilkan modal notifikasi kustom jika tidak ditemukan atau ada kesalahan
                        showNotificationModal(data.message || 'Terjadi kesalahan saat mencari.');
                    }
                })
                .catch(error => {
                    console.error('Error during search:', error);
                    showNotificationModal('Terjadi kesalahan saat mencari: ' + error.message);
                });
        } else {
            // Tampilkan modal jika input kosong
            showNotificationModal('Silakan masukkan kata kunci pencarian.');
        }
    }

    // Tambahkan event listener untuk tombol pencarian
    searchButton.addEventListener('click', performSearch);

    // Tambahkan event listener untuk tombol 'Enter' pada input pencarian
    searchInput.addEventListener('keypress', function(event) {
        if (event.key === 'Enter') {
            event.preventDefault(); // Mencegah form submit default jika ada
            performSearch();
        }
    });
});
</script>
