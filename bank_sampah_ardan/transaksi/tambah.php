<?php
$base = "../";
require $base . "cek_login.php";
wajib_admin($is_admin, $base);
require $base . "layout.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $target = (int) ($_POST["id_user"] ?? 0);
    $sampah = (int) ($_POST["id_sampah"] ?? 0);
    $tipe   = ($_POST["tipe"] ?? "setor") === "tarik" ? "tarik" : "setor";
    $berat  = (float) str_replace(",", ".", $_POST["berat_kg"] ?? 0);

    if ($target <= 0 || $sampah <= 0 || $berat <= 0) {
        set_flash("error", "Nasabah, jenis sampah, dan berat wajib diisi dengan benar.");
        header("Location: index.php"); exit();
    }

    $stmt = mysqli_prepare($koneksi, "SELECT * FROM sampah WHERE Id_sampah = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $sampah);
    mysqli_stmt_execute($stmt);
    $s = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$s) {
        set_flash("error", "Jenis sampah tidak ditemukan.");
        header("Location: index.php"); exit();
    }

    $total = $berat * (float) $s["Harga_per_kg"];

    if ($tipe === "tarik" && $berat > (float) $s["stok_kg"]) {
        set_flash("error", "Stok " . $s["Jenis_sampah"] . " hanya " . number_format($s["stok_kg"], 2, ",", ".") . " Kg. Tidak cukup untuk ditarik.");
        header("Location: index.php"); exit();
    }

    mysqli_begin_transaction($koneksi);
    try {
        $stmt = mysqli_prepare($koneksi, "INSERT INTO transaksi (id_user, id_sampah, tipe, berat_kg, total_rp) VALUES (?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "iisdd", $target, $sampah, $tipe, $berat, $total);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        if ($tipe === "setor") {
            $stmt = mysqli_prepare($koneksi, "UPDATE sampah SET stok_kg = stok_kg + ? WHERE Id_sampah = ?");
            mysqli_stmt_bind_param($stmt, "di", $berat, $sampah);
            mysqli_stmt_execute($stmt); mysqli_stmt_close($stmt);

            $stmt = mysqli_prepare($koneksi, "UPDATE users SET saldo = saldo + ? WHERE id_user = ?");
            mysqli_stmt_bind_param($stmt, "di", $total, $target);
            mysqli_stmt_execute($stmt); mysqli_stmt_close($stmt);

            set_flash("success", "Setoran dicatat. Saldo nasabah bertambah " . rp($total) . ".");
        } else {
            $stmt = mysqli_prepare($koneksi, "UPDATE sampah SET stok_kg = stok_kg - ? WHERE Id_sampah = ?");
            mysqli_stmt_bind_param($stmt, "di", $berat, $sampah);
            mysqli_stmt_execute($stmt); mysqli_stmt_close($stmt);

            set_flash("success", "Penarikan stok dicatat. Stok berkurang " . number_format($berat, 2, ",", ".") . " Kg.");
        }

        mysqli_commit($koneksi);
    } catch (Exception $ex) {
        mysqli_rollback($koneksi);
        set_flash("error", "Transaksi gagal disimpan.");
    }

    header("Location: index.php");
    exit();
}

$users   = mysqli_query($koneksi, "SELECT id_user, nama FROM users ORDER BY nama ASC");
$sampahs = mysqli_query($koneksi, "SELECT Id_sampah, Jenis_sampah, Harga_per_kg, stok_kg FROM sampah ORDER BY Jenis_sampah ASC");

tampil_header("Tambah Transaksi", "transaksi", $base, $is_admin);
?>
    <div class="header-action"><h2>Tambah Transaksi</h2></div>
    <div class="form-card">
        <form method="POST" action="tambah.php">
            <div class="form-group">
                <label>Nasabah</label>
                <select name="id_user" required>
                    <option value="">-- Pilih Nasabah --</option>
                    <?php while ($u = mysqli_fetch_assoc($users)): ?>
                        <option value="<?= (int) $u["id_user"] ?>"><?= e($u["nama"]) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Jenis Sampah</label>
                <select name="id_sampah" required>
                    <option value="">-- Pilih Sampah --</option>
                    <?php while ($s = mysqli_fetch_assoc($sampahs)): ?>
                        <option value="<?= (int) $s["Id_sampah"] ?>">
                            <?= e($s["Jenis_sampah"]) ?> &mdash; <?= rp($s["Harga_per_kg"]) ?>/Kg (stok <?= number_format($s["stok_kg"], 2, ",", ".") ?>)
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Tipe Transaksi</label>
                <select name="tipe" required>
                    <option value="setor">Setor (nasabah menyetor, saldo bertambah)</option>
                    <option value="tarik">Tarik (stok keluar ke pengepul)</option>
                </select>
            </div>

            <div class="form-group">
                <label>Berat (Kg)</label>
                <input type="number" step="0.01" min="0.01" name="berat_kg" placeholder="Contoh: 2.5" required>
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%">Simpan Transaksi</button>
        </form>
        <a class="btn-kembali" href="index.php">&larr; Kembali ke daftar transaksi</a>
    </div>
<?php tampil_footer(); ?>
