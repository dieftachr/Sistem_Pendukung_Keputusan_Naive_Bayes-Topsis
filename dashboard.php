<?php
// Tampilkan semua error PHP untuk debugging. Hapus ini di lingkungan produksi.
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'config.php';

// Cek apakah user sudah login
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: login.php");
    exit();
}

// Ambil user_id, username, dan email dari session
$user_id = $_SESSION["id"];
$username = htmlspecialchars($_SESSION["username"]); // Untuk tampilan di dashboard
$user_email_from_session = $_SESSION["email"];
$avatar_url_from_session = $_SESSION["avatar_url"] ?? 'uploads/avatars/default_avatar.png'; // Ambil avatar dari sesi, default jika tidak ada

// --- Bagian Penanganan Permintaan AJAX ---
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    header('Content-Type: application/json'); // Pastikan respons dalam format JSON

    $action = $_POST['action'] ?? null;
    if (!$action) {
        $input = file_get_contents('php://input');
        $data_json = json_decode($input, true);
        $action = $data_json['action'] ?? null;
    }

    $response = ["success" => false, "message" => "Aksi tidak dikenal."];
    echo json_encode($response);
    $conn->close();
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Metisys</title>
    <link rel="stylesheet" href="CSS/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .page-section {
            display: none; /* Default semua section tersembunyi */
        }
        .page-section.active {
            display: block; /* Section yang aktif akan ditampilkan */
        }

        .placeholder-box {
            background: white;
            border: 1px dashed #ccc;
            border-radius: 15px;
            padding: 20px;
            min-height: 400px;
            width: 100%;
            box-sizing: border-box; /* Sangat penting agar padding tidak merusak lebar */
            overflow: hidden; /* Mencegah konten meluap keluar kotak */
        }
    </style>
