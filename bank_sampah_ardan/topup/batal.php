<?php
$base = "../";
require $base . "cek_login.php";
wajib_admin($is_admin, $base);

if ($_SERVER["REQUEST_METHOD"] !== "POST" || !cek_csrf()) {
    set_flash("error", "Permintaan tidak valid.");
    header("Location: index.php");
    exit();
}

$id_topup = (int) ($_POST["id_topup"] ?? 0);

try {
    mysqli_begin_transaction($koneksi);

    $sel = mysqli_prepare($koneksi, "SELECT id_user, nominal FROM topup WHERE id_topup = ? FOR UPDATE");
    mysqli_stmt_bind_param($sel, "i", $id_topup);
    mysqli_stmt_execute($sel);
    $tp = mysqli_fetch_assoc(mysqli_stmt_get_result($sel));
    mysqli_stmt_close($sel);

    if (!$tp) {
        mysqli_rollback($koneksi);
        set_flash("error", "Data top up tidak ditemukan.");
        header("Location: index.php");
        exit();
    }

    // Kurangi saldo hanya jika saldo user masih cukup
    $upd = mysqli_prepare($koneksi, "UPDATE users SET saldo = saldo - ? WHERE id_user = ? AND saldo >= ?");
    mysqli_stmt_bind_param($upd, "did", $tp["nominal"], $tp["id_user"], $tp["nominal"]);
    mysqli_stmt_execute($upd);
    $berhasil = mysqli_stmt_affected_rows($upd) === 1;
    mysqli_stmt_close($upd);

    if (!$berhasil) {
        mysqli_rollback($koneksi);
        set_flash("error", "Tidak bisa dibatalkan: saldo user sudah lebih kecil dari nominal top up (kemungkinan sudah dipakai).");
        header("Location: index.php");
        exit();
    }

    $del = mysqli_prepare($koneksi, "DELETE FROM topup WHERE id_topup = ?");
    mysqli_stmt_bind_param($del, "i", $id_topup);
    mysqli_stmt_execute($del);
    mysqli_stmt_close($del);

    mysqli_commit($koneksi);
    set_flash("success", "Top up berhasil dibatalkan dan saldo user sudah dikurangi.");
} catch (Throwable $ex) {
    mysqli_rollback($koneksi);
    set_flash("error", "Gagal membatalkan top up.");
}

header("Location: index.php");
exit();
