<?php
/**
 * Class Produk - Manajemen Produk & Stok
 * Sistem Informasi Restoran Dapur Ina Aina
 */

require_once __DIR__ . '/Database.php';

class Produk {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // ── Ambil semua produk (dengan info kategori) ──
    public function getAll(?int $id_kategori = null, ?string $status = null): array {
        $sql = "SELECT p.*, k.nama_kategori, k.ikon 
                FROM produk p 
                JOIN kategori k ON p.id_kategori = k.id_kategori
                WHERE 1=1";
        $params = [];

        if ($id_kategori !== null) {
            $sql .= " AND p.id_kategori = :id_kategori";
            $params[':id_kategori'] = $id_kategori;
        }
        if ($status !== null) {
            $sql .= " AND p.status = :status";
            $params[':status'] = $status;
        }
        $sql .= " ORDER BY k.id_kategori, p.nama_produk";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // ── Ambil produk berdasarkan ID ──
    public function getById(int $id): ?array {
        $stmt = $this->db->prepare(
            "SELECT p.*, k.nama_kategori, k.ikon 
             FROM produk p 
             JOIN kategori k ON p.id_kategori = k.id_kategori
             WHERE p.id_produk = :id"
        );
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    // ── Tambah produk baru ──
    public function create(array $data): int {
        $stmt = $this->db->prepare(
            "INSERT INTO produk (id_kategori, nama_produk, deskripsi, foto, harga, stok, status)
             VALUES (:id_kategori, :nama_produk, :deskripsi, :foto, :harga, :stok, :status)"
        );
        $stmt->execute([
            ':id_kategori'  => $data['id_kategori'],
            ':nama_produk'  => $data['nama_produk'],
            ':deskripsi'    => $data['deskripsi'] ?? null,
            ':foto'         => !empty($data['foto']) ? $data['foto'] : null,
            ':harga'        => $data['harga'],
            ':stok'         => $data['stok'],
            ':status'       => ($data['stok'] > 0) ? 'tersedia' : 'habis',
        ]);
        return (int) $this->db->lastInsertId();
    }

    // ── Update produk ──
    public function update(int $id, array $data): bool {
        $fields = [
            'id_kategori = :id_kategori',
            'nama_produk = :nama_produk',
            'deskripsi   = :deskripsi',
            'harga       = :harga',
            'stok        = :stok',
            'status      = :status',
        ];
        $params = [
            ':id_kategori'  => $data['id_kategori'],
            ':nama_produk'  => $data['nama_produk'],
            ':deskripsi'    => $data['deskripsi'] ?? null,
            ':harga'        => $data['harga'],
            ':stok'         => $data['stok'],
            ':status'       => ($data['stok'] > 0) ? 'tersedia' : 'habis',
            ':id'           => $id,
        ];
        if (array_key_exists('foto', $data)) {
            $fields[] = 'foto = :foto';
            $params[':foto'] = !empty($data['foto']) ? $data['foto'] : null;
        }
        $sql = "UPDATE produk SET " . implode(', ', $fields) . " WHERE id_produk = :id";
        return $this->db->prepare($sql)->execute($params);
    }

    // ── Update stok saja ──
    public function updateStok(int $id, int $jumlah, string $operasi = 'tambah'): bool {
        if ($operasi === 'tambah') {
            $sql = "UPDATE produk SET stok = stok + :jumlah, status = IF(stok + :jumlah2 > 0, 'tersedia', 'habis') WHERE id_produk = :id";
            return $this->db->prepare($sql)->execute([':jumlah' => $jumlah, ':jumlah2' => $jumlah, ':id' => $id]);
        } else {
            // Kurangi stok (cek agar tidak negatif)
            $sql = "UPDATE produk SET stok = GREATEST(0, stok - :jumlah), status = IF(stok - :jumlah2 > 0, 'tersedia', 'habis') WHERE id_produk = :id";
            return $this->db->prepare($sql)->execute([':jumlah' => $jumlah, ':jumlah2' => $jumlah, ':id' => $id]);
        }
    }

    // ── Hapus produk ──
    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM produk WHERE id_produk = :id");
        return $stmt->execute([':id' => $id]);
    }

    // ── Ambil semua kategori ──
    public function getKategori(): array {
        return $this->db->query("SELECT * FROM kategori ORDER BY id_kategori")->fetchAll();
    }

    // ── Hitung stok hampir habis (stok <= 5) ──
    public function getStokHampirHabis(): array {
        return $this->db->query(
            "SELECT p.*, k.nama_kategori FROM produk p 
             JOIN kategori k ON p.id_kategori = k.id_kategori 
             WHERE p.stok <= 5 ORDER BY p.stok ASC"
        )->fetchAll();
    }
}
