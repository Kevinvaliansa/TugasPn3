<?php
/**
 * API: Upload Foto Produk
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$uploadDir = __DIR__ . '/../uploads/produk/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method tidak diizinkan']);
    exit;
}

if (!isset($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'File tidak diterima']);
    exit;
}

$file     = $_FILES['foto'];
$maxSize  = 2 * 1024 * 1024; // 2MB
$allowed  = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

if ($file['size'] > $maxSize) {
    echo json_encode(['status' => 'error', 'message' => 'Ukuran foto maksimal 2MB']);
    exit;
}

$finfo    = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, $allowed)) {
    echo json_encode(['status' => 'error', 'message' => 'Format file tidak didukung (JPG, PNG, WEBP, GIF)']);
    exit;
}

// Buat nama file unik
$ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'produk_' . uniqid() . '.' . strtolower($ext);
$destPath = $uploadDir . $filename;

if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan file']);
    exit;
}

echo json_encode([
    'status'   => 'success',
    'filename' => $filename,
    'url'      => 'uploads/produk/' . $filename,
]);
