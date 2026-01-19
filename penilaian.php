<?php
// --- 1. LOGIKA PHP (PROSES CRUD & NOTIFIKASI) ---
$status = isset($_GET['status']) ? $_GET['status'] : '';

// Proses Simpan/Update Penilaian
if (isset($conn) && isset($_POST['save_penilaian'])) {
    $id_alt = $_POST['id_alternatif'];
    $nilai_input = $_POST['nilai']; 

    foreach ($nilai_input as $id_kri => $val) {
        $val = mysqli_real_escape_string($conn, $val);
        // Cek apakah sudah ada nilainya sebelumnya
        $check = mysqli_query($conn, "SELECT id_penilaian FROM penilaian WHERE id_alternatif = '$id_alt' AND id_kriteria = '$id_kri'");
        
        if (mysqli_num_rows($check) > 0) {
            mysqli_query($conn, "UPDATE penilaian SET nilai = '$val' WHERE id_alternatif = '$id_alt' AND id_kriteria = '$id_kri'");
        } else {
            mysqli_query($conn, "INSERT INTO penilaian (id_alternatif, id_kriteria, nilai) VALUES ('$id_alt', '$id_kri', '$val')");
        }
    }
    echo "<script>window.location.href='dashboard.php?section=content-spk-penilaian&status=success';</script>";
    exit();
}

// Proses Hapus Penilaian
if (isset($conn) && isset($_GET['delete_penilaian_alt'])) {
    $id = $_GET['delete_penilaian_alt'];
    mysqli_query($conn, "DELETE FROM penilaian WHERE id_alternatif = '$id'");
    echo "<script>window.location.href='dashboard.php?section=content-spk-penilaian&status=deleted';</script>";
    exit();
}
?>
<style>
.inner-penilaian-content {
    width: 100%;
    color: #333;
}
.top-action-bar {
    display: flex;
    align-items: stretch;
    margin-bottom: 25px;
    background: #f8f9fa;
    border-radius: 12px;
    border: 1px solid #eee;
    overflow: hidden;
}

.search-section, .add-section {
    flex: 1; 
    padding: 20px;
}

.add-section {
    border-left: 1px solid #ddd;
}

/* --- PERUBAHAN: TOMBOL NAVY GLOSSY (SEKARANG SEPERTI btn-primary-glow) --- */
.btn-navy-glossy {
    background: linear-gradient(45deg, var(--primary-blue), var(--dark-blue)); /* Gradasi warna dari profile */
    color: var(--white) !important; /* Warna teks putih */
    padding: 12px 25px; /* Padding konsisten dengan tombol keren lainnya */
    border-radius: 8px;
    border: none;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease; /* Transisi untuk semua properti */
    font-size: 1em;
    white-space: nowrap;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15); /* Shadow yang lebih lembut */
    position: relative; /* Penting untuk pseudo-element ::before */
    overflow: hidden; /* Penting untuk pseudo-element ::before */
    text-shadow: none; /* Hapus text-shadow */
}

.btn-navy-glossy::before {
    content: "";
    position: absolute;
    top: 0;
    left: -100%; /* Mulai dari luar kiri */
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.2); /* Efek kilau putih transparan */
    transform: skewX(-30deg); /* Membuat efek miring */
    transition: all 0.5s ease;
}

.btn-navy-glossy:hover::before {
    left: 100%; /* Geser ke luar kanan saat hover */
}

.btn-navy-glossy:hover {
    transform: translateY(-2px) scale(1.01); /* Efek lift dan sedikit membesar */
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2); /* Shadow lebih kuat saat hover */
}
/* ----------------------------------------------- */


/* Custom Input (untuk search dan select di top bar) - SESUAI GAMBAR */
.custom-input {
    width: 100%; 
    padding: 11px; /* Disesuaikan agar tingginya cocok dengan tombol */
    border-radius: 8px; 
    border: 1px solid #ccc; 
    outline: none; 
    margin-top: 5px;
    font-size: 1em; /* Konsisten dengan kriteria.php */
    transition: all 0.3s ease;
    box-sizing: border-box; /* Penting agar padding tidak menambah lebar/tinggi */
}
.custom-input:focus {
    border-color: var(--primary-blue);
    box-shadow: 0 0 0 3px rgba(26, 115, 232, 0.2);
}

