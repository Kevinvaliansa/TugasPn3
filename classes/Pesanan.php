<?php
/**
 * Class Pesanan - Manajemen Pesanan Pelanggan
 * Sistem Informasi Restoran Dapur Ina Aina
 */

require_once __DIR__ . '/Database.php';

class Pesanan {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // ── Buat pesanan baru (+ simpan pelanggan) ──
    public function create(array $pelanggan, array $items, ?string $catatan = null): int {
        $this->db->beginTransaction();
        try {
            // 1. Simpan pelanggan
            $stmt = $this->db->prepare(
                "INSERT INTO pelanggan (nama_pelanggan, no_meja) VALUES (:nama, :meja)"
            );
            $stmt->execute([':nama' => $pelanggan['nama'], ':meja' => $pelanggan['no_meja']]);
            $id_pelanggan = (int) $this->db->lastInsertId();

            // 2. Hitung total harga & validasi ketersediaan stok
            $total = 0;
            foreach ($items as $item) {
                $stmtCheck = $this->db->prepare("SELECT nama_produk, stok, status FROM produk WHERE id_produk = :id FOR UPDATE");
                $stmtCheck->execute([':id' => $item['id_produk']]);
                $pData = $stmtCheck->fetch();

                if (!$pData) {
                    throw new Exception("Produk ID {$item['id_produk']} tidak ditemukan.");
                }
                if ($pData['stok'] <= 0 || $pData['status'] === 'habis') {
                    throw new Exception("Pesanan gagal! Menu '{$pData['nama_produk']}' saat ini sudah habis.");
                }
                if ($item['jumlah'] > $pData['stok']) {
                    throw new Exception("Pesanan melebihi stok! Menu '{$pData['nama_produk']}' hanya tersisa {$pData['stok']} porsi (Anda memesan {$item['jumlah']}).");
                }

                $total += $item['harga_satuan'] * $item['jumlah'];
            }

            // 3. Buat pesanan
            $stmt = $this->db->prepare(
                "INSERT INTO pesanan (id_pelanggan, total_harga, catatan, status) 
                 VALUES (:id_pelanggan, :total, :catatan, 'pending')"
            );
            $stmt->execute([
                ':id_pelanggan' => $id_pelanggan,
                ':total'        => $total,
                ':catatan'      => $catatan,
            ]);
            $id_pesanan = (int) $this->db->lastInsertId();

            // 4. Simpan detail pesanan + kurangi stok
            $stmtDetail = $this->db->prepare(
                "INSERT INTO detail_pesanan (id_pesanan, id_produk, jumlah, harga_satuan, subtotal)
                 VALUES (:id_pesanan, :id_produk, :jumlah, :harga_satuan, :subtotal)"
            );
            $stmtStok = $this->db->prepare(
                "UPDATE produk SET stok = GREATEST(0, stok - :jumlah), 
                 status = IF(stok - :jumlah2 > 0, 'tersedia', 'habis') 
                 WHERE id_produk = :id"
            );

            foreach ($items as $item) {
                $subtotal = $item['harga_satuan'] * $item['jumlah'];
                $stmtDetail->execute([
                    ':id_pesanan'   => $id_pesanan,
                    ':id_produk'    => $item['id_produk'],
                    ':jumlah'       => $item['jumlah'],
                    ':harga_satuan' => $item['harga_satuan'],
                    ':subtotal'     => $subtotal,
                ]);
                $stmtStok->execute([
                    ':jumlah'  => $item['jumlah'],
                    ':jumlah2' => $item['jumlah'],
                    ':id'      => $item['id_produk'],
                ]);
            }

            $this->db->commit();
            return $id_pesanan;

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    // ── Ambil semua pesanan ──
    public function getAll(?string $status = null): array {
        $sql = "SELECT p.*, pl.nama_pelanggan, pl.no_meja 
                FROM pesanan p 
                JOIN pelanggan pl ON p.id_pelanggan = pl.id_pelanggan";
        $params = [];
        if ($status !== null) {
            $sql .= " WHERE p.status = :status";
            $params[':status'] = $status;
        }
        $sql .= " ORDER BY p.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // ── Ambil semua pesanan dengan rincian item (untuk monitor order admin/dapur) ──
    public function getAllWithItems(?string $status = null): array {
        $orders = $this->getAll($status);
        if (empty($orders)) return [];

        $stmtDetail = $this->db->prepare(
            "SELECT dp.*, pr.nama_produk, k.nama_kategori, pr.foto 
             FROM detail_pesanan dp 
             JOIN produk pr ON dp.id_produk = pr.id_produk 
             JOIN kategori k ON pr.id_kategori = k.id_kategori 
             WHERE dp.id_pesanan = :id"
        );
        $stmtTrx = $this->db->prepare(
            "SELECT * FROM transaksi WHERE id_pesanan = :id ORDER BY id_transaksi DESC LIMIT 1"
        );
        foreach ($orders as &$order) {
            $stmtDetail->execute([':id' => $order['id_pesanan']]);
            $order['items'] = $stmtDetail->fetchAll();
            $stmtTrx->execute([':id' => $order['id_pesanan']]);
            $order['transaksi'] = $stmtTrx->fetch() ?: null;
        }
        return $orders;
    }

    // ── Ambil detail pesanan by ID ──
    public function getById(int $id): ?array {
        // Header pesanan
        $stmt = $this->db->prepare(
            "SELECT p.*, pl.nama_pelanggan, pl.no_meja 
             FROM pesanan p 
             JOIN pelanggan pl ON p.id_pelanggan = pl.id_pelanggan 
             WHERE p.id_pesanan = :id"
        );
        $stmt->execute([':id' => $id]);
        $pesanan = $stmt->fetch();
        if (!$pesanan) return null;

        // Detail item
        $stmt = $this->db->prepare(
            "SELECT dp.*, pr.nama_produk, k.nama_kategori 
             FROM detail_pesanan dp 
             JOIN produk pr ON dp.id_produk = pr.id_produk 
             JOIN kategori k ON pr.id_kategori = k.id_kategori 
             WHERE dp.id_pesanan = :id"
        );
        $stmt->execute([':id' => $id]);
        $pesanan['items'] = $stmt->fetchAll();

        // Transaksi pembayaran (jika sudah dibayar)
        $stmtTrx = $this->db->prepare(
            "SELECT * FROM transaksi WHERE id_pesanan = :id ORDER BY id_transaksi DESC LIMIT 1"
        );
        $stmtTrx->execute([':id' => $id]);
        $pesanan['transaksi'] = $stmtTrx->fetch() ?: null;

        return $pesanan;
    }

    // ── Update status pesanan ──
    public function updateStatus(int $id, string $status): bool {
        $stmt = $this->db->prepare(
            "UPDATE pesanan SET status = :status WHERE id_pesanan = :id"
        );
        return $stmt->execute([':status' => $status, ':id' => $id]);
    }

    // ── Hitung total pesanan hari ini ──
    public function getTotalHariIni(): int {
        $result = $this->db->query(
            "SELECT COUNT(*) as total FROM pesanan WHERE DATE(created_at) = CURDATE()"
        )->fetch();
        return (int) $result['total'];
    }
}
