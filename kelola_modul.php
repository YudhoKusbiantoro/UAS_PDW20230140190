<?php
session_start();
require_once '../config.php';

// Pastikan hanya asisten yang bisa mengakses halaman ini
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'asisten') {
    header("Location: ../login.php");
    exit();
}

$pageTitle = "Kelola Modul Praktikum";
include 'templates/header_asisten.php';

$message = '';
$message_type = '';
$edit_mode = false;
// Sesuaikan inisialisasi modul_data: Hapus 'deskripsi_modul'
$modul_data = ['id' => '', 'id_praktikum' => '', 'judul' => '', 'file_materi' => ''];
$id_asisten = $_SESSION['user_id']; // ID asisten yang sedang login

// Pastikan direktori uploads ada dan bisa ditulis
$upload_dir = __DIR__ . '/../uploads/modul/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// --- Logika Tambah & Update Modul ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sesuaikan nama input form dengan nama kolom di DB
    $id_praktikum = $_POST['mata_praktikum_id']; // Nama input form tetap 'mata_praktikum_id'
    $judul = trim($_POST['judul_modul']);      // Nama input form tetap 'judul_modul'
    // Kolom 'deskripsi' tidak ada di tabel modul, jadi tidak perlu diambil dari POST
    $existing_file_materi = $_POST['existing_file_materi'] ?? null; // Untuk mode edit

    // Validasi dasar
    if (empty($id_praktikum) || empty($judul)) {
        $message = "Mata praktikum dan judul modul wajib diisi!";
        $message_type = "error";
    } else {
        $file_materi_name = $existing_file_materi; // Default ke file lama jika tidak ada upload baru

        // Handle file upload
        if (isset($_FILES['file_materi']) && $_FILES['file_materi']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['file_materi']['tmp_name'];
            $fileName = $_FILES['file_materi']['name'];
            $fileSize = $_FILES['file_materi']['size'];
            $fileNameCmps = explode(".", $fileName);
            $fileExtension = strtolower(end($fileNameCmps));
            $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
            $dest_path = $upload_dir . $newFileName;

            // Izinkan tipe file tertentu dan batasi ukuran
            $allowedfileExtensions = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'zip', 'rar'];
            $maxFileSize = 10 * 1024 * 1024; // 10 MB

            if (!in_array($fileExtension, $allowedfileExtensions)) {
                $message = "Jenis file materi tidak diizinkan. Hanya PDF, DOC, DOCX, PPT, PPTX, ZIP, RAR yang diizinkan.";
                $message_type = "error";
            } elseif ($fileSize > $maxFileSize) {
                $message = "Ukuran file terlalu besar. Maksimal 10 MB.";
                $message_type = "error";
            } else {
                if (move_uploaded_file($fileTmpPath, $dest_path)) {
                    // Hapus file lama jika ada dan berhasil upload file baru
                    if ($existing_file_materi && file_exists($upload_dir . $existing_file_materi)) {
                        unlink($upload_dir . $existing_file_materi);
                    }
                    $file_materi_name = $newFileName;
                } else {
                    $message = "Terjadi kesalahan saat mengunggah file materi.";
                    $message_type = "error";
                }
            }
        } elseif (isset($_FILES['file_materi']) && $_FILES['file_materi']['error'] !== UPLOAD_ERR_NO_FILE) {
            // Menangani error upload selain NO_FILE
            switch ($_FILES['file_materi']['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $message = "Ukuran file melebihi batas yang diizinkan di server.";
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $message = "File hanya terunggah sebagian.";
                    break;
                default:
                    $message = "Terjadi kesalahan tidak dikenal saat mengunggah file.";
            }
            $message_type = "error";
        }

        // Lanjutkan proses INSERT/UPDATE hanya jika tidak ada pesan error upload
        if (empty($message)) {
            if (isset($_POST['update_modul'])) { // Proses Update
                $id = $_POST['id'];
                // Sesuaikan nama kolom di query SQL: Hapus 'deskripsi' dan 'tanggal_perbarui'
                $sql = "UPDATE modul SET id_praktikum = ?, id_asisten = ?, judul = ?, file_materi = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                if ($stmt) {
                    // Sesuaikan bind_param: Hapus parameter untuk deskripsi
                    $stmt->bind_param("iisssi", $id_praktikum, $id_asisten, $judul, $file_materi_name, $id);
                    if ($stmt->execute()) {
                        $message = "Modul berhasil diperbarui.";
                        $message_type = "success";
                    } else {
                        $message = "Gagal memperbarui modul: " . $stmt->error;
                        $message_type = "error";
                    }
                    $stmt->close();
                } else {
                    $message = "Gagal menyiapkan statement: " . $conn->error;
                    $message_type = "error";
                }
            } else { // Proses Tambah
                // Sesuaikan nama kolom di query SQL: Hapus 'deskripsi', 'tanggal_unggah', 'tanggal_perbarui'
                $sql = "INSERT INTO modul (id_praktikum, id_asisten, judul, file_materi) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                if ($stmt) {
                    // Sesuaikan bind_param: Hapus parameter untuk deskripsi
                    $stmt->bind_param("iiss", $id_praktikum, $id_asisten, $judul, $file_materi_name);
                    if ($stmt->execute()) {
                        $message = "Modul berhasil ditambahkan.";
                        $message_type = "success";
                    } else {
                        $message = "Gagal menambahkan modul: " . $stmt->error;
                        $message_type = "error";
                    }
                    $stmt->close();
                } else {
                    $message = "Gagal menyiapkan statement: " . $conn->error;
                    $message_type = "error";
                }
            }
        }
    }
    // Redirect setelah POST untuk mencegah resubmission
    header("Location: kelola_modul.php?message=" . urlencode($message) . "&type=" . urlencode($message_type));
    exit();
}

