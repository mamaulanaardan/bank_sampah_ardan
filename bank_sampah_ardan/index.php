<?php
$base = "";
require "cek_login.php";
require "layout.php";

$totalJenisSampah = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS jml FROM sampah"))["jml"];
$totalStok = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COALESCE(SUM(stok_kg),0) AS jml FROM sampah"))["jml"];

if ($is_admin) {
    $totalTransaksi = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS jml FROM transaksi"))["jml"];
    $tarikPending = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS jml FROM tarik_saldo WHERE status = 'pending'"))["jml"];
} else {
    $stmt = mysqli_prepare($koneksi, "SELECT COUNT(*) AS jml FROM transaksi WHERE id_user = ?");
    mysqli_stmt_bind_param($stmt, "i", $id_user);
    mysqli_stmt_execute($stmt);
    $totalTransaksi = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))["jml"];
    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare($koneksi, "SELECT COUNT(*) AS jml FROM tarik_saldo WHERE status = 'pending' AND id_user = ?");
    mysqli_stmt_bind_param($stmt, "i", $id_user);
    mysqli_stmt_execute($stmt);
    $tarikPending = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))["jml"];
    mysqli_stmt_close($stmt);
}

// Ambil 5 transaksi terakhir untuk widget aktivitas terbaru
$sqlTerbaru = "SELECT t.*, u.nama, s.Jenis_sampah 
               FROM transaksi t 
               JOIN users u ON t.id_user = u.id_user 
               JOIN sampah s ON t.id_sampah = s.Id_sampah ";
if (!$is_admin) {
    $sqlTerbaru .= " WHERE t.id_user = " . (int)$id_user;
}
$sqlTerbaru .= " ORDER BY t.id_transaksi DESC LIMIT 5";
$recentTx = mysqli_query($koneksi, $sqlTerbaru);

tampil_header("Dashboard", "dashboard", $base, $is_admin);
?>

