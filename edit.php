
<?php
session_start();

require '../config/database.php';
require '../config/upload_siswa.php';

$error = '';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Mengambil ID siswa
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    die("ID siswa tidak valid.");
}

// Mengambil data siswa
$stmt = $pdo->prepare("SELECT * FROM siswa WHERE id = ?");
$stmt->execute([$id]);

$siswa = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$siswa) {
    die("Data siswa tidak ditemukan.");
}

// Proses update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nis = trim($_POST['nis'] ?? '');
    $nama = trim($_POST['nama'] ?? '');
    $kelas = trim($_POST['kelas'] ?? '');
    $jurusan = trim($_POST['jurusan'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    $no_hp = trim($_POST['no_hp'] ?? '');

    if (
        !isset($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')
    ) {
        $error = "Permintaan tidak valid.";
    }

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
        $fotoLama = $siswa['foto'] ?? null;

        try {

            $fotoUpload = simpanFotoSiswa($_FILES['foto'] ?? []);
            $fotoBaru = $fotoUpload;
            $foto = $fotoUpload ?? $fotoLama;

            $sql = "UPDATE siswa SET
                nis = ?,
                nama = ?,
                kelas = ?,
                jurusan = ?,
                alamat = ?,
                no_hp = ?,
                foto = ?
                WHERE id = ?";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                $nis,
                $nama,
                $kelas,
                $jurusan,
                $alamat,
                $no_hp,
                $foto,
                $id
            ]);

            if ($fotoBaru !== null && $fotoLama !== $fotoBaru) {
                hapusFotoSiswa($fotoLama);
            }

            header("Location: ../index.php?status=diubah");
            exit;

        } catch (RuntimeException $e) {

            hapusFotoSiswa($fotoBaru);
            $error = $e->getMessage();

        } catch (PDOException $e) {

            hapusFotoSiswa($fotoBaru);

            if ($e->getCode() == '23000') {
                $error = "NIS sudah digunakan siswa lain!";
            } else {
                $error = "Gagal memperbarui data siswa.";
            }

        }
    }

    // Mempertahankan input jika validasi gagal
    $siswa = array_merge($siswa, [
        'nis' => $nis,
        'nama' => $nama,
        'kelas' => $kelas,
        'jurusan' => $jurusan,
        'alamat' => $alamat,
        'no_hp' => $no_hp
    ]);
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Edit Data Siswa</title>

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
        <h1 class="h3 mb-2">Edit Data Siswa</h1>
        <p class="mb-0">Perbarui informasi siswa</p>
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
                <input type="text" name="nis"
                    class="form-control"
                    value="<?= htmlspecialchars($siswa['nis']) ?>"
                    required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Nama Lengkap *</label>
                <input type="text" name="nama"
                    class="form-control"
                    value="<?= htmlspecialchars($siswa['nama']) ?>"
                    required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Kelas *</label>
                <input type="text" name="kelas"
                    class="form-control"
                    value="<?= htmlspecialchars($siswa['kelas']) ?>"
                    required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Jurusan *</label>
                <select name="jurusan" class="form-select" required>
                    <option value="">Pilih Jurusan</option>

                    <?php
                    $jurusanList = ['RPL', 'TKJ', 'DKV', 'AKL', 'MPLB'];

                    foreach ($jurusanList as $j):
                    ?>

                    <option value="<?= $j ?>"
                        <?= $siswa['jurusan'] === $j ? 'selected' : '' ?>>
                        <?= $j ?>
                    </option>

                    <?php endforeach; ?>

                    <?php if (!in_array($siswa['jurusan'], $jurusanList)): ?>
                        <option value="<?= htmlspecialchars($siswa['jurusan']) ?>" selected>
                            <?= htmlspecialchars($siswa['jurusan']) ?>
                        </option>
                    <?php endif; ?>

                </select>
            </div>

            <div class="col-12">
                <label class="form-label">Alamat</label>
                <textarea name="alamat"
                    class="form-control"
                    rows="3"><?= htmlspecialchars($siswa['alamat'] ?? '') ?></textarea>
            </div>

            <div class="col-md-6">
                <label class="form-label">Nomor HP</label>
                <input type="text" name="no_hp"
                    class="form-control"
                    value="<?= htmlspecialchars($siswa['no_hp'] ?? '') ?>">
            </div>

            <div class="col-md-6">
                <label class="form-label" for="foto">Foto Profil</label>
                <div class="mb-2">
                    <?php if (fotoSiswaTersedia($siswa['foto'] ?? null)): ?>
                        <img id="fotoPreview"
                            src="../<?= htmlspecialchars($siswa['foto'], ENT_QUOTES, 'UTF-8') ?>"
                            alt="Foto <?= htmlspecialchars($siswa['nama'], ENT_QUOTES, 'UTF-8') ?>"
                            class="rounded-circle"
                            style="width:72px;height:72px;object-fit:cover">
                    <?php else: ?>
                        <div id="fotoFallback"
                            class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold"
                            style="width:72px;height:72px;background:#087f70">
                            <?= htmlspecialchars(strtoupper(substr((string)$siswa['nama'], 0, 1))) ?>
                        </div>
                        <img id="fotoPreview"
                            alt="Pratinjau foto profil"
                            class="rounded-circle d-none"
                            style="width:72px;height:72px;object-fit:cover">
                    <?php endif; ?>
                </div>
                <input type="file"
                    id="foto"
                    name="foto"
                    class="form-control"
                    accept="image/jpeg,image/png,image/webp">
                <div class="form-text">JPG, PNG, atau WebP. Maksimal 5 MB. Kosongkan jika tidak ingin mengganti foto.</div>
            </div>

            <div class="col-12 d-flex flex-wrap gap-2 pt-2">
                <button type="submit" class="btn btn-primary px-4">Update Data</button>
                <a href="../index.php" class="btn btn-outline-secondary px-4">Kembali</a>
            </div>

        </form>

    </div>

</div>

<script>
const inputFoto = document.getElementById('foto');
const pratinjauFoto = document.getElementById('fotoPreview');
const fotoFallback = document.getElementById('fotoFallback');
let urlPratinjauFoto = null;

inputFoto.addEventListener('change', () => {
    const file = inputFoto.files[0];
    if (!file) return;

    if (urlPratinjauFoto) URL.revokeObjectURL(urlPratinjauFoto);
    urlPratinjauFoto = URL.createObjectURL(file);
    pratinjauFoto.src = urlPratinjauFoto;
    pratinjauFoto.classList.remove('d-none');
    if (fotoFallback) fotoFallback.classList.add('d-none');
});
</script>

</body>
</html>
