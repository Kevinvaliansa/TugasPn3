<?php
/**
 * API: Transaksi - Payment Processing
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../classes/Transaksi.php';

$method   = $_SERVER['REQUEST_METHOD'];
$transaksi = new Transaksi();

try {
    switch ($method) {
        case 'GET':
            $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
            if ($id) {
                $data = $transaksi->getStruk($id);
                if (!$data) throw new Exception("Transaksi tidak ditemukan", 404);
                echo json_encode(['status' => 'success', 'data' => $data]);
            } elseif (isset($_GET['aksi']) && $_GET['aksi'] === 'pendapatan_hari_ini') {
                echo json_encode(['status' => 'success', 'data' => $transaksi->getPendapatanHariIni()]);
            } else {
                echo json_encode(['status' => 'success', 'data' => $transaksi->getAll()]);
            }
            break;

        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            if (empty($data['id_pesanan']) || empty($data['metode_bayar'])) {
                throw new Exception("Data pembayaran tidak lengkap");
            }

            $metode = strtolower(trim($data['metode_bayar']));
            if ($metode === 'nontunai' || $metode === 'non_tunai') {
                $metode = 'non tunai';
            }

            if ($metode === 'tunai') {
                $uang_bayar = isset($data['uang_bayar']) ? (float)$data['uang_bayar'] : 0;
                $result = $transaksi->bayarTunai((int)$data['id_pesanan'], $uang_bayar);
                echo json_encode([
                    'status'       => 'success',
                    'message'      => 'Pembayaran tunai berhasil',
                    'id_transaksi' => $result['id_transaksi'],
                    'kembalian'    => $result['kembalian'],
                ]);
            } else {
                $no_ref = !empty($data['no_referensi']) ? trim($data['no_referensi']) : 'REF-' . strtoupper(substr(uniqid(), -8));
                $bank   = !empty($data['nama_bank']) ? trim($data['nama_bank']) : 'QRIS / Non-Tunai';
                $result = $transaksi->bayarNonTunai(
                    (int)$data['id_pesanan'],
                    'non tunai',
                    $no_ref,
                    $bank
                );
                echo json_encode([
                    'status'       => 'success',
                    'message'      => 'Pembayaran non tunai berhasil',
                    'id_transaksi' => $result['id_transaksi'],
                    'no_referensi' => $no_ref,
                ]);
            }
            break;

        default:
            throw new Exception("Method tidak diizinkan", 405);
    }
} catch (Exception $e) {
    $code = $e->getCode() ?: 400;
    http_response_code($code);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
