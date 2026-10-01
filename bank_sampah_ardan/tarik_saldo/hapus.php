<?php
$base = "../";
require $base . "cek_login.php";
wajib_admin($is_admin, $base);

$id = (int) ($_GET["id"] ?? 0);

if ($id > 0) {
    $stmt = mysqli_prepare($koneksi, "SELECT status FROM tarik_saldo WHERE id_tarik = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $t = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$t) {
        set_flash("error", "Data tidak ditemukan.");
    } elseif ($t["status"] === "pending") {
        set_flash("error", "Pengajuan masih pending. Setujui atau tolak dulu sebelum dihapus.");
    } else {
        $stmt = mysqli_prepare($koneksi, "DELETE FROM tarik_saldo WHERE id_tarik = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        set_flash("success", "Data pengajuan penarikan dihapus.");
    }
}

header("Location: index.php");
exit();
