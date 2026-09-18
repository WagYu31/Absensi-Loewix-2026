<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['nip']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak. Silakan login sebagai Admin.']);
    exit();
}

include '../conn.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'get_account':
        $nip = trim($_GET['nip'] ?? $_POST['nip'] ?? '');
        if (empty($nip)) {
            echo json_encode(['status' => 'error', 'message' => 'NIP karyawan tidak boleh kosong.']);
            exit();
        }

        // Get employee basic info
        $stmt_kar = $conn->prepare("SELECT nip, nik, nama, email, jabatan FROM karyawan WHERE nip = ? LIMIT 1");
        $stmt_kar->bind_param("s", $nip);
        $stmt_kar->execute();
        $res_kar = $stmt_kar->get_result();

        if ($res_kar->num_rows === 0) {
            echo json_encode(['status' => 'error', 'message' => 'Data karyawan tidak ditemukan.']);
            $stmt_kar->close();
            exit();
        }

        $kar_data = $res_kar->fetch_assoc();
        $stmt_kar->close();

        // Get user account info from users table
        $stmt_user = $conn->prepare("SELECT username, role FROM users WHERE nip = ? LIMIT 1");
        $stmt_user->bind_param("s", $nip);
        $stmt_user->execute();
        $res_user = $stmt_user->get_result();

        $user_account = null;
        if ($res_user->num_rows > 0) {
            $user_account = $res_user->fetch_assoc();
        }
        $stmt_user->close();

        echo json_encode([
            'status' => 'success',
            'data' => [
                'nip' => $kar_data['nip'],
                'nik' => $kar_data['nik'],
                'nama' => $kar_data['nama'],
                'email' => $kar_data['email'],
                'jabatan' => $kar_data['jabatan'],
                'has_account' => ($user_account !== null),
                'username' => $user_account ? $user_account['username'] : '',
                'role' => $user_account ? $user_account['role'] : 'karyawan'
            ]
        ]);
        break;

    case 'save_account':
        $nip = trim($_POST['nip'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $role = trim($_POST['role'] ?? 'karyawan');

        if (empty($nip)) {
            echo json_encode(['status' => 'error', 'message' => 'NIP karyawan wajib diisi.']);
            exit();
        }

        if (empty($username)) {
            echo json_encode(['status' => 'error', 'message' => 'Username tidak boleh kosong.']);
            exit();
        }

        // Validate that employee exists
        $stmt_check_kar = $conn->prepare("SELECT nama FROM karyawan WHERE nip = ? LIMIT 1");
        $stmt_check_kar->bind_param("s", $nip);
        $stmt_check_kar->execute();
        if ($stmt_check_kar->get_result()->num_rows === 0) {
            echo json_encode(['status' => 'error', 'message' => 'Karyawan tidak ditemukan.']);
            $stmt_check_kar->close();
            exit();
        }
        $stmt_check_kar->close();

        // Check if username is used by another NIP
        $stmt_check_user = $conn->prepare("SELECT nip FROM users WHERE username = ? AND nip != ? LIMIT 1");
        $stmt_check_user->bind_param("ss", $username, $nip);
        $stmt_check_user->execute();
        if ($stmt_check_user->get_result()->num_rows > 0) {
            echo json_encode(['status' => 'error', 'message' => "Username '$username' sudah digunakan oleh pengguna lain!"]);
            $stmt_check_user->close();
            exit();
        }
        $stmt_check_user->close();

        // Check if user already exists in users table
        $stmt_exist = $conn->prepare("SELECT nip FROM users WHERE nip = ? LIMIT 1");
        $stmt_exist->bind_param("s", $nip);
        $stmt_exist->execute();
        $user_exists = ($stmt_exist->get_result()->num_rows > 0);
        $stmt_exist->close();

        if ($user_exists) {
            // Update existing user
            if (!empty($password)) {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt_upd = $conn->prepare("UPDATE users SET username = ?, password = ?, role = ? WHERE nip = ?");
                $stmt_upd->bind_param("ssss", $username, $hashed, $role, $nip);
            } else {
                $stmt_upd = $conn->prepare("UPDATE users SET username = ?, role = ? WHERE nip = ?");
                $stmt_upd->bind_param("sss", $username, $role, $nip);
            }

            if ($stmt_upd->execute()) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Akun login berhasil diperbarui! ' . (!empty($password) ? "Password baru telah disetel: <b>$password</b>" : ''),
                    'username' => $username,
                    'password_changed' => !empty($password)
                ]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui akun: ' . $conn->error]);
            }
            $stmt_upd->close();
        } else {
            // Insert new user
            if (empty($password)) {
                echo json_encode(['status' => 'error', 'message' => 'Password wajib diisi untuk pembuatan akun baru.']);
                exit();
            }

            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt_ins = $conn->prepare("INSERT INTO users (nip, username, password, role) VALUES (?, ?, ?, ?)");
            $stmt_ins->bind_param("ssss", $nip, $username, $hashed, $role);

            if ($stmt_ins->execute()) {
                echo json_encode([
                    'status' => 'success',
                    'message' => "Akun login berhasil dibuat! Username: <b>$username</b> | Password: <b>$password</b>",
                    'username' => $username,
                    'password_changed' => true
                ]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Gagal membuat akun: ' . $conn->error]);
            }
            $stmt_ins->close();
        }
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Aksi tidak dikenali.']);
        break;
}

$conn->close();
?>
