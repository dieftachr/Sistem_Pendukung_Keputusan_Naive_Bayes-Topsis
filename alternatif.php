<?php
$status = $_GET['status'] ?? '';
if (isset($_POST['save_alternatif'])) {
    $id    = $_POST['id_alternatif'];
    $nama  = mysqli_real_escape_string($conn, $_POST['nama_alternatif']);

    if (empty($id)) {
        // Tambah Data Baru
        $sql = "INSERT INTO alternatif (nama_alternatif) VALUES ('$nama')";
    } else {
        // Update Data Lama
        $sql = "UPDATE alternatif SET nama_alternatif='$nama' WHERE id_alternatif=$id";
    }
    
    if (mysqli_query($conn, $sql)) {
        echo "<script>window.location.href='dashboard.php?section=content-spk-alternatif&status=success';</script>";
        exit();
    }
}

// Proses Hapus
if (isset($_GET['delete_alt_id'])) {
    $id = $_GET['delete_alt_id'];
    mysqli_query($conn, "DELETE FROM alternatif WHERE id_alternatif=$id");
    echo "<script>window.location.href='dashboard.php?section=content-spk-alternatif&status=deleted';</script>";
    exit();
}
?>

<!-- CSS khusus untuk alternatif.php -->
<style>
/* ======================================= / / SPECIFIC PAGE STYLES (ALTERNATIF) / / ======================================= */

.inner-alternatif-content {
    width: 100%;
    color: #333;
}

/* Header Tabel: Search & Button */
.alt-action-bar {
    display: flex;
    justify-content: space-between; /* Search kiri, tombol kanan */
    align-items: center;
    margin-bottom: 20px;
    gap: 15px; /* Tambah jarak antar elemen */
    flex-wrap: wrap; /* Agar responsif di layar kecil */
}

.alt-search-input {
    padding: 12px 18px 12px 40px; /* PERBESAR PADDING, tambah padding kiri untuk ikon */
    border: 1px solid #ddd;
    border-radius: 8px;
    width: 100%; /* Default 100% */
    max-width: 300px; /* Batasi lebar maksimum */
    outline: none;
    font-size: 1em; /* PERBESAR FONT */
     transition: all 0.3s ease;
     /* Menambahkan ikon search */
    background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="%23999" stroke="%23999" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>');
    background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="%23999" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>');
     background-repeat: no-repeat;
     background-position: 12px center;
     background-size: 20px;
}

.alt-search-input:focus {
    border-color: var(--primary-blue); /* Menggunakan variabel CSS dari style.css utama */
    box-shadow: 0 0 0 3px rgba(26, 115, 232, 0.2);
}

/* --- KELAS BARU UNTUK TOMBOL TAMBAH ALTERNATIF (DENGAN EFEK GRADASI & KILAUAN) --- */
.btn-add-alternatif {
    background: linear-gradient(45deg, var(--primary-blue), var(--dark-blue)); /* Gradasi warna dari profile */
    color: var(--white) !important; /* Warna teks putih */
    padding: 12px 25px;
    border-radius: 8px;
    border: none;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease; /* Transisi untuk semua properti */
    font-size: 1em;
    white-space: nowrap;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15); /* Shadow yang lebih lembut */
    position: relative; /* Penting untuk pseudo-element ::before */
    overflow: hidden; /* Penting untuk pseudo-element ::before */
}

.btn-add-alternatif::before {
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

.btn-add-alternatif:hover::before {
    left: 100%; /* Geser ke luar kanan saat hover */
}

.btn-add-alternatif:hover {
    transform: translateY(-2px) scale(1.01); /* Efek lift dan sedikit membesar */
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2); /* Shadow lebih kuat saat hover */
}
/* ----------------------------------------------- */


/* Tabel Minimalis */
.table-alternatif {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08); /* Tambah shadow untuk efek modern */
}

/* Header Tabel Profesional (tanpa gradasi) */
.table-alternatif thead th {
    background: #2c3e50; /* Warna biru gelap solid, lebih profesional */
    color: white; /* Teks putih */
    text-align: left;
    padding: 15px;
    border-bottom: 2px solid #34495e; /* Border bawah sedikit lebih gelap */
    font-size: 16px; /* PERBESAR FONT HEADER */
    font-weight: 700; /* Lebih tebal */
    text-shadow: none; /* Menghilangkan bayangan teks */
}

