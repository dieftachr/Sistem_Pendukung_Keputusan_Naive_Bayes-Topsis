<?php
// Bagian Logika PHP - Penanganan Database
$status = $_GET['status'] ?? '';
if (isset($conn)) {
    // Menghitung jumlah kriteria untuk membuat kode otomatis (C1, C2, dst)
    $query_count = mysqli_query($conn, "SELECT COUNT(*) as total FROM kriteria");
    $row_count = mysqli_fetch_assoc($query_count);
    $next_code = "C" . ($row_count['total'] + 1);
} else {
    $next_code = "C1"; 
}

// Proses Simpan (Tambah & Edit)
if (isset($_POST['save_kriteria'])) {
    $id    = $_POST['id_kriteria'];
    $kode  = mysqli_real_escape_string($conn, $_POST['kode']);
    $nama  = mysqli_real_escape_string($conn, $_POST['nama']);
    $tipe  = $_POST['tipe'];
    $bobot = (float) $_POST['bobot']; // Menyesuaikan tabel (double)
    $sifat = $_POST['sifat'];         // Menyesuaikan tabel (enum)

    if (empty($id)) {
        // Query Tambah Data
        $sql = "INSERT INTO kriteria (kode_kriteria, nama_kriteria, tipe_kriteria, bobot, sifat) 
                VALUES ('$kode', '$nama', '$tipe', '$bobot', '$sifat')";
    } else {
        // Query Update Data
        $sql = "UPDATE kriteria SET 
                kode_kriteria='$kode', 
                nama_kriteria='$nama', 
                tipe_kriteria='$tipe',
                bobot='$bobot',
                sifat='$sifat' 
                WHERE id_kriteria=$id";
    }
    
    if (mysqli_query($conn, $sql)) {
        echo "<script>window.location.href='dashboard.php?section=content-spk-kriteria&status=success';</script>";
        exit();
    }
}

// Proses Hapus
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    mysqli_query($conn, "DELETE FROM kriteria WHERE id_kriteria=$id");
    echo "<script>window.location.href='dashboard.php?section=content-spk-kriteria&status=deleted';</script>";
    exit();
}
?>

<style>
.inner-kriteria-content { 
    width: 100%; 
    color: #333; 
}
.input-navy {
    width: 100%;
    padding: 12px;
    border-radius: 8px;
    border: 1px solid rgba(255,255,255,0.2);
    background: rgba(255,255,255,0.15);
    color: #ffffff;
}

/* dropdown dibuka */
.input-navy option {
    background-color: #ffffff !important;
    color: #1a237e !important;
}
select.input-navy {
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
}

.kriteria-action-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    gap: 15px;
    flex-wrap: wrap;
}

.kriteria-search-input {
    padding: 12px 18px 12px 40px;
    border: 1px solid #ddd;
    border-radius: 8px;
    width: 100%;
    max-width: 300px;
    outline: none;
    background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="%23999" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>');
    background-repeat: no-repeat;
    background-position: 12px center;
    background-size: 20px;
}

.btn-add-kriteria {
    background: linear-gradient(45deg, #1a73e8, #0d47a1);
    color: white !important;
    padding: 12px 25px;
    border-radius: 8px;
    border: none;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: 0.3s;
}

.btn-add-kriteria:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.2); }

.table-kriteria {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
}

.table-kriteria thead th {
    background: #2c3e50;
    color: white;
    text-align: left;
    padding: 15px;
}

