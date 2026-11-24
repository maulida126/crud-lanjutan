
<?php
require '../config/database.php';
require '../config/upload_siswa.php';

session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Mengambil data form
    $nis = trim($_POST['nis'] ?? '');
    $nama = trim($_POST['nama'] ?? '');
    $kelas = trim($_POST['kelas'] ?? '');
    $jurusan = trim($_POST['jurusan'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    $no_hp = trim($_POST['no_hp'] ?? '');

    // Validasi CSRF
    if (
        !isset($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')
    ) {
        $error = "Permintaan tidak valid. Silakan muat ulang halaman.";
    }

    // Validasi input
    elseif (
        $nis === '' ||
        $nama === '' ||
        $kelas === '' ||
        $jurusan === ''
    ) {
        $error = "Semua kolom wajib harus diisi!";
    }

    else {

        $fotoBaru = null;

        try {

            $fotoBaru = simpanFotoSiswa($_FILES['foto'] ?? []);

            $sql = "INSERT INTO siswa
            (nis, nama, kelas, jurusan, alamat, no_hp, foto)
            VALUES (?, ?, ?, ?, ?, ?, ?)";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                $nis,
                $nama,
                $kelas,
                $jurusan,
                $alamat,
                $no_hp,
                $fotoBaru
            ]);

            header("Location: ../index.php?status=berhasil");
            exit;

        } catch (RuntimeException $e) {

            hapusFotoSiswa($fotoBaru);
            $error = $e->getMessage();

        } catch (PDOException $e) {

            hapusFotoSiswa($fotoBaru);

            if ($e->getCode() == '23000') {
                $error = "NIS sudah digunakan!";
            } else {
                $error = "Gagal menyimpan data siswa.";
            }

        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Tambah Siswa</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
:root {
    --primary: #087f70;
    --primary-dark: #066457;
    --ink: #19332f;
}

body {
    background: #f3f6f1;
    color: var(--ink);
    font-family: "Segoe UI", Arial, sans-serif;
}

.card {
    border: none;
    border-radius: 20px;
    box-shadow: 0 10px 30px rgba(30, 60, 48, .055);
    max-width: 900px;
    margin: 0 auto;
}

.header {
    background: linear-gradient(115deg, #143d36, #17675a 68%, #188574);
    color: white;
    padding: clamp(24px, 5vw, 36px);
    border-radius: 24px;
    box-shadow: 0 16px 35px rgba(25, 76, 64, .18);
}

.header h1 {
    font-weight: 800;
    letter-spacing: -.04em;
}

.header p {
    color: rgba(255,255,255,.72);
}

.form-label {
    color: #35584c;
    font-size: .9rem;
    font-weight: 650;
}

.form-control,
.form-select {
    border-color: #e3eae3;
    border-radius: 11px;
    padding: 11px 13px;
}

.form-control:focus,
.form-select:focus {
    border-color: #78b9a2;
    box-shadow: 0 0 0 .22rem rgba(8,127,112,.12);
}

.btn {
    border-radius: 10px;
    font-weight: 650;
}

.btn-primary {
    background: var(--primary);
    border-color: var(--primary);
}

.btn-primary:hover,
.btn-primary:focus {
    background: var(--primary-dark);
    border-color: var(--primary-dark);
}
</style>

</head>

<body>

<div class="container py-4 py-lg-5">

    <div class="header mb-4">
        <div class="text-uppercase small fw-semibold mb-2" style="letter-spacing:.13em;color:#bde8cf">Portal akademik</div>
        <h1 class="h3 mb-2">Tambah Data Siswa</h1>
        <p class="mb-0">Masukkan informasi siswa baru</p>
    </div>

    <div class="card p-3 p-md-4">

        <?php if ($error): ?>

            <div class="alert alert-danger">
                <?= htmlspecialchars($error) ?>
            </div>

        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="row g-3">

            <input type="hidden" name="csrf_token"
                value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

            <div class="col-md-6">
                <label class="form-label">NIS *</label>
                <input type="text"
                    name="nis"
                    class="form-control"
                    placeholder="Masukkan NIS"
                    value="<?= htmlspecialchars($_POST['nis'] ?? '') ?>"
                    required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Nama Lengkap *</label>
                <input type="text"
                    name="nama"
                    class="form-control"
                    placeholder="Masukkan nama siswa"
                    value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>"
                    required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Kelas *</label>
                <input type="text"
                    name="kelas"
                    class="form-control"
                    placeholder="Contoh: XI RPL 1"
                    value="<?= htmlspecialchars($_POST['kelas'] ?? '') ?>"
                    required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Jurusan *</label>
                <select name="jurusan" class="form-select" required>
                    <option value="">-- Pilih Jurusan --</option>
                    <option value="RPL">RPL</option>
                    <option value="TKJ">TKJ</option>
                    <option value="DKV">DKV</option>
                    <option value="AKL">AKL</option>
                    <option value="MPLB">MPLB</option>
                </select>
            </div>

            <div class="col-12">
                <label class="form-label">Alamat</label>
                <textarea name="alamat"
                    class="form-control"
                    rows="3"
                    placeholder="Masukkan alamat"><?= htmlspecialchars($_POST['alamat'] ?? '') ?></textarea>
            </div>

            <div class="col-md-6">
                <label class="form-label">Nomor HP</label>
                <input type="text"
                    name="no_hp"
                    class="form-control"
                    placeholder="08xxxxxxxxxx"
                    value="<?= htmlspecialchars($_POST['no_hp'] ?? '') ?>">
            </div>

            <div class="col-md-6">
                <label class="form-label" for="foto">Foto Profil</label>
                <div class="mb-2">
                    <img id="fotoPreview"
                        alt="Pratinjau foto profil"
                        class="rounded-circle d-none"
                        style="width:72px;height:72px;object-fit:cover">
                </div>
                <input type="file"
                    id="foto"
                    name="foto"
                    class="form-control"
                    accept="image/jpeg,image/png,image/webp">
                <div class="form-text">JPG, PNG, atau WebP. Maksimal 5 MB.</div>
            </div>

            <div class="col-12 d-flex flex-wrap gap-2 pt-2">
                <button type="submit" class="btn btn-primary px-4">Simpan Data</button>
                <a href="../index.php" class="btn btn-outline-secondary px-4">Kembali</a>
            </div>

        </form>

    </div>

</div>

<script>
const inputFoto = document.getElementById('foto');
const pratinjauFoto = document.getElementById('fotoPreview');
let urlPratinjauFoto = null;

inputFoto.addEventListener('change', () => {
    const file = inputFoto.files[0];
    if (!file) return;

    if (urlPratinjauFoto) URL.revokeObjectURL(urlPratinjauFoto);
    urlPratinjauFoto = URL.createObjectURL(file);
    pratinjauFoto.src = urlPratinjauFoto;
    pratinjauFoto.classList.remove('d-none');
});
</script>

</body>
</html>
