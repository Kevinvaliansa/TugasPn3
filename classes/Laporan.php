<?php
/**
 * Class Laporan - Laporan Penjualan Berkala
 * Sistem Informasi Restoran Dapur Ina Aina
 */

require_once __DIR__ . '/Database.php';

class Laporan {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // ── Laporan mingguan ──
    public function getMingguan(int $minggu, int $tahun): array {
        $stmt = $this->db->prepare(
            "SELECT t.*, pl.nama_pelanggan, pl.no_meja, t.metode_bayar
             FROM transaksi t
             JOIN pesanan p ON t.id_pesanan = p.id_pesanan
             JOIN pelanggan pl ON p.id_pelanggan = pl.id_pelanggan
             WHERE t.status = 'berhasil'
               AND WEEK(t.created_at) = :minggu
               AND YEAR(t.created_at) = :tahun
             ORDER BY t.created_at DESC"
        );
        $stmt->execute([':minggu' => $minggu, ':tahun' => $tahun]);
        return $stmt->fetchAll();
    }

    // ── Laporan bulanan ──
    public function getBulanan(int $bulan, int $tahun): array {
        $stmt = $this->db->prepare(
            "SELECT t.*, pl.nama_pelanggan, pl.no_meja, t.metode_bayar
             FROM transaksi t
             JOIN pesanan p ON t.id_pesanan = p.id_pesanan
             JOIN pelanggan pl ON p.id_pelanggan = pl.id_pelanggan
             WHERE t.status = 'berhasil'
               AND MONTH(t.created_at) = :bulan
               AND YEAR(t.created_at) = :tahun
             ORDER BY t.created_at DESC"
        );
        $stmt->execute([':bulan' => $bulan, ':tahun' => $tahun]);
        return $stmt->fetchAll();
    }

    // ── Ringkasan laporan (total pendapatan, jumlah transaksi) ──
    public function getRingkasan(string $periode, int $nilai, int $tahun): array {
        if ($periode === 'minggu') {
            $where = "WEEK(t.created_at) = :nilai AND YEAR(t.created_at) = :tahun";
        } else {
            $where = "MONTH(t.created_at) = :nilai AND YEAR(t.created_at) = :tahun";
        }

        $stmt = $this->db->prepare(
            "SELECT 
               COUNT(*) as jumlah_transaksi,
               COALESCE(SUM(total_bayar), 0) as total_pendapatan,
               COALESCE(AVG(total_bayar), 0) as rata_transaksi,
               SUM(CASE WHEN metode_bayar = 'tunai' THEN 1 ELSE 0 END) as bayar_tunai,
               SUM(CASE WHEN metode_bayar IN ('non tunai', 'debit', 'kredit') THEN 1 ELSE 0 END) as bayar_nontunai,
               SUM(CASE WHEN metode_bayar = 'debit' THEN 1 ELSE 0 END) as bayar_debit,
               SUM(CASE WHEN metode_bayar = 'kredit' THEN 1 ELSE 0 END) as bayar_kredit
             FROM transaksi t
             WHERE t.status = 'berhasil' AND $where"
        );
        $stmt->execute([':nilai' => $nilai, ':tahun' => $tahun]);
        return $stmt->fetch();
    }

    // ── Top 5 produk terlaris ──
    public function getTopProduk(?int $bulan = null, ?int $tahun = null): array {
        $where = "WHERE t.status = 'berhasil'";
        $params = [];
        if ($bulan !== null && $tahun !== null) {
            $where .= " AND MONTH(t.created_at) = :bulan AND YEAR(t.created_at) = :tahun";
            $params = [':bulan' => $bulan, ':tahun' => $tahun];
        }

        $stmt = $this->db->prepare(
            "SELECT pr.nama_produk, k.nama_kategori,
                    SUM(dp.jumlah) as total_terjual,
                    SUM(dp.subtotal) as total_pendapatan
             FROM detail_pesanan dp
             JOIN produk pr ON dp.id_produk = pr.id_produk
             JOIN kategori k ON pr.id_kategori = k.id_kategori
             JOIN pesanan p ON dp.id_pesanan = p.id_pesanan
             JOIN transaksi t ON p.id_pesanan = t.id_pesanan
             $where
             GROUP BY pr.id_produk, pr.nama_produk, k.nama_kategori
             ORDER BY total_terjual DESC
             LIMIT 5"
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // ── Grafik penjualan 7 hari terakhir ──
    public function getGrafik7Hari(): array {
        return $this->db->query(
            "SELECT DATE(created_at) as tanggal, 
                    COALESCE(SUM(total_bayar), 0) as total
             FROM transaksi
             WHERE status = 'berhasil'
               AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
             GROUP BY DATE(created_at)
             ORDER BY tanggal ASC"
        )->fetchAll();
    }

    // ── Rekap per kategori ──
    public function getRekapKategori(?int $bulan = null, ?int $tahun = null): array {
        $where = "WHERE t.status = 'berhasil'";
        $params = [];
        if ($bulan !== null && $tahun !== null) {
            $where .= " AND MONTH(t.created_at) = :bulan AND YEAR(t.created_at) = :tahun";
            $params = [':bulan' => $bulan, ':tahun' => $tahun];
        }
        $stmt = $this->db->prepare(
            "SELECT k.nama_kategori, k.ikon,
                    SUM(dp.jumlah) as total_terjual,
                    SUM(dp.subtotal) as total_pendapatan
             FROM detail_pesanan dp
             JOIN produk pr ON dp.id_produk = pr.id_produk
             JOIN kategori k ON pr.id_kategori = k.id_kategori
             JOIN pesanan p ON dp.id_pesanan = p.id_pesanan
             JOIN transaksi t ON p.id_pesanan = t.id_pesanan
             $where
             GROUP BY k.id_kategori, k.nama_kategori, k.ikon
             ORDER BY total_pendapatan DESC"
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
