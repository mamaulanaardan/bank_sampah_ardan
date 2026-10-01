<?php
$base = "../";
require $base . "cek_login.php";
wajib_admin($is_admin, $base);

if ($_SERVER["REQUEST_METHOD"] !== "POST" || !cek_csrf()) {
    set_flash("error", "Permintaan tidak valid.");
    header("Location: index.php");
    exit();
}

$id = (int) ($_POST["id_user"] ?? 0);

if ($id === $id_user) {
    set_flash("error", "Anda tidak bisa menghapus akun yang sedang dipakai.");
    header("Location: index.php");
    exit();
}

$stmt = mysqli_prepare($koneksi, "SELECT nama, role FROM users WHERE id_user = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$target = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$target) {
    set_flash("error", "User tidak ditemukan.");
    header("Location: index.php");
    exit();
}

if ($target["role"] === "admin") {
    $cnt = mysqli_query($koneksi, "SELECT COUNT(*) AS jml FROM users WHERE role = 'admin'");
    if ((int) mysqli_fetch_assoc($cnt)["jml"] <= 1) {
        set_flash("error", "Tidak bisa menghapus satu-satunya admin.");
        header("Location: index.php");
        exit();
    }
}

try {
    $del = mysqli_prepare($koneksi, "DELETE FROM users WHERE id_user = ?");
    mysqli_stmt_bind_param($del, "i", $id);
    mysqli_stmt_execute($del);
    mysqli_stmt_close($del);
    set_flash("success", "User " . $target["nama"] . " berhasil dihapus.");
} catch (Throwable $ex) {
    set_flash("error", "Gagal menghapus user.");
}

header("Location: index.php");
exit();
