<?php
session_start();
require_once '../config.php';

// Pastikan hanya asisten yang bisa mengakses halaman ini
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'asisten') {
    header("Location: ../login.php");
    exit();
}

$pageTitle = "Laporan Masuk";
include 'templates/header_asisten.php';

$message = '';
$message_type = '';

// --- Logika Penilaian Laporan ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nilai_laporan'])) {
    $id_laporan = intval($_POST['id_laporan']);
    $nilai = trim($_POST['nilai']);
    $komentar = trim($_POST['komentar']);
    $id_asisten = $_SESSION['user_id'];

    // Ambil id_user dan id_modul dari laporan yang akan dinilai
    $sql_get_laporan_info = "SELECT id_user, id_modul FROM laporan WHERE id = ?";
    $stmt_get_laporan_info = $conn->prepare($sql_get_laporan_info);
    if ($stmt_get_laporan_info) {
        $stmt_get_laporan_info->bind_param("i", $id_laporan);
        $stmt_get_laporan_info->execute();
        $result_laporan_info = $stmt_get_laporan_info->get_result();
        if ($laporan_info = $result_laporan_info->fetch_assoc()) {
            $id_user = $laporan_info['id_user'];
            $id_modul = $laporan_info['id_modul'];

            // Coba update nilai jika sudah ada, jika tidak, insert baru
            $sql_upsert_nilai = "
                INSERT INTO nilai (id_user, id_modul, nilai, komentar)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE nilai = VALUES(nilai), komentar = VALUES(komentar), tanggal_nilai = CURRENT_TIMESTAMP
            ";
            $stmt_upsert_nilai = $conn->prepare($sql_upsert_nilai);
            if ($stmt_upsert_nilai) {
                $stmt_upsert_nilai->bind_param("iiss", $id_user, $id_modul, $nilai, $komentar);
                if ($stmt_upsert_nilai->execute()) {
                    $message = "Laporan berhasil dinilai/diperbarui.";
                    $message_type = "success";

                    // Tambahkan notifikasi ke mahasiswa
                    $pesan_notifikasi = "Laporan Anda untuk modul '" . $laporan_info['id_modul'] . "' telah dinilai. Nilai: " . htmlspecialchars($nilai);
                    $sql_insert_notif = "INSERT INTO notifikasi (id_mahasiswa, pesan) VALUES (?, ?)";
                    $stmt_notif = $conn->prepare($sql_insert_notif);
                    if ($stmt_notif) {
                        $stmt_notif->bind_param("is", $id_user, $pesan_notifikasi);
                        $stmt_notif->execute();
                        $stmt_notif->close();
                    }

                } else {
                    $message = "Gagal menyimpan nilai: " . $stmt_upsert_nilai->error;
                    $message_type = "error";
                }
                $stmt_upsert_nilai->close();
            } else {
                $message = "Gagal menyiapkan statement penilaian: " . $conn->error;
                $message_type = "error";
            }
        } else {
            $message = "Laporan tidak ditemukan.";
            $message_type = "error";
        }
        $stmt_get_laporan_info->close();
    } else {
        $message = "Gagal menyiapkan statement info laporan: " . $conn->error;
        $message_type = "error";
    }
}

// --- Ambil Data Laporan untuk Ditampilkan ---
$laporan_list = [];
$sql_select_laporan = "
    SELECT
        l.id AS laporan_id,
        u.nama AS nama_mahasiswa,
        p.nama_praktikum,
        m.judul AS judul_modul,
        l.file_laporan,
        l.tanggal_upload,
        n.nilai,
        n.komentar
    FROM laporan l
    JOIN users u ON l.id_user = u.id
    JOIN modul m ON l.id_modul = m.id
    JOIN praktikum p ON m.id_praktikum = p.id
    LEFT JOIN nilai n ON l.id_user = n.id_user AND l.id_modul = n.id_modul
    ORDER BY l.tanggal_upload DESC
";
$result_select_laporan = $conn->query($sql_select_laporan);
if ($result_select_laporan) {
    $laporan_list = $result_select_laporan->fetch_all(MYSQLI_ASSOC);
} else {
    $message = "Gagal mengambil data laporan: " . $conn->error;
    $message_type = "error";
}

?>
<div class="page-header">
    <h2>Laporan Masuk Praktikum</h2>
    <p>Daftar laporan yang telah dikumpulkan oleh mahasiswa dan menunggu penilaian.</p>
