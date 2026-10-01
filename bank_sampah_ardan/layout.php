<?php
// layout.php

if (!function_exists('e')) {
    function e($str) {
        return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('rp')) {
    function rp($angka) {
        return "Rp " . number_format((float)$angka, 0, ',', '.');
    }
}

function tampil_header($title = "Bank Sampah", $active = "", $base = "", $is_admin = false) {
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?> - Bank Sampah Ardan</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg-main: #f1f5f9;
            --navbar-bg: #ffffff;
            --text-dark: #0f172a;
            --text-muted: #64748b;
            --primary: #059669;
            --primary-light: #ecfdf5;
            --border: #e2e8f0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        body {
            background-color: var(--bg-main);
            color: var(--text-dark);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Navbar Modern Light */
        .navbar {
            background-color: var(--navbar-bg);
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
        }

        .navbar-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 24px;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: var(--text-dark);
            font-weight: 800;
            font-size: 18px;
        }

        .navbar-brand-icon {
            width: 36px;
            height: 36px;
            background: var(--primary);
            color: #ffffff;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 8px;
            list-style: none;
        }

        .nav-item a {
            text-decoration: none;
            color: var(--text-muted);
            font-size: 14px;
            font-weight: 600;
            padding: 8px 16px;
            border-radius: 10px;
            transition: all 0.2s ease;
        }

        .nav-item a:hover {
            color: var(--primary);
            background: var(--primary-light);
        }

        .nav-item a.active {
            color: var(--primary);
            background: var(--primary-light);
            font-weight: 700;
        }

        .btn-logout {
            color: #dc2626 !important;
            background: #fef2f2;
        }

        .btn-logout:hover {
            background: #fee2e2 !important;
            color: #b91c1c !important;
        }

        /* Container Utama */
        .main-wrapper {
            max-width: 1200px;
            width: 100%;
            margin: 0 auto;
            padding: 32px 24px;
            flex: 1;
        }

        /* Tampilan Tabel & Form Umum */
        .table-card, .form-card {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
            margin-top: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 12px 16px;
            font-size: 12px;
            text-transform: uppercase;
            color: var(--text-muted);
            border-bottom: 2px solid var(--border);
        }

        td {
            padding: 14px 16px;
            font-size: 14px;
            border-bottom: 1px solid var(--border);
            color: var(--text-dark);
        }

        .btn {
            padding: 8px 16px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 13px;
            text-decoration: none;
            display: inline-block;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-primary { background: var(--primary); color: #ffffff; }
        .btn-primary:hover { background: #047857; }

        .btn-warning { background: #f59e0b; color: #ffffff; }
        .btn-danger { background: #ef4444; color: #ffffff; }

        /* Komponen umum (dipakai modul topup & user) */
        .page-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
        .page-head h1 { font-size: 22px; font-weight: 800; letter-spacing: -0.3px; }
        .page-head p { font-size: 13px; color: var(--text-muted); margin-top: 2px; }
        .alert { padding: 12px 16px; border-radius: 12px; font-size: 13px; font-weight: 600; margin-top: 16px; }
        .alert-success { background: #ecfdf5; border: 1px solid #a7f3d0; color: #047857; }
        .alert-error { background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; }
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted); margin-bottom: 8px; }
        .form-control { width: 100%; padding: 12px 14px; border: 1.5px solid var(--border); border-radius: 12px; font-size: 14px; color: var(--text-dark); outline: none; background: #f8fafc; transition: all 0.2s ease; }
        .form-control:focus { background: #ffffff; border-color: var(--primary); box-shadow: 0 0 0 4px var(--primary-light); }
        .form-hint { font-size: 12px; color: var(--text-muted); margin-top: 6px; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 100px; font-size: 11px; font-weight: 700; background: #e0f2fe; color: #0369a1; }
        .badge-admin { background: #f3e8ff; color: #7c3aed; }
        .btn-secondary { background: #e2e8f0; color: #334155; }
        .btn-secondary:hover { background: #cbd5e1; }
        .btn-sm { padding: 6px 12px; font-size: 12px; }
        .btn[disabled] { opacity: 0.45; cursor: not-allowed; }
        .table-wrap { overflow-x: auto; }
        .text-right { text-align: right; }
        .actions { display: flex; gap: 6px; flex-wrap: wrap; }
        .actions form { display: inline; }
        .chip-nominal { background: #f1f5f9; border: 1px solid var(--border); border-radius: 100px; padding: 6px 12px; font-size: 12px; font-weight: 600; cursor: pointer; }
        .chip-nominal:hover { background: var(--primary-light); border-color: var(--primary); color: var(--primary); }

        .footer {
            background: #ffffff;
            border-top: 1px solid var(--border);
            text-align: center;
            padding: 20px;
            font-size: 13px;
            color: var(--text-muted);
            margin-top: auto;
        }

        @media (max-width: 768px) {
            .navbar-container {
                flex-direction: column;
                height: auto;
                padding: 16px;
                gap: 16px;
            }
            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
            }
        }
    </style>
</head>
<body>

    <!-- Header Navigasi -->
    <nav class="navbar">
        <div class="navbar-container">
            <a class="navbar-brand" href="<?= $base ?>index.php">
                <div class="navbar-brand-icon">&#9851;</div>
                <span>Bank Sampah Ardan</span>
            </a>

            <ul class="nav-links">
                <li class="nav-item">
                    <a href="<?= $base ?>index.php" class="<?= $active === 'dashboard' ? 'active' : '' ?>">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a href="<?= $base ?>sampah/index.php" class="<?= $active === 'sampah' ? 'active' : '' ?>">Sampah</a>
                </li>
                <li class="nav-item">
                    <a href="<?= $base ?>transaksi/index.php" class="<?= $active === 'transaksi' ? 'active' : '' ?>">Transaksi</a>
                </li>
                <li class="nav-item">
                    <a href="<?= $base ?>tarik_saldo/index.php" class="<?= $active === 'tarik' ? 'active' : '' ?>">Tarik Saldo</a>
                </li>
                <li class="nav-item">
                    <a href="<?= $base ?>topup/index.php" class="<?= $active === 'topup' ? 'active' : '' ?>">Top Up</a>
                </li>
                <?php if ($is_admin): ?>
                <li class="nav-item">
                    <a href="<?= $base ?>user/index.php" class="<?= $active === 'user' ? 'active' : '' ?>">User</a>
                </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a href="<?= $base ?>logout.php" class="btn-logout" onclick="return confirm('Yakin ingin keluar?')">Logout</a>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Konten Utama -->
    <main class="main-wrapper">
<?php
}

function tampil_footer() {
?>
    </main>

    <footer class="footer">
        &copy; <?= date('Y') ?> Bank Sampah Ardan &bull; Sistem Pengelolaan Tabungan Lingkungan
    </footer>

</body>
</html>
<?php
}
?>