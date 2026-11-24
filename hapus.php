
<?php

session_start();

require '../config/database.php';
require '../config/upload_siswa.php';

// Memastikan permintaan menggunakan POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../index.php");
    exit;
}

// Memeriksa token CSRF
if (
    !isset($_SESSION['csrf_token']) ||
    !hash_equals(
        $_SESSION['csrf_token'],
        $_POST['csrf_token'] ?? ''
    )
) {
    die("Permintaan tidak valid. Silakan kembali.");
}

// Memeriksa ID
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    die("ID siswa tidak valid.");
}

try {

    $stmtFoto = $pdo->prepare(
        "SELECT foto FROM siswa WHERE id = ?"
    );
    $stmtFoto->execute([$id]);
    $foto = $stmtFoto->fetchColumn();

    // Menghapus data siswa
    $stmt = $pdo->prepare(
        "DELETE FROM siswa WHERE id = ?"
    );

    $stmt->execute([$id]);

    if ($stmt->rowCount() > 0) {
        hapusFotoSiswa($foto === false ? null : $foto);
        header("Location: ../index.php?status=dihapus");
        exit;
    } else {
        die("Data siswa tidak ditemukan.");
    }

} catch (PDOException $e) {

    die("Gagal menghapus data siswa.");
}
