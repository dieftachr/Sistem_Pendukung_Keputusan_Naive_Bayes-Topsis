<?php
// Inisialisasi variabel default
$display_username = "Tamu";
$display_avatar_url = "uploads/avatars/default_avatar.png"; // Default avatar

// Ambil username
// Prioritaskan variabel $username yang mungkin di-pass dari file utama
if (isset($username)) {
    $display_username = htmlspecialchars($username);
} elseif (isset($_SESSION["username"])) { // Jika ada di session
    $display_username = htmlspecialchars($_SESSION["username"]);
}

// Ambil avatar URL
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
<aside class="sidebar">
    <div class="sidebar-header">
        <img src="images/logo.png" alt="Logo Metisys" class="sidebar-logo">
    </div>
    <ul class="sidebar-nav">
        <!-- PENTING: Ubah href ke dashboard.php dengan parameter section -->
        <li><a href="dashboard.php?section=content-dashboard" class="nav-link" data-target="content-dashboard" data-title="Dashboard"><i class="fas fa-th-large"></i> <span>Dashboard</span></a></li>
        <li class="sidebar-menu-separator">MENU</li>
        <li><a href="dashboard.php?section=content-spk-kriteria" class="nav-link" data-target="content-spk-kriteria" data-title="Data Kriteria"><i class="fas fa-clipboard-list"></i> <span>Data Kriteria</span></a></li>
        <li><a href="dashboard.php?section=content-spk-alternatif" class="nav-link" data-target="content-spk-alternatif" data-title="Data Alternatif"><i class="fas fa-sitemap"></i> <span>Data Alternatif</span></a></li>
        <li><a href="dashboard.php?section=content-spk-penilaian" class="nav-link" data-target="content-spk-penilaian" data-title="Data Penilaian"><i class="fas fa-clipboard-check"></i> <span>Data Penilaian</span></a></li>
        <li><a href="dashboard.php?section=content-spk-latih" class="nav-link" data-target="content-spk-latih" data-title="Data Training"><i class="fas fa-chart-bar"></i> <span>Data Training</span></a></li>
        <li><a href="dashboard.php?section=content-spk-perhitungan" class="nav-link" data-target="content-spk-perhitungan" data-title="Metode Naive Bayes"><i class="fas fa-calculator"></i> <span>Metode Naive Bayes</span></a></li>
        <li><a href="dashboard.php?section=content-spk-topsis" class="nav-link" data-target="content-spk-topsis" data-title="Metode Topsis"><i class="fas fa-balance-scale"></i> <span>Metode Topsis</span></a></li>
        <li><a href="dashboard.php?section=pengaturan" class="nav-link" data-target="pengaturan" data-title="Pengaturan"><i class="fas fa-cogs"></i> <span>Pengaturan</span></a></li>
        <!-- Link Profil User di navbar sudah benar ke profile.php -->
        <li><a href="#" class="logout-trigger nav-link" data-title="Logout"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
    </ul>
</aside>
