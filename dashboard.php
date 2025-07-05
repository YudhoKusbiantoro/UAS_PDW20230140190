<?php
session_start();
require_once '../config.php';

// Pastikan hanya mahasiswa yang bisa mengakses halaman ini
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mahasiswa') {
    header("Location: ../login.php");
    exit();
}

$pageTitle = "Dashboard Mahasiswa";
include 'templates/header_mahasiswa.php';
$namaPengguna = $_SESSION['nama'] ?? 'Pengguna';

// Anda bisa menambahkan logika untuk mengambil data ringkasan mahasiswa di sini
// Misalnya, jumlah praktikum yang diikuti, laporan yang belum dinilai, notifikasi, dll.

// Contoh mengambil notifikasi (jika Anda ingin menampilkannya di dashboard)
$notifications = [];
$sql_notif = "SELECT pesan, icon, created_at FROM notifikasi WHERE id_mahasiswa = ? ORDER BY created_at DESC LIMIT 5";
$stmt_notif = $conn->prepare($sql_notif);
if ($stmt_notif) {
    $stmt_notif->bind_param("i", $_SESSION['user_id']);
    $stmt_notif->execute();
    $result_notif = $stmt_notif->get_result();
    $notifications = $result_notif->fetch_all(MYSQLI_ASSOC);
    $stmt_notif->close();
}
?>

<div class="page-header">
    <h2>Selamat Datang, <?php echo htmlspecialchars($namaPengguna); ?>!</h2>
    <p>Selamat belajar! Gunakan menu di samping untuk memulai dan melihat progres praktikum Anda.</p>
</div>

<div class="card" style="margin-bottom: 24px;">
    <h3><i class='bx bxs-megaphone' style="vertical-align: middle; margin-right: 5px;"></i> Pengumuman</h3>
    <?php if (!empty($notifications)): ?>
        <ul style="list-style: none; padding: 0;">
            <?php foreach ($notifications as $notif): ?>
                <li style="padding: 10px 0; border-bottom: 1px dashed var(--border-color);">
                    <span style="margin-right: 8px;"><?php echo htmlspecialchars($notif['icon']); ?></span>
                    <?php echo htmlspecialchars($notif['pesan']); ?>
                    <small style="color: var(--text-secondary); float: right;"><?php echo date('d M Y H:i', strtotime($notif['created_at'])); ?></small>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p style="color: var(--text-secondary);">Saat ini belum ada pengumuman penting atau notifikasi untuk Anda. Silakan periksa kembali nanti.</p>
    <?php endif; ?>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 24px;">
    <div class="card">
        <h3>Praktikum Diikuti</h3>
        <?php
        $sql_total_joined = "SELECT COUNT(id) AS total FROM peserta_praktikum WHERE id_mahasiswa = ?";
        $stmt_total_joined = $conn->prepare($sql_total_joined);
        $total_joined = 0;
        if ($stmt_total_joined) {
            $stmt_total_joined->bind_param("i", $_SESSION['user_id']);
            $stmt_total_joined->execute();
            $result_total_joined = $stmt_total_joined->get_result();
            if ($row = $result_total_joined->fetch_assoc()) {
                $total_joined = $row['total'];
            }
            $stmt_total_joined->close();
        }
        ?>
        <p style="font-size: 36px; font-weight: 700; margin: 0;"><?php echo $total_joined; ?></p>
        <small><a href="praktikum_saya.php">Lihat Praktikum Saya</a></small>
    </div>
    <div class="card">
        <h3>Laporan Menunggu Nilai</h3>
        <?php
        $sql_laporan_menunggu = "
            SELECT COUNT(l.id) AS total
            FROM laporan l
            LEFT JOIN nilai n ON l.id_user = n.id_user AND l.id_modul = n.id_modul
            WHERE l.id_user = ? AND n.id IS NULL
        ";
        $stmt_laporan_menunggu = $conn->prepare($sql_laporan_menunggu);
        $total_menunggu = 0;
        if ($stmt_laporan_menunggu) {
            $stmt_laporan_menunggu->bind_param("i", $_SESSION['user_id']);
            $stmt_laporan_menunggu->execute();
            $result_laporan_menunggu = $stmt_laporan_menunggu->get_result();
            if ($row = $result_laporan_menunggu->fetch_assoc()) {
                $total_menunggu = $row['total'];
            }
            $stmt_laporan_menunggu->close();
        }
        ?>
        <p style="font-size: 36px; font-weight: 700; margin: 0;"><?php echo $total_menunggu; ?></p>
        <small><a href="praktikum_saya.php">Tinjau Laporan Anda</a></small>
    </div>
</div>


<?php include 'templates/footer_mahasiswa.php'; ?>