/* --- PERUBAHAN: Wrapper untuk input search dengan ikon --- */
.search-input-wrapper {
    position: relative;
    margin-top: 5px; /* Sama dengan margin-top custom-input */
}

.search-input-wrapper .custom-input {
    padding-left: 40px; /* Ruang untuk ikon */
}

.search-input-wrapper .search-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #999;
    font-size: 1.1em;
}
/* ---------------------------------------------------- */


/* Notifikasi Alert */
.alert-notif {
    padding: 15px; margin-bottom: 20px; border-radius: 8px; font-weight: 500;
    display: flex; justify-content: space-between; align-items: center;
}
.alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
.alert-deleted { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }


/* Tabel Minimalis - KONSISTEN DENGAN KRITERIA.PHP */
.table-penilaian {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08); /* Tambah shadow untuk efek modern */
}

/* Header Tabel Profesional (tanpa gradasi) - KONSISTEN DENGAN KRITERIA.PHP */
.table-penilaian thead th {
    background: #2c3e50; /* Warna biru gelap solid, lebih profesional */
    color: white; /* Teks putih */
    text-align: left;
    padding: 15px;
    border-bottom: 2px solid #34495e; /* Border bawah sedikit lebih gelap */
    font-size: 16px; /* PERBESAR FONT HEADER */
    font-weight: 700; /* Lebih tebal */
    font-style: normal; /* --- PERBAIKAN: Hapus italic --- */
    text-shadow: none; /* Menghilangkan bayangan teks */
}

.table-penilaian td {
    padding: 15px;
    border-bottom: 1px solid #f1f1f1;
    font-size: 15px; /* PERBESAR FONT ISI TABEL */
    vertical-align: middle;
}

/* --- PERUBAHAN: Gaya untuk nama alternatif di tabel body --- */
.table-penilaian tbody td.alt-name-col {
    font-weight: bold;
    font-style: italic;
}
/* ---------------------------------------------------------- */

/* Pengaturan lebar kolom spesifik untuk simetri dan penggunaan ruang yang lebih baik */
.table-penilaian th:nth-child(1), .table-penilaian td:nth-child(1) { width: 40px; text-align: center; } /* No */
/* Kolom 'Nama Alternatif' (nth-child(2)) akan mengisi sisa ruang secara otomatis */
.table-penilaian th:last-child, .table-penilaian td:last-child { width: 100px; text-align: center; } /* Aksi */


/* EFEK HOVER PADA BARIS TABEL - KONSISTEN DENGAN KRITERIA.PHP */
.table-penilaian tbody tr {
    transition: all 0.3s ease; /* Transisi halus untuk efek hover */
}

.table-penilaian tbody tr:hover {
    background-color: #eef4f8; /* Warna latar belakang saat hover */
    transform: scale(1.005); /* Sedikit membesar */
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); /* Shadow lebih terlihat saat hover */
    cursor: pointer; /* Menunjukkan bahwa baris dapat diklik/interaktif */
}

/* STYLING BARU UNTUK IKON AKSI - KONSISTEN DENGAN KRITERIA.PHP */
.table-penilaian td:last-child { /* Target sel terakhir (aksi) */
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 5px; /* Jarak antar ikon */
}

.action-icon-btn {
    background: none;
    border: none;
    padding: 8px; /* Padding untuk area klik yang lebih besar */
    border-radius: 50%; /* Bentuk lingkaran */
    cursor: pointer;
    font-size: 1.1em; /* PERBESAR UKURAN IKON */
    transition: all 0.3s ease;
    display: inline-flex; /* Agar ikon di tengah */
    align-items: center;
    justify-content: center;
    width: 36px; /* Lebar dan tinggi tetap */
    height: 36px;
}

.action-icon-btn i {
    font-size: 1.1em; /* Pastikan ikon di dalamnya juga besar */
}

