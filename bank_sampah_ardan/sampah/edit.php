<?php
$base = "../";
require $base . "cek_login.php";
wajib_admin($is_admin, $base);
require $base . "layout.php";

$id = (int) ($_GET["id"] ?? 0);

$stmt = mysqli_prepare($koneksi, "SELECT * FROM sampah WHERE id_sampah = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$data) {
    set_flash("error", "Data sampah tidak ditemukan.");
    header("Location: index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $jenis = trim($_POST["jenis_sampah"] ?? "");
    $harga = (float) str_replace(",", ".", $_POST["harga_per_kg"] ?? 0);
    $stok  = (float) str_replace(",", ".", $_POST["stok_kg"] ?? 0);

    if ($jenis === "") {
        set_flash("error", "Jenis sampah tidak boleh kosong.");
    } elseif ($harga <= 0) {
        set_flash("error", "Harga per kg harus lebih dari 0.");
    } elseif ($stok < 0) {
        set_flash("error", "Stok tidak boleh minus.");
    } else {
        $stmt = mysqli_prepare($koneksi, "UPDATE sampah SET jenis_sampah = ?, harga_per_kg = ?, stok_kg = ? WHERE id_sampah = ?");
        mysqli_stmt_bind_param($stmt, "sddi", $jenis, $harga, $stok, $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        set_flash("success", "Data sampah berhasil diperbarui.");
    }
    header("Location: index.php");
    exit();
}

tampil_header("Edit Sampah", "sampah", $base, $is_admin);
?>
    <div class="header-action"><h2>Edit Jenis Sampah</h2></div>
    <div class="form-card">
        <form method="POST" action="edit.php?id=<?= (int) $id ?>">
            <div class="form-group">
                <label>Jenis Sampah</label>
                <input type="text" name="jenis_sampah" value="<?= e($data["jenis_sampah"]) ?>" required>
            </div>
            <div class="form-group">
                <label>Harga per Kg (Rp)</label>
                <input type="number" name="harga_per_kg" min="1" step="0.01" value="<?= e($data["harga_per_kg"]) ?>" required>
            </div>
            <div class="form-group">
                <label>Stok (Kg)</label>
                <input type="number" name="stok_kg" min="0" step="0.01" value="<?= e($data["stok_kg"]) ?>" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%">Simpan Perubahan</button>
        </form>
        <a class="btn-kembali" href="index.php">&larr; Kembali ke daftar sampah</a>
    </div>
<?php tampil_footer(); ?>
