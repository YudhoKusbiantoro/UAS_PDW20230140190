<?php
session_start();
require_once '../config.php';

// Pastikan hanya asisten yang bisa mengakses halaman ini
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'asisten') {
    header("Location: ../login.php");
    exit();
}

$pageTitle = "Kelola Pengguna";
include 'templates/header_asisten.php';

$message = '';
$message_type = '';
$edit_mode = false;
$user_data = ['id' => '', 'nama' => '', 'email' => '', 'role' => 'mahasiswa'];

// --- Logika Tambah & Update Pengguna ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama']);
    $email = trim($_POST['email']);
    $role = trim($_POST['role']);
    $password = $_POST['password'] ?? ''; // Password bisa kosong jika update

    if (empty($nama) || empty($email) || empty($role)) {
        $message = "Nama, email, dan peran wajib diisi!";
        $message_type = "error";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Format email tidak valid!";
        $message_type = "error";
    } elseif (!in_array($role, ['mahasiswa', 'asisten'])) {
        $message = "Peran tidak valid!";
        $message_type = "error";
    } else {
        if (isset($_POST['update_user'])) { // Proses Update
            $id = $_POST['id'];
            $hashed_password = null;

            // Cek apakah email sudah digunakan oleh pengguna lain
            $sql_check_email = "SELECT id FROM users WHERE email = ? AND id != ?";
            $stmt_check_email = $conn->prepare($sql_check_email);
            if ($stmt_check_email) {
                $stmt_check_email->bind_param("si", $email, $id);
                $stmt_check_email->execute();
                $stmt_check_email->store_result();
                if ($stmt_check_email->num_rows > 0) {
                    $message = "Email sudah digunakan oleh pengguna lain.";
                    $message_type = "error";
                    goto end_post_logic; // Lompat ke bagian akhir logika POST
                }
                $stmt_check_email->close();
            }

            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                $sql = "UPDATE users SET nama = ?, email = ?, password = ?, role = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                if ($stmt) {
                    $stmt->bind_param("ssssi", $nama, $email, $hashed_password, $role, $id);
                }
            } else {
                $sql = "UPDATE users SET nama = ?, email = ?, role = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                if ($stmt) {
                    $stmt->bind_param("sssi", $nama, $email, $role, $id);
                }
            }

            if ($stmt) {
                if ($stmt->execute()) {
                    $message = "Data pengguna berhasil diperbarui.";
                    $message_type = "success";
                } else {
                    $message = "Gagal memperbarui pengguna: " . $stmt->error;
                    $message_type = "error";
                }
                $stmt->close();
            } else {
                $message = "Gagal menyiapkan statement: " . $conn->error;
                $message_type = "error";
            }
        } else { // Proses Tambah
            if (empty($password)) {
                $message = "Password wajib diisi untuk pengguna baru!";
                $message_type = "error";
            } else {
                // Cek apakah email sudah terdaftar
                $sql_check_email = "SELECT id FROM users WHERE email = ?";
                $stmt_check_email = $conn->prepare($sql_check_email);
                if ($stmt_check_email) {
                    $stmt_check_email->bind_param("s", $email);
                    $stmt_check_email->execute();
                    $stmt_check_email->store_result();
                    if ($stmt_check_email->num_rows > 0) {
                        $message = "Email sudah terdaftar. Silakan gunakan email lain.";
                        $message_type = "error";
                        goto end_post_logic;
                    }
                    $stmt_check_email->close();
                }

                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                $sql = "INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                if ($stmt) {
                    $stmt->bind_param("ssss", $nama, $email, $hashed_password, $role);
                    if ($stmt->execute()) {
                        $message = "Pengguna berhasil ditambahkan.";
                        $message_type = "success";
                    } else {
                        $message = "Gagal menambahkan pengguna: " . $stmt->error;
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
    end_post_logic:; // Label untuk goto
}

// --- Logika Edit (mengambil data untuk form) ---
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $sql = "SELECT id, nama, email, role FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 1) {
            $user_data = $result->fetch_assoc();
            $edit_mode = true;
        } else {
            $message = "Data pengguna tidak ditemukan.";
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

    // Pastikan asisten tidak menghapus dirinya sendiri
    if ($id == $_SESSION['user_id']) {
        $message = "Anda tidak bisa menghapus akun Anda sendiri!";
        $message_type = "error";
    } else {
        $sql = "DELETE FROM users WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $message = "Pengguna berhasil dihapus.";
                $message_type = "success";
            } else {
                $message = "Gagal menghapus pengguna: " . $stmt->error;
                $message_type = "error";
            }
            $stmt->close();
        } else {
            $message = "Gagal menyiapkan statement: " . $conn->error;
            $message_type = "error";
        }
    }
}

