<?php
/**
 * API: Produk - CRUD & Stok Management
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../classes/Produk.php';

$method = $_SERVER['REQUEST_METHOD'];
$produk = new Produk();

try {
    switch ($method) {
        case 'GET':
            $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
            if ($id) {
                $data = $produk->getById($id);
                if (!$data) throw new Exception("Produk tidak ditemukan", 404);
                echo json_encode(['status' => 'success', 'data' => $data]);
            } elseif (isset($_GET['aksi']) && $_GET['aksi'] === 'kategori') {
                echo json_encode(['status' => 'success', 'data' => $produk->getKategori()]);
            } elseif (isset($_GET['aksi']) && $_GET['aksi'] === 'hampir_habis') {
                echo json_encode(['status' => 'success', 'data' => $produk->getStokHampirHabis()]);
            } else {
                $id_kategori = isset($_GET['id_kategori']) ? (int)$_GET['id_kategori'] : null;
                $status      = $_GET['status'] ?? null;
                echo json_encode(['status' => 'success', 'data' => $produk->getAll($id_kategori, $status)]);
            }
            break;

        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            if (isset($data['aksi']) && $data['aksi'] === 'update_stok') {
                $produk->updateStok((int)$data['id_produk'], (int)$data['jumlah'], $data['operasi']);
                echo json_encode(['status' => 'success', 'message' => 'Stok berhasil diperbarui']);
            } else {
                if (empty($data['nama_produk']) || empty($data['harga']) || empty($data['id_kategori'])) {
                    throw new Exception("Data tidak lengkap");
                }
                $id = $produk->create($data);
                echo json_encode(['status' => 'success', 'message' => 'Produk berhasil ditambahkan', 'id' => $id]);
            }
            break;

        case 'PUT':
            $id   = (int)($_GET['id'] ?? 0);
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$id) throw new Exception("ID produk diperlukan");
            $produk->update($id, $data);
            echo json_encode(['status' => 'success', 'message' => 'Produk berhasil diperbarui']);
            break;

        case 'DELETE':
            $id = (int)($_GET['id'] ?? 0);
            if (!$id) throw new Exception("ID produk diperlukan");
            $produk->delete($id);
            echo json_encode(['status' => 'success', 'message' => 'Produk berhasil dihapus']);
            break;

        default:
            throw new Exception("Method tidak diizinkan", 405);
    }
} catch (Exception $e) {
    $code = $e->getCode() ?: 400;
    http_response_code($code);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
