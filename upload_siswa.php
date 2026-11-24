<?php

function simpanFotoSiswa(array $file): ?string
{
    $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;

    if ($error === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($error !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Foto gagal diunggah. Silakan coba lagi.');
    }

    $temporaryFile = $file['tmp_name'] ?? '';

    if (!is_uploaded_file($temporaryFile)) {
        throw new RuntimeException('File foto tidak valid.');
    }

    $fileSize = filesize($temporaryFile);
    if ($fileSize === false || $fileSize > 5 * 1024 * 1024) {
        throw new RuntimeException('Ukuran foto maksimal 5 MB.');
    }

    $mimeTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    $mimeType = (new finfo(FILEINFO_MIME_TYPE))->file($temporaryFile);
    $imageInfo = getimagesize($temporaryFile);

    if (
        !isset($mimeTypes[$mimeType]) ||
        $imageInfo === false ||
        ($imageInfo['mime'] ?? '') !== $mimeType
    ) {
        throw new RuntimeException('Format foto harus JPG, PNG, atau WebP.');
    }

    if ($imageInfo[0] > 6000 || $imageInfo[1] > 6000) {
        throw new RuntimeException('Resolusi foto maksimal 6000 × 6000 piksel.');
    }

    $uploadDirectory = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'siswa';

    if (
        !is_dir($uploadDirectory) &&
        !mkdir($uploadDirectory, 0755, true) &&
        !is_dir($uploadDirectory)
    ) {
        throw new RuntimeException('Folder penyimpanan foto tidak dapat dibuat.');
    }

    $fileName = bin2hex(random_bytes(16)) . '.' . $mimeTypes[$mimeType];
    $destination = $uploadDirectory . DIRECTORY_SEPARATOR . $fileName;

    if (!move_uploaded_file($temporaryFile, $destination)) {
        throw new RuntimeException('Foto tidak dapat disimpan. Periksa izin folder uploads.');
    }

    return 'uploads/siswa/' . $fileName;
}

function fotoSiswaTersedia(?string $path): bool
{
    if (
        $path === null ||
        !preg_match('/\Auploads\/siswa\/[a-f0-9]{32}\.(?:jpg|png|webp)\z/', $path)
    ) {
        return false;
    }

    $filePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
    return is_file($filePath);
}

function hapusFotoSiswa(?string $path): void
{
    if (!fotoSiswaTersedia($path)) {
        return;
    }

    $filePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);

    if (!unlink($filePath)) {
        error_log('Tidak dapat menghapus foto siswa: ' . $path);
    }
}
