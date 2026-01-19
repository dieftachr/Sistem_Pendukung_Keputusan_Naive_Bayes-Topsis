<?php
include_once __DIR__ . '/config.php';
// Pastikan $conn terdefinisi, jika tidak, gunakan $koneksi
if (!isset($conn) && isset($koneksi)) { $conn = $koneksi; }

// --- Tambahkan kode ini untuk mengambil pengaturan dari database ---
// Fungsi untuk mendapatkan pengaturan dari database
if (!function_exists('get_setting')) { // Pastikan fungsi hanya didefinisikan sekali
    function get_setting($conn, $name, $default = '') {
        if (!$conn) { // Tambahkan pengecekan koneksi
            return $default;
        }
        $name = mysqli_real_escape_string($conn, $name); // Sanitasi input
        $q = mysqli_query($conn, "SELECT setting_value FROM app_settings WHERE setting_name = '$name'");
        if ($q && mysqli_num_rows($q) > 0) {
            return mysqli_fetch_assoc($q)['setting_value'];
        }
        return $default;
    }
}
if (!function_exists('konversiKeAngka')) {
    function konversiKeAngka($nilai) {
        if (is_numeric($nilai)) return (float)$nilai;
        $map = [
            "Sangat Baik"  => 1,
            "Baik"         => 2,
            "Cukup"        => 3,
            "Buruk"        => 4,
            "Sangat Buruk" => 5
        ];
        return isset($map[$nilai]) ? (float)$map[$nilai] : 0.0;
    }
}

// Ambil pengaturan dari database
$company_name = get_setting($conn, 'company_name', 'Nama Perusahaan Anda');
$company_address = get_setting($conn, 'company_address', 'Alamat Perusahaan');
$company_phone = get_setting($conn, 'company_phone', 'Telepon Perusahaan');
$company_email = get_setting($conn, 'company_email', 'Email Perusahaan');
$company_logo = get_setting($conn, 'company_logo', 'metisys_logo.png'); // Default logo jika tidak ada di DB
$lecturer_name = get_setting($conn, 'lecturer_name', 'Nama Dosen/Penanggung Jawab');
$lecturer_title = get_setting($conn, 'lecturer_title', 'Jabatan');
$lecturer_signature = get_setting($conn, 'lecturer_signature', ''); // Ini yang penting!

// --- Akhir penambahan kode pengaturan ---


// --- 1. FUNGSI GAUSSIAN ---
// Pastikan fungsi ini hanya didefinisikan sekali
if (!function_exists('hitung_gaussian')) {
    function hitung_gaussian($x, $mean, $std_dev) {
        // Tambahkan penanganan jika std_dev sangat kecil atau nol untuk menghindari pembagian nol
        if ($std_dev <= 0) {
            // Mengembalikan nilai yang sangat kecil untuk menghindari log(0) atau masalah lain
            return 0.000001;
        }
        $exp = exp(-pow($x - $mean, 2) / (2 * pow(max(0.000001, $std_dev), 2))); // Gunakan max untuk std_dev
        $res = (1 / (sqrt(2 * M_PI) * max(0.000001, $std_dev))) * $exp; // Gunakan max untuk std_dev
        // Pastikan hasil tidak nol untuk menghindari masalah perkalian
        return ($res <= 0) ? 0.000001 : $res;
    }
}

// --- 2. DATA PRIOR ---
$total_data = 0;
$total_layak = 0;
$total_tidak = 0;

if (isset($conn)) {
    $q_total = mysqli_query($conn, "SELECT COUNT(*) as total FROM data_latih");
    $total_data = mysqli_fetch_assoc($q_total)['total'];

    // Pastikan total_data tidak nol untuk menghindari pembagian nol
    if ($total_data > 0) {
        $total_layak = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM data_latih WHERE keputusan='Layak'"))['total'];
        $total_tidak = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM data_latih WHERE keputusan='Tidak Layak'"))['total'];
    }
}

