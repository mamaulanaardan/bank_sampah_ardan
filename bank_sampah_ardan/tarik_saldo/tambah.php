<?php
$base = "../";
require $base . "cek_login.php";
require $base . "layout.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nominal = (float) str_replace(",", ".", $_POST["nominal"] ?? 0);
    $target  = $is_admin ? (int) ($_POST["id_user"] ?? 0) : $id_user;

    if ($target <= 0) {
        set_flash("error", "Pilih nasabah terlebih dahulu.");
    } elseif ($nominal <= 0) {
        set_flash("error", "Nominal penarikan harus lebih dari 0.");
    } else {
        $stmt = mysqli_prepare($koneksi, "SELECT saldo FROM users WHERE id_user = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "i", $target);
        mysqli_stmt_execute($stmt);
        $saldoTarget = (float) (mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))["saldo"] ?? 0);
        mysqli_stmt_close($stmt);

        $stmt = mysqli_prepare($koneksi, "SELECT COALESCE(SUM(nominal),0) AS jml FROM tarik_saldo WHERE id_user = ? AND status = 'pending'");
        mysqli_stmt_bind_param($stmt, "i", $target);
        mysqli_stmt_execute($stmt);
        $pending = (float) mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))["jml"];
        mysqli_stmt_close($stmt);

        if ($nominal > ($saldoTarget - $pending)) {
            set_flash("error", "Saldo tersedia hanya " . rp($saldoTarget - $pending) . " (saldo " . rp($saldoTarget) . ", pending " . rp($pending) . ").");
        } else {
            $stmt = mysqli_prepare($koneksi, "INSERT INTO tarik_saldo (id_user, nominal, status) VALUES (?, ?, 'pending')");
            mysqli_stmt_bind_param($stmt, "id", $target, $nominal);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            set_flash("success", "Pengajuan penarikan " . rp($nominal) . " berhasil dibuat, menunggu persetujuan admin.");
        }
    }

    header("Location: index.php");
    exit();
}

$users = mysqli_query($koneksi, "SELECT id_user, nama, saldo FROM users ORDER BY nama ASC");

tampil_header("Ajukan Penarikan", "tarik_saldo", $base, $is_admin);
?>
    <div class="header-action"><h2>Ajukan Penarikan Saldo</h2></div>
    <div class="form-card">
        <form method="POST" action="tambah.php">
            <?php if ($is_admin): ?>
            <div class="form-group">
                <label>Nasabah</label>
                <select name="id_user" required>
                    <option value="">-- Pilih Nasabah --</option>
                    <?php while ($u = mysqli_fetch_assoc($users)): ?>
                        <option value="<?= (int) $u["id_user"] ?>"><?= e($u["nama"]) ?> &mdash; <?= rp($u["saldo"]) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <?php else: ?>
                <div class="form-group">
                    <label>Saldo Anda saat ini</label>
                    <input type="text" value="<?= rp($user["saldo"]) ?>" disabled>
                </div>
            <?php endif; ?>

            <div class="form-group">
                <label>Nominal Penarikan (Rp)</label>
                <input type="number" step="0.01" min="1" name="nominal" placeholder="Contoh: 50000" required>
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%">Ajukan Penarikan</button>
        </form>
        <a class="btn-kembali" href="index.php">&larr; Kembali ke daftar penarikan</a>
    </div>
<?php tampil_footer(); ?>
