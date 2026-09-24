<?php
/**
 * API: Autentikasi Pengguna (Login, Register, Logout, Me)
 * Sistem Informasi Restoran Dapur Ina Aina
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../classes/User.php';

$userModel = new User();
$aksi = $_GET['aksi'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'POST') {
        $body = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        if ($aksi === 'login') {
            $username = trim($body['username'] ?? '');
            $password = trim($body['password'] ?? '');

            if (!$username || !$password) {
                throw new Exception("Username dan password wajib diisi", 400);
            }

            $user = $userModel->login($username, $password);
            echo json_encode([
                'status'  => 'success',
                'message' => 'Login berhasil',
                'data'    => $user
            ]);
            exit;
        }

        if ($aksi === 'register') {
            $id = $userModel->register($body);
            echo json_encode([
                'status'  => 'success',
                'message' => 'Pendaftaran akun berhasil!',
                'data'    => $userModel->getCurrentUser()
            ]);
            exit;
        }

        if ($aksi === 'logout') {
            $userModel->logout();
            echo json_encode([
                'status'  => 'success',
                'message' => 'Berhasil logout'
            ]);
            exit;
        }

        throw new Exception("Aksi tidak valid", 400);
    } 
    
    if ($method === 'GET') {
        if ($aksi === 'me') {
            $currentUser = $userModel->getCurrentUser();
            echo json_encode([
                'status' => 'success',
                'data'   => $currentUser
            ]);
            exit;
        }

        if ($aksi === 'logout') {
            $userModel->logout();
            header('Location: ../login.php');
            exit;
        }

        throw new Exception("Aksi tidak valid", 400);
    }

    throw new Exception("Method tidak diizinkan", 405);

} catch (Exception $e) {
    $code = $e->getCode();
    if ($code < 100 || $code > 599) $code = 400;
    http_response_code($code);
    echo json_encode([
        'status'  => 'error',
        'message' => $e->getMessage()
    ]);
}
