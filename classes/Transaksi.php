<?php
/**
 * Class Transaksi - Manajemen Transaksi & Billing
 * Sistem Informasi Restoran Dapur Ina Aina
 */

require_once __DIR__ . '/Database.php';

class Transaksi {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // ── Generate kode transaksi unik ──
    private function generateKode(): string {
        return 'TRX-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
    }

    // ── Proses pembayaran tunai ──
    public function bayarTunai(int $id_pesanan, float $uang_bayar = 0): array {
        $pesanan = $this->getPesananTotal($id_pesanan);
        if (!$pesanan) throw new Exception("Pesanan tidak ditemukan");

        $total  = (float) $pesanan['total_harga'];
        if ($uang_bayar <= 0) {
            $uang_bayar = $total;
        }
        if ($uang_bayar < $total) {
            throw new Exception("Uang bayar kurang dari total tagihan");
        }
        $kembalian = $uang_bayar - $total;

        $stmt = $this->db->prepare(
            "INSERT INTO transaksi (id_pesanan, kode_transaksi, metode_bayar, total_bayar, uang_bayar, kembalian, status)
             VALUES (:id_pesanan, :kode, 'tunai', :total, :uang, :kembali, 'berhasil')"
        );
        $stmt->execute([
            ':id_pesanan' => $id_pesanan,
            ':kode'       => $this->generateKode(),
            ':total'      => $total,
            ':uang'       => $uang_bayar,
            ':kembali'    => $kembalian,
        ]);
        $id_transaksi = (int) $this->db->lastInsertId();

        // Update status pesanan (jika belum selesai/dibatalkan, pertahankan status memasak di dapur)
        $this->db->prepare("UPDATE pesanan SET status = IF(status IN ('pending', 'diproses'), status, 'selesai') WHERE id_pesanan = :id")
                 ->execute([':id' => $id_pesanan]);

        return ['id_transaksi' => $id_transaksi, 'kembalian' => $kembalian];
    }

    // ── Proses pembayaran non-tunai ──
    public function bayarNonTunai(int $id_pesanan, string $metode = 'non tunai', string $no_referensi = '', string $nama_bank = 'QRIS'): array {
        $pesanan = $this->getPesananTotal($id_pesanan);
        if (!$pesanan) throw new Exception("Pesanan tidak ditemukan");

        if (!in_array($metode, ['non tunai', 'debit', 'kredit'])) {
            throw new Exception("Metode pembayaran tidak valid");
        }

        if (empty($no_referensi)) {
            $no_referensi = 'REF-' . strtoupper(substr(uniqid(), -8));
        }
        if (empty($nama_bank)) {
            $nama_bank = 'QRIS / Non Tunai';
        }

        $stmt = $this->db->prepare(
            "INSERT INTO transaksi (id_pesanan, kode_transaksi, metode_bayar, total_bayar, no_referensi, nama_bank, status)
             VALUES (:id_pesanan, :kode, :metode, :total, :ref, :bank, 'berhasil')"
        );
        $stmt->execute([
            ':id_pesanan' => $id_pesanan,
            ':kode'       => $this->generateKode(),
            ':metode'     => $metode,
            ':total'      => $pesanan['total_harga'],
            ':ref'        => $no_referensi,
            ':bank'       => $nama_bank,
        ]);
        $id_transaksi = (int) $this->db->lastInsertId();

        $this->db->prepare("UPDATE pesanan SET status = IF(status IN ('pending', 'diproses'), status, 'selesai') WHERE id_pesanan = :id")
                 ->execute([':id' => $id_pesanan]);

        return ['id_transaksi' => $id_transaksi];
    }

    // ── Ambil total harga pesanan ──
    private function getPesananTotal(int $id_pesanan): ?array {
        $stmt = $this->db->prepare("SELECT * FROM pesanan WHERE id_pesanan = :id");
        $stmt->execute([':id' => $id_pesanan]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    // ── Ambil detail transaksi lengkap (untuk billing/struk) ──
    public function getStruk(int $id_transaksi): ?array {
        $stmt = $this->db->prepare(
            "SELECT t.*, p.catatan, pl.nama_pelanggan, pl.no_meja
             FROM transaksi t
             JOIN pesanan p ON t.id_pesanan = p.id_pesanan
             JOIN pelanggan pl ON p.id_pelanggan = pl.id_pelanggan
             WHERE t.id_transaksi = :id"
        );
        $stmt->execute([':id' => $id_transaksi]);
        $trx = $stmt->fetch();
        if (!$trx) return null;

        // Detail item pesanan
        $stmt = $this->db->prepare(
            "SELECT dp.*, pr.nama_produk, k.nama_kategori
             FROM detail_pesanan dp
             JOIN produk pr ON dp.id_produk = pr.id_produk
             JOIN kategori k ON pr.id_kategori = k.id_kategori
             WHERE dp.id_pesanan = :id_pesanan"
        );
        $stmt->execute([':id_pesanan' => $trx['id_pesanan']]);
        $trx['items'] = $stmt->fetchAll();

        return $trx;
    }

    // ── Pendapatan hari ini ──
    public function getPendapatanHariIni(): float {
        $result = $this->db->query(
            "SELECT COALESCE(SUM(total_bayar), 0) as total 
             FROM transaksi 
             WHERE status = 'berhasil' AND DATE(created_at) = CURDATE()"
        )->fetch();
        return (float) $result['total'];
    }

    // ── Ambil semua transaksi ──
    public function getAll(): array {
        return $this->db->query(
            "SELECT t.*, pl.nama_pelanggan, pl.no_meja
             FROM transaksi t
             JOIN pesanan p ON t.id_pesanan = p.id_pesanan
             JOIN pelanggan pl ON p.id_pelanggan = pl.id_pelanggan
             ORDER BY t.created_at DESC LIMIT 50"
        )->fetchAll();
    }
}
