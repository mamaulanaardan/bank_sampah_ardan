<?php
$base = "../";
require $base . "cek_login.php";
require $base . "layout.php";

$sql = "SELECT t.*, u.nama, s.Jenis_sampah
        FROM transaksi t
        JOIN users u  ON t.id_user   = u.id_user
        JOIN sampah s ON t.id_sampah = s.Id_sampah";
if (!$is_admin) {
    $sql .= " WHERE t.id_user = " . (int) $id_user;
}
$sql .= " ORDER BY t.id_transaksi DESC";
$data = mysqli_query($koneksi, $sql);

tampil_header("Data Transaksi", "transaksi", $base, $is_admin);
?>
    <div class="header-action">
        <h2>Data Transaksi</h2>
        <?php if ($is_admin): ?>
            <a class="btn-tambah" href="tambah.php">+ Tambah Transaksi</a>
        <?php endif; ?>
    </div>

    <div class="table-card">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <?php if ($is_admin): ?><th>Nasabah</th><?php endif; ?>
                    <th>Jenis Sampah</th>
                    <th>Tipe</th>
                    <th>Berat</th>
                    <th>Total</th>
                    <th>Tanggal</th>
                    <?php if ($is_admin): ?><th>Aksi</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if ($data && mysqli_num_rows($data) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($data)): ?>
                    <tr>
                        <td>#<?= (int) $row["id_transaksi"] ?></td>
                        <?php if ($is_admin): ?><td><strong><?= e($row["nama"]) ?></strong></td><?php endif; ?>
                        <td><?= e($row["Jenis_sampah"]) ?></td>
                        <td>
                            <span class="badge-tipe tipe-<?= strtolower($row["tipe"]) ?>">
                                <?= ucfirst(e($row["tipe"])) ?>
                            </span>
                        </td>
                        <td><?= number_format($row["berat_kg"], 2, ",", ".") ?> Kg</td>
                        <td style="color:#34d399;font-weight:700;"><?= rp($row["total_rp"]) ?></td>
                        <td><?= date("d/m/Y H:i", strtotime($row["tanggal"])) ?></td>
                        <?php if ($is_admin): ?>
                        <td>
                            <a class="btn btn-sm btn-danger" href="hapus.php?id=<?= (int) $row["id_transaksi"] ?>"
                               onclick="return confirm('Hapus/batalkan transaksi ini? Stok dan saldo akan dikembalikan.')">Hapus</a>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="8" class="empty-row">Belum ada transaksi.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
<?php tampil_footer(); ?>
