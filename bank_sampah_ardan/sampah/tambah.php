<?php
$base = "../";
require $base . "cek_login.php";
wajib_admin($is_admin, $base);
require $base . "layout.php";

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
        $stmt = mysqli_prepare($koneksi, "INSERT INTO sampah (jenis_sampah, harga_per_kg, stok_kg) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "sdd", $jenis, $harga, $stok);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        set_flash("success", "Jenis sampah baru berhasil ditambahkan.");
    }
    header("Location: index.php");
    exit();
}

tampil_header("Tambah Sampah", "sampah", $base, $is_admin);
?>
    <div class="header-action"><h2>Tambah Jenis Sampah</h2></div>
    <div class="form-card">
        <form method="POST" action="tambah.php">
            <div class="form-group">
                <label>Jenis Sampah</label>
                <input type="text" name="jenis_sampah" placeholder="Contoh: Plastik Botol" required>
            </div>
            <div class="form-group">
                <label>Harga per Kg (Rp)</label>
                <input type="number" name="harga_per_kg" min="1" step="0.01" placeholder="2000" required>
            </div>
            <div class="form-group">
                <label>Stok Awal (Kg)</label>
                <input type="number" name="stok_kg" min="0" step="0.01" value="0" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%">Simpan</button>
        </form>
        <a class="btn-kembali" href="index.php">&larr; Kembali ke daftar sampah</a>
    </div>
<?php tampil_footer(); ?>