.table-kriteria td { padding: 15px; border-bottom: 1px solid #f1f1f1; }

.text-navy { color: #1a237e; font-weight: bold; }

.action-icon-btn {
    background: none; border: none; padding: 8px; cursor: pointer; font-size: 1.1em;
}

.edit-btn { color: #1a2b4a; }
.delete-btn { color: #e74a3b; }

/* Modal Styles */
.logout-modal-overlay {
    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0,0,0,0.7); display: flex; justify-content: center; align-items: center;
    z-index: 2000; opacity: 0; visibility: hidden; transition: 0.3s;
}

.logout-modal-overlay.show { opacity: 1; visibility: visible; backdrop-filter: blur(5px); }

.modal-navy-content {
    background: linear-gradient(135deg, #0d1435 0%, #1a237e 100%);
    color: white; padding: 30px; border-radius: 20px; width: 100%; max-width: 450px;
}

.form-group-kriteria { margin-bottom: 15px; }
.form-group-kriteria label { display: block; margin-bottom: 8px; }
.input-navy {
    width: 100%; padding: 12px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.2);
    background: rgba(255,255,255,0.1); color: white !important; box-sizing: border-box;
}

.btn-navy-grad {
    background: #2c3e50; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer;
}
</style>

<div class="inner-kriteria-content">
    <div class="kriteria-action-bar">
        <input type="text" id="kriteriaSearch" class="kriteria-search-input" placeholder="Cari kriteria..." onkeyup="filterKriteria()">
        <button class="btn-add-kriteria" onclick="openModalKriteria()">
            <i class="fas fa-plus-circle"></i> Tambah Kriteria
        </button>
    </div>

    <table class="table-kriteria" id="tableKriteria">
        <thead>
            <tr>
                <th>No</th>
                <th>Kode</th>
                <th>Nama Kriteria</th>
                <th>Tipe</th>
                <th>Bobot</th>
                <th>Sifat</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            if (isset($conn)) {
                $res = mysqli_query($conn, "SELECT * FROM kriteria ORDER BY kode_kriteria ASC");
                $no = 1;
                while($row = mysqli_fetch_assoc($res)): ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><b><?= $row['kode_kriteria'] ?></b></td>
                    <td><?= $row['nama_kriteria'] ?></td>
                    <td><span class="text-navy"><?= $row['tipe_kriteria'] ?></span></td>
                    <td><?= number_format($row['bobot'], 2) ?></td>
                    <td><span class="text-navy"><?= ucfirst($row['sifat']) ?></span></td>
                    <td>
                        <button type="button" class="action-icon-btn edit-btn" 
                                onclick="editKriteria('<?= $row['id_kriteria'] ?>','<?= $row['kode_kriteria'] ?>','<?= $row['nama_kriteria'] ?>','<?= $row['tipe_kriteria'] ?>','<?= $row['bobot'] ?>','<?= $row['sifat'] ?>')">
                            <i class="fas fa-edit"></i>
                        </button>
                        <a href="dashboard.php?section=content-spk-kriteria&delete_id=<?= $row['id_kriteria'] ?>"
                           onclick="return confirm('Hapus kriteria ini?')" class="action-icon-btn delete-btn">
                            <i class="fas fa-trash"></i>
                        </a>
                    </td>
                </tr>
                <?php endwhile;
            } ?>
        </tbody>
    </table>
</div>

<div id="modalKriteria" class="logout-modal-overlay">
    <div class="modal-navy-content">
        <h3 id="modalTitle">Tambah Kriteria</h3>
        <form action="" method="POST">
            <input type="hidden" name="id_kriteria" id="form_id">
            
            <div class="form-group-kriteria">
                <label>Kode Kriteria</label>
                <input type="text" name="kode" id="form_kode" class="input-navy" readonly>
            </div>

            <div class="form-group-kriteria">
                <label>Nama Kriteria</label>
                <input type="text" name="nama" id="form_nama" class="input-navy" required>
            </div>

            <div class="form-group-kriteria">
                <label>Tipe Data</label>
                <select name="tipe" id="form_tipe" class="input-navy">
                    <option value="Kategorikal">Kategorikal</option>
                    <option value="Numerik">Numerik</option>
                </select>
            </div>

            <div class="form-group-kriteria">
                <label>Bobot</label>
                <input type="number" step="0.01" name="bobot" id="form_bobot" class="input-navy" required>
            </div>

            <div class="form-group-kriteria">
                <label>Sifat Kriteria</label>
                <select name="sifat" id="form_sifat" class="input-navy">
                    <option value="Benefit">Benefit</option>
                    <option value="Cost">Cost</option>
                </select>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                <button type="button" onclick="closeModalKriteria()" class="btn-navy-grad" style="background:rgba(255,255,255,0.1)">Batal</button>
                <button type="submit" name="save_kriteria" class="btn-navy-grad">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
const nextCodeAuto = "<?= $next_code ?>";

function openModalKriteria() {
    document.getElementById('modalTitle').innerText = 'Tambah Kriteria';
    document.getElementById('form_id').value = '';
    document.getElementById('form_kode').value = nextCodeAuto;
    document.getElementById('form_nama').value = '';
    document.getElementById('form_tipe').value = 'Kategorikal';
    document.getElementById('form_bobot').value = '';
    document.getElementById('form_sifat').value = 'Benefit';
    document.getElementById('modalKriteria').classList.add('show');
}

function editKriteria(id, kode, nama, tipe, bobot, sifat) {
    document.getElementById('modalTitle').innerText = 'Edit Kriteria';
    document.getElementById('form_id').value = id;
    document.getElementById('form_kode').value = kode;
    document.getElementById('form_nama').value = nama;
    document.getElementById('form_tipe').value = tipe;
    document.getElementById('form_bobot').value = bobot;
    document.getElementById('form_sifat').value = sifat;
    document.getElementById('modalKriteria').classList.add('show');
}

function closeModalKriteria() {
    document.getElementById('modalKriteria').classList.remove('show');
}

function filterKriteria() {
    let input = document.getElementById("kriteriaSearch").value.toLowerCase();
    let rows = document.querySelectorAll("#tableKriteria tbody tr");
    rows.forEach(row => {
        row.style.display = row.innerText.toLowerCase().includes(input) ? "" : "none";
    });
}
</script>