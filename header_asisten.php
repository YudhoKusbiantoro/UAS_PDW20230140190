<?php
if (session_status() == PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'asisten') {
    header("Location: ../login.php");
    exit();
}
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Dashboard Asisten'; ?> - SIMPRAK</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        :root {
            --primary-color: #3B82F6; --primary-light: #EFF6FF;
            --sidebar-bg: #1F2937; --sidebar-text: #D1D5DB; --sidebar-hover: #374151; --sidebar-active: #3B82F6;
            --main-bg: #F9FAFB;
            --text-primary: #1F2937; --text-secondary: #6B7280;
            --border-color: #E5E7EB; --card-bg: #FFFFFF;
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            margin: 0;
            background-color: var(--main-bg);
            color: var(--text-primary);
            display: flex;
        }
        .sidebar {
            width: 260px;
            background-color: var(--sidebar-bg);
            display: flex;
            flex-direction: column;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            padding: 16px;
        }
        .sidebar-header {
            font-size: 24px;
            font-weight: 700;
            color: #FFFFFF;
            padding: 16px;
            text-align: center;
            margin-bottom: 16px;
        }
        .sidebar-nav { flex-grow: 1; list-style: none; padding: 0; margin: 0; }
        .sidebar-nav li a {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 14px 16px;
            margin-bottom: 8px;
            text-decoration: none;
            color: var(--sidebar-text);
            font-weight: 500;
            border-radius: 8px;
            transition: background-color 0.2s, color 0.2s;
        }
        .sidebar-nav li a .bx { font-size: 22px; }
        .sidebar-nav li a:hover { background-color: var(--sidebar-hover); color: #FFFFFF; }
        .sidebar-nav li a.active { background-color: var(--sidebar-active); color: #FFFFFF; font-weight: 600; }
        .sidebar-footer {
            padding: 16px;
            border-top: 1px solid #374151;
        }
        .user-profile { display: flex; align-items: center; gap: 12px; color: #FFFFFF; }
        .user-profile .avatar {
            width: 40px; height: 40px;
            background-color: var(--primary-color);
            display: flex; align-items: center; justify-content: center;
            border-radius: 50%; font-weight: 600;
        }
        .user-profile .user-info { line-height: 1.4; }
        .user-profile .name { font-weight: 600; }
        .user-profile .role { font-size: 14px; color: var(--sidebar-text); }
        .logout-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            text-align: center;
            padding: 12px;
            background-color: var(--sidebar-hover);
            color: #FFFFFF;
            text-decoration: none;
            font-weight: 500;
            border-radius: 8px;
            margin-top: 16px;
        }
        .main-content {
            margin-left: 260px;
            flex-grow: 1;
            padding: 40px;
            overflow-y: auto;
        }
        .page-header { margin-bottom: 32px; }
        .page-header h2 { font-size: 28px; font-weight: 700; margin: 0 0 8px 0; }
        .page-header p { color: var(--text-secondary); margin: 0; }
        .card {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
        }
        .card h3 { font-size: 20px; font-weight: 600; margin: 0 0 20px 0; }
        /* General Styles for Form, Table, etc. */
        .form-group label { display: block; margin-bottom: 8px; font-weight: 500; font-size: 14px; color: var(--text-primary); }
        .form-group .input-field {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-group .input-field:focus { outline: none; border-color: var(--primary-color); box-shadow: 0 0 0 3px var(--primary-light); }
        .btn { padding: 12px 20px; border: none; border-radius: 8px; font-size: 14px; font-weight: 700; cursor: pointer; transition: background-color 0.2s; text-decoration: none; display: inline-block; }
        .btn-primary { background-color: var(--primary-color); color: white; }
        .btn-danger { background-color: #FEE2E2; color: #DC2626; }
        table { width: 100%; border-collapse: collapse; }
        thead { border-bottom: 1px solid var(--border-color); }
        th { padding: 12px 16px; text-align: left; font-size: 12px; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; }
        td { padding: 16px; vertical-align: middle; }
        tbody tr { border-bottom: 1px solid var(--border-color); }
        tbody tr:last-child { border-bottom: none; }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="sidebar-header">SIMPRAK</div>
        <ul class="sidebar-nav">
            <li><a href="dashboard.php" class="<?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>"><i class='bx bxs-dashboard'></i> Dashboard</a></li>
            <li><a href="kelola_mata_praktikum.php" class="<?php echo ($current_page == 'kelola_mata_praktikum.php') ? 'active' : ''; ?>"><i class='bx bx-book-content'></i> Mata Praktikum</a></li>
            <li><a href="kelola_modul.php" class="<?php echo ($current_page == 'kelola_modul.php') ? 'active' : ''; ?>"><i class='bx bx-sitemap'></i> Modul</a></li>
            <li><a href="laporan_masuk.php" class="<?php echo ($current_page == 'laporan_masuk.php') ? 'active' : ''; ?>"><i class='bx bx-file'></i> Laporan Masuk</a></li>
            <li><a href="kelola_pengguna.php" class="<?php echo ($current_page == 'kelola_pengguna.php') ? 'active' : ''; ?>"><i class='bx bx-group'></i> Pengguna</a></li>
        </ul>
        <div class="sidebar-footer">
            <div class="user-profile">
                <div class="avatar"><?php echo strtoupper(substr($_SESSION['nama'], 0, 1)); ?></div>
                <div class="user-info">
                    <p class="name"><?php echo htmlspecialchars($_SESSION['nama']); ?></p>
                    <p class="role"><?php echo ucfirst($_SESSION['role']); ?></p>
                </div>
            </div>
            <a href="../logout.php" class="logout-btn"><i class='bx bx-log-out'></i> Logout</a>
        </div>
    </aside>
    <main class="main-content">