</div>

<?php if (!empty($message)): ?>
    <div class="message <?php echo $message_type; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>
    
<div class="card">
    <h3>Daftar Laporan</h3>
    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="border-bottom: 1px solid var(--border-color);">
                <th style="padding: 12px; text-align: left;">Mahasiswa</th>
                <th style="padding: 12px; text-align: left;">Praktikum & Modul</th>
                <th style="padding: 12px; text-align: left;">Tanggal Kumpul</th>
                <th style="padding: 12px; text-align: left;">Status & Nilai</th>
                <th style="padding: 12px; text-align: left;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($laporan_list)): ?>
                <?php foreach ($laporan_list as $laporan): ?>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 12px;"><?php echo htmlspecialchars($laporan['nama_mahasiswa']); ?></td>
                        <td style="padding: 12px;">
                            <strong><?php echo htmlspecialchars($laporan['nama_praktikum']); ?></strong><br>
                            Modul: <?php echo htmlspecialchars($laporan['judul_modul']); ?>
                        </td>
                        <td style="padding: 12px;"><?php echo htmlspecialchars($laporan['tanggal_upload']); ?></td>
                        <td style="padding: 12px;">
                            <?php if (!empty($laporan['nilai'])): ?>
                                <span style="color: #198754; font-weight: 600;">Sudah Dinilai</span><br>
                                Nilai: <strong><?php echo htmlspecialchars($laporan['nilai']); ?></strong><br>
                                <?php if (!empty($laporan['komentar'])): ?>
                                    <small>Komentar: "<?php echo htmlspecialchars($laporan['komentar']); ?>"</small>
                                <?php endif; ?>
                            <?php else: ?>
                                <span style="color: var(--warning-color); font-weight: 600;">Menunggu Penilaian</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 12px;">
                            <?php if ($laporan['file_laporan']): ?>
                                <a href="../uploads/laporan/<?php echo htmlspecialchars($laporan['file_laporan']); ?>" target="_blank" class="btn btn-sm" style="margin-bottom: 5px;">Unduh Laporan</a>
                            <?php endif; ?>
                            <button type="button" class="btn btn-sm btn-primary" onclick="openModal(<?php echo $laporan['laporan_id']; ?>, '<?php echo htmlspecialchars($laporan['nilai']); ?>', '<?php echo htmlspecialchars(str_replace(["\r", "\n"], ['\\r', '\\n'], $laporan['komentar'])); ?>')">
                                <?php echo !empty($laporan['nilai']) ? 'Edit Nilai' : 'Nilai Laporan'; ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" style="text-align:center; padding: 40px; color: var(--text-secondary);">Tidak ada laporan masuk saat ini.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div id="nilaiModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4);">
    <div class="modal-content" style="background-color: #fefefe; margin: 10% auto; padding: 30px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.3); width: 80%; max-width: 500px; position: relative;">
        <span class="close-button" onclick="closeModal()" style="color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
        <h3 style="margin-top: 0;">Nilai Laporan</h3>
        <form action="laporan_masuk.php" method="POST">
            <input type="hidden" name="id_laporan" id="modal_id_laporan">
            <div class="form-group">
                <label for="nilai">Nilai (0-100)</label>
                <input type="number" id="modal_nilai" name="nilai" min="0" max="100" step="0.01" required>
            </div>
            <div class="form-group">
                <label for="komentar">Komentar (Opsional)</label>
                <textarea id="modal_komentar" name="komentar" rows="4"></textarea>
            </div>
            <button type="submit" name="nilai_laporan" class="btn btn-primary">Simpan Nilai</button>
        </form>
    </div>
</div>

<script>
function openModal(laporanId, nilai, komentar) {
    document.getElementById('modal_id_laporan').value = laporanId;
    document.getElementById('modal_nilai').value = nilai;
    document.getElementById('modal_komentar').value = komentar.replace(/\\r\\n/g, '\\n').replace(/\\n/g, '\\r\\n'); // Fix newline for textarea
    document.getElementById('nilaiModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('nilaiModal').style.display = 'none';
}

// Close the modal if the user clicks outside of it
window.onclick = function(event) {
    var modal = document.getElementById('nilaiModal');
    if (event.target == modal) {
        modal.style.display = "none";
    }
}
</script>

<?php include 'templates/footer_asisten.php'; ?>