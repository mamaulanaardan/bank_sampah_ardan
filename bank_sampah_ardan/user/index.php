<?php
$base = "../";
require $base . "cek_login.php";
require $base . "layout.php";
wajib_admin($is_admin, $base);

$flash = ambil_flash();
$q = trim($_GET["q"] ?? "");

if ($q !== "") {
    $like = "%" . addcslashes($q, "%_\\") . "%";
    $stmt = mysqli_prepare($koneksi, "SELECT * FROM users WHERE nama LIKE ? OR username LIKE ? ORDER BY id_user ASC");
    mysqli_stmt_bind_param($stmt, "ss", $like, $like);
} else {
    $stmt = mysqli_prepare($koneksi, "SELECT * FROM users ORDER BY id_user ASC");
}
mysqli_stmt_execute($stmt);
$rows = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

$totalSaldo = 0;
foreach ($rows as $r) {
    $totalSaldo += (float) $r["saldo"];
}

tampil_header("Kelola User", "user", $base, $is_admin);
?>

<div class="page-head">
    <div>
        <h1>👥 Kelola User</h1>
        <p>Tambah, ubah, dan hapus akun pengguna</p>
    </div>
    <a href="tambah.php" class="btn btn-primary">+ Tambah User</a>
</div>

<?php if ($flash): ?>
    <div class="alert alert-<?= $flash["tipe"] === "success" ? "success" : "error" ?>"><?= e($flash["pesan"]) ?></div>
<?php endif; ?>

<div class="table-card">
    <form method="GET" action="index.php" style="display:flex; gap:8px; margin-bottom:16px; flex-wrap:wrap;">
        <input type="text" name="q" class="form-control" style="max-width:320px;" placeholder="Cari nama atau username..." value="<?= e($q) ?>">
        <button type="submit" class="btn btn-primary">Cari</button>
        <?php if ($q !== ""): ?>
            <a href="index.php" class="btn btn-secondary">Reset</a>
        <?php endif; ?>
    </form>

    <div style="font-size:13px; color:var(--text-muted); margin-bottom:12px;">
        <?= count($rows) ?> user &bull; total saldo <strong style="color:var(--primary);"><?= rp($totalSaldo) ?></strong>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nama</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th class="text-right">Saldo</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($rows) === 0): ?>
                    <tr>
                        <td colspan="6" style="text-align:center; color:var(--text-muted); padding:28px;">
                            User tidak ditemukan.
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($rows as $r): ?>
                    <?php $diriSendiri = ((int) $r["id_user"] === $id_user); ?>
                    <tr>
                        <td><?= (int) $r["id_user"] ?></td>
                        <td>
                            <strong><?= e($r["nama"]) ?></strong>
                            <?= $diriSendiri ? '<span style="font-size:11px; color:var(--text-muted);"> (Anda)</span>' : "" ?>
                        </td>
                        <td>@<?= e($r["username"]) ?></td>
                        <td><span class="badge badge-<?= e($r["role"]) ?>"><?= e(ucfirst($r["role"])) ?></span></td>
                        <td class="text-right" style="font-weight:700;"><?= rp($r["saldo"]) ?></td>
                        <td>
                            <div class="actions">
                                <a href="../topup/tambah.php?id_user=<?= (int) $r["id_user"] ?>" class="btn btn-primary btn-sm">Top Up</a>
                                <a href="edit.php?id=<?= (int) $r["id_user"] ?>" class="btn btn-warning btn-sm">Edit</a>
                                <?php if (!$diriSendiri): ?>
                                    <form method="POST" action="hapus.php"
                                          onsubmit="return confirm('Hapus user <?= e(addslashes($r["nama"])) ?>? Semua transaksi, tarik saldo, dan top up milik user ini ikut terhapus.')">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id_user" value="<?= (int) $r["id_user"] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php tampil_footer(); ?>