</head>
<body>
    <div class="dashboard-wrapper sidebar-collapsed"> <!-- Sidebar dimulai dalam keadaan collapsed -->
        <?php
        // Include sidebar, pass avatar_url_from_session jika diperlukan di sidebar.php
        include_once 'sidebar.php';
        ?>
        <div class="main-content">
            <?php include_once 'navbar.php'; ?>
            <!-- Page Content (Area Dinamis) -->
            <div class="page-content">
                <!-- BAGIAN KONTEN DASHBOARD -->
                <div id="content-dashboard" class="page-section active">
                    <h1>Selamat Datang di Dashboard Metisys!</h1>
                    <p>Halo, <?php echo $username; ?>! Ini adalah area utama dashboard Anda. Di sini Anda bisa melihat ringkasan data dan mengakses fitur-fitur utama.</p>

                    <div class="dashboard-cards">
                        <div class="card">
                            <h3>Data Kriteria</h3>
                            <p>Klasifikasi data berdasarkan teorema Naive Bayes.</p>
                            <a href="kriteria.php" class="card-link navigate-link" data-target="content-spk-kriteria" data-title="SPK Kriteria">Lihat Detail <i class="fas fa-arrow-right"></i></a>
                        </div>
                        <div class="card">
                            <h3>Data Alternatif</h3>
                            <p>Klasifikasi data berdasarkan teorema Naive Bayes.</p>
                            <a href="alternatif.php" class="card-link navigate-link" data-target="content-spk-alternatif" data-title="SPK Alternatif">Lihat Detail <i class="fas fa-arrow-right"></i></a>
                        </div>
                    </div>

                    <h2>Ringkasan SPK Naive Bayes</h2>
                    <p>Grafik atau data ringkasan untuk SPK Naive Bayes akan muncul di sini.</p>
                    <div class="placeholder-box" style="margin-bottom: 30px; padding: 0; overflow: hidden;">
                        <iframe src="perhitungan.php" style="width: 100%; height: 500px; border: none;"></iframe>
                    </div>
                </div>
                <!-- content-kriteria -->
                <div id="content-spk-kriteria" class="page-section">
                    <h1>Data Kriteria</h1>
                    <p>Ini adalah halaman untuk mengelola data kriteria dalam sistem pendukung keputusan.</p>
                    <div class="placeholder-box">
                        <?php include 'kriteria.php'; ?>
                    </div>
                </div>

                <!-- content-alternatif -->
                <div id="content-spk-alternatif" class="page-section">
                    <h1>Data Alternatif</h1>
                    <p>Ini adalah halaman untuk mengelola data alternatif dalam sistem pendukung keputusan.</p>
                    <div class="placeholder-box">
                        <?php include 'alternatif.php'; ?>
                    </div>
                </div>

                <!-- content-penilaian -->
                <div id="content-spk-penilaian" class="page-section">
                    <h1>Data Penilaian</h1>
                    <p>Ini adalah halaman untuk mengelola data Penilaian dalam sistem pendukung keputusan.</p>
                    <div class="placeholder-box">
                        <?php include 'penilaian.php'; ?>
                    </div>
                </div>

                <!-- content-training -->
                <div id="content-spk-latih" class="page-section">
                    <h1>Data Training</h1>
                    <p>Ini adalah halaman untuk mengelola data Training dalam sistem pendukung keputusan.</p>
                    <div class="placeholder-box">
                        <?php include 'data_latih.php'; ?>
                    </div>
                </div>

                <div id="content-spk-perhitungan" class="page-section">
                    <h1>Perhitungan Naive Bayes</h1>
                    <p>Halaman ini memuat hasil perhitungan metode Naive Bayes.</p>
                    
                    <iframe src="perhitungan.php" style="width: 100%; height: 600px; border: none; border-radius: 10px; background: white;"></iframe>
                </div>
                <!-- BAGIAN KONTEN SPK NAIVE BAYES -->
                <div id="content-spk-naive-bayes" class="page-section">
                    <h1>SPK Naive Bayes</h1>
                    <p>Ini adalah halaman untuk Sistem Pendukung Keputusan menggunakan metode Naive Bayes.</p>
                    <div class="placeholder-box">
                        [Konten dan fitur SPK Naive Bayes akan muncul di sini.]
                    </div>
                </div>

                <div id="content-spk-topsis" class="page-section">
                    <h1>Perhitungan Topsis</h1>
                    <p>Halaman ini memuat hasil perhitungan metode Topsis.</p>
                    
                    <iframe src="topsis.php" style="width: 100%; height: 600px; border: none; border-radius: 10px; background: white;"></iframe>
                </div>
                <!-- BAGIAN KONTEN TOPSIS -->
                <div id="content-spk-topsis" class="page-section">
                    <h1>SPK Topsis</h1>
                    <p>Ini adalah halaman untuk Sistem Pendukung Keputusan menggunakan metode Topsis.</p>
                    <div class="placeholder-box">
                        [Konten dan fitur SPK Topsis akan muncul di sini.]
                    </div>
                </div>

                <!-- BAGIAN KONTEN PENGATURAN (Diubah) -->
                <div id="pengaturan" class="page-section <?php echo ($active_section == 'pengaturan') ? 'active' : ''; ?>">
                    <h1>Pengaturan Aplikasi</h1>
                    <p>Kelola informasi perusahaan dan detail tanda tangan laporan di sini.</p>
                    <!-- Konten dari pengaturan.php akan dimuat di sini -->
                    <?php include 'pengaturan.php'; ?>
                </div>

            </div> <!-- End of .page-content -->
        </div> <!-- End of .main-content -->
    </div> <!-- End of .dashboard-wrapper -->

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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const dashboardWrapper = document.querySelector('.dashboard-wrapper');
            const hamburgerBtn = document.getElementById('hamburgerBtn');
            const navbarPageTitle = document.getElementById('navbarPageTitle');
            const sidebarNavLinks = document.querySelectorAll('.sidebar-nav .nav-link'); // Hanya link navigasi sidebar
            const cardNavLinks = document.querySelectorAll('.dashboard-cards .card-link'); // Link dari card
            const userProfileDropdown = document.getElementById('userProfileDropdown');
            const userInfoTrigger = userProfileDropdown ? userProfileDropdown.querySelector('.user-info-trigger') : null;
            const dropdownMenu = userProfileDropdown ? userProfileDropdown.querySelector('.dropdown-menu') : null;
            const sidebar = document.querySelector('.sidebar');
            const pageSections = document.querySelectorAll('.page-section'); // Semua bagian konten

            // --- Logout Modal Logic ---
            const logoutTriggers = document.querySelectorAll('.logout-trigger');
            const logoutModal = document.getElementById('logoutModal');
            const confirmLogoutBtn = document.getElementById('confirmLogoutBtn');
            const cancelLogoutBtn = document.getElementById('cancelLogoutBtn');
            const body = document.body;

            if (logoutModal) {
                logoutTriggers.forEach(trigger => {
                    if (!trigger.hasAttribute('data-listener-added')) { // Prevent duplicate listeners
                        trigger.addEventListener('click', function(e) {
                            e.preventDefault();
                            logoutModal.classList.add('show');
                            body.classList.add('modal-open');
                            // Tutup dropdown user jika terbuka saat modal logout muncul
                            if (dropdownMenu) dropdownMenu.classList.remove('show');
                            if (userInfoTrigger) {
                                const arrowIcon = userInfoTrigger.querySelector('.dropdown-arrow');
                                if (arrowIcon) {
                                    arrowIcon.classList.remove('fa-caret-down');
                                    arrowIcon.classList.add('fa-caret-up');
                                }
                            }
                        });
                        trigger.setAttribute('data-listener-added', 'true');
                    }
                });

                if (cancelLogoutBtn && !cancelLogoutBtn.hasAttribute('data-listener-added')) {
                    cancelLogoutBtn.addEventListener('click', function() {
                        logoutModal.classList.remove('show');
                        body.classList.remove('modal-open');
                    });
                    cancelLogoutBtn.setAttribute('data-listener-added', 'true');
                }

                if (confirmLogoutBtn && !confirmLogoutBtn.hasAttribute('data-listener-added')) {
                    confirmLogoutBtn.addEventListener('click', function() {
                        window.location.href = 'logout_handler.php'; // Arahkan ke logout_handler.php
                    });
                    confirmLogoutBtn.setAttribute('data-listener-added', 'true');
                }

                if (!logoutModal.hasAttribute('data-listener-added')) {
                    logoutModal.addEventListener('click', function(e) {
                        if (e.target === logoutModal) {
                            logoutModal.classList.remove('show');
                            body.classList.remove('modal-open');
                        }
                    });
                    logoutModal.setAttribute('data-listener-added', 'true');
                }
            }
            // --- End Logout Modal Logic ---

            // --- Sidebar & Navbar Interaction ---

            // Sidebar dimulai dalam keadaan collapsed (sudah diatur di dashboard.php)
            if (hamburgerBtn && !hamburgerBtn.hasAttribute('data-listener-added')) {
                hamburgerBtn.addEventListener('click', function(e) {
                    e.stopPropagation(); // Mencegah event menyebar ke document
                    dashboardWrapper.classList.toggle('sidebar-collapsed');
                });
                hamburgerBtn.setAttribute('data-listener-added', 'true');
            }

            // Toggle user profile dropdown
            if (userInfoTrigger && !userInfoTrigger.hasAttribute('data-listener-added')) {
                userInfoTrigger.addEventListener('click', function(e) {
                    e.stopPropagation(); // Mencegah event menyebar ke document
                    dropdownMenu.classList.toggle('show');
                    const arrowIcon = userInfoTrigger.querySelector('.dropdown-arrow');
                    if (arrowIcon) {
                        if (dropdownMenu.classList.contains('show')) {
                            arrowIcon.classList.remove('fa-caret-down');
                            arrowIcon.classList.add('fa-caret-up');
                        } else {
                            arrowIcon.classList.remove('fa-caret-up');
                            arrowIcon.classList.add('fa-caret-down');
                        }
                    }
                });
                userInfoTrigger.setAttribute('data-listener-added', 'true');
            }

            // --- Initial Page Load: Check URL for section parameter ---
            const urlParams = new URLSearchParams(window.location.search);
            const requestedSection = urlParams.get('section');
            let initialActiveSectionId = 'content-dashboard'; // Default section

            if (requestedSection) {
                // Validate if the requested section actually exists in this dashboard
                const potentialSection = document.getElementById(requestedSection);
                if (potentialSection) {
                    initialActiveSectionId = requestedSection;
                }
            }

            // Deactivate all sections first
            pageSections.forEach(section => {
                section.classList.remove('active');
            });
            // Activate the determined initial section
            const initialSectionElement = document.getElementById(initialActiveSectionId);
            if (initialSectionElement) {
                initialSectionElement.classList.add('active');
                const initialActiveNavLink = document.querySelector(`.sidebar-nav .nav-link[data-target="${initialActiveSectionId}"]`);
                if (initialActiveNavLink) {
                    navbarPageTitle.textContent = initialActiveNavLink.dataset.title;
                    initialActiveNavLink.classList.add('active'); // Also activate the sidebar link
                } else {
                    // Fallback for dashboard title if no matching sidebar link (e.g., if initialActiveSectionId is not a sidebar link)
                    navbarPageTitle.textContent = "Dashboard";
                }
            }

            // --- Handle navigation clicks for sidebar links ---
            sidebarNavLinks.forEach(link => {
                if (!link.hasAttribute('data-listener-added')) {
                    link.addEventListener('click', function(e) {
                        // If it's a logout link, let the modal handle it
                        if (this.classList.contains('logout-trigger')) {
                            return;
                        }

                        const href = this.getAttribute('href');
                        // If the link is for an internal dashboard section (e.g., dashboard.php?section=...)
                        if (href && href.startsWith('dashboard.php?section=')) {
                            e.preventDefault(); // Prevent full page reload
                            const targetId = this.dataset.target;
                            const title = this.dataset.title;

                            // Show the target section
                            pageSections.forEach(section => {
                                section.classList.remove('active');
                            });
                            const targetSection = document.getElementById(targetId);
                            if (targetSection) {
                                targetSection.classList.add('active');
                                navbarPageTitle.textContent = title; // Update judul di navbar
                            }

                            // Update active state for sidebar links
                            document.querySelectorAll('.sidebar-nav .nav-link').forEach(item => item.classList.remove('active'));
                            this.classList.add('active');

                            // Update URL using History API without reloading
                            history.pushState(null, '', `dashboard.php?section=${targetId}`);

                            // Close sidebar if open on mobile
                            if (window.innerWidth <= 768 && !dashboardWrapper.classList.contains('sidebar-collapsed')) {
                                dashboardWrapper.classList.add('sidebar-collapsed');
                            }
                            // Close user dropdown if open
                            if (dropdownMenu) dropdownMenu.classList.remove('show');
                            if (userInfoTrigger) {
                                const arrowIcon = userInfoTrigger.querySelector('.dropdown-arrow');
                                if (arrowIcon) {
                                    arrowIcon.classList.remove('fa-caret-down');
                                    arrowIcon.classList.add('fa-caret-up');
                                }
                            }
                        }
                    });
                    link.setAttribute('data-listener-added', 'true');
                }
            });
            // --- Handle navigation clicks for card links (internal to dashboard) ---
            cardNavLinks.forEach(link => {
                if (!link.hasAttribute('data-listener-added')) {
                    link.addEventListener('click', function(e) {
                        e.preventDefault(); // Always prevent default for card links
                        const targetId = this.dataset.target;
                        const title = this.dataset.title;
                        if (targetId) {
                            // showPageSection function
                            pageSections.forEach(section => {
                                section.classList.remove('active');
                            });
                            const targetSection = document.getElementById(targetId);
                            if (targetSection) {
                                targetSection.classList.add('active');
                                navbarPageTitle.textContent = title; // Update judul di navbar
                            }

                            // Remove 'active' class from all sidebar nav links
                            document.querySelectorAll('.sidebar-nav .nav-link').forEach(item => item.classList.remove('active'));
                            // Add 'active' class to the corresponding sidebar link
                            const sidebarLink = document.querySelector(`.sidebar-nav .nav-link[data-target="${targetId}"]`);
                            if (sidebarLink) {
                                sidebarLink.classList.add('active');
                            }

                            // Update URL without reloading
                            history.pushState(null, '', `dashboard.php?section=${targetId}`);

                            // Close sidebar if open on mobile after click
                            if (window.innerWidth <= 768 && !dashboardWrapper.classList.contains('sidebar-collapsed')) {
                                dashboardWrapper.classList.add('sidebar-collapsed');
                            }
                            // Close user dropdown if open
                            if (dropdownMenu) dropdownMenu.classList.remove('show');
                            if (userInfoTrigger) {
                                const arrowIcon = userInfoTrigger.querySelector('.dropdown-arrow');
                                if (arrowIcon) {
                                    arrowIcon.classList.remove('fa-caret-down');
                                    arrowIcon.classList.add('fa-caret-up');
                                }
                            }
                        }
                    });
                    link.setAttribute('data-listener-added', 'true');
                }
            });


            // Close dropdown and sidebar if clicked outside
            if (!document.body.hasAttribute('data-global-listener-added')) {
                document.addEventListener('click', function(e) {
                    // Close user dropdown
                    if (userProfileDropdown && !userProfileDropdown.contains(e.target)) {
                        if (dropdownMenu) dropdownMenu.classList.remove('show');
                        if (userInfoTrigger) {
                            const arrowIcon = userInfoTrigger.querySelector('.dropdown-arrow');
                            if (arrowIcon) {
                                arrowIcon.classList.remove('fa-caret-up');
                                arrowIcon.classList.add('fa-caret-down');
                            }
                        }
                    }

                    // Close sidebar if it's open and click is outside sidebar and hamburger button
                    if (!dashboardWrapper.classList.contains('sidebar-collapsed') && !sidebar.contains(e.target) && !hamburgerBtn.contains(e.target)) {
                        dashboardWrapper.classList.add('sidebar-collapsed');
                    }
                });
                document.body.setAttribute('data-global-listener-added', 'true');
            }
        });
    </script>
</body>
</html>
