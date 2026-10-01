<?php
$base = "../";
require $base . "cek_login.php";
require $base . "layout.php";
wajib_admin($is_admin, $base);

$roles = daftar_role($koneksi);
$id = (int) ($_POST["id_user"] ?? $_GET["id"] ?? 0);

$stmt = mysqli_prepare($koneksi, "SELECT * FROM users WHERE id_user = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$target = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$target) {
    set_flash("error", "User tidak ditemukan.");
    header("Location: index.php");
    exit();
}

$error = "";
$in = ["nama" => $target["nama"], "username" => $target["username"], "role" => $target["role"]];

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
    } elseif ($password !== "" && strlen($password) < 4) {
        $error = "Password baru minimal 4 karakter.";
    } elseif ($password !== $konfirmasi) {
        $error = "Konfirmasi password tidak sama.";
    } else {
        // Username tidak boleh dipakai user lain
        $cek = mysqli_prepare($koneksi, "SELECT 1 FROM users WHERE username = ? AND id_user <> ? LIMIT 1");
        mysqli_stmt_bind_param($cek, "si", $in["username"], $id);
        mysqli_stmt_execute($cek);
        $dipakai = mysqli_fetch_assoc(mysqli_stmt_get_result($cek));
        mysqli_stmt_close($cek);

        // Jangan sampai tidak ada admin tersisa
        $adminLain = 1;
        if ($target["role"] === "admin" && $in["role"] !== "admin") {
            $cnt = mysqli_prepare($koneksi, "SELECT COUNT(*) AS jml FROM users WHERE role = 'admin' AND id_user <> ?");
            mysqli_stmt_bind_param($cnt, "i", $id);
            mysqli_stmt_execute($cnt);
            $adminLain = (int) mysqli_fetch_assoc(mysqli_stmt_get_result($cnt))["jml"];
            mysqli_stmt_close($cnt);
        }

        if ($dipakai) {
            $error = "Username sudah dipakai user lain.";
        } elseif ($adminLain === 0) {
            $error = "Tidak bisa mengubah role: ini satu-satunya admin.";
        } else {
            try {
                if ($password !== "") {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $upd = mysqli_prepare($koneksi, "UPDATE users SET nama = ?, username = ?, role = ?, password = ? WHERE id_user = ?");
                    mysqli_stmt_bind_param($upd, "ssssi", $in["nama"], $in["username"], $in["role"], $hash, $id);
                } else {
                    $upd = mysqli_prepare($koneksi, "UPDATE users SET nama = ?, username = ?, role = ? WHERE id_user = ?");
                    mysqli_stmt_bind_param($upd, "sssi", $in["nama"], $in["username"], $in["role"], $id);
                }
                mysqli_stmt_execute($upd);
                mysqli_stmt_close($upd);

                // Kalau yang diedit akun sendiri, sinkronkan session
                if ($id === $id_user) {
                    $_SESSION["nama"]     = $in["nama"];
                    $_SESSION["username"] = $in["username"];
                    $_SESSION["role"]     = $in["role"];
                }

                set_flash("success", "Data user " . $in["nama"] . " berhasil diperbarui.");
                header("Location: index.php");
                exit();
            } catch (Throwable $ex) {
                $error = "Gagal menyimpan perubahan.";
            }
        }
    }
}

tampil_header("Edit User", "user", $base, $is_admin);
?>

<div class="page-head">
    <div>
        <h1>✏️ Edit User</h1>
        <p>Saldo saat ini <strong><?= rp($target["saldo"]) ?></strong> (ubah lewat menu Top Up)</p>
    </div>
    <a href="index.php" class="btn btn-secondary">&larr; Kembali</a>
</div>

<?php if ($error): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
<?php endif; ?>

<div class="form-card" style="max-width:560px;">
    <form method="POST" action="edit.php" autocomplete="off">
        <?= csrf_field() ?>
        <input type="hidden" name="id_user" value="<?= (int) $target["id_user"] ?>">

        <div class="form-group">
            <label for="nama">Nama Lengkap</label>
            <input type="text" id="nama" name="nama" class="form-control" maxlength="100" value="<?= e($in["nama"]) ?>" required>
        </div>

        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" class="form-control" maxlength="50" value="<?= e($in["username"]) ?>" required>
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
            <label for="password">Password Baru</label>
            <input type="password" id="password" name="password" class="form-control" autocomplete="new-password">
            <div class="form-hint">Kosongkan jika tidak ingin mengganti password.</div>
        </div>

        <div class="form-group">
            <label for="konfirmasi">Ulangi Password Baru</label>
            <input type="password" id="konfirmasi" name="konfirmasi" class="form-control" autocomplete="new-password">
        </div>

        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
    </form>
</div>

<?php tampil_footer(); ?>
