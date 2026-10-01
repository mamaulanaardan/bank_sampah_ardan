<?php
session_start();
require "koneksi.php";

// Redirect jika sudah login
if (isset($_SESSION["id_user"])) {
    header("Location: index.php");
    exit();
}

// Generate CSRF Token jika belum ada
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Validasi CSRF Token
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        $error = "Sesi tidak valid, silakan muat ulang halaman.";
    } else {
        $username = trim($_POST["username"] ?? "");
        $password = $_POST["password"] ?? "";

        if ($username === "" || $password === "") {
            $error = "Username dan password wajib diisi!";
        } else {
            $stmt = mysqli_prepare($koneksi, "SELECT * FROM users WHERE username = ? LIMIT 1");
            mysqli_stmt_bind_param($stmt, "s", $username);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $data = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);

            $cocok = false;
            $needs_rehash = false;

            if ($data) {
                $tersimpan = $data["password"];
                if (password_get_info($tersimpan)["algo"]) {
                    $cocok = password_verify($password, $tersimpan);
                } else {
                    $cocok = hash_equals($tersimpan, $password);
                    if ($cocok) {
                        $needs_rehash = true;
                    }
                }
            }

            if ($cocok) {
                if ($needs_rehash || password_needs_rehash($data["password"], PASSWORD_DEFAULT)) {
                    $new_hash = password_hash($password, PASSWORD_DEFAULT);
                    $update_stmt = mysqli_prepare($koneksi, "UPDATE users SET password = ? WHERE id_user = ?");
                    mysqli_stmt_bind_param($update_stmt, "si", $new_hash, $data["id_user"]);
                    mysqli_stmt_execute($update_stmt);
                    mysqli_stmt_close($update_stmt);
                }

                session_regenerate_id(true);
                $_SESSION["id_user"]  = $data["id_user"];
                $_SESSION["nama"]     = $data["nama"];
                $_SESSION["username"] = $data["username"];
                $_SESSION["role"]     = $data["role"];

                header("Location: index.php");
                exit();
            } else {
                $error = "Username atau password salah!";
            }
        }
    }
}