<style>
    :root {
        --dash-bg: #f8fafc;
        --card-bg: #ffffff;
        --border-color: #e2e8f0;
        --text-primary: #0f172a;
        --text-secondary: #64748b;
        --accent-green: #10b981;
        --accent-blue: #0284c7;
        --accent-purple: #8b5cf6;
        --accent-orange: #f59e0b;
    }

    .dash-header-banner {
        background: linear-gradient(135deg, #059669 0%, #10b981 50%, #34d399 100%);
        border-radius: 20px;
        padding: 32px 36px;
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: space-between;
        box-shadow: 0 10px 25px -5px rgba(16, 185, 129, 0.25);
        margin-bottom: 28px;
        position: relative;
        overflow: hidden;
    }

    .dash-header-banner::after {
        content: '♻';
        position: absolute;
        right: -10px;
        bottom: -30px;
        font-size: 160px;
        opacity: 0.12;
        pointer-events: none;
    }

    .welcome-text h1 {
        font-size: 26px;
        font-weight: 800;
        letter-spacing: -0.5px;
        margin-bottom: 6px;
    }

    .welcome-text p {
        color: #d1fae5;
        font-size: 14px;
        font-weight: 500;
    }

    .role-pill {
        background: rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(8px);
        padding: 8px 18px;
        border-radius: 100px;
        font-size: 13px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        border: 1px solid rgba(255, 255, 255, 0.3);
    }

    /* Grid Statistik */
    .metrics-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 20px;
        margin-bottom: 32px;
    }

    .metric-card {
        background: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 22px 20px;
        display: flex;
        align-items: center;
        gap: 16px;
        transition: all 0.25s ease;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
    }

    .metric-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 20px -5px rgba(0, 0, 0, 0.06);
    }

    .metric-icon {
        width: 52px;
        height: 52px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        flex-shrink: 0;
    }

    .icon-green { background: #ecfdf5; color: var(--accent-green); }
    .icon-blue { background: #e0f2fe; color: var(--accent-blue); }
    .icon-purple { background: #f3e8ff; color: var(--accent-purple); }
    .icon-orange { background: #fff7ed; color: var(--accent-orange); }

    .metric-data .val {
        font-size: 20px;
        font-weight: 800;
        color: var(--text-primary);
        line-height: 1.2;
    }

    .metric-data .lbl {
        font-size: 12px;
        font-weight: 600;
        color: var(--text-secondary);
        margin-top: 4px;
    }

    /* Layout Konten Utama */
    .dashboard-layout {
        display: grid;
        grid-template-columns: 1.8fr 1.2fr;
        gap: 24px;
    }

    .section-title {
        font-size: 16px;
        font-weight: 800;
        color: var(--text-primary);
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    /* Menu Pintas Quick Access */
    .quick-actions {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 16px;
    }

    .action-card {
        background: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 20px;
        text-decoration: none;
        color: var(--text-primary);
        transition: all 0.25s ease;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        min-height: 120px;
    }

    .action-card:hover {
        border-color: var(--accent-green);
        box-shadow: 0 10px 20px -5px rgba(16, 185, 129, 0.12);
        transform: translateY(-2px);
    }

    .action-card .act-icon {
        font-size: 28px;
        margin-bottom: 12px;
    }

    .action-card .act-title {
        font-weight: 700;
        font-size: 15px;
    }

    .action-card .act-desc {
        font-size: 12px;
        color: var(--text-secondary);
        margin-top: 4px;
    }

    /* Table Widget Transaksi Terbaru */
    .recent-table-card {
        background: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 20px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
    }

    .recent-table {
        width: 100%;
        border-collapse: collapse;
    }

    .recent-table th {
        text-align: left;
        font-size: 11px;
        text-transform: uppercase;
        color: var(--text-secondary);
        padding-bottom: 12px;
        border-bottom: 1px solid var(--border-color);
    }

    .recent-table td {
        padding: 12px 0;
        font-size: 13px;
        border-bottom: 1px solid #f1f5f9;
        color: var(--text-primary);
    }

    .recent-table tr:last-child td {
        border-bottom: none;
    }

    .badge-mini {
        padding: 3px 8px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 700;
    }
    .badge-setor { background: #ecfdf5; color: #047857; }
    .badge-tarik { background: #fef2f2; color: #dc2626; }

    @media (max-width: 900px) {
        .dashboard-layout {
            grid-template-columns: 1fr;
        }
        .dash-header-banner {
            flex-direction: column;
            align-items: flex-start;
            gap: 16px;
        }
    }
</style>

<!-- Banner Selamat Datang -->
<div class="dash-header-banner">
    <div class="welcome-text">
        <h1>Selamat Datang Kembali, <?= e($user["nama"]) ?> 👋</h1>
        <p>Akses akun: @<?= e($user["username"]) ?> &bull; Sistem Bank Sampah Ardan Aktif</p>
    </div>
    <div class="role-pill"><?= e($user["role"]) ?></div>
</div>

<!-- Grid Kartu Statistik -->
<div class="metrics-grid">
    <div class="metric-card">
        <div class="metric-icon icon-green">💰</div>
        <div class="metric-data">
            <div class="val"><?= rp($user["saldo"]) ?></div>
            <div class="lbl">Saldo <?= $is_admin ? "Utama" : "Saya" ?></div>
        </div>
    </div>

    <div class="metric-card">
        <div class="metric-icon icon-blue">🗑️</div>
        <div class="metric-data">
            <div class="val"><?= (int) $totalJenisSampah ?></div>
            <div class="lbl">Jenis Sampah</div>
        </div>
    </div>

    <div class="metric-card">
        <div class="metric-icon icon-purple">⚖️</div>
        <div class="metric-data">
            <div class="val"><?= number_format($totalStok, 1, ",", ".") ?> Kg</div>
            <div class="lbl">Total Stok Sampah</div>
        </div>
    </div>

    <div class="metric-card">
        <div class="metric-icon icon-orange">🔄</div>
        <div class="metric-data">
            <div class="val"><?= (int) $totalTransaksi ?></div>
            <div class="lbl"><?= $is_admin ? "Total Transaksi" : "Transaksi Saya" ?></div>
        </div>
    </div>
</div>

<!-- Layout 2 Kolom Konten -->
<div class="dashboard-layout">
    <!-- Kolom Kiri: Akses Pintas Menu -->
    <div>
        <div class="section-title">⚡ Akses Cepat</div>
        <div class="quick-actions">
            <a href="sampah/index.php" class="action-card">
                <div>
                    <div class="act-icon">📦</div>
                    <div class="act-title">Data Sampah</div>
                    <div class="act-desc"><?= $is_admin ? "Atur jenis, harga & stok" : "Daftar harga per Kg" ?></div>
                </div>
            </a>

            <a href="transaksi/index.php" class="action-card">
                <div>
                    <div class="act-icon">📝</div>
                    <div class="act-title">Transaksi</div>
                    <div class="act-desc"><?= $is_admin ? "Catat setoran nasabah" : "Riwayat setoran" ?></div>
                </div>
            </a>

            <a href="tarik_saldo/index.php" class="action-card">
                <div>
                    <div class="act-icon">💸</div>
                    <div class="act-title">Tarik Saldo</div>
                    <div class="act-desc">
                        Pengajuan pencairan
                        <?php if ($tarikPending > 0): ?>
                            <span style="color: #eab308; font-weight:800;">(<?= (int)$tarikPending ?> Pending)</span>
                        <?php endif; ?>
                    </div>
                </div>
            </a>

            <a href="topup/index.php" class="action-card">
                <div>
                    <div class="act-icon">💳</div>
                    <div class="act-title">Top Up</div>
                    <div class="act-desc"><?= $is_admin ? "Isi saldo nasabah" : "Riwayat top up saya" ?></div>
                </div>
            </a>

            <?php if ($is_admin): ?>
            <a href="user/index.php" class="action-card">
                <div>
                    <div class="act-icon">👥</div>
                    <div class="act-title">Kelola User</div>
                    <div class="act-desc">Tambah, ubah & hapus akun</div>
                </div>
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Kolom Kanan: Aktivitas Transaksi Terbaru -->
    <div>
        <div class="section-title">🕒 Transaksi Terakhir</div>
        <div class="recent-table-card">
            <?php if ($recentTx && mysqli_num_rows($recentTx) > 0): ?>
                <table class="recent-table">
                    <thead>
                        <tr>
                            <th>Jenis</th>
                            <th>Tipe</th>
                            <th>Nominal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($r = mysqli_fetch_assoc($recentTx)): ?>
                            <tr>
                                <td>
                                    <strong><?= e($r["Jenis_sampah"]) ?></strong>
                                    <div style="font-size:11px; color:var(--text-secondary);"><?= date("d/m H:i", strtotime($r["tanggal"])) ?></div>
                                </td>
                                <td>
                                    <span class="badge-mini badge-<?= strtolower($r["tipe"]) ?>">
                                        <?= ucfirst(e($r["tipe"])) ?>
                                    </span>
                                </td>
                                <td style="font-weight: 700; color: var(--accent-green);">
                                    <?= rp($r["total_rp"]) ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="text-align:center; padding: 20px 0; color: var(--text-secondary); font-size:13px;">
                    Belum ada riwayat transaksi.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php tampil_footer(); ?>