// --- Ambil Data Pengguna untuk Ditampilkan ---
$users_list = [];
$sql_select = "SELECT id, nama, email, role FROM users ORDER BY role ASC, nama ASC";
$result_select = $conn->query($sql_select);
if ($result_select) {
    $users_list = $result_select->fetch_all(MYSQLI_ASSOC);
} else {
    $message = "Gagal mengambil data pengguna: " . $conn->error;
    $message_type = "error";
}

?>

<div class="page-header">
    <h2>Kelola Pengguna</h2>
    <p>Mengelola akun untuk mahasiswa dan asisten.</p>
</div>

<?php if (!empty($message)): ?>
    <div class="message <?php echo $message_type; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<div class="card">
    <h3><?php echo $edit_mode ? 'Edit' : 'Tambah'; ?> Pengguna Baru</h3>
    <form action="kelola_pengguna.php" method="POST" style="display: flex; flex-direction: column; gap: 16px;">
        <?php if ($edit_mode): ?>
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($user_data['id']); ?>">
            <input type="hidden" name="update_user" value="1">
        <?php endif; ?>
         <div class="form-group">
            <label for="nama">Nama Lengkap</label>
            <input type="text" id="nama" name="nama" value="<?php echo htmlspecialchars($user_data['nama']); ?>" required>
        </div>
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
        </div>
        <div class="form-group">
            <label for="password">Password <?php echo $edit_mode ? '(Kosongkan jika tidak ingin mengubah)' : ''; ?></label>
            <input type="password" id="password" name="password" <?php echo $edit_mode ? '' : 'required'; ?>>
        </div>
        <div class="form-group">
            <label for="role">Peran</label>
            <select id="role" name="role" required>
                <option value="mahasiswa" <?php echo ($user_data['role'] == 'mahasiswa') ? 'selected' : ''; ?>>Mahasiswa</option>
                <option value="asisten" <?php echo ($user_data['role'] == 'asisten') ? 'selected' : ''; ?>>Asisten</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary"><?php echo $edit_mode ? 'Perbarui Pengguna' : 'Tambah Pengguna'; ?></button>
        <?php if ($edit_mode): ?>
            <a href="kelola_pengguna.php" class="btn btn-secondary" style="margin-left: 10px;">Batal Edit</a>
        <?php endif; ?>
    </form>
</div>

<div class="card" style="margin-top: 30px;">
    <h3>Daftar Pengguna</h3>
    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="border-bottom: 1px solid var(--border-color);">
                <th style="padding: 12px; text-align: left;">Nama</th>
                <th style="padding: 12px; text-align: left;">Email</th>
                <th style="padding: 12px; text-align: left;">Peran</th>
                <th style="padding: 12px; text-align: left;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($users_list)): ?>
                <?php foreach ($users_list as $user): ?>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 12px;"><?php echo htmlspecialchars($user['nama']); ?></td>
                        <td style="padding: 12px;"><?php echo htmlspecialchars($user['email']); ?></td>
                        <td style="padding: 12px;"><?php echo htmlspecialchars(ucfirst($user['role'])); ?></td>
                        <td style="padding: 12px; display:flex; gap:15px;">
                            <a href="kelola_pengguna.php?action=edit&id=<?php echo $user['id']; ?>" style="color:var(--primary-color); text-decoration:none;">Edit</a>
                            <?php if ($user['id'] != $_SESSION['user_id']): // Tidak bisa menghapus akun sendiri ?>
                                <a href="kelola_pengguna.php?action=delete&id=<?php echo $user['id']; ?>" onclick="return confirm('Anda yakin ingin menghapus pengguna ini? Semua data terkait (praktikum, laporan, nilai) akan ikut terhapus!');" style="color: #dc3545; text-decoration:none;">Hapus</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4" style="text-align:center; padding: 40px; color: var(--text-secondary);">Belum ada pengguna terdaftar.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include 'templates/footer_asisten.php'; ?>