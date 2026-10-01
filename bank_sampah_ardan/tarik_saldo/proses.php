<?php
$base = "../";
require $base . "cek_login.php";
wajib_admin($is_admin, $base);

$id   = (int) ($_GET["id"] ?? 0);
$aksi = $_GET["aksi"] ?? "";

if ($id > 0 && in_array($aksi, ["setuju", "tolak"], true)) {
    $stmt = mysqli_prepare($koneksi, "SELECT * FROM tarik_saldo WHERE id_tarik = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $t = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$t || $t["status"] !== "pending") {
        set_flash("error", "Pengajuan tidak ditemukan atau sudah diproses.");
        header("Location: index.php"); exit();
    }

    if ($aksi === "setuju") {
        $stmt = mysqli_prepare($koneksi, "SELECT saldo FROM users WHERE id_user = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "i", $t["id_user"]);
        mysqli_stmt_execute($stmt);
        $saldoTarget = (float) mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))["saldo"];
        mysqli_stmt_close($stmt);

        if ((float) $t["nominal"] > $saldoTarget) {
            set_flash("error", "Saldo nasabah tidak mencukupi. Pengajuan tidak bisa disetujui.");
            header("Location: index.php"); exit();
        }

        mysqli_begin_transaction($koneksi);
        try {
            $stmt = mysqli_prepare($koneksi, "UPDATE users SET saldo = saldo - ? WHERE id_user = ?");
            mysqli_stmt_bind_param($stmt, "di", $t["nominal"], $t["id_user"]);
            mysqli_stmt_execute($stmt); mysqli_stmt_close($stmt);

            $stmt = mysqli_prepare($koneksi, "UPDATE tarik_saldo SET status = 'disetujui' WHERE id_tarik = ?");
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt); mysqli_stmt_close($stmt);

            mysqli_commit($koneksi);
            set_flash("success", "Penarikan " . rp($t["nominal"]) . " disetujui dan saldo sudah dipotong.");
        } catch (Exception $ex) {
            mysqli_rollback($koneksi);
            set_flash("error", "Gagal memproses persetujuan.");
        }
    } else {
        $stmt = mysqli_prepare($koneksi, "UPDATE tarik_saldo SET status = 'ditolak' WHERE id_tarik = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        set_flash("success", "Pengajuan penarikan ditolak.");
    }
}

header("Location: index.php");
exit();