.table-alternatif td {
    padding: 15px;
    border-bottom: 1px solid #f1f1f1;
    font-size: 15px; /* PERBESAR FONT ISI TABEL */
    vertical-align: middle;
}

/* Pengaturan lebar kolom spesifik untuk simetri dan penggunaan ruang yang lebih baik */
.table-alternatif th:nth-child(1), .table-alternatif td:nth-child(1) { width: 40px; text-align: center; } /* No */
/* Kolom 'Nama Alternatif' (nth-child(2)) akan mengisi sisa ruang secara otomatis */
.table-alternatif th:nth-child(3), .table-alternatif td:nth-child(3) { width: 100px; text-align: center; } /* Aksi */


/* EFEK HOVER PADA BARIS TABEL */
.table-alternatif tbody tr {
    transition: all 0.3s ease; /* Transisi halus untuk efek hover */
}

.table-alternatif tbody tr:hover {
    background-color: #eef4f8; /* Warna latar belakang saat hover */
    transform: scale(1.005); /* Sedikit membesar */
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); /* Shadow lebih terlihat saat hover */
    cursor: pointer; /* Menunjukkan bahwa baris dapat diklik/interaktif */
}

/* --- PERUBAHAN: Warna teks .text-navy --- */
.text-navy { color: #1a2b4a; font-weight: bold; }
/* ----------------------------------------- */

/* STYLING BARU UNTUK IKON AKSI */
.table-alternatif td:last-child { /* Target sel terakhir (aksi) */
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

/* Modal Styling - Rata Kiri & Navy (khusus untuk modal di alternatif.php) */
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

.form-group-alt {
    margin-bottom: 15px;
    text-align: left;
}

.form-group-alt label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: rgba(255,255,255,0.9);
}

.input-navy {
    width: 100%;
    padding: 12px;
    background: rgba(255,255,255,0.1);
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: 8px;
    color: white !important;
    box-sizing: border-box;
}

.input-navy::placeholder { color: rgba(255,255,255,0.4); }

.alt-info-box {
    background: rgba(0,0,0,0.2);
    padding: 10px;
    border-radius: 8px;
    font-size: 11px;
    margin-top: 8px;
    line-height: 1.4;
    color: #ccc;
}

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

/* --- STYLING KHUSUS UNTUK TOMBOL DI MODAL ALTERNATIF --- */
.modal-navy-content .btn-navy-grad[type="submit"] {
    background: #2c3e50 !important; /* Warna sesuai header tabel alternatif */
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
/* -------------------------------------------------- */


/* ======================================= / / RESPONSIVE ADJUSTMENTS / / ======================================= */

@media (max-width: 768px) {
    .alt-action-bar {
        flex-direction: column;
        align-items: stretch;
    }
    .alt-search-input {
        max-width: 100%;
        padding-left: 18px; /* Sesuaikan padding kiri di mobile jika ikon tidak diperlukan */
        background-image: none; /* Sembunyikan ikon search di mobile */
    }
    .btn-add-alternatif { /* Terapkan ke tombol */
        width: 100%;
        justify-content: center;
    }
    .table-alternatif th, .table-alternatif td {
        padding: 10px;
        font-size: 14px;
    }
    .table-alternatif td:last-child { /* Sel aksi */
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
}

@media (max-width: 480px) {
    .table-alternatif th, .table-alternatif td {
        font-size: 13px;
    }
}

</style>

<div class="inner-alternatif-content">
    <div class="alt-action-bar">
        <input type="text" id="altSearch" class="alt-search-input" placeholder="Cari alternatif..." onkeyup="filterAlt()">
        <!-- Menggunakan kelas baru untuk tombol "Tambah Alternatif" -->
        <button class="btn-add-alternatif" onclick="openModalAlt()">
            <i class="fas fa-plus-circle"></i> Tambah Alternatif
        </button>
    </div>

    <?php if ($status == 'success'): ?>
        <div class="alert-notif alert-success">
            Data Alternatif berhasil disimpan!
            <span onclick="this.parentElement.style.display='none'" style="cursor:pointer">&times;</span>
        </div>

        <?php elseif ($status == 'updated'): ?>
        <div class="alert-notif alert-success">
            Data Alternatif berhasil diperbarui!
            <span onclick="this.parentElement.style.display='none'" style="cursor:pointer">&times;</span>
        </div>

        <?php elseif ($status == 'deleted'): ?>
        <div class="alert-notif alert-deleted">
            Data Alternatif berhasil dihapus!
            <span onclick="this.parentElement.style.display='none'" style="cursor:pointer">&times;</span>
        </div>
    <?php endif; ?>
    <table class="table-alternatif" id="tableAlt">
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Alternatif</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            // Pastikan $conn sudah terdefinisi sebelum digunakan
            if (isset($conn)) {
                $res = mysqli_query($conn, "SELECT * FROM alternatif ORDER BY id_alternatif DESC");
                $no = 1;
                while($row = mysqli_fetch_assoc($res)): ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><b><?= $row['nama_alternatif'] ?></b></td>
                    <td>
                        <button type="button" class="action-icon-btn edit-btn" onclick="editAlt('<?= $row['id_alternatif'] ?>', '<?= $row['nama_alternatif'] ?>')">
                            <i class="fas fa-edit"></i>
                        </button>
                        <a href="dashboard.php?section=content-spk-alternatif&delete_alt_id=<?= $row['id_alternatif'] ?>"
                           onclick="return confirm('Hapus alternatif ini?')" class="action-icon-btn delete-btn">
                            <i class="fas fa-trash"></i>
                        </a>
                    </td>
                </tr>
                <?php endwhile;
            } else {
                echo "<tr><td colspan='3'>Koneksi database tidak tersedia atau tabel alternatif kosong.</td></tr>";
            }
            ?>
            <?php if(isset($res) && mysqli_num_rows($res) == 0): ?>
            <tr>
                <td colspan="3" style="text-align: center; color: #999; padding: 30px;">Belum ada data alternatif.</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div id="modalAlt" class="logout-modal-overlay">
    <div class="modal-navy-content logout-modal-content">
        <h3 id="modalAltTitle" style="margin-top: 0; margin-bottom: 25px;">Tambah Alternatif</h3>
        
        <form action="" method="POST">
            <input type="hidden" name="id_alternatif" id="form_alt_id">
            
            <div class="form-group-alt">
                <label>Nama Alternatif / Objek</label>
                <input type="text" name="nama_alternatif" id="form_alt_nama" class="input-navy" placeholder="Contoh: Pelamar A atau Siswa B" required>
                <div class="alt-info-box">
                    <i class="fas fa-info-circle"></i> <b>Catatan:</b><br>
                    Gunakan nama unik untuk setiap alternatif agar memudahkan proses penilaian nantinya.
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 25px;">
                <button type="button" onclick="closeModalAlt()" style="background: rgba(255,255,255,0.1); color: white; border: none; border-radius: 8px; cursor: pointer;">Batal</button>
                <button type="submit" name="save_alternatif" class="btn-navy-grad">Simpan Data</button>
            </div>
        </form>
    </div>
</div>

<script>
function filterAlt() {
    let input = document.getElementById("altSearch").value.toLowerCase();
    let rows = document.querySelectorAll("#tableAlt tbody tr");
    rows.forEach(row => {
        row.style.display = row.innerText.toLowerCase().includes(input) ? "" : "none";
    });
}

function openModalAlt() {
    document.getElementById('modalAltTitle').innerText = 'Tambah Alternatif';
    document.getElementById('form_alt_id').value = '';
    document.getElementById('form_alt_nama').value = '';
    document.getElementById('modalAlt').classList.add('show');
    document.body.classList.add('modal-open'); // Tambahkan kelas ke body
}

function closeModalAlt() {
    document.getElementById('modalAlt').classList.remove('show');
    document.body.classList.remove('modal-open'); // Hapus kelas dari body
}

function editAlt(id, nama) {
    document.getElementById('modalAltTitle').innerText = 'Edit Alternatif';
    document.getElementById('form_alt_id').value = id;
    document.getElementById('form_alt_nama').value = nama;
    document.getElementById('modalAlt').classList.add('show');
    document.body.classList.add('modal-open'); // Tambahkan kelas ke body
}

// Tambahkan event listener untuk menutup modal saat mengklik di luar konten modal
document.addEventListener('DOMContentLoaded', function() {
    const modalOverlay = document.getElementById('modalAlt');
    if (modalOverlay) {
        modalOverlay.addEventListener('click', function(event) {
            if (event.target === modalOverlay) {
                closeModalAlt();
            }
        });
    }
});
</script>