// Menampilkan pesan dari redirect (setelah POST)
if (isset($_GET['message']) && isset($_GET['type'])) {
    $message = htmlspecialchars($_GET['message']);
    $message_type = htmlspecialchars($_GET['type']);
}

// --- Logika Edit (mengambil data untuk form) ---
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    // Sesuaikan nama kolom di SELECT dari tabel 'modul': Hapus 'deskripsi'
    $sql = "SELECT id, id_praktikum, judul, file_materi FROM modul WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 1) {
            // Sesuaikan nama kolom yang diambil
            $modul_data = $result->fetch_assoc();
            $edit_mode = true;
        } else {
            $message = "Data modul tidak ditemukan.";
            $message_type = "error";
        }
        $stmt->close();
    } else {
        $message = "Gagal menyiapkan statement: " . $conn->error;
        $message_type = "error";
    }
}

// --- Logika Hapus ---
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // Hapus file materi terkait jika ada
    // Sesuaikan nama kolom di SELECT dari tabel 'modul'
    $sql_get_file = "SELECT file_materi FROM modul WHERE id = ?";
    $stmt_get_file = $conn->prepare($sql_get_file);
    if ($stmt_get_file) {
        $stmt_get_file->bind_param("i", $id);
        $stmt_get_file->execute();
        $result_get_file = $stmt_get_file->get_result();
        if ($file_row = $result_get_file->fetch_assoc()) {
            if ($file_row['file_materi'] && file_exists($upload_dir . $file_row['file_materi'])) {
                unlink($upload_dir . $file_row['file_materi']);
            }
        }
        $stmt_get_file->close();
    }

    // Sesuaikan nama tabel di DELETE
    $sql = "DELETE FROM modul WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $message = "Modul berhasil dihapus.";
            $message_type = "success";
        } else {
            $message = "Gagal menghapus modul: " . $stmt->error;
            $message_type = "error";
        }
        $stmt->close();
    } else {
        $message = "Gagal menyiapkan statement: " . $conn->error;
        $message_type = "error";
    }
    // Redirect setelah POST/DELETE untuk mencegah resubmission
    header("Location: kelola_modul.php?message=" . urlencode($message) . "&type=" . urlencode($message_type));
    exit();
}

// --- Ambil Daftar Mata Praktikum untuk Dropdown ---
$mata_praktikum_list = [];
// Sesuaikan nama tabel di SELECT (dari 'praktikum')
$sql_praktikum_list = "SELECT id, nama_praktikum FROM praktikum ORDER BY nama_praktikum ASC";
$result_praktikum_list = $conn->query($sql_praktikum_list);
if ($result_praktikum_list) {
    $mata_praktikum_list = $result_praktikum_list->fetch_all(MYSQLI_ASSOC);
} else {
    if (empty($message)) { // Hindari menimpa pesan error yang sudah ada
        $message = "Gagal mengambil daftar mata praktikum: " . $conn->error;
        $message_type = "error";
    }
}

// --- Ambil Daftar Modul untuk Ditampilkan ---
$modul_list = [];
// Sesuaikan nama tabel dan kolom di SELECT (dari 'modul' JOIN 'praktikum'): Hapus 'm.deskripsi'
$sql_select_modul = "SELECT m.id, m.judul, m.file_materi, p.nama_praktikum
                     FROM modul m
                     JOIN praktikum p ON m.id_praktikum = p.id
                     ORDER BY p.nama_praktikum, m.judul ASC";
$result_select_modul = $conn->query($sql_select_modul);
if ($result_select_modul) {
    $modul_list = $result_select_modul->fetch_all(MYSQLI_ASSOC);
} else {
    if (empty($message)) { // Hindari menimpa pesan error yang sudah ada
        $message = "Gagal mengambil data modul: " . $conn->error;
        $message_type = "error";
    }
}

?>

