
<?php

require 'config/database.php';
require 'config/upload_siswa.php';

// Mengambil ID dari URL
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    die("ID siswa tidak valid.");
}

// Mengambil data siswa
$stmt = $pdo->prepare(
    "SELECT * FROM siswa WHERE id = ?"
);

$stmt->execute([$id]);

$siswa = $stmt->fetch(PDO::FETCH_ASSOC);

// Memeriksa data
if (!$siswa) {
    die("Data siswa tidak ditemukan.");
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Detail Siswa</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
:root {
    --primary: #087f70;
    --primary-dark: #066457;
    --ink: #19332f;
    --muted: #73827d;
}
body {
    background: #f3f6f1;
    color: var(--ink);
    font-family: "Segoe UI", Arial, sans-serif;
}

.header {
    background: linear-gradient(115deg, #143d36, #17675a 68%, #188574);
    color: white;
    padding: clamp(24px, 5vw, 36px);
    border-radius: 24px;
    box-shadow: 0 16px 35px rgba(25, 76, 64, .18);
}

.card {
    border: none;
    border-radius: 20px;
    box-shadow: 0 10px 30px rgba(30, 60, 48, .055);
}

.label {
    color: var(--muted);
    font-size: .82rem;
    font-weight: 600;
    margin-bottom: 5px;
}

.value {
    font-weight: 600;
    overflow-wrap: anywhere;
}

.avatar {
    width: 82px;
    height: 82px;
    border-radius: 25px;
    background: linear-gradient(135deg, #087f70, #55aa83);
    box-shadow: 0 8px 18px rgba(8, 127, 112, .22);
    font-size: 2rem;
    font-weight: 700;
}

.avatar-photo {
    object-fit: cover;
}

.detail-item {
    background: #f7f9f6;
    border: 1px solid #e8eee8;
    border-radius: 14px;
    padding: 15px 17px;
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

    <div class="header mb-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <div class="text-uppercase small fw-semibold mb-2" style="letter-spacing:.12em">Profil siswa</div>
            <h1 class="h3 fw-bold mb-1">Detail Data Siswa</h1>
            <p class="mb-0 text-white-50">Informasi lengkap siswa</p>
        </div>
        <a href="index.php" class="btn btn-light fw-semibold">← Kembali ke daftar</a>
    </div>

    <div class="card p-3 p-md-4">

        <div class="d-flex flex-column flex-sm-row align-items-center align-items-sm-start gap-3 mb-4">
            <?php if (fotoSiswaTersedia($siswa['foto'] ?? null)): ?>
                <img src="<?= htmlspecialchars($siswa['foto'], ENT_QUOTES, 'UTF-8') ?>"
                    alt="Foto <?= htmlspecialchars($siswa['nama'], ENT_QUOTES, 'UTF-8') ?>"
                    class="avatar avatar-photo flex-shrink-0">
            <?php else: ?>
                <div class="avatar text-white d-flex align-items-center justify-content-center flex-shrink-0">
                    <?= htmlspecialchars(strtoupper(substr((string)$siswa['nama'], 0, 1))) ?>
                </div>
            <?php endif; ?>
            <div class="text-center text-sm-start">
                <h2 class="h4 fw-bold mb-2"><?= htmlspecialchars($siswa['nama']) ?></h2>
                <span class="badge rounded-pill text-bg-primary px-3 py-2">
                    <?= htmlspecialchars($siswa['jurusan'] ?? '-') ?>
                </span>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <div class="detail-item h-100">
                    <div class="label">NIS</div>
                    <div class="value"><?= htmlspecialchars($siswa['nis']) ?></div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="detail-item h-100">
                    <div class="label">Kelas</div>
                    <div class="value"><?= htmlspecialchars($siswa['kelas']) ?></div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="detail-item h-100">
                    <div class="label">Jurusan</div>
                    <div class="value"><?= htmlspecialchars($siswa['jurusan'] ?? '-') ?></div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="detail-item h-100">
                    <div class="label">Nomor HP</div>
                    <div class="value"><?= htmlspecialchars($siswa['no_hp'] ?? '-') ?></div>
                </div>
            </div>
            <div class="col-12">
                <div class="detail-item h-100">
                    <div class="label">Alamat</div>
                    <div class="value"><?= nl2br(htmlspecialchars($siswa['alamat'] ?? '-')) ?></div>
                </div>
            </div>
            <div class="col-12">
                <div class="detail-item h-100">
                    <div class="label">Tanggal Ditambahkan</div>
                    <div class="value"><?= htmlspecialchars($siswa['created_at'] ?? '-') ?></div>
                </div>
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2 mt-4">
            <a href="halaman/edit.php?id=<?= (int)$siswa['id'] ?>" class="btn btn-primary px-4">
                Edit Data
            </a>
            <a href="index.php" class="btn btn-outline-secondary px-4">
                Kembali ke Daftar
            </a>
        </div>

    </div>

</div>

</body>
</html>