.edit-btn {
    color: #1a2b4a; /* Menggunakan warna #1a2b4a agar senada */
}

.edit-btn:hover {
    background-color: rgba(26, 43, 74, 0.1); /* Latar belakang transparan dari warna senada */
    color: #0f1c30; /* Warna sedikit lebih gelap saat hover */
    transform: translateY(-1px);
}

.delete-btn {
    color: #e74a3b; /* Warna merah */
}

.delete-btn:hover {
    background-color: rgba(231, 76, 60, 0.1); /* Latar belakang merah muda saat hover */
    color: var(--error-red); /* Menggunakan variabel CSS dari style.css utama */
    transform: translateY(-1px);
}

/* Modal Styling - Rata Kiri & Navy - KONSISTEN DENGAN KRITERIA.PHP */
.modal-navy-content.logout-modal-content { /* Menargetkan kombinasi kelas */
    background: linear-gradient(135deg, #0d1435 0%, #1a237e 100%) !important;
    color: white !important;
    border-radius: 20px !important;
    padding: 30px !important;
    text-align: left !important; /* Rata Kiri */
    max-width: 450px !important;
    /* Pastikan tidak ada efek translateY awal dari logout modal */
    transform: translateY(0) !important;
    opacity: 1 !important; /* Pastikan terlihat */
}

.form-group-penilaian {
    margin-bottom: 15px;
    text-align: left;
}

.form-group-penilaian label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: rgba(255,255,255,0.9);
}

.input-navy { /* Digunakan untuk input/select di dalam modal */
    width: 100%;
    padding: 12px;
    background: rgba(255,255,255,0.15);
    border: 1px solid rgba(255,255,255,0.3);
    border-radius: 8px;
    color: white !important;
    box-sizing: border-box;
    outline: none;
    font-size: 1em;
    caret-color: white;
}

.input-navy option {
    background: white;
    color: #1a237e !important;
}

@media screen and (-webkit-min-device-pixel-ratio:0) {
    .input-navy option {
        color: #333; /* Warna gelap untuk opsi dropdown jika backgroundnya putih */
        background-color: white;
    }
}
.input-navy::-webkit-outer-spin-button,
.input-navy::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

.input-navy::placeholder { color: rgba(255,255,255,0.4); }

/* ======================================= / / MODAL OVERLAY & BLUR FIX / / ======================================= */

/* Overlay modal */
.logout-modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.75); /* Overlay lebih gelap */
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 2000;
  opacity: 0;
  visibility: hidden;
  transition: opacity 0.3s ease, visibility 0.3s ease, backdrop-filter 0.3s ease;
}

/* Ketika modal ditampilkan, terapkan blur pada latar belakang */
.logout-modal-overlay.show {
  opacity: 1;
  visibility: visible;
  backdrop-filter: blur(5px); /* Efek blur pada background di balik modal */
}

/* PENTING: HAPUS filter dari body.modal-open dan dashboard-wrapper.modal-active-bg */
body.modal-open {
  overflow: hidden; /* Mencegah scrolling saat modal terbuka */
  /* filter: blur(3px) brightness(0.7); <--- BARIS INI DIHAPUS */
  transition: none; /* Hapus transisi filter dari body */
}

.dashboard-wrapper.modal-active-bg {
  /* filter: blur(3px) brightness(0.7); */ /* BARIS INI DIHAPUS */
}

/* --- STYLING KHUSUS UNTUK TOMBOL DI MODAL PENILAIAN - KONSISTEN DENGAN KRITERIA.PHP --- */
.modal-navy-content .btn-navy-grad[type="submit"] {
    background: #2c3e50 !important; /* Warna sesuai header tabel penilaian */
    padding: 10px 25px !important; /* Padding konsisten */
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2); /* Shadow awal */
}

.modal-navy-content .btn-navy-grad[type="submit"]:hover {
    background: #233342 !important; /* Sedikit lebih gelap saat hover */
    transform: translateY(-1px);
    box-shadow: 0 6px 15px rgba(0, 0, 0, 0.3); /* Shadow lebih kuat saat hover */
}

