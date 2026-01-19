<?php
// 1. KONEKSI DATABASE
require_once 'config.php';

// Inisialisasi variabel status untuk notifikasi
$status = $_GET['status'] ?? '';

// --- 2. LOGIKA PHP: SIMPAN & UPDATE ---
if (isset($_POST['save_latih'])) {
    $id_kri    = mysqli_real_escape_string($conn, $_POST['id_kriteria']);
    $nilai     = mysqli_real_escape_string($conn, $_POST['nilai']);
    $keputusan = mysqli_real_escape_string($conn, $_POST['keputusan']);
    $id_edit   = mysqli_real_escape_string($conn, $_POST['id_latih_edit']);

    if (!empty($id_edit)) {
        mysqli_query($conn, "UPDATE data_latih SET id_kriteria='$id_kri', nilai='$nilai', keputusan='$keputusan' WHERE id_latih='$id_edit'");
        $redirect_status = "updated";
    } else {
        mysqli_query($conn, "INSERT INTO data_latih (id_kriteria, nilai, keputusan) VALUES ('$id_kri', '$nilai', '$keputusan')");
        $redirect_status = "success";
    }
    
    echo "<script>window.location.href='dashboard.php?section=content-spk-latih&status=" . $redirect_status . "';</script>";
    exit();
}

// --- 3. LOGIKA PHP: HAPUS ---
if (isset($_GET['delete_latih'])) {
    $id = mysqli_real_escape_string($conn, $_GET['delete_latih']);
    mysqli_query($conn, "DELETE FROM data_latih WHERE id_latih = '$id'");
    echo "<script>window.location.href='dashboard.php?section=content-spk-latih&status=deleted';</script>";
    exit();
}
?>

