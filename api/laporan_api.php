<?php
/**
 * API: Laporan - Sales Reports
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../classes/Laporan.php';
require_once __DIR__ . '/../classes/Pesanan.php';
require_once __DIR__ . '/../classes/Transaksi.php';
require_once __DIR__ . '/../classes/Produk.php';

$laporan   = new Laporan();
$pesanan   = new Pesanan();
$transaksi = new Transaksi();
$produk    = new Produk();

try {
    $aksi  = $_GET['aksi'] ?? 'dashboard';
    $tahun = (int)($_GET['tahun'] ?? date('Y'));

    switch ($aksi) {
        case 'dashboard':
            echo json_encode(['status' => 'success', 'data' => [
                'total_pesanan_hari_ini' => $pesanan->getTotalHariIni(),
                'pendapatan_hari_ini'    => $transaksi->getPendapatanHariIni(),
                'stok_hampir_habis'      => count($produk->getStokHampirHabis()),
                'grafik_7_hari'          => $laporan->getGrafik7Hari(),
                'top_produk'             => $laporan->getTopProduk(),
                'rekap_kategori'         => $laporan->getRekapKategori(),
            ]]);
            break;

        case 'mingguan':
            $minggu = (int)($_GET['minggu'] ?? date('W'));
            echo json_encode(['status' => 'success', 'data' => [
                'transaksi' => $laporan->getMingguan($minggu, $tahun),
                'ringkasan' => $laporan->getRingkasan('minggu', $minggu, $tahun),
                'top_produk' => $laporan->getTopProduk(),
            ]]);
            break;

        case 'bulanan':
            $bulan = (int)($_GET['bulan'] ?? date('n'));
            echo json_encode(['status' => 'success', 'data' => [
                'transaksi'     => $laporan->getBulanan($bulan, $tahun),
                'ringkasan'     => $laporan->getRingkasan('bulan', $bulan, $tahun),
                'top_produk'    => $laporan->getTopProduk($bulan, $tahun),
                'rekap_kategori' => $laporan->getRekapKategori($bulan, $tahun),
            ]]);
            break;

        default:
            throw new Exception("Aksi tidak dikenal");
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