.modal-navy-content button[type="button"] { /* Tombol "Batal" */
    padding: 10px 25px !important; /* Padding konsisten */
}

/* --- TOMBOL NAVY GRADIENT (DIGUNAKAN DI MODAL) --- */
/* Ini adalah gaya umum untuk tombol gradien, digunakan oleh tombol submit di modal */
.btn-navy-grad {
    background: linear-gradient(135deg, #0d1435 0%, #1a237e 100%);
    color: white !important;
    padding: 12px 25px; /* PERBESAR PADDING */
    border-radius: 8px;
    border: none;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: 0.3s;
    font-size: 1em; /* PERBESAR FONT */
    white-space: nowrap; /* Mencegah teks tombol patah */
}

.btn-navy-grad:hover {
    opacity: 0.9;
    transform: translateY(-1px);
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
}
/* -------------------------------------------------- */


/* ======================================= / / RESPONSIVE ADJUSTMENTS / / ======================================= */

@media (max-width: 768px) {
    .top-action-bar {
        flex-direction: column;
    }
    .add-section {
        border-left: none; /* Hapus border kiri di mobile */
        border-top: 1px solid #ddd; /* Tambah border atas sebagai pemisah */
    }
    .custom-input {
        max-width: 100%;
    }
    .btn-navy-glossy {
        width: 100%;
        justify-content: center;
    }
    .table-penilaian th, .table-penilaian td {
        padding: 10px;
        font-size: 14px;
    }
    .table-penilaian td:last-child { /* Sel aksi */
        flex-wrap: wrap; /* Izinkan ikon untuk wrap jika terlalu sempit */
        justify-content: center;
    }
    .action-icon-btn {
        width: 32px;
        height: 32px;
        font-size: 1em;
    }
    .action-icon-btn i {
        font-size: 1em;
    }
    .search-input-wrapper .search-icon { /* Pastikan ikon search tetap di tengah */
        font-size: 1em; /* Sesuaikan ukuran ikon jika perlu */
    }
}

@media (max-width: 480px) {
    .table-penilaian th, .table-penilaian td {
        font-size: 13px;
    }
}
</style>

<div class="inner-penilaian-content">
    
    <?php if($status == 'success'): ?>
        <div class="alert-notif alert-success"> Data berhasil disimpan! <span onclick="this.parentElement.style.display='none'" style="cursor:pointer">&times;</span></div>
    <?php elseif($status == 'deleted'): ?>
        <div class="alert-notif alert-deleted"> Data berhasil dihapus! <span onclick="this.parentElement.style.display='none'" style="cursor:pointer">&times;</span></div>
    <?php endif; ?>

    <!-- TOP ACTION BAR - SESUAI GAMBAR -->
    <div class="top-action-bar">
        <div class="search-section">
            <label style="font-weight: bold; color: #0d1435;">Cari Data</label>
            <div class="search-input-wrapper"> <!-- --- PERUBAHAN: Wrapper untuk input search --- -->
                <i class="fas fa-search search-icon"></i> <!-- --- PERUBAHAN: Ikon search --- -->
                <input type="text" id="penilaianSearch" class="custom-input" placeholder="Ketik nama alternatif..." onkeyup="filterPenilaian()">
            </div>
        </div>

        <div class="add-section">
            <label style="font-weight: bold; color: #0d1435;">Input Nilai Baru</label>
            <div style="display: flex; gap: 10px; align-items: center; margin-top: 5px;"> <!-- --- PERBAIKAN: align-items: center --- -->
                <select id="selectAlt" class="custom-input" style="flex: 1;">
                    <option value="">-- Pilih Alternatif --</option>
                    <?php
                    if (isset($conn)) {
                        $list_alt = mysqli_query($conn, "SELECT * FROM alternatif ORDER BY nama_alternatif ASC");
                        while($la = mysqli_fetch_assoc($list_alt)) {
                            echo "<option value='".$la['id_alternatif']."'>".$la['nama_alternatif']."</option>";
                        }
                    }
                    ?>
                </select>
                <button type="button" class="btn-navy-glossy" onclick="openAddFromDropdown()">
                    <i class="fas fa-plus"></i> Input
                </button>
            </div>
        </div>
    </div>

    <table class="table-penilaian" id="tablePenilaian">
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Alternatif</th>
                <?php
                $kriteria_ids = [];
                if (isset($conn)) {
                    $kri_h = mysqli_query($conn, "SELECT id_kriteria, kode_kriteria FROM kriteria ORDER BY kode_kriteria ASC");
                    while($h = mysqli_fetch_assoc($kri_h)) { 
                        echo "<th>".$h['kode_kriteria']."</th>"; 
                        $kriteria_ids[] = $h['id_kriteria'];
                    }
                }
                ?>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if (isset($conn)) {
                $alt_res = mysqli_query($conn, "SELECT * FROM alternatif ORDER BY nama_alternatif ASC");
                $no = 1;
                $has_data = false;
                while($alt = mysqli_fetch_assoc($alt_res)):
                    $id_a = $alt['id_alternatif'];
                    $check_data = mysqli_query($conn, "SELECT id_penilaian FROM penilaian WHERE id_alternatif='$id_a' LIMIT 1");
                    if(mysqli_num_rows($check_data) == 0) continue;
                    $has_data = true;
                ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td class="alt-name-col"><?= $alt['nama_alternatif'] ?></td> <!-- --- PERUBAHAN: Tambah class alt-name-col --- -->
                    <?php
                    $current_values = [];
                    foreach($kriteria_ids as $id_k) {
                        $val_q = mysqli_query($conn, "SELECT nilai FROM penilaian WHERE id_alternatif='$id_a' AND id_kriteria='$id_k'");
                        $val_d = mysqli_fetch_assoc($val_q);
                        $nilai = $val_d['nilai'] ?? '-';
                        $current_values[$id_k] = $nilai;
                        echo "<td>$nilai</td>";
                    }
                    $json_values = htmlspecialchars(json_encode($current_values));
                    ?>
                    <td>
                        <button type="button" class="action-icon-btn edit-btn" onclick="editWithValues('<?= $id_a ?>', '<?= $alt['nama_alternatif'] ?>', '<?= $json_values ?>')">
                            <i class="fas fa-edit"></i>
                        </button>
                        <a href="dashboard.php?section=content-spk-penilaian&delete_penilaian_alt=<?= $id_a ?>"
                           onclick="return confirm('Hapus semua penilaian untuk alternatif ini?')" class="action-icon-btn delete-btn">
                            <i class="fas fa-trash"></i>
                        </a>
                    </td>
                </tr>
                <?php endwhile;
                if (!$has_data): ?>
                <tr>
                    <td colspan="<?= 3 + count($kriteria_ids) ?>" style="text-align: center; color: #999; padding: 30px;">Belum ada data penilaian.</td>
                </tr>
                <?php endif;
            } else {
                echo "<tr><td colspan='". (3 + count($kriteria_ids)) ."'>Koneksi database tidak tersedia atau tabel penilaian kosong.</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>

<div id="modalPenilaian" class="logout-modal-overlay">
    <div class="modal-navy-content logout-modal-content">
        <h3 id="modalPenilaianTitle" style="margin-top: 0; margin-bottom: 25px;">Input Nilai: <span id="altNameDisp"></span></h3>
        
        <form action="" method="POST">
            <input type="hidden" name="id_alternatif" id="modal_alt_id">
            
            <div id="dynamicInputs">
                <?php
                if (isset($conn)) {
                    // Fetch data kriteria diluar while agar tidak infinite loop
                    $q_modal_kri = mysqli_query($conn, "SELECT * FROM kriteria ORDER BY kode_kriteria ASC");
                    while($kf = mysqli_fetch_assoc($q_modal_kri)):
                    ?>
                    <div class="form-group-penilaian">
                        <label><?= $kf['kode_kriteria'] ?> - <?= $kf['nama_kriteria'] ?></label>
                        
                        <?php if($kf['tipe_kriteria'] == 'Numerik'): ?>
                            <input type="number" step="any" name="nilai[<?= $kf['id_kriteria'] ?>]" id="input_kri_<?= $kf['id_kriteria'] ?>" class="input-navy" placeholder="Masukkan angka..." required>
                        <?php else: ?>
                           <select name="nilai[<?= $kf['id_kriteria'] ?>]" id="input_kri_<?= $kf['id_kriteria'] ?>" class="input-navy" required>
                                <option value="">-- Pilih Kondisi --</option>
                                <?php if($kf['nama_kriteria'] == 'Penghasilan Bulanan'): ?>

                                    <option value="5">< Rp 500.000</option>
                                    <option value="4">Rp 500.000 - 1.5jt</option>
                                    <option value="3">Rp 1.5jt - 3jt</option>
                                    <option value="2">Rp 3jt - 5jt</option>
                                    <option value="1">> Rp 5jt</option>
                                <?php else: ?>
                                    <option value="5">Sangat Buruk</option>
                                    <option value="4">Buruk</option>
                                    <option value="3">Cukup</option>
                                    <option value="2">Baik</option>
                                    <option value="1">Sangat Baik</option>
                                <?php endif; ?>
                            </select>
                        <?php endif; ?>
                    </div>
                    <?php endwhile; 
                } else {
                    echo "<p style='color: #ccc;'>Koneksi database tidak tersedia. Tidak dapat menampilkan input kriteria.</p>";
                }
                ?>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 25px;">
                <button type="button" onclick="closeModalPenilaian()" style="background: rgba(255,255,255,0.1); color: white; border: none; border-radius: 8px; cursor: pointer;">Batal</button>
                <button type="submit" name="save_penilaian" class="btn-navy-grad">Simpan Nilai</button>
            </div>
        </form>
    </div>
</div>

<script>
function filterPenilaian() {
    let input = document.getElementById("penilaianSearch").value.toLowerCase();
    let rows = document.querySelectorAll("#tablePenilaian tbody tr");
    rows.forEach(row => {
        // Cek teks di semua kolom, termasuk nama alternatif
        row.style.display = row.innerText.toLowerCase().includes(input) ? "" : "none";
    });
}

function openAddFromDropdown() {
    let sel = document.getElementById('selectAlt');
    if(!sel.value) {
        alert("Pilih alternatif terlebih dahulu!");
        return;
    }
    
    document.getElementById('modal_alt_id').value = sel.value;
    document.getElementById('altNameDisp').innerText = sel.options[sel.selectedIndex].text;
    document.getElementById('modalPenilaianTitle').innerText = 'Input Nilai: ' + sel.options[sel.selectedIndex].text;
    
    // Reset inputs
    document.querySelectorAll('#dynamicInputs .input-navy').forEach(i => i.value = ''); // Target hanya input di dynamicInputs
    document.getElementById('modalPenilaian').classList.add('show');
    document.body.classList.add('modal-open'); // Tambahkan kelas ke body
}

function editWithValues(id, nama, valuesJson) {
    let values = JSON.parse(valuesJson);
    document.getElementById('modal_alt_id').value = id;
    document.getElementById('altNameDisp').innerText = nama;
    document.getElementById('modalPenilaianTitle').innerText = 'Edit Nilai: ' + nama;
    
    for (let id_kri in values) {
        let input = document.getElementById('input_kri_' + id_kri);
        if (input) input.value = (values[id_kri] === '-' ? '' : values[id_kri]);
    }
    document.getElementById('modalPenilaian').classList.add('show');
    document.body.classList.add('modal-open'); // Tambahkan kelas ke body
}

function closeModalPenilaian() {
    document.getElementById('modalPenilaian').classList.remove('show');
    document.body.classList.remove('modal-open'); // Hapus kelas dari body
}

// Tambahkan event listener untuk menutup modal saat mengklik di luar konten modal
document.addEventListener('DOMContentLoaded', function() {
    const modalOverlay = document.getElementById('modalPenilaian');
    if (modalOverlay) {
        modalOverlay.addEventListener('click', function(event) {
            if (event.target === modalOverlay) {
                closeModalPenilaian();
            }
        });
    }
});
</script>