// Ambil daftar user untuk opsi quick select
$users = mysqli_query($koneksi, "SELECT id_user, nama, username, role FROM users ORDER BY id_user ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Bank Sampah Ardan</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #059669;
            --primary-dark: #047857;
            --primary-light: #ecfdf5;
            --accent: #10b981;
            --bg-body: #0f172a;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --radius: 20px;
            --shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #091e16;
            background-image: 
                radial-gradient(at 0% 0%, rgba(16, 185, 129, 0.25) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(5, 150, 105, 0.2) 0px, transparent 50%);
            padding: 20px;
        }

        .login-card-container {
            width: 100%;
            max-width: 980px;
            background: #ffffff;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            display: flex;
            overflow: hidden;
            min-height: 560px;
        }

        /* Side Visual Box */
        .login-visual {
            flex: 1.1;
            background: linear-gradient(135deg, #059669 0%, #022c22 100%);
            padding: 48px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            color: #ffffff;
            overflow: hidden;
        }

        .login-visual::before {
            content: '';
            position: absolute;
            top: -20%;
            right: -20%;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.08);
            pointer-events: none;
        }

        .brand-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 800;
            font-size: 20px;
            letter-spacing: -0.5px;
        }

        .brand-icon {
            width: 42px;
            height: 42px;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(8px);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .visual-content h2 {
            font-size: 32px;
            font-weight: 800;
            line-height: 1.25;
            margin-bottom: 12px;
        }

        .visual-content p {
            color: #a7f3d0;
            font-size: 14px;
            line-height: 1.6;
        }

        .visual-footer {
            font-size: 13px;
            color: #6ee7b7;
            font-weight: 600;
        }

        /* Form Area */
        .login-form-area {
            flex: 1;
            padding: 48px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .form-header {
            margin-bottom: 28px;
        }

        .form-header h3 {
            font-size: 24px;
            font-weight: 800;
            color: var(--text-main);
            letter-spacing: -0.5px;
        }

        .form-header p {
            font-size: 13px;
            color: var(--text-muted);
            margin-top: 4px;
        }

        .alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-muted);
            margin-bottom: 8px;
        }

        .input-control {
            width: 100%;
            padding: 14px 16px;
            border: 1.5px solid var(--border);
            border-radius: 12px;
            font-size: 14px;
            color: var(--text-main);
            outline: none;
            transition: all 0.2s ease;
            background: #f8fafc;
        }

        .input-control:focus {
            background: #ffffff;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px var(--primary-light);
        }

        .btn-submit {
            width: 100%;
            padding: 14px;
            background: var(--primary);
            color: #ffffff;
            border: none;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 10px 15px -3px rgba(5, 150, 105, 0.3);
            margin-top: 8px;
        }

        .btn-submit:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        /* Quick Account Chips */
        .quick-accounts {
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid var(--border);
        }

        .quick-accounts-title {
            font-size: 11px;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 10px;
        }

        .account-chips {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .chip {
            background: #f1f5f9;
            border: 1px solid var(--border);
            padding: 6px 12px;
            border-radius: 100px;
            font-size: 12px;
            font-weight: 600;
            color: var(--text-main);
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .chip:hover {
            background: var(--primary-light);
            border-color: var(--primary);
            color: var(--primary-dark);
        }

        .chip-role {
            font-size: 10px;
            background: rgba(0,0,0,0.06);
            padding: 2px 6px;
            border-radius: 10px;
            color: var(--text-muted);
        }

        @media (max-width: 850px) {
            .login-card-container {
                flex-direction: column;
            }
            .login-visual {
                padding: 32px;
                min-height: 200px;
            }
            .visual-content h2 {
                font-size: 24px;
            }
            .login-form-area {
                padding: 32px 24px;
            }
        }
    </style>
</head>
<body>

    <div class="login-card-container">
        <!-- Panel Kiri (Visual & Branding) -->
        <div class="login-visual">
            <div class="brand-logo">
                <div class="brand-icon">&#9851;</div>
                <span>Bank Sampah Ardan</span>
            </div>

            <div class="visual-content">
                <h2>Kelola Sampah,<br>Jaga Masa Depan.</h2>
                <p>Ubah sampah menjadi tabungan bernilai. Masuk ke akun Anda untuk memantau transaksi dan saldo lingkungan.</p>
            </div>

            <div class="visual-footer">
                &copy; <?= date("Y") ?> Bank Sampah Ardan &bull; #EcoSystem
            </div>
        </div>

        <!-- Panel Kanan (Form Login) -->
        <div class="login-form-area">
            <div class="form-header">
                <h3>Masuk ke Akun</h3>
                <p>Silakan masukkan username dan password Anda</p>
            </div>

            <?php if ($error): ?>
                <div class="alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="login.php">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" class="input-control" placeholder="Masukkan username" required autofocus autocomplete="username">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="input-control" placeholder="Masukkan password" required autocomplete="current-password">
                </div>

                <button type="submit" class="btn-submit">Masuk Sekarang &rarr;</button>
            </form>

            <?php if ($users && mysqli_num_rows($users) > 0): ?>
                <div class="quick-accounts">
                    <div class="quick-accounts-title">Pilih Akun Cepat:</div>
                    <div class="account-chips">
                        <?php while ($u = mysqli_fetch_assoc($users)): ?>
                            <div class="chip" onclick="fillUser(<?= htmlspecialchars(json_encode($u['username']), ENT_QUOTES) ?>)">
                                <span><?= htmlspecialchars($u["nama"]) ?></span>
                                <span class="chip-role"><?= ucfirst(htmlspecialchars($u["role"])) ?></span>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function fillUser(username) {
            const usernameInput = document.getElementById('username');
            const passwordInput = document.getElementById('password');
            usernameInput.value = username;
            passwordInput.focus();
        }
    </script>
</body>
</html>