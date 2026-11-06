<?php
require_once __DIR__ . '/../_admin_session.php';
require_once __DIR__ . '/../../koneksi/koneksi.php';

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if ($username === '' || $password === '') {
    $_SESSION['error'] = "Username dan password wajib diisi!";
    session_write_close();
    header("Location: ../login.php");
    exit;
}

$sql = "SELECT id, username, password, status
        FROM admin
        WHERE username = ?
        LIMIT 1";

$stmt = mysqli_prepare($koneksi, $sql);
mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$admin  = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$admin) {
    $_SESSION['error'] = "Login gagal!";
    session_write_close();
    header("Location: ../login.php");
    exit;
}

if (strtolower($admin['status']) !== 'active') {
    $_SESSION['error'] = "Akun tidak aktif.";
    session_write_close();
    header("Location: ../login.php");
    exit;
}

if (!password_verify($password, $admin['password'])) {
    $_SESSION['error'] = "Login gagal!";
    session_write_close();
    header("Location: ../login.php");
    exit;
}

// LOGIN BERHASIL
session_regenerate_id(true);

$_SESSION['login_admin'] = true;
$_SESSION['admin_id'] = (int)$admin['id'];
$_SESSION['admin_username'] = $admin['username'];

session_write_close();

header("Location: ../index.php");
exit;