// Hindari pembagian nol jika total_data adalah 0
$prior_layak = ($total_data > 0) ? $total_layak / $total_data : 0;
$prior_tidak = ($total_data > 0) ? $total_tidak / $total_data : 0;

// --- 3. PERHITUNGAN ALTERNATIF ---
$hasil_ranking = [];
if (isset($conn)) {
    $q_alt = mysqli_query($conn, "SELECT * FROM alternatif");

    while($alt = mysqli_fetch_assoc($q_alt)) {
        $id_alt = $alt['id_alternatif'];
        $P_L = $prior_layak;
        $P_T = $prior_tidak;
        $likelihood_data = [];

        $q_nilai = mysqli_query($conn, "SELECT n.nilai, k.nama_kriteria, k.tipe_kriteria, k.id_kriteria
                                        FROM penilaian n
                                        JOIN kriteria k ON n.id_kriteria = k.id_kriteria
                                        WHERE n.id_alternatif = '$id_alt'");

        while($n = mysqli_fetch_assoc($q_nilai)) {
            $val = $n['nilai']; $id_k = $n['id_kriteria'];
            $l_layak = 0.000001; // Default likelihood sangat kecil untuk menghindari nol
            $l_tidak = 0.000001; // Default likelihood sangat kecil untuk menghindari nol

            // Pastikan ada data latih untuk kriteria ini sebelum menghitung statistik
            $check_kriteria_layak = mysqli_query($conn, "SELECT COUNT(*) as c FROM data_latih WHERE id_kriteria='$id_k' AND keputusan='Layak'");
            $count_kriteria_layak = mysqli_fetch_assoc($check_kriteria_layak)['c'];

            $check_kriteria_tidak = mysqli_query($conn, "SELECT COUNT(*) as c FROM data_latih WHERE id_kriteria='$id_k' AND keputusan='Tidak Layak'");
            $count_kriteria_tidak = mysqli_fetch_assoc($check_kriteria_tidak)['c'];


            if ($n['tipe_kriteria'] == 'Numerik') { // Menggunakan 'Numerik' sesuai kriteria.php
                $val_numeric = konversiKeAngka($val);
                if ($count_kriteria_layak > 0) {
                    $s_l = mysqli_fetch_assoc(mysqli_query($conn, "SELECT AVG(nilai) as m, STDDEV(nilai) as s FROM data_latih WHERE id_kriteria='$id_k' AND keputusan='Layak'"));
                    // Pastikan std_dev tidak nol atau sangat kecil
                    $l_layak = hitung_gaussian($val_numeric, $s_l['m'], max(0.000001, (float)$s_l['s']));
                }
                if ($count_kriteria_tidak > 0) {
                    $s_t = mysqli_fetch_assoc(mysqli_query($conn, "SELECT AVG(nilai) as m, STDDEV(nilai) as s FROM data_latih WHERE id_kriteria='$id_k' AND keputusan='Tidak Layak'"));
                    // Pastikan std_dev tidak nol atau sangat kecil
                    $l_tidak = hitung_gaussian($val_numeric, $s_t['m'], max(0.000001, (float)$s_t['s']));
                }
            } else { // Kategorikal
                if ($total_layak > 0) { // Hanya hitung jika ada data layak
                    $f_l = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM data_latih WHERE id_kriteria='$id_k' AND nilai='$val' AND keputusan='Layak'"))['c'];
                    $l_layak = ($f_l + 1) / ($total_layak + 2); // Laplace smoothing
                }
                if ($total_tidak > 0) { // Hanya hitung jika ada data tidak layak
                    $f_t = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM data_latih WHERE id_kriteria='$id_k' AND nilai='$val' AND keputusan='Tidak Layak'"))['c'];
                    $l_tidak = ($f_t + 1) / ($total_tidak + 2); // Laplace smoothing
                }
            }
            $P_L *= $l_layak;
            $P_T *= $l_tidak;
            $likelihood_data[] = ['kriteria' => $n['nama_kriteria'], 'nilai' => $val, 'l_l' => $l_layak, 'l_t' => $l_tidak];
        }
        $hasil_ranking[] = [
            'id' => $id_alt, 'nama' => $alt['nama_alternatif'], 'p_l' => $P_L, 'p_t' => $P_T,
            'kep' => ($P_L >= $P_T) ? "Layak" : "Tidak Layak", 'detail' => $likelihood_data
        ];
    }
}
usort($hasil_ranking, function($a, $b) {
    if ($a['kep'] !== $b['kep']) {
        return ($a['kep'] === 'Layak') ? -1 : 1;
    }
    return $b['p_l'] <=> $a['p_l']; 
});
?>

<!DOCTYPE html>
<html>
<head>
    <title>Ranking SPK Naive Bayes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome untuk ikon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
            --success-green: #1cc88a;
        }

        body { font-family: 'Segoe UI', sans-serif; padding: 20px; background: #f8f9fa; color: var(--text-color); }
        h3 { color: #0d1435; font-weight: 700; margin-bottom: 20px; }

        /* Styling Tombol Cetak Laporan */
        .btn-print-action {
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

        .btn-print-action::before {
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

        .btn-print-action:hover::before {
            left: 100%;
        }

        .btn-print-action:hover {
            transform: translateY(-2px) scale(1.01);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        }

        /* Tabel Utama */
        .table-ranking {
            width: 100%;
            border-collapse: collapse;
            background: var(--white);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px var(--shadow-light);
        }

        .table-ranking thead th {
            background: #2c3e50; /* Warna biru gelap solid */
            color: var(--white);
            text-align: left;
            padding: 15px;
            border-bottom: 2px solid #34495e;
            font-size: 16px;
            font-weight: 700;
            vertical-align: middle;
        }

        .table-ranking tbody tr {
            transition: all 0.3s ease;
        }

        .table-ranking tbody tr:hover {
            background-color: #eef4f8;
        }

        .table-ranking td {
            padding: 15px;
            border-bottom: 1px solid #f1f1f1;
            font-size: 15px;
            vertical-align: middle;
        }

        /* Badge Keputusan */
        .badge-layak {
            background-color: #e6f4ea;
            color: #137333;
            padding: 5px 10px;
            border-radius: 5px;
        }
        .badge-tidak {
            background:#fce8e6;
            color:#a50e0e;
            padding: 5px 10px;
            border-radius: 5px;
        }

        /* Detail Perhitungan */
        .detail-row {
            background-color: #f1f3f5 !important;
        }
        .detail-row .table-sm th, .detail-row .table-sm td {
            padding: 8px;
            font-size: 0.9em;
        }
        .detail-row .table-secondary {
            background-color: #e9ecef !important;
            color: #495057;
        }
        .detail-row .table-warning {
            background-color: #fff3cd !important;
        }

        /* Print-specific styles */
        .print-only {
            display: none; /* Hidden by default */
        }

        @media print {
            .badge-layak, 
            .badge-tidak {
                background: none !important;
                color: #000 !important;
                padding: 0 !important;
                border-radius: 0 !important;
                font-weight: normal !important;
            }
            /* Menghilangkan header/footer bawaan browser dan mengatur ukuran kertas */
            @page {
                margin: 0; 
                size: A4 portrait; /* Mengatur ukuran kertas A4 potret */
            }

            body {
                background: none !important;
                padding: 0;
                color: black;
                /* Mengatur margin kustom untuk konten di dalam halaman */
                margin: 20mm 25mm; /* Atas/Bawah 20mm, Kiri/Kanan 25mm */
                -webkit-print-color-adjust: exact; /* Memastikan warna latar belakang dicetak */
                print-color-adjust: exact;
            }
            .no-print {
                display: none !important;
            }
            .print-only {
                display: block !important; /* Tampilkan hanya saat cetak */
            }

            /* Kop Surat */
            .print-header {
                text-align: center;
                margin-bottom: 30px;
                padding-bottom: 15px;
                position: relative;
            }
            .print-header img {
                max-width: 150px; /* Ukuran logo lebih besar di cetakan */
                height: auto;
                margin-bottom: 15px; /* Jarak lebih besar di bawah logo */
            }
            .print-header h4 {
                margin: 0;
                font-size: 1.8em; /* Nama perusahaan lebih besar */
                color: #333;
            }
            .print-header p {
                margin: 2px 0;
                font-size: 1em; /* Teks sedikit lebih besar */
                color: #555;
            }
            /* Garis pemisah di bawah header */
            .print-header::after {
                content: '';
                display: block;
                width: 100%;
                height: 1px; /* Ketebalan garis */
                background-color: #333; /* Warna garis */
                margin-top: 10px;
                margin-bottom: 20px; /* Jarak antara garis dan konten berikutnya */
            }


            /* Tanda Tangan Dosen */
            .print-footer {
                margin-top: 60px; /* Jarak lebih besar di atas footer */
                text-align: right;
                padding-right: 0;
            }
            .print-footer p {
                margin: 5px 0;
                line-height: 1.5;
            }
            .print-footer .signature-space {
                height: 70px;
                border-bottom: 1px dashed #aaa;
                width: 250px; /* Lebar konsisten */
                margin: 10px 0 5px auto;
                display: block;
            }
            .print-footer .print-signature-image {
                max-width: 200px; /* Sesuaikan lebar maksimum tanda tangan */
                height: auto;
                display: block;
                margin: 10px 0 5px auto;
            }
            .print-footer .lecturer-name {
                font-weight: bold;
                text-decoration: underline;
                display: block;
            }
            .print-footer .lecturer-title {
                display: block;
            }


            /* Penyesuaian tabel untuk cetak */
            .table-responsive-wrapper {
                width: 100%; /* Pastikan wrapper tabel mengambil lebar penuh */
                box-sizing: border-box;
            }
            .table-ranking, .detail-row .table-sm {
                border: 1px solid #ccc;
                width: 100%; /* Pastikan tabel mengambil lebar penuh di cetakan */
                min-width: unset;
                margin-bottom: 20px; /* Jarak setelah tabel */
            }
            .table-ranking th, .table-ranking td,
            .detail-row .table-sm th, .detail-row .table-sm td {
                border-color: #ddd !important;
                color: black !important;
                padding: 10px; /* Padding sel tabel sedikit lebih besar */
            }

            /* Warna tabel utama di cetakan */
            .table-ranking thead th {
                background: #e9ecef !important;
                color: #333 !important;
                border-bottom: 2px solid #ced4da !important;
            }
            .table-ranking tbody tr:nth-child(even) {
                background-color: #f8f9fa !important;
            }
            .table-ranking tbody tr:hover {
                background-color: transparent !important;
            }

            /* Warna tabel detail di cetakan */
            .detail-row {
                background-color: #f1f3f5 !important;
            }
            .detail-row .table-secondary {
                background-color: #dee2e6 !important;
                color: #333 !important;
            }
            .detail-row .table-warning {
                background-color: #ffeeba !important;
                color: #333 !important;
            }

            /* Pastikan elemen tidak terpotong antar halaman */
            .table-ranking, .detail-row {
                page-break-inside: avoid;
            }
            .table-ranking thead {
                display: table-header-group;
            }
            .table-ranking tr {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>

<div class="container-fluid">
    <!-- Kop Surat (Hanya Muncul Saat Cetak) -->
    <div class="print-only print-header">
        <img src="images/<?= htmlspecialchars($company_logo) ?>" alt="Logo Perusahaan">
        <h4><?= htmlspecialchars($company_name) ?></h4>
        <p><?= htmlspecialchars($company_address) ?></p>
        <p>Telepon: <?= htmlspecialchars($company_phone) ?> | Email: <?= htmlspecialchars($company_email) ?></p>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <h3>Hasil Perhitungan & Ranking</h3>
        <button class="btn-print-action" onclick="window.print()">
            <i class="fas fa-print"></i> Cetak Laporan
        </button>
    </div>

    <div class="table-responsive-wrapper"> <!-- Wrapper untuk tabel responsif -->
        <table class="table-ranking">
            <thead>
                <tr class="text-center">
                    <th>Rank</th>
                    <th>Nama Alternatif</th>
                    <th>P(Layak)</th>
                    <th>P(Tidak)</th>
                    <th>Keputusan</th>
                    <th class="no-print">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php $no=1; foreach($hasil_ranking as $row): ?>
                <tr class="align-middle">
                    <td class="text-center"><?= $no++ ?></td>
                    <td><strong><?= $row['nama'] ?></strong></td>
                    <td><?= number_format($row['p_l'], 5) ?></td>
                    <td><?= number_format($row['p_t'], 5) ?></td>
                    <td class="text-center">
                        <span class="<?= $row['kep'] == 'Layak' ? 'badge-layak' : 'badge-tidak' ?>">
                            <?= $row['kep'] ?>
                        </span>
                    </td>
                    <td class="text-center no-print">
                        <button class="btn btn-sm btn-dark" type="button" data-bs-toggle="collapse" data-bs-target="#detail<?= $row['id'] ?>" aria-expanded="false" aria-controls="detail<?= $row['id'] ?>">
                            Detail Perhitungan <i class="fas fa-chevron-down"></i>
                        </button>
                    </td>
                </tr>

                <tr class="collapse no-print detail-row" id="detail<?= $row['id'] ?>">
                    <td colspan="6">
                        <div class="p-3">
                            <h6 class="fw-bold">Rincian Likelihood: <?= $row['nama'] ?></h6>

                            <table class="table table-sm table-bordered bg-light">
                                <thead class="table-secondary">
                                    <tr>
                                        <th>Kriteria</th>
                                        <th>Nilai Input</th>
                                        <th>Likelihood (Layak)</th>
                                        <th>Likelihood (Tidak)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($row['detail'] as $d): ?>
                                    <tr>
                                        <td><?= $d['kriteria'] ?></td>
                                        <td><?= $d['nilai'] ?></td>
                                        <td><?= number_format($d['l_l'], 8) ?></td>
                                        <td><?= number_format($d['l_t'], 8) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="2">Prior Probability</th>
                                        <td><?= number_format($prior_layak, 8) ?></td>
                                        <td><?= number_format($prior_tidak, 8) ?></td>
                                    </tr>
                                    <tr class="table-warning">
                                        <th colspan="2">Skor Akhir (Posterior)</th>
                                        <td><strong><?= number_format($row['p_l'], 10) ?></strong></td>
                                        <td><strong><?= number_format($row['p_t'], 10) ?></strong></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Tanda Tangan Dosen (Hanya Muncul Saat Cetak) -->
    <div class="print-only print-footer">
        <p>Bekasi, <?= date('d F Y') ?></p>
        <p>Mengetahui,</p>
        <?php
        $signature_path = 'uploads/signatures/' . $lecturer_signature;
        if (!empty($lecturer_signature) && file_exists($signature_path)):
        ?>
            <img src="<?= htmlspecialchars($signature_path) ?>" alt="Tanda Tangan Digital" class="print-signature-image">
        <?php else: ?>
            <div class="signature-space"></div> <!-- Ruang untuk tanda tangan jika tidak ada gambar -->
        <?php endif; ?>
        <p class="lecturer-name"><?= htmlspecialchars($lecturer_name) ?></p>
        <p class="lecturer-title"><?= htmlspecialchars($lecturer_title) ?></p>
    </div>

</div>
 
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>