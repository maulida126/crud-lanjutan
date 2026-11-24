
<?php
session_start();

require 'config/database.php';
require 'config/upload_siswa.php';

// Membuat token CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Pencarian
$cari = trim($_GET['cari'] ?? '');

// Pagination
$limit = 4;
$page = max(1, (int)($_GET['page'] ?? 1));

// Menghitung jumlah data
$sqlCount = "SELECT COUNT(*) FROM siswa
             WHERE nis LIKE :cari
             OR nama LIKE :cari
             OR kelas LIKE :cari";

$stmtCount = $pdo->prepare($sqlCount);
$stmtCount->execute([
    'cari' => "%$cari%"
]);

$totalData = (int)$stmtCount->fetchColumn();

$totalSiswa = (int)$pdo->query("SELECT COUNT(*) FROM siswa")->fetchColumn();

$totalPage = max(1, (int)ceil($totalData / $limit));
$page = min($page, $totalPage);

$offset = ($page - 1) * $limit;

// Mengambil data siswa
$sql = "SELECT * FROM siswa
        WHERE nis LIKE :cari
        OR nama LIKE :cari
        OR kelas LIKE :cari
        ORDER BY id DESC
        LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);

$stmt->bindValue(':cari', "%$cari%", PDO::PARAM_STR);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();

$data = $stmt->fetchAll();

// Ringkasan seluruh data siswa untuk grafik.
$stmtJurusan = $pdo->query(
    "SELECT jurusan, COUNT(*) AS total
     FROM siswa
     GROUP BY jurusan
     ORDER BY total DESC, jurusan ASC"
);
$ringkasanJurusan = $stmtJurusan->fetchAll();

// Notifikasi
$pesan = [
    'berhasil' => 'Data siswa berhasil ditambahkan!',
    'diubah' => 'Data siswa berhasil diperbarui!',
    'dihapus' => 'Data siswa berhasil dihapus!'
];

$status = $_GET['status'] ?? '';
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>CRUD Data Siswa</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
:root {
    --primary: #087f70;
    --primary-dark: #066457;
    --ink: #19332f;
    --muted: #73827d;
    --canvas: #f3f6f1;
    --line: #e8eee8;
    --accent: #f39a5b;
}

* { box-sizing: border-box; }

body {
    background:
        radial-gradient(ellipse at 2% 5%, rgba(198, 222, 203, .45), transparent 28rem),
        var(--canvas);
    color: var(--ink);
    font-family: "Segoe UI", Arial, sans-serif;
}

.dashboard {
    max-width: 1280px;
}

.header {
    position: relative;
    isolation: isolate;
    overflow: hidden;
    background: linear-gradient(115deg, #143d36 0%, #17675a 68%, #188574 100%);
    color: white;
    padding: clamp(28px, 5vw, 52px);
    border-radius: 26px;
    margin-bottom: 25px;
    box-shadow: 0 18px 38px rgba(25, 76, 64, .18);
}

.header::before,
.header::after {
    content: "";
    position: absolute;
    z-index: -1;
    border: 1px solid rgba(255,255,255,.12);
    border-radius: 50%;
}

.header::before {
    width: 340px;
    height: 340px;
    right: -85px;
    top: -205px;
    box-shadow: 0 0 0 34px rgba(255,255,255,.035), 0 0 0 70px rgba(255,255,255,.025);
}

.header::after {
    width: 145px;
    height: 145px;
    right: 21%;
    bottom: -105px;
    background: rgba(243, 154, 91, .16);
    border: 0;
}

.eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #bde8cf;
    font-size: .73rem;
    font-weight: 750;
    letter-spacing: .15em;
    text-transform: uppercase;
}

.eyebrow::before {
    content: "";
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--accent);
    box-shadow: 0 0 0 4px rgba(243,154,91,.18);
}

.header h1 {
    font-size: clamp(2rem, 4vw, 3rem);
    font-weight: 800;
    letter-spacing: -.055em;
}

.header p {
    color: rgba(255,255,255,.72);
    max-width: 440px;
}

.header .btn-light {
    color: #16483e;
    border: 0;
    border-radius: 12px;
    padding: 12px 18px !important;
    box-shadow: 0 7px 16px rgba(0,0,0,.12);
}

.header .btn-light:hover {
    background: #ffe4c9;
    color: #16483e;
}

.card {
    border: none;
    border-radius: 20px;
    box-shadow: 0 10px 30px rgba(30, 60, 48, .055);
}

