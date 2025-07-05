<?php
session_start();
require_once '../config.php';
include 'templates/header_mahasiswa.php';

$pageTitle = "Praktikum Saya";

$message = '';
$message_type = '';

$id_mahasiswa = null;
if (isset($_SESSION['user_id'])) {
    $id_mahasiswa = $_SESSION['user_id'];
} else {
    header("Location: ../login.php");
    exit();
}

$joined_praktikum = [];

if ($id_mahasiswa) {
    $sql = "SELECT p.id, p.nama_praktikum, p.deskripsi
        FROM peserta_praktikum pp  -- Nama tabel diubah
        JOIN praktikum p ON pp.id_praktikum = p.id -- Nama tabel dan kolom join diubah
        WHERE pp.id_mahasiswa = ?
        ORDER BY p.nama_praktikum ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_mahasiswa);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        $joined_praktikum = $result->fetch_all(MYSQLI_ASSOC);
    } else {
        $message = "Gagal mengambil data praktikum Anda: " . $conn->error;
        $message_type = "error";
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
    /* Menambahkan properti ini untuk mengatasi teks panjang ke samping */
    .praktikum-card p {
        color: var(--text-secondary);
        flex-grow: 1;
        line-height: 1.6;
        word-wrap: break-word; /* Memastikan teks panjang memecah baris */
        overflow-wrap: break-word; /* Alternatif untuk word-wrap */
        white-space: normal; /* Memastikan teks normal tidak satu baris */
    }
    .praktikum-card .btn {
        margin-top: 15px; /* Memberi sedikit jarak antara deskripsi dan tombol */
        align-self: flex-start; /* Memastikan tombol tidak meregang penuh */
    }
</style>

<div class="page-header">
    <h2>Praktikum yang Saya Ikuti</h2>
    <p>Lihat detail modul, materi, dan kumpulkan laporan tugas Anda di sini.</p>
</div>

<?php if (!empty($message)): ?>
    <div class="message <?php echo $message_type; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<div class="card-grid">
    <?php if (!empty($joined_praktikum)): ?>
        <?php foreach ($joined_praktikum as $praktikum): ?>
            <div class="praktikum-card">
                <h3><?php echo htmlspecialchars($praktikum['nama_praktikum']); ?></h3>
                <p><?php echo htmlspecialchars($praktikum['deskripsi']); ?></p>
                <a href="detail_praktikum.php?id=<?php echo htmlspecialchars($praktikum['id']); ?>" class="btn btn-primary">Lihat Detail</a>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="card" style="grid-column: 1 / -1; text-align: center; padding: 40px; color: var(--text-secondary);">
            <p>Anda belum mengikuti praktikum apa pun. Jelajahi <a href="mata_praktikum.php" style="color: var(--primary-color); text-decoration: none; font-weight: 600;">Mata Praktikum</a> untuk mendaftar.</p>
        </div>
    <?php endif; ?>
</div>

<?php include 'templates/footer_mahasiswa.php'; ?>