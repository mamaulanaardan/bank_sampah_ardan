<?php
$base = "../";
require $base . "cek_login.php";
require $base . "layout.php";
wajib_admin($is_admin, $base);

$error      = "";
$in_user    = (int) ($_GET["id_user"] ?? 0);
$in_nominal = "";
$in_ket     = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $in_user    = (int) ($_POST["id_user"] ?? 0);
    $in_nominal = preg_replace('/[^0-9]/', '', $_POST["nominal"] ?? "");
    $in_ket     = trim($_POST["keterangan"] ?? "");
    $nominal    = strlen($in_nominal) > 12 ? PHP_INT_MAX : (int) $in_nominal;

    if (!cek_csrf()) {
        $error = "Sesi tidak valid, silakan muat ulang halaman.";
    } elseif ($in_user <= 0) {
        $error = "Pilih user tujuan top up.";
    } elseif ($nominal < 1000) {
        $error = "Nominal minimal Rp 1.000.";
    } elseif ($nominal > 100000000) {
        $error = "Nominal maksimal Rp 100.000.000 per top up.";
    } elseif (mb_strlen($in_ket) > 100) {
        $error = "Keterangan maksimal 100 karakter.";
    } else {
        $cek = mysqli_prepare($koneksi, "SELECT nama FROM users WHERE id_user = ? LIMIT 1");
        mysqli_stmt_bind_param($cek, "i", $in_user);
        mysqli_stmt_execute($cek);
        $target = mysqli_fetch_assoc(mysqli_stmt_get_result($cek));
        mysqli_stmt_close($cek);

        if (!$target) {
            $error = "User tidak ditemukan.";
        } else {
            $ket = $in_ket !== "" ? $in_ket : "Top up saldo";
            try {
                mysqli_begin_transaction($koneksi);

                $ins = mysqli_prepare($koneksi, "INSERT INTO topup (id_user, nominal, keterangan, dicatat_oleh) VALUES (?, ?, ?, ?)");
                mysqli_stmt_bind_param($ins, "iisi", $in_user, $nominal, $ket, $id_user);
                mysqli_stmt_execute($ins);
                mysqli_stmt_close($ins);

                $upd = mysqli_prepare($koneksi, "UPDATE users SET saldo = saldo + ? WHERE id_user = ?");
                mysqli_stmt_bind_param($upd, "ii", $nominal, $in_user);
                mysqli_stmt_execute($upd);
                mysqli_stmt_close($upd);

                mysqli_commit($koneksi);

                set_flash("success", "Top up " . rp($nominal) . " untuk " . $target["nama"] . " berhasil.");
                header("Location: index.php");
                exit();
            } catch (Throwable $ex) {
                mysqli_rollback($koneksi);
                $error = "Top up gagal disimpan. Pastikan tabel topup sudah dibuat (jalankan topup.sql).";
            }
        }
    }
}

$daftarUser = mysqli_fetch_all(
    mysqli_query($koneksi, "SELECT id_user, nama, username, saldo FROM users ORDER BY nama ASC"),
    MYSQLI_ASSOC
);

tampil_header("Top Up Baru", "topup", $base, $is_admin);
?>

<div class="page-head">
    <div>
        <h1>💳 Top Up Baru</h1>
        <p>Isi saldo user. Saldo langsung bertambah setelah disimpan.</p>
    </div>
    <a href="index.php" class="btn btn-secondary">&larr; Kembali</a>
</div>

<?php if ($error): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
<?php endif; ?>

<div class="form-card" style="max-width:560px;">
    <form method="POST" action="tambah.php">
        <?= csrf_field() ?>

        <div class="form-group">
            <label for="id_user">User</label>
            <select id="id_user" name="id_user" class="form-control" required>
                <option value="">-- Pilih user --</option>
                <?php foreach ($daftarUser as $u): ?>
                    <option value="<?= (int) $u["id_user"] ?>" <?= $in_user === (int) $u["id_user"] ? "selected" : "" ?>>
                        <?= e($u["nama"]) ?> (@<?= e($u["username"]) ?>) &mdash; saldo <?= rp($u["saldo"]) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="nominal">Nominal (Rp)</label>
            <input type="text" id="nominal" name="nominal" class="form-control" inputmode="numeric"
                   placeholder="contoh: 50000" value="<?= e($in_nominal) ?>" required autocomplete="off">
            <div style="display:flex; gap:8px; flex-wrap:wrap; margin-top:10px;">
                <?php foreach ([10000, 20000, 50000, 100000] as $n): ?>
                    <button type="button" class="chip-nominal" onclick="setNominal(<?= $n ?>)"><?= rp($n) ?></button>
                <?php endforeach; ?>
            </div>
            <div class="form-hint">Minimal Rp 1.000, maksimal Rp 100.000.000.</div>
        </div>

        <div class="form-group">
            <label for="keterangan">Keterangan (opsional)</label>
            <input type="text" id="keterangan" name="keterangan" class="form-control" maxlength="100"
                   placeholder="Top up saldo" value="<?= e($in_ket) ?>">
        </div>

        <button type="submit" class="btn btn-primary">Simpan Top Up</button>
    </form>
</div>

<script>
    function setNominal(n) {
        document.getElementById('nominal').value = n;
    }
</script>

<?php tampil_footer(); ?>
