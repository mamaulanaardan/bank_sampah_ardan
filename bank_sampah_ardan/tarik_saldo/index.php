<?php
$base = "../";
require $base . "cek_login.php";
require $base . "layout.php";

$sql = "SELECT ts.*, u.nama FROM tarik_saldo ts JOIN users u ON ts.id_user = u.id_user";
if (!$is_admin) {
    $sql .= " WHERE ts.id_user = " . (int) $id_user;
}
$sql .= " ORDER BY ts.id_tarik DESC";
$data = mysqli_query($koneksi, $sql);

tampil_header("Tarik Saldo", "tarik_saldo", $base, $is_admin);
?>
    <div class="header-action">
        <h2>Penarikan Saldo</h2>
        <a class="btn-tambah" href="tambah.php">+ Ajukan Penarikan</a>
    </div>

    <div class="table-card">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <?php if ($is_admin): ?><th>Nasabah</th><?php endif; ?>
                    <th>Nominal</th>
                    <th>Tanggal</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($data && mysqli_num_rows($data) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($data)): ?>
                    <tr>
                        <td>#<?= (int) $row["id_tarik"] ?></td>
                        <?php if ($is_admin): ?><td><strong><?= e($row["nama"]) ?></strong></td><?php endif; ?>
                        <td style="color:#34d399;font-weight:700;"><?= rp($row["nominal"]) ?></td>
                        <td><?= date("d/m/Y H:i", strtotime($row["tanggal"])) ?></td>
                        <td>
                            <span class="badge-status status-<?= strtolower($row["status"]) ?>">
                                <?= ucfirst(e($row["status"])) ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-btns">
                                <?php if ($is_admin && $row["status"] === "pending"): ?>
                                    <a class="btn btn-sm btn-success" href="proses.php?aksi=setuju&id=<?= (int) $row["id_tarik"] ?>"
                                       onclick="return confirm('Setujui penarikan ini? Saldo nasabah akan dipotong.')">Setujui</a>
                                    <a class="btn btn-sm btn-warning" href="proses.php?aksi=tolak&id=<?= (int) $row["id_tarik"] ?>"
                                       onclick="return confirm('Tolak pengajuan ini?')">Tolak</a>
                                <?php endif; ?>
                                <?php if ($is_admin): ?>
                                    <a class="btn btn-sm btn-danger" href="hapus.php?id=<?= (int) $row["id_tarik"] ?>"
                                       onclick="return confirm('Hapus data pengajuan ini?')">Hapus</a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="empty-row">Belum ada pengajuan penarikan.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
<?php tampil_footer(); ?>
