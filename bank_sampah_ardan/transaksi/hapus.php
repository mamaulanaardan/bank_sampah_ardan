<?php
$base = "../";
require $base . "cek_login.php";
wajib_admin($is_admin, $base);

$id = (int) ($_GET["id"] ?? 0);

if ($id > 0) {
    $stmt = mysqli_prepare($koneksi, "SELECT * FROM transaksi WHERE id_transaksi = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $t = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if ($t) {
        $berat = (float) $t["berat_kg"];
        $total = (float) $t["total_rp"];
        $sid   = (int) $t["id_sampah"];
        $uid   = (int) $t["id_user"];

        mysqli_begin_transaction($koneksi);
        try {
            if ($t["tipe"] === "setor") {
                // Kembalikan: stok berkurang, saldo nasabah berkurang
                $stmt = mysqli_prepare($koneksi, "UPDATE sampah SET stok_kg = stok_kg - ? WHERE Id_sampah = ?");
                mysqli_stmt_bind_param($stmt, "di", $berat, $sid);
                mysqli_stmt_execute($stmt); mysqli_stmt_close($stmt);

                $stmt = mysqli_prepare($koneksi, "UPDATE users SET saldo = GREATEST(saldo - ?, 0) WHERE id_user = ?");
                mysqli_stmt_bind_param($stmt, "di", $total, $uid);
                mysqli_stmt_execute($stmt); mysqli_stmt_close($stmt);
            } else {
                // Kembalikan: stok bertambah lagi
                $stmt = mysqli_prepare($koneksi, "UPDATE sampah SET stok_kg = stok_kg + ? WHERE Id_sampah = ?");
                mysqli_stmt_bind_param($stmt, "di", $berat, $sid);
                mysqli_stmt_execute($stmt); mysqli_stmt_close($stmt);
            }

            $stmt = mysqli_prepare($koneksi, "DELETE FROM transaksi WHERE id_transaksi = ?");
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt); mysqli_stmt_close($stmt);

            mysqli_commit($koneksi);
            set_flash("success", "Transaksi dibatalkan, stok dan saldo dikembalikan.");
        } catch (Exception $ex) {
            mysqli_rollback($koneksi);
            set_flash("error", "Gagal membatalkan transaksi.");
        }
    } else {
        set_flash("error", "Transaksi tidak ditemukan.");
    }
}

header("Location: index.php");
exit();
