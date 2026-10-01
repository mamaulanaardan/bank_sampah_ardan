<?php
$base = "../";
require $base . "cek_login.php";
require $base . "layout.php";
wajib_admin($is_admin, $base);

$roles = daftar_role($koneksi);
$error = "";
$in = ["nama" => "", "username" => "", "role" => in_array("nasabah", $roles, true) ? "nasabah" : $roles[0]];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $in["nama"]     = trim($_POST["nama"] ?? "");
    $in["username"] = trim($_POST["username"] ?? "");
    $in["role"]     = $_POST["role"] ?? "";
    $password       = $_POST["password"] ?? "";
    $konfirmasi     = $_POST["konfirmasi"] ?? "";

    if (!cek_csrf()) {
        $error = "Sesi tidak valid, silakan muat ulang halaman.";
    } elseif ($in["nama"] === "" || mb_strlen($in["nama"]) > 100) {
        $error = "Nama wajib diisi (maksimal 100 karakter).";
    } elseif (!preg_match('/^[A-Za-z0-9_.]{3,50}$/', $in["username"])) {
        $error = "Username 3-50 karakter, hanya huruf, angka, titik, dan underscore.";
    } elseif (!in_array($in["role"], $roles, true)) {
        $error = "Role tidak valid.";
    } elseif (strlen($password) < 4) {
        $error = "Password minimal 4 karakter.";
    } elseif ($password !== $konfirmasi) {
        $error = "Konfirmasi password tidak sama.";
    } else {
        $cek = mysqli_prepare($koneksi, "SELECT 1 FROM users WHERE username = ? LIMIT 1");
        mysqli_stmt_bind_param($cek, "s", $in["username"]);
        mysqli_stmt_execute($cek);
        $ada = mysqli_fetch_assoc(mysqli_stmt_get_result($cek));
        mysqli_stmt_close($cek);

        if ($ada) {
            $error = "Username sudah dipakai, pilih yang lain.";
        } else {
            try {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $ins = mysqli_prepare($koneksi, "INSERT INTO users (nama, username, password, role, saldo) VALUES (?, ?, ?, ?, 0)");
                mysqli_stmt_bind_param($ins, "ssss", $in["nama"], $in["username"], $hash, $in["role"]);
                mysqli_stmt_execute($ins);
                mysqli_stmt_close($ins);

                set_flash("success", "User " . $in["nama"] . " berhasil ditambahkan.");
                header("Location: index.php");
                exit();
            } catch (Throwable $ex) {
                $error = "Gagal menyimpan user.";
            }
        }
    }
}

tampil_header("Tambah User", "user", $base, $is_admin);
?>

<div class="page-head">
    <div>
        <h1>👤 Tambah User</h1>
        <p>Saldo awal 0. Isi saldo lewat menu Top Up.</p>
    </div>
    <a href="index.php" class="btn btn-secondary">&larr; Kembali</a>
</div>

<?php if ($error): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
<?php endif; ?>

<div class="form-card" style="max-width:560px;">
    <form method="POST" action="tambah.php" autocomplete="off">
        <?= csrf_field() ?>

        <div class="form-group">
            <label for="nama">Nama Lengkap</label>
            <input type="text" id="nama" name="nama" class="form-control" maxlength="100" value="<?= e($in["nama"]) ?>" required autofocus>
        </div>

        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" class="form-control" maxlength="50" value="<?= e($in["username"]) ?>" required>
            <div class="form-hint">Dipakai untuk login. Huruf, angka, titik, underscore.</div>
        </div>

        <div class="form-group">
            <label for="role">Role</label>
            <select id="role" name="role" class="form-control" required>
                <?php foreach ($roles as $r): ?>
                    <option value="<?= e($r) ?>" <?= $in["role"] === $r ? "selected" : "" ?>><?= e(ucfirst($r)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" class="form-control" minlength="4" required autocomplete="new-password">
        </div>

        <div class="form-group">
            <label for="konfirmasi">Ulangi Password</label>
            <input type="password" id="konfirmasi" name="konfirmasi" class="form-control" minlength="4" required autocomplete="new-password">
        </div>

        <button type="submit" class="btn btn-primary">Simpan User</button>
    </form>
</div>

<?php tampil_footer(); ?>
