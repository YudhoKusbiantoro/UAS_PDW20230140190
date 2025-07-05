<?php
session_start();
require_once '../config.php'; // <--- Panggil config.php DULUAN
// Memastikan header_mahasiswa.php dipanggil setelah session_start() dan config.php
// agar session dan koneksi database tersedia di header.
include 'templates/header_mahasiswa.php';

$pageTitle = "Cari Mata Praktikum";

$message = '';
$message_type = '';

// --- Logika Pendaftaran Praktikum ---
// Pastikan ID mahasiswa yang login dari sesi
$id_mahasiswa = null;
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'mahasiswa') {
    $id_mahasiswa = $_SESSION['user_id'];
} else {
    // Jika tidak ada user_id di sesi atau bukan mahasiswa, redirect ke login.php
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['daftar_praktikum'])) {
    $id_praktikum_to_register = intval($_POST['id_praktikum']);

    // Cek apakah mahasiswa sudah terdaftar di praktikum ini
    $sql_check = "SELECT COUNT(*) AS count FROM peserta_praktikum WHERE id_mahasiswa = ? AND id_praktikum = ?";
    $stmt_check = $conn->prepare($sql_check);
    if ($stmt_check) {
        $stmt_check->bind_param("ii", $id_mahasiswa, $id_praktikum_to_register);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        $row_check = $result_check->fetch_assoc();
        $stmt_check->close();

        if ($row_check['count'] > 0) {
            $message = "Anda sudah terdaftar di praktikum ini!";
            $message_type = "warning";
        } else {
            // Lakukan pendaftaran
            $sql_insert = "INSERT INTO peserta_praktikum (id_mahasiswa, id_praktikum) VALUES (?, ?)";
            $stmt_insert = $conn->prepare($sql_insert);
            if ($stmt_insert) {
                $stmt_insert->bind_param("ii", $id_mahasiswa, $id_praktikum_to_register);
                if ($stmt_insert->execute()) {
                    $message = "Anda berhasil mendaftar ke praktikum!";
                    $message_type = "success";
                } else {
                    $message = "Gagal mendaftar praktikum: " . $stmt_insert->error;
                    $message_type = "error";
                }
                $stmt_insert->close();
            } else {
                $message = "Gagal menyiapkan statement pendaftaran: " . $conn->error;
                $message_type = "error";
            }
        }
    } else {
        $message = "Gagal menyiapkan statement cek pendaftaran: " . $conn->error;
        $message_type = "error";
    }
}

// --- Ambil Data Mata Praktikum yang Tersedia dari Database ---
$available_praktikum = [];
$sql_select_praktikum = "SELECT id, nama_praktikum, deskripsi FROM praktikum ORDER BY nama_praktikum ASC";
$result_select_praktikum = $conn->query($sql_select_praktikum);

if ($result_select_praktikum) {
    $available_praktikum = $result_select_praktikum->fetch_all(MYSQLI_ASSOC);
} else {
    $message = "Gagal mengambil data mata praktikum: " . $conn->error;
    $message_type = "error";
}

// Ambil daftar praktikum yang sudah diikuti oleh mahasiswa yang login
$registered_praktikum_ids = [];
if ($id_mahasiswa) {
    $sql_registered = "SELECT id_praktikum FROM peserta_praktikum WHERE id_mahasiswa = ?";
    $stmt_registered = $conn->prepare($sql_registered);
    if ($stmt_registered) {
        $stmt_registered->bind_param("i", $id_mahasiswa);
        $stmt_registered->execute();
        $result_registered = $stmt_registered->get_result();
        while ($row = $result_registered->fetch_assoc()) {
            $registered_praktikum_ids[] = $row['id_praktikum'];
        }
        $stmt_registered->close();
    }
}
?>

<style>
    .card-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 24px;
    }
    .praktikum-card {
        background-color: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 24px;
        display: flex;
        flex-direction: column;
    }
    .praktikum-card h3 {
        margin-top: 0;
        font-size: 20px;
        color: var(--text-primary);
    }
    .praktikum-card p {
        color: var(--text-secondary);
        flex-grow: 1;
        line-height: 1.6;
    }
    .praktikum-card .btn {
        margin-top: 15px;
        width: 100%;
        padding: 12px;
        font-size: 16px;
    }
    .praktikum-card .registered-tag {
        display: inline-block;
        background-color: #D1FAE5;
        color: #065F46;
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 600;
        margin-top: 10px;
        cursor: default;
    }
</style>

<div class="page-header">
    <h2>Cari Mata Praktikum</h2>
    <p>Temukan dan daftar ke mata praktikum yang tersedia.</p>
</div>

<?php if (!empty($message)): ?>
    <div class="message <?php echo $message_type; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<div class="card-grid">
    <?php if (!empty($available_praktikum)): ?>
        <?php foreach ($available_praktikum as $praktikum): ?>
            <div class="praktikum-card">
                <h3><?php echo htmlspecialchars($praktikum['nama_praktikum']); ?></h3>
                <p><?php echo nl2br(htmlspecialchars($praktikum['deskripsi'])); ?></p>
                <?php if (in_array($praktikum['id'], $registered_praktikum_ids)): ?>
                    <span class="registered-tag"><i class='bx bx-check-circle'></i> Sudah Terdaftar</span>
                    <a href="detail_praktikum.php?id=<?php echo $praktikum['id']; ?>" class="btn btn-primary" style="margin-top: 10px;">Lihat Detail</a>
                <?php else: ?>
                    <form action="mata_praktikum.php" method="POST">
                        <input type="hidden" name="id_praktikum" value="<?php echo htmlspecialchars($praktikum['id']); ?>">
                        <button type="submit" name="daftar_praktikum" class="btn btn-primary">Daftar Praktikum</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p style="grid-column: 1 / -1; text-align: center; padding: 40px; color: var(--text-secondary);">Tidak ada mata praktikum yang tersedia saat ini.</p>
    <?php endif; ?>
</div>

<?php include 'templates/footer_mahasiswa.php'; ?>