<main class="container">
    <div class="page-header">
        <h2>Kelola Modul Praktikum</h2>
        <p>Tambah, edit, atau hapus modul untuk setiap mata praktikum.</p>
    </div>

    <?php if (!empty($message)): ?>
        <div class="message <?php echo $message_type; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <h3><?php echo $edit_mode ? 'Edit' : 'Tambah'; ?> Modul Baru</h3>
        <form action="kelola_modul.php" method="POST" enctype="multipart/form-data">
            <?php if ($edit_mode): ?>
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($modul_data['id']); ?>">
                <input type="hidden" name="update_modul" value="1">
                <input type="hidden" name="existing_file_materi" value="<?php echo htmlspecialchars($modul_data['file_materi']); ?>">
            <?php endif; ?>

            <div class="form-group">
                <label for="mata_praktikum_id">Pilih Mata Praktikum</label>
                <select id="mata_praktikum_id" name="mata_praktikum_id" required>
                    <option value="">-- Pilih Praktikum --</option>
                    <?php foreach ($mata_praktikum_list as $praktikum): ?>
                        <option value="<?php echo htmlspecialchars($praktikum['id']); ?>"
                            <?php echo ($edit_mode && $modul_data['id_praktikum'] == $praktikum['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($praktikum['nama_praktikum']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="judul_modul">Judul Modul</label>
                <input type="text" id="judul_modul" name="judul_modul" value="<?php echo htmlspecialchars($modul_data['judul']); ?>" required>
            </div>
            <!-- Kolom 'deskripsi' tidak ada di tabel 'modul', jadi input ini dihapus -->
            <!-- <div class="form-group">
                <label for="deskripsi_modul">Deskripsi</label>
                <textarea id="deskripsi_modul" name="deskripsi_modul" rows="4"><?php echo htmlspecialchars($modul_data['deskripsi']); ?></textarea>
            </div> -->
            <div class="form-group">
                <label for="file_materi">File Materi (Opsional)</label>
                <input type="file" id="file_materi" name="file_materi" style="padding:8px; border-radius: 6px;">
                <?php if ($edit_mode && $modul_data['file_materi']): ?>
                    <small style="font-size: 14px; color: var(--text-secondary); display: block; margin-top: 5px;">File saat ini: <a href="../uploads/modul/<?php echo htmlspecialchars($modul_data['file_materi']); ?>" target="_blank"><?php echo htmlspecialchars($modul_data['file_materi']); ?></a> (Upload baru akan menimpa)</small>
                <?php endif; ?>
                <small style="font-size: 12px; color: var(--text-secondary); display: block; margin-top: 5px;">
                    Ukuran maksimum: 10MB. Format yang diizinkan: PDF, DOC, DOCX, PPT, PPTX, ZIP, RAR.
                </small>
            </div>
            <button type="submit" name="submit_modul" class="btn btn-primary"><?php echo $edit_mode ? 'Perbarui Modul' : 'Tambah Modul'; ?></button>
            <?php if ($edit_mode): ?>
                <a href="kelola_modul.php" class="btn btn-secondary" style="margin-left: 10px;">Batal Edit</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="card" style="margin-top: 30px;">
        <h3>Daftar Modul</h3>
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 1px solid var(--border-color);">
                    <th style="padding: 12px; text-align: left;">Judul Modul</th>
                    <th style="padding: 12px; text-align: left;">Mata Praktikum</th>
                    <!-- Kolom 'Deskripsi' tidak ada di tabel 'modul', jadi header ini dihapus -->
                    <!-- <th style="padding: 12px; text-align: left;">Deskripsi</th> -->
                    <th style="padding: 12px; text-align: left;">File Materi</th>
                    <th style="width: 15%; padding: 12px; text-align: left;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($modul_list)): ?>
                    <?php foreach ($modul_list as $modul): ?>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 12px;"><?php echo htmlspecialchars($modul['judul']); ?></td>
                            <td style="padding: 12px;"><?php echo htmlspecialchars($modul['nama_praktikum']); ?></td>
                            <!-- Kolom 'deskripsi' tidak ada di tabel 'modul', jadi cell ini dihapus -->
                            <!-- <td style="padding: 12px; color: var(--text-secondary);"><?php echo htmlspecialchars($modul['deskripsi']); ?></td> -->
                            <td style="padding: 12px;">
                                <?php if ($modul['file_materi']): ?>
                                    <a href="../uploads/modul/<?php echo htmlspecialchars($modul['file_materi']); ?>" target="_blank" class="file-download">
                                        <i class='bx bxs-download'></i> Unduh File
                                    </a>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td class="action-links" style="display:flex; gap:16px; padding: 12px;">
                                <a href="kelola_modul.php?action=edit&id=<?php echo $modul['id']; ?>" style="color:var(--primary-color); text-decoration:none; font-weight:600;">Edit</a>
                                <a href="kelola_modul.php?action=delete&id=<?php echo $modul['id']; ?>" onclick="return confirm('Anda yakin ingin menghapus modul ini? Ini juga akan menghapus semua laporan dan nilai terkait modul ini!');" style="color: #EF4444; text-decoration:none; font-weight:600;">Hapus</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3" style="text-align:center; padding: 40px; color: var(--text-secondary);">Belum ada modul yang ditambahkan.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

<?php include 'templates/footer_asisten.php'; ?>