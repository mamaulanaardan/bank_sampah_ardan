<?php
$base = "../";
require $base . "cek_login.php";
wajib_admin($is_admin, $base);

$id = (int) ($_GET["id"] ?? 0);

if ($id > 0) {
    // Cegah hapus kalau masih dipakai di tabel transaksi
    $stmt = mysqli_prepare($koneksi, "SELECT COUNT(*) AS jml FROM transaksi WHERE id_sampah = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $dipakai = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))["jml"];
    mysqli_stmt_close($stmt);

    if ($dipakai > 0) {
        set_flash("error", "Tidak bisa dihapus: jenis sampah ini sudah dipakai di $dipakai transaksi.");
    } else {
        $stmt = mysqli_prepare($koneksi, "DELETE FROM sampah WHERE id_sampah = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        set_flash("success", "Jenis sampah berhasil dihapus.");
    }
}

header("Location: index.php");
exit();
