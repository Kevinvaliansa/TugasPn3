<?php
/**
 * API: Pesanan - Order Management
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../classes/Pesanan.php';

$method  = $_SERVER['REQUEST_METHOD'];
$pesanan = new Pesanan();

try {
    switch ($method) {
        case 'GET':
            $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
            if ($id) {
                $data = $pesanan->getById($id);
                if (!$data) throw new Exception("Pesanan tidak ditemukan", 404);
                echo json_encode(['status' => 'success', 'data' => $data]);
            } else {
                $status = $_GET['status'] ?? null;
                $withItems = isset($_GET['with_items']) && $_GET['with_items'] == '1';
                $data = $withItems ? $pesanan->getAllWithItems($status) : $pesanan->getAll($status);
                echo json_encode(['status' => 'success', 'data' => $data]);
            }
            break;

        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            if (empty($data['pelanggan']['nama']) || empty($data['pelanggan']['no_meja'])) {
                throw new Exception("Data pelanggan tidak lengkap");
            }
            if (empty($data['items'])) {
                throw new Exception("Pesanan tidak boleh kosong");
            }
            $id = $pesanan->create($data['pelanggan'], $data['items'], $data['catatan'] ?? null);
            echo json_encode(['status' => 'success', 'message' => 'Pesanan berhasil dibuat', 'id_pesanan' => $id]);
            break;

        case 'PUT':
            $data = json_decode(file_get_contents('php://input'), true);
            $id   = (int)($_GET['id'] ?? 0);
            if (!$id || empty($data['status'])) throw new Exception("Data tidak lengkap");
            $pesanan->updateStatus($id, $data['status']);
            echo json_encode(['status' => 'success', 'message' => 'Status pesanan diperbarui']);
            break;

        default:
            throw new Exception("Method tidak diizinkan", 405);
    }
} catch (Exception $e) {
    $code = $e->getCode() ?: 400;
    http_response_code($code);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