<style>
    .data-latih-container { width: 100%; padding: 10px; box-sizing: border-box; }

    /* ======================================= 
       NOTIFIKASI - DISAMAKAN DENGAN ALTERNATIF 
       ======================================= */
    .alert-notif {
        padding: 12px 20px;
        border-radius: 8px;
        margin-bottom: 20px;
        color: white !important;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 14px !important; 
        font-weight: 500 !important;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    }
    .alert-success { background-color: #1cc88a !important; }
    .alert-deleted { background-color: #e74a3b !important; }

    /* Card Modern */
    .card-modern {
        background: #ffffff;
        padding: 25px;
        border-radius: 12px;
        border: 1px solid #e0e0e0;
        margin-bottom: 25px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    }

    /* Form Styling */
    .data-latih-form { display: flex; flex-wrap: wrap; gap: 20px; align-items: flex-end; }
    .form-group-latih { flex: 1; min-width: 200px; }
    .form-label { display: block; font-weight: bold; margin-bottom: 8px; color: #555; font-size: 14px; }
    .form-input-field {
        width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #ccc;
        font-size: 14px; box-sizing: border-box;
    }

    /* Tombol Sejajar */
    .btn-action-group { display: flex; gap: 10px; align-items: center; }

    .btn-primary-action {
        background: linear-gradient(45deg, #1a73e8, #0d47a1);
        color: white; padding: 11px 25px; border: none; border-radius: 8px;
        font-weight: bold; cursor: pointer; transition: 0.3s; font-size: 14px;
        white-space: nowrap;
    }
    .btn-secondary { 
        background: #6c757d; color: white; padding: 11px 25px; border: none; 
        border-radius: 8px; cursor: pointer; font-size: 14px; white-space: nowrap;
    }

    /* Table Styling */
    .table-responsive-wrapper { overflow-x: auto; border-radius: 10px; }
    .data-latih-table { width: 100%; border-collapse: collapse; background: white; min-width: 700px; }
    .data-latih-table thead th { background: #2c3e50; color: white; padding: 15px; text-align: left; font-size: 14px; }
    .data-latih-table td { padding: 12px 15px; border-bottom: 1px solid #eee; font-size: 13px; }

    /* Badge Status */
    .badge-status {
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 700;
        color: white !important;
        display: inline-block;
    }
    .status-ijo { background-color: #28a745 !important; }
    .status-merah { background-color: #dc3545 !important; }

    .action-icon-btn { border: none; background: none; cursor: pointer; font-size: 15px; padding: 5px; }
    .edit-btn { color: #1a73e8; }
    .delete-btn { color: #e74a3b; }
</style>

<div class="data-latih-container">

    <?php if ($status == 'success'): ?>
        <div class="alert-notif alert-success">
            Data Latih berhasil disimpan!
            <span onclick="this.parentElement.style.display='none'" style="cursor:pointer; font-size: 20px;">&times;</span>
        </div>
    <?php elseif ($status == 'updated'): ?>
        <div class="alert-notif alert-success">
            Data Latih berhasil diperbarui!
            <span onclick="this.parentElement.style.display='none'" style="cursor:pointer; font-size: 20px;">&times;</span>
        </div>
    <?php elseif ($status == 'deleted'): ?>
        <div class="alert-notif alert-deleted">
            Data Latih berhasil dihapus!
            <span onclick="this.parentElement.style.display='none'" style="cursor:pointer; font-size: 20px;">&times;</span>
        </div>
    <?php endif; ?>

    <div class="card-modern">
        <form action="" method="POST" id="formLatih" class="data-latih-form">
            <input type="hidden" name="id_latih_edit" id="id_latih_edit">

            <div class="form-group-latih">
                <label class="form-label">Kriteria SPK</label>
                <select name="id_kriteria" id="id_kriteria" onchange="cekTipe()" class="form-input-field" required>
                    <option value="">-- Pilih Kriteria --</option>
                    <?php
                    $q_kri = mysqli_query($conn, "SELECT * FROM kriteria ORDER BY nama_kriteria ASC");
                    while($k = mysqli_fetch_assoc($q_kri)) {
                        echo "<option value='".$k['id_kriteria']."' data-tipe='".$k['tipe_kriteria']."'>".$k['nama_kriteria']."</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="form-group-latih">
                <label class="form-label">Nilai / Bobot</label>
                <div id="tempat-input">
                    <select name="nilai" id="nilai_field" class="form-input-field">
                        <option value="5">Sangat Buruk (5)</option>
                        <option value="4">Buruk (4)</option>
                        <option value="3">Cukup (3)</option>
                        <option value="2">Baik (2)</option>
                        <option value="1">Sangat Baik (1)</option>
                    </select>
                </div>
            </div>

            <div class="form-group-latih">
                <label class="form-label">Keputusan</label>
                <select name="keputusan" id="keputusan" class="form-input-field" required>
                    <option value="Layak">Layak</option>
                    <option value="Tidak Layak">Tidak Layak</option>
                </select>
            </div>

            <div class="btn-action-group">
                <button type="submit" name="save_latih" id="btnS" class="btn-primary-action">Simpan</button>
                <button type="button" id="btnB" onclick="batalEdit()" class="btn-secondary" style="display:none;">Batal</button>
            </div>
        </form>
    </div>

    <div class="card-modern" style="padding: 0;">
        <div class="table-responsive-wrapper">
            <table class="data-latih-table">
                <thead>
                    <tr>
                        <th>Kriteria</th>
                        <th>Nilai</th>
                        <th style="text-align:center">Keputusan</th>
                        <th style="text-align:center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
    <?php
    $sql = "SELECT data_latih.*, kriteria.nama_kriteria FROM data_latih 
            JOIN kriteria ON data_latih.id_kriteria = kriteria.id_kriteria 
            ORDER BY id_latih DESC";
    $res = mysqli_query($conn, $sql);
    while($row = mysqli_fetch_assoc($res)):
        // PERBAIKAN DI SINI: Samakan case agar pengecekan akurat
        $kpt = trim(strtoupper($row['keputusan']));
        $css_status = ($kpt == 'LAYAK') ? 'status-ijo' : 'status-merah';
    ?>
    <tr>
        <td><strong><?= $row['nama_kriteria'] ?></strong></td>
        <td><?= $row['nilai'] ?></td>
        <td align="center">
            <span class="badge-status <?= $css_status ?>">
                <?= $row['keputusan'] ?>
            </span>
        </td>
        <td align="center">
            <button type="button" onclick='isiEdit(<?= json_encode($row) ?>)' class="action-icon-btn edit-btn"><i class="fas fa-edit"></i></button>
            <a href="dashboard.php?section=content-spk-latih&delete_latih=<?= $row['id_latih'] ?>" onclick="return confirm('Hapus data ini?')" class="action-icon-btn delete-btn"><i class="fas fa-trash"></i></a>
        </td>
    </tr>
    <?php endwhile; ?>
</tbody>
            </table>
        </div>
    </div>
</div>

<script>
function cekTipe() {
    var s = document.getElementById('id_kriteria');
    var opt = s.options[s.selectedIndex];
    if (!opt.value) return;
    var t = opt.getAttribute('data-tipe');
    var box = document.getElementById('tempat-input');
    
    if (t === 'Numerik') {
        box.innerHTML = '<input type="number" name="nilai" id="nilai_field" required class="form-input-field" placeholder="Angka...">';
    } else {
        box.innerHTML = `
            <select name="nilai" id="nilai_field" class="form-input-field">
                <option value="5">Sangat Buruk (5)</option>
                <option value="4">Buruk (4)</option>
                <option value="3">Cukup (3)</option>
                <option value="2">Baik (2)</option>
                <option value="1">Sangat Baik (1)</option>
            </select>`;
    }
}

function isiEdit(data) {
    document.getElementById('id_latih_edit').value = data.id_latih;
    document.getElementById('id_kriteria').value = data.id_kriteria;
    cekTipe();
    setTimeout(function() {
        document.getElementById('nilai_field').value = data.nilai;
        document.getElementById('keputusan').value = data.keputusan;
        document.getElementById('btnS').innerText = 'Update';
        document.getElementById('btnB').style.display = 'inline-block';
        window.scrollTo({top: 0, behavior: 'smooth'});
    }, 100);
}

function batalEdit() {
    document.getElementById('id_latih_edit').value = "";
    document.getElementById('formLatih').reset();
    document.getElementById('btnS').innerText = 'Simpan';
    document.getElementById('btnB').style.display = 'none';
}
</script>