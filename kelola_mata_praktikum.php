<?php
session_start();
require_once '../config.php';

// Pastikan hanya asisten yang bisa mengakses halaman ini
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'asisten') {
    header("Location: ../login.php");
    exit();
}

$pageTitle = "Kelola Mata Praktikum";
include 'templates/header_asisten.php';

$message = '';
$message_type = '';
$edit_mode = false;
$praktikum_data = ['id' => '', 'nama_praktikum' => '', 'deskripsi' => ''];

// --- Logika Tambah & Update ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_praktikum = trim($_POST['nama_praktikum']);
    $deskripsi = trim($_POST['deskripsi']);

    if (empty($nama_praktikum)) {
        $message = "Nama mata praktikum wajib diisi!";
        $message_type = "error";
    } else {
        if (isset($_POST['update_praktikum'])) { // Proses Update
            $id = $_POST['id'];
            $sql = "UPDATE praktikum SET nama_praktikum = ?, deskripsi = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("ssi", $nama_praktikum, $deskripsi, $id);
                if ($stmt->execute()) {
                    $message = "Data praktikum berhasil diperbarui.";
                    $message_type = "success";
                } else {
                    $message = "Gagal memperbarui: " . ($stmt->errno == 1062 ? "Nama praktikum sudah ada." : $stmt->error);
                    $message_type = "error";
                }
                $stmt->close();
            } else {
                $message = "Gagal menyiapkan statement: " . $conn->error;
                $message_type = "error";
            }
        } else { // Proses Tambah
            $sql = "INSERT INTO praktikum (nama_praktikum, deskripsi) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("ss", $nama_praktikum, $deskripsi);
                if ($stmt->execute()) {
                    $message = "Mata praktikum berhasil ditambahkan.";
                    $message_type = "success";
                } else {
                    $message = "Gagal menambahkan: " . ($stmt->errno == 1062 ? "Nama praktikum sudah ada." : $stmt->error);
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

// --- Logika Edit (mengambil data untuk form) ---
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $sql = "SELECT id, nama_praktikum, deskripsi FROM praktikum WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 1) {
            $praktikum_data = $result->fetch_assoc();
            $edit_mode = true;
        } else {
            $message = "Data praktikum tidak ditemukan.";
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
    $sql = "DELETE FROM praktikum WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $message = "Mata praktikum berhasil dihapus.";
            $message_type = "success";
        } else {
            $message = "Gagal menghapus mata praktikum: " . $stmt->error;
            $message_type = "error";
        }
        $stmt->close();
    } else {
        $message = "Gagal menyiapkan statement: " . $conn->error;
        $message_type = "error";
    }
}

// --- Ambil Data Mata Praktikum untuk Ditampilkan ---
$mata_praktikum = [];
$sql_select = "SELECT id, nama_praktikum, deskripsi FROM praktikum ORDER BY nama_praktikum ASC";
$result_select = $conn->query($sql_select);
if ($result_select) {
    $mata_praktikum = $result_select->fetch_all(MYSQLI_ASSOC);
} else {
    $message = "Gagal mengambil data mata praktikum: " . $conn->error;
    $message_type = "error";
}

?>

<main class="container">
    <div class="page-header">
        <h2>Kelola Mata Praktikum</h2>
        <p>Tambah, edit, atau hapus mata praktikum yang tersedia.</p>
    </div>

    <?php if (!empty($message)): ?>
        <div class="message <?php echo $message_type; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <h3><?php echo $edit_mode ? 'Edit' : 'Tambah'; ?> Mata Praktikum</h3>
        <form action="kelola_mata_praktikum.php" method="POST">
            <?php if ($edit_mode): ?>
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($praktikum_data['id']); ?>">
                <input type="hidden" name="update_praktikum" value="1">
            <?php endif; ?>
            <div class="form-group">
                <label for="nama_praktikum">Nama Mata Praktikum</label>
                <input type="text" id="nama_praktikum" name="nama_praktikum" value="<?php echo htmlspecialchars($praktikum_data['nama_praktikum']); ?>" required>
            </div>
            <div class="form-group">
                <label for="deskripsi">Deskripsi</label>
                <textarea id="deskripsi" name="deskripsi" rows="4"><?php echo htmlspecialchars($praktikum_data['deskripsi']); ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary"><?php echo $edit_mode ? 'Perbarui' : 'Tambah'; ?> Praktikum</button>
            <?php if ($edit_mode): ?>
                <a href="kelola_mata_praktikum.php" class="btn btn-secondary" style="margin-left: 10px;">Batal Edit</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="card" style="margin-top: 30px;">
        <h3>Daftar Mata Praktikum</h3>
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 1px solid var(--border-color);">
                    <th style="padding: 12px; text-align: left;">Nama Praktikum</th>
                    <th style="padding: 12px; text-align: left;">Deskripsi</th>
                    <th style="width: 15%; padding: 12px; text-align: left;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($mata_praktikum)): ?>
                    <?php foreach ($mata_praktikum as $praktikum): ?>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="font-weight: 600; color: var(--text-primary); padding: 12px;"><?php echo htmlspecialchars($praktikum['nama_praktikum']); ?></td>
                            <td style="color: var(--text-secondary); padding: 12px;"><?php echo htmlspecialchars($praktikum['deskripsi']); ?></td>
                            <td style="display:flex; gap:16px; padding: 12px;">
                                <a href="kelola_mata_praktikum.php?action=edit&id=<?php echo $praktikum['id']; ?>" style="color:var(--primary-color); text-decoration:none; font-weight:600;">Edit</a>
                                <a href="kelola_mata_praktikum.php?action=delete&id=<?php echo $praktikum['id']; ?>" onclick="return confirm('Anda yakin ingin menghapus data ini? Semua modul dan pendaftaran terkait akan ikut terhapus!');" style="color: #EF4444; text-decoration:none; font-weight:600;">Hapus</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3" style="text-align:center; padding: 40px; color: var(--text-secondary);">Belum ada mata praktikum yang ditambahkan.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

<?php include 'templates/footer_asisten.php'; ?>