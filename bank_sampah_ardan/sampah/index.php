<?php
$base = "../";
require $base . "cek_login.php";
require $base . "layout.php";

$daftar = mysqli_query($koneksi, "SELECT * FROM sampah ORDER BY jenis_sampah ASC");
tampil_header("Data Sampah", "sampah", $base, $is_admin);
?>
    <div class="header-action">
        <h2>Data Sampah</h2>
        <?php if ($is_admin): ?>
            <a class="btn-tambah" href="tambah.php">+ Tambah Sampah</a>
        <?php endif; ?>
    </div>

    <div class="table-card">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Jenis Sampah</th>
                    <th>Harga / Kg</th>
                    <th>Stok</th>
                    <?php if ($is_admin): ?><th>Aksi</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
            <?php if ($daftar && mysqli_num_rows($daftar) > 0): ?>
                <?php while ($s = mysqli_fetch_assoc($daftar)): ?>
                    <tr>
                        <td>#<?= (int) $s["id_sampah"] ?></td>
                        <td><strong><?= e($s["jenis_sampah"]) ?></strong></td>
                        <td><?= rp($s["harga_per_kg"]) ?></td>
                        <td>
                            <?= number_format($s["stok_kg"], 2, ",", ".") ?> Kg
                            <?php if ($s["stok_kg"] <= 0): ?><span class="badge badge-no">Habis</span><?php endif; ?>
                        </td>
                        <?php if ($is_admin): ?>
                        <td class="action-btns">
                            <a href="edit.php?id=<?= (int) $s["id_sampah"] ?>" class="btn btn-sm btn-warning">Edit</a>
                            <a href="hapus.php?id=<?= (int) $s["id_sampah"] ?>" class="btn btn-sm btn-danger"
                               onclick="return confirm('Hapus jenis sampah ini?')">Hapus</a>
                        </td>
                        <?php endif; ?>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="5" class="empty-row">Belum ada data sampah.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
<?php tampil_footer(); ?>
