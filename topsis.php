<?php
include_once __DIR__ . '/config.php';
if (!isset($conn) && isset($koneksi)) { $conn = $koneksi; }

// --- 1. SETTINGS & HELPERS ---
if (!function_exists('get_setting')) {
    function get_setting($conn, $name, $default = '') {
        if (!$conn) return $default;
        $name = mysqli_real_escape_string($conn, $name);
        $q = mysqli_query($conn, "SELECT setting_value FROM app_settings WHERE setting_name = '$name'");
        if ($q && mysqli_num_rows($q) > 0) return mysqli_fetch_assoc($q)['setting_value'];
        return $default;
    }
}

if (!function_exists('konversiKeAngka')) {
    function konversiKeAngka($nilai, $sifat = 'benefit') {
        $map = ["Sangat Baik" => 1, "Baik" => 2, "Cukup" => 3, "Buruk" => 4, "Sangat Buruk" => 5];
        $angka = is_numeric($nilai) ? (float)$nilai : ($map[$nilai] ?? 0.0);
        if (strtolower($sifat) == 'cost') { return (6 - $angka); }
        return $angka;
    }
}

// Ambil Data Pengaturan untuk Laporan
$company_logo    = get_setting($conn, 'company_logo', 'logo.png');
$company_name    = get_setting($conn, 'company_name', 'Nama Perusahaan');
$company_address = get_setting($conn, 'company_address', 'Alamat Perusahaan');
$company_phone   = get_setting($conn, 'company_phone', '000-000');
$company_email   = get_setting($conn, 'company_email', 'email@perusahaan.com');
$lecturer_name   = get_setting($conn, 'lecturer_name', 'Penanggung Jawab');
$lecturer_title  = get_setting($conn, 'lecturer_title', 'Jabatan');
$lecturer_signature = get_setting($conn, 'lecturer_signature', '');

// --- 2. PROSES TOPSIS ---
$kriteria = [];
$q_kri = mysqli_query($conn, "SELECT * FROM kriteria ORDER BY id_kriteria ASC");
while($k = mysqli_fetch_assoc($q_kri)) {
    $kriteria[$k['id_kriteria']] = ['nama' => $k['nama_kriteria'], 'bobot' => (float)$k['bobot'], 'sifat' => strtolower($k['sifat'])];
}

$alternatif = [];
$matriks_x = [];
$matriks_topsis = [];
$q_alt = mysqli_query($conn, "SELECT * FROM alternatif");
while($alt = mysqli_fetch_assoc($q_alt)) {
    $id_alt = $alt['id_alternatif'];
    $alternatif[$id_alt] = $alt['nama_alternatif'];
    foreach($kriteria as $id_k => $k_info) {
        $q_n = mysqli_query($conn, "SELECT nilai FROM penilaian WHERE id_alternatif='$id_alt' AND id_kriteria='$id_k'");
        $dn = mysqli_fetch_assoc($q_n);
        $nilai_raw = $dn['nilai'] ?? 0;
        $matriks_x[$id_alt][$id_k] = $nilai_raw;
        $matriks_topsis[$id_alt][$id_k] = konversiKeAngka($nilai_raw, $k_info['sifat']);
    }
}

// Langkah 1 & 2: Normalisasi Terbobot
$pembagi = [];
foreach($kriteria as $id_k => $v) {
    $ms = 0;
    foreach($alternatif as $id_alt => $nama) { $ms += pow($matriks_topsis[$id_alt][$id_k], 2); }
    $pembagi[$id_k] = ($ms > 0) ? sqrt($ms) : 1;
}

$matriks_y = [];
foreach($alternatif as $id_alt => $nama) {
    foreach($kriteria as $id_k => $v) {
        $matriks_y[$id_alt][$id_k] = ($matriks_topsis[$id_alt][$id_k] / $pembagi[$id_k]) * $v['bobot'];
    }
}

// Langkah 3: Solusi Ideal Positif & Negatif
$ideal_pos = []; $ideal_neg = [];
foreach($kriteria as $id_k => $v) {
    $kolom_y = array_column($matriks_y, $id_k);
    if ($v['sifat'] == 'benefit') {
        $ideal_pos[$id_k] = max($kolom_y); $ideal_neg[$id_k] = min($kolom_y);
    } else {
        $ideal_pos[$id_k] = min($kolom_y); $ideal_neg[$id_k] = max($kolom_y);
    }
}

