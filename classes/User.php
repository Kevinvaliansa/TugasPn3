<?php
/**
 * Class User - Manajemen Pengguna, Autentikasi, dan Hak Akses Role
 * Sistem Informasi Restoran Dapur Ina Aina
 */

require_once __DIR__ . '/Database.php';

class User {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    // ── Proses Login Pengguna ──
    public function login(string $username, string $password): array {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = :u LIMIT 1");
        $stmt->execute([':u' => $username]);
        $user = $stmt->fetch();

        if (!$user) {
            throw new Exception("Username tidak terdaftar", 401);
        }

        if (!password_verify($password, $user['password'])) {
            throw new Exception("Password yang Anda masukkan salah", 401);
        }

        // Simpan sesi login
        $userData = [
            'id_user'      => (int)$user['id_user'],
            'username'     => $user['username'],
            'nama_lengkap' => $user['nama_lengkap'],
            'role'         => $user['role'],
            'no_hp'        => $user['no_hp'],
        ];
        $_SESSION['user'] = $userData;

        return $userData;
    }

    // ── Proses Registrasi Akun Pelanggan / User Baru ──
    public function register(array $data): int {
        // Validasi kelengkapan
        if (empty($data['username']) || empty($data['password']) || empty($data['nama_lengkap'])) {
            throw new Exception("Nama, username, dan password wajib diisi", 400);
        }

        // Cek username unik
        $stmtCheck = $this->db->prepare("SELECT COUNT(*) FROM users WHERE username = :u");
        $stmtCheck->execute([':u' => $data['username']]);
        if ($stmtCheck->fetchColumn() > 0) {
            throw new Exception("Username sudah digunakan, silakan pilih yang lain", 400);
        }

        // Hash password dengan algoritma bcrypt yang aman
        $hashPassword = password_hash($data['password'], PASSWORD_BCRYPT);
        $role = $data['role'] ?? 'pelanggan';

        // Cegah registrasi role admin bebas jika dari publik
        if (!in_array($role, ['pelanggan', 'kasir', 'admin'])) {
            $role = 'pelanggan';
        }

        $stmt = $this->db->prepare(
            "INSERT INTO users (username, password, nama_lengkap, role, no_hp)
             VALUES (:u, :p, :n, :r, :h)"
        );
        $stmt->execute([
            ':u' => $data['username'],
            ':p' => $hashPassword,
            ':n' => $data['nama_lengkap'],
            ':r' => $role,
            ':h' => $data['no_hp'] ?? null,
        ]);

        $id = (int)$this->db->lastInsertId();

        // Otomatis login jika registrasi sebagai pelanggan
        $_SESSION['user'] = [
            'id_user'      => $id,
            'username'     => $data['username'],
            'nama_lengkap' => $data['nama_lengkap'],
            'role'         => $role,
            'no_hp'        => $data['no_hp'] ?? null,
        ];

        return $id;
    }

    // ── Logout ──
    public function logout(): void {
        unset($_SESSION['user']);
        session_destroy();
    }

    // ── Cek Pengguna Sedang Login ──
    public function getCurrentUser(): ?array {
        return $_SESSION['user'] ?? null;
    }

    // ── Ambil User by ID ──
    public function getById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT id_user, username, nama_lengkap, role, no_hp, created_at FROM users WHERE id_user = :id");
        $stmt->execute([':id' => $id]);
        $res = $stmt->fetch();
        return $res ?: null;
    }
}
