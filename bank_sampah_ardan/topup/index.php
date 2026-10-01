<?php
$base = "../";
require $base . "cek_login.php";
require $base . "layout.php";

$flash = ambil_flash();

$sql = "SELECT tp.*, u.nama, u.username, a.nama AS nama_admin
        FROM topup tp
        JOIN users u ON tp.id_user = u.id_user
        LEFT JOIN users a ON tp.dicatat_oleh = a.id_user";

if ($is_admin) {
    $stmt = mysqli_prepare($koneksi, $sql . " ORDER BY tp.id_topup DESC");
} else {
    $stmt = mysqli_prepare($koneksi, $sql . " WHERE tp.id_user = ? ORDER BY tp.id_topup DESC");
    mysqli_stmt_bind_param($stmt, "i", $id_user);
}
mysqli_stmt_execute($stmt);
$rows = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

$totalTopup = 0;
foreach ($rows as $r) {
    $totalTopup += (float) $r["nominal"];
}

tampil_header("Top Up", "topup", $base, $is_admin);
?>

<div class="page-head">
    <div>
        <h1>💳 Top Up Saldo</h1>
        <p><?= $is_admin ? "Riwayat pengisian saldo semua user" : "Riwayat top up saldo Anda" ?></p>
    </div>
    <?php if ($is_admin): ?>
        <a href="tambah.php" class="btn btn-primary">+ Top Up Baru</a>
    <?php endif; ?>
</div>

<?php if ($flash): ?>
    <div class="alert alert-<?= $flash["tipe"] === "success" ? "success" : "error" ?>"><?= e($flash["pesan"]) ?></div>
<?php endif; ?>

<div class="table-card">
    <div style="font-size:13px; color:var(--text-muted); margin-bottom:12px;">
        Total top up: <strong style="color:var(--primary);"><?= rp($totalTopup) ?></strong>
        &bull; <?= count($rows) ?> catatan
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Tanggal</th>
                    <?php if ($is_admin): ?><th>User</th><?php endif; ?>
                    <th>Keterangan</th>
                    <th class="text-right">Nominal</th>
                    <?php if ($is_admin): ?><th>Dicatat Oleh</th><th>Aksi</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (count($rows) === 0): ?>
                    <tr>
                        <td colspan="<?= $is_admin ? 7 : 4 ?>" style="text-align:center; color:var(--text-muted); padding:28px;">
                            Belum ada riwayat top up.
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?= (int) $r["id_topup"] ?></td>
                        <td><?= date("d/m/Y H:i", strtotime($r["tanggal"])) ?></td>
                        <?php if ($is_admin): ?>
                            <td>
                                <strong><?= e($r["nama"]) ?></strong>
                                <div style="font-size:11px; color:var(--text-muted);">@<?= e($r["username"]) ?></div>
                            </td>
                        <?php endif; ?>
                        <td><?= e($r["keterangan"]) ?></td>
                        <td class="text-right" style="font-weight:700; color:var(--primary);">+ <?= rp($r["nominal"]) ?></td>
                        <?php if ($is_admin): ?>
                            <td><?= $r["nama_admin"] !== null ? e($r["nama_admin"]) : "-" ?></td>
                            <td>
                                <form method="POST" action="batal.php"
                                      onsubmit="return confirm('Batalkan top up ini? Saldo user akan dikurangi sebesar nominalnya.')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id_topup" value="<?= (int) $r["id_topup"] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">Batalkan</button>
                                </form>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php tampil_footer(); ?>