// Langkah 4 & 5: Jarak Euclidean & Nilai Preferensi
$hasil_topsis = [];
foreach($alternatif as $id_alt => $nama) {
    $d_pos = 0; $d_neg = 0;
    foreach($kriteria as $id_k => $v) {
        $d_pos += pow($matriks_y[$id_alt][$id_k] - $ideal_pos[$id_k], 2);
        $d_neg += pow($matriks_y[$id_alt][$id_k] - $ideal_neg[$id_k], 2);
    }
    $dp = sqrt($d_pos); $dn = sqrt($d_neg);
    $v_i = ($dp + $dn != 0) ? $dn / ($dp + $dn) : 0;
    $hasil_topsis[] = ['id' => $id_alt, 'nama' => $nama, 'v' => $v_i, 'dp' => $dp, 'dn' => $dn, 'nilai_awal' => $matriks_x[$id_alt]];
}
usort($hasil_topsis, fn($a, $b) => $b['v'] <=> $a['v']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Ranking TOPSIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-blue: #1a73e8;
            --dark-blue: #0d47a1;
            --white: #ffffff;
            --text-color: #333;
        }

        body { font-family: 'Segoe UI', sans-serif; background: #f8f9fa; color: var(--text-color); padding: 20px; font-size: 0.9rem; }
        
        .btn-theme {
            background: linear-gradient(45deg, var(--primary-blue), var(--dark-blue));
            color: white !important; border: none; border-radius: 8px;
            padding: 10px 20px; font-weight: 600; transition: 0.3s;
        }
        .btn-theme:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }

        .table-ranking {
            background: white; border-collapse: separate; border-spacing: 0;
            border-radius: 10px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        .table-ranking thead th {
            background: #2c3e50; color: white; padding: 15px; border: none; font-weight: 600;
        }
        .table-ranking td { padding: 12px 15px; border-bottom: 1px solid #f1f1f1; vertical-align: middle; }
        .table-ranking tbody tr:hover { background-color: #f8fbff; }

        /* Print-specific Styles */
        .print-only { display: none; }
        @media print {
            .no-print { display: none !important; }
            .print-only { display: block !important; }
            body { background: white; padding: 0; margin: 20mm 25mm; }
            .table-ranking { box-shadow: none; border: 1px solid #ddd; }
            .print-header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 10px; }
            .print-header img { max-width: 120px; margin-bottom: 10px; }
            .print-footer { margin-top: 50px; text-align: right; }
            .signature-space { height: 80px; }
        }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="print-only print-header">
        <img src="images/<?= htmlspecialchars($company_logo) ?>" alt="Logo">
        <h4 class="m-0 fw-bold"><?= htmlspecialchars($company_name) ?></h4>
        <p class="m-0"><?= htmlspecialchars($company_address) ?></p>
        <p class="small">Telepon: <?= htmlspecialchars($company_phone) ?> | Email: <?= htmlspecialchars($company_email) ?></p>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <h4 class="fw-bold text-dark m-0">Hasil Perangkingan TOPSIS</h4>
        <button class="btn-theme" onclick="window.print()">
            <i class="fas fa-print me-2"></i>Cetak Laporan
        </button>
    </div>

    

    <div class="table-responsive">
        <table class="table table-ranking w-100">
            <thead>
                <tr class="text-center">
                    <th width="60">Rank</th>
                    <th class="text-start">Nama Penerima</th>
                    <th>Jarak Positif (D+)</th>
                    <th>Jarak Negatif (D-)</th>
                    <th>Skor Preferensi (V)</th>
                    <th class="no-print">Nilai</th>
                </tr>
            </thead>
            <tbody>
                <?php $no=1; foreach($hasil_topsis as $row): ?>
                <tr class="text-center">
                    <td><?= $no++ ?></td>
                    <td class="text-start fw-bold"><?= $row['nama'] ?></td>
                    <td class="text-muted"><?= number_format($row['dp'], 4) ?></td>
                    <td class="text-muted"><?= number_format($row['dn'], 4) ?></td>
                    <td class="text-primary fw-bold" style="font-size: 1.05rem;"><?= number_format($row['v'], 5) ?></td>
                    <td class="no-print">
                        <button class="btn btn-sm btn-outline-dark" type="button" data-bs-toggle="collapse" data-bs-target="#dtl<?= $row['id'] ?>">
                            <i class="fas fa-eye"></i>
                        </button>
                    </td>
                </tr>
                <tr class="collapse no-print" id="dtl<?= $row['id'] ?>" style="background: #fafafa;">
                    <td colspan="6">
                        <div class="p-3">
                            <table class="table table-sm table-bordered bg-white m-0" style="font-size: 11px;">
                                <tr class="table-light">
                                    <?php foreach($kriteria as $k): ?>
                                        <th class="text-center"><?= $k['nama'] ?></th>
                                    <?php endforeach; ?>
                                </tr>
                                <tr class="text-center">
                                    <?php foreach($kriteria as $id_k => $k): ?>
                                        <td><?= htmlspecialchars($row['nilai_awal'][$id_k]) ?></td>
                                    <?php endforeach; ?>
                                </tr>
                            </table>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="print-only print-footer">
        <p>Dicetak pada: <?= date('d F Y') ?></p>
        <p>Mengetahui,</p>
        <?php 
        $sig_path = 'uploads/signatures/' . $lecturer_signature;
        if (!empty($lecturer_signature) && file_exists($sig_path)): ?>
            <img src="<?= $sig_path ?>" style="max-width: 150px; margin: 10px 0;">
        <?php else: ?>
            <div class="signature-space"></div>
        <?php endif; ?>
        <p class="fw-bold mb-0" style="text-decoration: underline;"><?= htmlspecialchars($lecturer_name) ?></p>
        <p><?= htmlspecialchars($lecturer_title) ?></p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>