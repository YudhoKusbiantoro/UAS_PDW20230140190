<?php
$pageTitle = "Detail Praktikum";
include 'templates/header_mahasiswa.php';
require_once '../config.php';
// ... Logika PHP untuk mengambil detail modul dan upload laporan ...
?>
<style>
    .modul-card { margin-bottom: 24px; }
    .modul-card .file-download {
        display: inline-flex; align-items: center; gap: 8px;
        background-color: #F3F4F6; color: var(--text-secondary);
        padding: 10px 16px; border-radius: 8px; text-decoration: none; font-weight: 600;
    }
    .modul-card .file-download:hover { background-color: #E5E7EB; }
    .report-section { border-top: 1px solid var(--border-color); margin-top: 24px; padding-top: 24px; }
    .report-status .label { font-size: 14px; color: var(--text-secondary); }
    .report-status .value { font-weight: 600; }
    .report-status .nilai { color: var(--primary-color); font-size: 20px; }
    .report-status .feedback { background-color: #F9FAFB; border-left: 3px solid #D1D5DB; padding: 12px; margin-top: 8px; font-style: italic; }
    .form-group label { display: block; margin-bottom: 8px; font-weight: 500; font-size: 14px; color: var(--text-primary); }
    .form-group .input-field { width: 100%; padding: 12px 16px; border: 1px solid var(--border-color); border-radius: 8px; }
</style>

<div class="page-header">
    <h2>Pemrograman Web Lanjut</h2>
    <p>Lihat detail modul, materi, dan kumpulkan laporan tugas Anda di sini.</p>
</div>

<div class="card modul-card">
    <h3>Modul 1: Pengenalan Framework</h3>
    <p>Pengenalan arsitektur MVC dan dasar-dasar penggunaan framework PHP modern.</p>
    <a href="#" class="file-download"><i class='bx bxs-file-pdf'></i> Unduh Materi Modul 1</a>

    <div class="report-section">
        <h4><i class='bx bxs-file-blank' style="vertical-align: middle; margin-right: 5px;"></i>Laporan Anda</h4>
        <div class="report-status">
            <p><span class="label">Status: </span><span class="value" style="color: #D97706;">Menunggu Penilaian</span></p>
            <p><span class="label">File: </span><a href="#" class="value">laporan_modul1_budi.pdf</a></p>
        </div>
        
        <form method="post" enctype="multipart/form-data" style="margin-top: 20px;">
            <div class="form-group">
                <label for="file_laporan_1">Unggah Ulang Laporan (jika ada perbaikan)</label>
                <input type="file" name="file_laporan" id="file_laporan_1" class="input-field">
            </div>
            <button type="submit" class="btn btn-primary" style="margin-top: 10px;">Unggah</button>
        </form>
    </div>
</div>

<div class="card modul-card">
    <h3>Modul 2: Interaksi Database</h3>
    <p>Mempelajari cara menghubungkan aplikasi dengan database menggunakan ORM.</p>
    <div class="report-section">
        <h4><i class='bx bxs-file-check' style="vertical-align: middle; margin-right: 5px;"></i>Laporan Anda</h4>
        <div class="report-status">
            <p><span class="label">Status: </span><span class="value" style="color: var(--primary-color);">Sudah Dinilai</span></p>
            <p><span class="label">Nilai: </span><span class="value nilai">92.50</span></p>
            <div class="feedback">
                <p class="label">Feedback dari Asisten:</p>
                "Kerja yang sangat baik! Penjelasan pada bagian query sudah sangat jelas."
            </div>
        </div>
    </div>
</div>

<?php include 'templates/footer_mahasiswa.php'; ?>