.summary-card {
    position: relative;
    overflow: hidden;
    min-height: 158px;
    padding: 23px;
    border: 1px solid rgba(255,255,255,.1);
}

.summary-card.total-card {
    color: white;
    background: linear-gradient(135deg, #176b5d, #0d5147);
    box-shadow: 0 12px 25px rgba(16, 94, 78, .18);
}

.summary-card.total-card::after {
    content: "";
    position: absolute;
    width: 125px;
    height: 125px;
    right: -38px;
    bottom: -62px;
    border-radius: 50%;
    border: 18px solid rgba(255,255,255,.08);
}

.summary-label {
    color: var(--muted);
    font-size: .83rem;
    font-weight: 600;
}

.total-card .summary-label,
.total-card .summary-hint {
    color: rgba(255,255,255,.7);
}

.summary-value {
    color: var(--ink);
    font-size: 2rem;
    font-weight: 800;
    line-height: 1.2;
    letter-spacing: -.04em;
}

.total-card .summary-value {
    color: white;
    font-size: 2.5rem;
}

.summary-icon {
    width: 48px;
    height: 48px;
    border-radius: 15px;
    display: grid;
    place-items: center;
    background: #e9f4ed;
    color: var(--primary);
    font-size: 1.2rem;
}

.total-card .summary-icon {
    color: #fff;
    background: rgba(255,255,255,.14);
}

.summary-hint {
    color: var(--muted);
    font-size: .78rem;
}

.chart-card {
    padding: 24px;
    height: 100%;
}

.chart-heading-icon {
    width: 42px;
    height: 42px;
    display: grid;
    place-items: center;
    border-radius: 13px;
    background: #edf5ef;
    color: var(--primary);
}

.chart-wrap {
    position: relative;
    min-height: 280px;
    max-height: 325px;
    margin-top: 16px;
}

.list-card {
    padding: clamp(18px, 3vw, 28px);
}

.list-heading {
    font-weight: 800;
    letter-spacing: -.035em;
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

.input-group .form-control {
    min-width: 180px;
}

.input-group .btn {
    padding-left: 18px;
    padding-right: 18px;
}

.btn {
    border-radius: 10px;
    font-weight: 650;
}

.btn-primary {
    background: var(--primary);
    border-color: var(--primary);
    box-shadow: 0 5px 12px rgba(8,127,112,.14);
}

.btn-primary:hover,
.btn-primary:focus {
    background: var(--primary-dark);
    border-color: var(--primary-dark);
}

.btn-outline-secondary {
    border-color: #dce5df;
    color: #53665e;
}

.table th {
    background: #f4f7f3;
    border-bottom-width: 1px;
    color: #6e7c74;
    font-size: .72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .09em;
    white-space: nowrap;
    padding: 14px 15px;
}

.table td {
    padding: 15px;
    vertical-align: middle;
}

.table > :not(caption) > * > * {
    border-color: var(--line);
}

.table-hover tbody tr:hover {
    --bs-table-hover-bg: #f7faf6;
}

.student-name {
    color: var(--ink);
    font-weight: 700;
}

.student-avatar {
    display: inline-grid;
    place-items: center;
    width: 36px;
    height: 36px;
    flex: 0 0 36px;
    border-radius: 12px;
    color: #176b5d;
    background: #e4f1e7;
    font-size: .82rem;
    font-weight: 800;
}

.student-photo {
    object-fit: cover;
}

.nis-tag {
    color: #718078;
    font-size: .84rem;
    font-variant-numeric: tabular-nums;
}

.class-tag,
.major-tag {
    display: inline-block;
    border-radius: 8px;
    padding: 5px 9px;
    background: #f0f4ee;
    color: #52685b;
    font-size: .78rem;
    font-weight: 650;
    white-space: nowrap;
}

.major-tag {
    background: #fff1e5;
    color: #ad5e2d;
}

.action-link {
    padding: 6px 9px;
    border-radius: 8px;
    font-size: .8rem;
    font-weight: 650;
}

.action-detail {
    color: #087f70;
    background: #e9f5ef;
}

.action-edit {
    color: #a45b28;
    background: #fff1e5;
}

.action-delete {
    color: #a64040;
    background: #fff0ef;
    border: 0;
}

.page-link {
    color: var(--primary);
    border-color: var(--line);
}

.page-item.active .page-link {
    background: var(--primary);
    border-color: var(--primary);
}

.page-link:focus {
    box-shadow: 0 0 0 .2rem rgba(8,127,112,.12);
}

@media (max-width: 575.98px) {
    .container {
        padding-left: 16px;
        padding-right: 16px;
    }

    .chart-card {
        padding: 19px;
    }

    .chart-wrap {
        min-height: 240px;
    }

    .header .btn-light {
        width: 100%;
    }
}
</style>
</head>

<body>

<div class="container dashboard py-4 py-lg-5">

    <div class="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <div class="eyebrow mb-3">Portal akademik</div>
                <h1 class="mb-2">Sistem Data Siswa</h1>
                <p class="mb-0">Semua informasi siswa dalam satu tempat. Pantau data, jelajahi jurusan, dan kelola informasi dengan mudah.</p>
            </div>
            <a href="halaman/tambah.php" class="btn btn-light fw-semibold px-4 py-2">
                <span aria-hidden="true">＋</span> Tambah Siswa
            </a>
        </div>
    </div>

    <?php if (isset($pesan[$status])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= htmlspecialchars($pesan[$status]) ?>
            <button type="button" class="btn-close"
                data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4 mb-4 align-items-stretch">
        <div class="col-lg-8">
            <div class="card chart-card">
                <div class="d-flex align-items-center gap-3">
                    <div class="chart-heading-icon" aria-hidden="true">▥</div>
                    <div>
                        <h2 class="h5 fw-bold mb-1">Sebaran Siswa per Jurusan</h2>
                        <p class="text-muted small mb-0">Perbandingan jumlah siswa pada setiap jurusan</p>
                    </div>
                </div>
                <?php if ($ringkasanJurusan): ?>
                    <div class="chart-wrap">
                        <canvas id="jurusanChart" role="img" aria-label="Grafik jumlah siswa berdasarkan jurusan"></canvas>
                    </div>
                <?php else: ?>
                    <div class="text-center text-muted py-5">
                        Belum ada data untuk ditampilkan pada grafik.
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-lg-4 d-flex flex-column gap-3">
            <div class="card summary-card total-card flex-grow-1">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="summary-label mb-2">Total siswa</div>
                        <div class="summary-value"><?= number_format($totalSiswa, 0, ',', '.') ?></div>
                        <div class="summary-hint mt-2">Seluruh data yang tersimpan</div>
                    </div>
                    <div class="summary-icon" aria-hidden="true">♙</div>
                </div>
            </div>
            <div class="card summary-card flex-grow-1">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="summary-label mb-2">Pilihan jurusan</div>
                        <div class="summary-value"><?= count($ringkasanJurusan) ?></div>
                        <div class="summary-hint mt-2">Jurusan dengan data siswa</div>
                    </div>
                    <div class="summary-icon" aria-hidden="true">▦</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card list-card">

        <div class="d-flex justify-content-between flex-wrap gap-3 mb-4">

            <div>
                <div class="eyebrow mb-2" style="color:var(--primary)">Direktori</div>
                <h2 class="list-heading h4 mb-1">Daftar Siswa</h2>
                <small class="text-muted">
                    <?= $totalData ?> data ditemukan<?= $cari !== '' ? ' untuk pencarian "' . htmlspecialchars($cari) . '"' : '' ?>
                </small>
            </div>

        </div>

        <form method="GET" class="mb-3">

            <div class="input-group">

                <input type="text"
                    name="cari"
                    class="form-control"
                    placeholder="Cari NIS, nama, atau kelas..."
                    value="<?= htmlspecialchars($cari) ?>">

                <button class="btn btn-primary">
                    <span aria-hidden="true">⌕</span> Cari
                </button>

                <a href="index.php" class="btn btn-secondary">
                    Reset
                </a>

            </div>

        </form>

        <div class="table-responsive">

            <table class="table table-hover align-middle mb-0">

                <thead>
                    <tr>
                        <th>#</th>
                        <th>NIS</th>
                        <th>Nama Siswa</th>
                        <th>Kelas</th>
                        <th>Jurusan</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (count($data) > 0): ?>

                    <?php $no = $offset + 1; ?>

                    <?php foreach ($data as $siswa): ?>

                    <tr>
                        <td class="text-muted small"><?= str_pad((string)$no++, 2, '0', STR_PAD_LEFT) ?></td>

                        <td><span class="nis-tag"><?= htmlspecialchars($siswa['nis']) ?></span></td>

                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <?php if (fotoSiswaTersedia($siswa['foto'] ?? null)): ?>
                                    <img src="<?= htmlspecialchars($siswa['foto'], ENT_QUOTES, 'UTF-8') ?>"
                                        alt="Foto <?= htmlspecialchars($siswa['nama'], ENT_QUOTES, 'UTF-8') ?>"
                                        class="student-avatar student-photo">
                                <?php else: ?>
                                    <span class="student-avatar"><?= htmlspecialchars(strtoupper(substr((string)$siswa['nama'], 0, 1))) ?></span>
                                <?php endif; ?>
                                <span class="student-name"><?= htmlspecialchars($siswa['nama']) ?></span>
                            </div>
                        </td>

                        <td><span class="class-tag"><?= htmlspecialchars($siswa['kelas']) ?></span></td>

                        <td><span class="major-tag"><?= htmlspecialchars($siswa['jurusan']) ?></span></td>

                        <td class="text-end text-nowrap">
                            <a href="detail.php?id=<?= $siswa['id'] ?>"
                                class="btn action-link action-detail">
                                Detail
                            </a>

                            <a href="halaman/edit.php?id=<?= $siswa['id'] ?>"
                                class="btn action-link action-edit">
                                Edit
                            </a>

                            <form action="proses/hapus.php"
                                method="POST"
                                class="d-inline"
                                onsubmit="return confirm('Yakin ingin menghapus siswa ini?')">

                                <input type="hidden" name="id"
                                    value="<?= (int)$siswa['id'] ?>">

                                <input type="hidden" name="csrf_token"
                                    value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                                <button type="submit"
                                    class="btn action-link action-delete">
                                    Hapus
                                </button>

                            </form>
                        </td>
                    </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="6" class="text-center">
                            Data siswa tidak ditemukan.
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

        <?php if ($totalPage > 1): ?>

        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-3 mt-4">
            <small class="text-muted">
                Menampilkan <?= $offset + 1 ?>–<?= min($offset + count($data), $totalData) ?>
                dari <?= $totalData ?> siswa
            </small>

            <nav aria-label="Navigasi halaman data siswa">
                <ul class="pagination mb-0">

                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <?php if ($page <= 1): ?>
                            <span class="page-link">Sebelumnya</span>
                        <?php else: ?>
                            <a class="page-link"
                                href="?cari=<?= urlencode($cari) ?>&page=<?= $page - 1 ?>">
                                Sebelumnya
                            </a>
                        <?php endif; ?>
                    </li>

                    <?php for ($i = 1; $i <= $totalPage; $i++): ?>
                        <li class="page-item <?= $page === $i ? 'active' : '' ?>">
                            <a class="page-link"
                                href="?cari=<?= urlencode($cari) ?>&page=<?= $i ?>"
                                <?= $page === $i ? 'aria-current="page"' : '' ?>>
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>

                    <li class="page-item <?= $page >= $totalPage ? 'disabled' : '' ?>">
                        <?php if ($page >= $totalPage): ?>
                            <span class="page-link">Berikutnya</span>
                        <?php else: ?>
                            <a class="page-link"
                                href="?cari=<?= urlencode($cari) ?>&page=<?= $page + 1 ?>">
                                Berikutnya
                            </a>
                        <?php endif; ?>
                    </li>

                </ul>
            </nav>
        </div>

        <?php endif; ?>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php if ($ringkasanJurusan): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
const jurusanData = <?= json_encode(
    $ringkasanJurusan,
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
) ?>;

new Chart(document.getElementById('jurusanChart'), {
    type: 'bar',
    data: {
        labels: jurusanData.map(item => item.jurusan || 'Belum diisi'),
        datasets: [{
            label: 'Jumlah siswa',
            data: jurusanData.map(item => Number(item.total)),
            backgroundColor: jurusanData.map((item, index) => (
                ['#138574', '#f39a5b', '#93b89b', '#e4bd5e', '#5b9e91'][index % 5]
            )),
            hoverBackgroundColor: '#0d6558',
            borderRadius: 7,
            borderSkipped: false,
            maxBarThickness: 54
        }]
    },
    options: {
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: { displayColors: false }
        },
        scales: {
            x: {
                grid: { display: false },
                ticks: { color: '#73827d' }
            },
            y: {
                beginAtZero: true,
                ticks: { precision: 0, color: '#73827d' },
                grid: { color: '#e8eee8' }
            }
        }
    }
});
</script>
<?php endif; ?>

</body>
</html>
