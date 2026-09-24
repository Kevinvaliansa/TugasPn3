<?php
/**
 * Halaman Login & Registrasi
 * Sistem Informasi Restoran Dapur Ina Aina
 */
require_once __DIR__ . '/classes/User.php';
$userModel = new User();
$currentUser = $userModel->getCurrentUser();

// Jika sudah login, langsung redirect ke halaman sesuai role
if ($currentUser) {
    if ($currentUser['role'] === 'admin' || $currentUser['role'] === 'kasir') {
        header('Location: index.php');
    } else {
        header('Location: user.php');
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Login dan Registrasi Pengguna Dapur Ina Aina - Akses Pelanggan dan Admin Restoran">
  <title>Masuk & Daftar Akun - Dapur Ina Aina</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    .auth-wrapper {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 24px;
      background: linear-gradient(135deg, #FFFFFF 0%, #F3F4F6 100%);
    }
    .auth-card {
      background: #FFFFFF;
      border: 1px solid var(--border);
      border-radius: var(--radius-xl);
      box-shadow: var(--shadow-lg);
      width: 100%;
      max-width: 460px;
      overflow: hidden;
    }
    .auth-header {
      padding: 32px 32px 20px;
      text-align: center;
      border-bottom: 1px solid var(--border);
      background: #FAFAFA;
    }
    .auth-tabs {
      display: flex;
      border-bottom: 1px solid var(--border);
      background: #FFFFFF;
    }
    .auth-tab {
      flex: 1;
      padding: 14px;
      font-size: 14px;
      font-weight: 700;
      border: none;
      background: transparent;
      cursor: pointer;
      color: var(--text-muted);
      border-bottom: 3px solid transparent;
      transition: all var(--transition);
      text-align: center;
    }
    .auth-tab.active {
      color: var(--primary-dark);
      border-bottom-color: var(--primary);
      background: #FFFFFF;
    }
    .auth-body {
      padding: 28px 32px 32px;
    }
  </style>
</head>
<body>

  <!-- Toast Container -->
  <div class="toast-container" id="toast-container"></div>

  <div class="auth-wrapper">
    <div class="auth-card">

      <!-- Header -->
      <div class="auth-header">
        <div style="font-size:44px;margin-bottom:6px">🍽️</div>
        <h2 style="font-size:22px;font-weight:800;color:var(--text-primary);margin-bottom:4px">Dapur Ina Aina</h2>
        <p style="font-size:13px;color:var(--text-muted)">Sistem Informasi Manajemen Restoran & E-Menu</p>
      </div>

      <!-- Tabs (Login vs Register) -->
      <div class="auth-tabs">
        <button class="auth-tab active" id="tab-btn-login" onclick="switchAuthTab('login')">
          🔑 Masuk (Login)
        </button>
        <button class="auth-tab" id="tab-btn-register" onclick="switchAuthTab('register')">
          📝 Daftar Akun Baru
        </button>
      </div>

      <!-- Tab Content: LOGIN -->
      <div class="auth-body" id="auth-box-login">
        <form id="form-login" onsubmit="handleLoginSubmit(event)">
          <div class="form-group">
            <label for="login-username">Username:</label>
            <input type="text" id="login-username" class="form-control" placeholder="Masukkan username" required autofocus>
          </div>

          <div class="form-group">
            <label for="login-password">Password:</label>
            <input type="password" id="login-password" class="form-control" placeholder="Masukkan password" required>
          </div>

          <button type="submit" class="btn btn-order" id="btn-submit-login" style="width:100%;padding:12px;font-size:15px;margin-top:12px">
            🚀 Masuk Sekarang
          </button>
        </form>

        <div style="text-align:center;margin-top:24px;font-size:13px">
          <span style="color:var(--text-muted)">Ingin pesan menu tanpa login? </span>
          <a href="user.php" style="color:var(--accent-terracotta);font-weight:700">Pesan Sebagai Tamu →</a>
        </div>
      </div>

      <!-- Tab Content: REGISTER -->
      <div class="auth-body" id="auth-box-register" style="display:none">
        <form id="form-register" onsubmit="handleRegisterSubmit(event)">
          <div class="form-group">
            <label for="reg-nama">Nama Lengkap *</label>
            <input type="text" id="reg-nama" class="form-control" placeholder="Contoh: Rina Wahyuni" required>
          </div>

          <div class="form-group">
            <label for="reg-username">Username *</label>
            <input type="text" id="reg-username" class="form-control" placeholder="Pilih username unik" required>
          </div>

          <div class="form-group">
            <label for="reg-nohp">Nomor WhatsApp / HP (Opsional)</label>
            <input type="tel" id="reg-nohp" class="form-control" placeholder="Contoh: 081234567890">
          </div>

          <div class="form-group">
            <label for="reg-password">Password *</label>
            <input type="password" id="reg-password" class="form-control" placeholder="Minimal 6 karakter" required minlength="6">
          </div>

          <div class="form-group">
            <label for="reg-role">Mendaftar Sebagai:</label>
            <select id="reg-role" class="form-control">
              <option value="pelanggan" selected>👤 Pelanggan / Tamu Restoran</option>
              <option value="kasir">💳 Staf Kasir</option>
            </select>
          </div>

          <button type="submit" class="btn btn-order" id="btn-submit-register" style="width:100%;padding:12px;font-size:15px;margin-top:8px">
            ✨ Daftarkan Akun Saya
          </button>
        </form>

        <div style="text-align:center;margin-top:20px;font-size:13px">
          <span style="color:var(--text-muted)">Sudah memiliki akun? </span>
          <a href="javascript:void(0)" onclick="switchAuthTab('login')" style="color:var(--primary-dark);font-weight:700">Masuk di sini</a>
        </div>
      </div>

    </div>
  </div>

  <script>
    function toast(msg, type = 'info') {
      const icons = { success: '✅', error: '❌', info: 'ℹ️', warning: '⚠️' };
      const c = document.getElementById('toast-container');
      const t = document.createElement('div');
      t.className = `toast ${type}`;
      t.innerHTML = `<span>${icons[type]}</span><span>${msg}</span>`;
      c.appendChild(t);
      setTimeout(() => t.remove(), 3500);
    }

    function switchAuthTab(tab) {
      document.getElementById('tab-btn-login').classList.toggle('active', tab === 'login');
      document.getElementById('tab-btn-register').classList.toggle('active', tab === 'register');
      document.getElementById('auth-box-login').style.display    = tab === 'login' ? 'block' : 'none';
      document.getElementById('auth-box-register').style.display = tab === 'register' ? 'block' : 'none';
    }

    async function handleLoginSubmit(e) {
      e.preventDefault();
      const username = document.getElementById('login-username').value.trim();
      const password = document.getElementById('login-password').value;
      const btn = document.getElementById('btn-submit-login');

      btn.disabled = true;
      btn.textContent = 'Memverifikasi...';

      try {
        const res = await fetch('api/auth_api.php?aksi=login', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ username, password })
        }).then(r => r.json());

        if (res.status !== 'success') {
          throw new Error(res.message || 'Login gagal');
        }

        toast('Login berhasil! Mengalihkan...', 'success');

        // Simpan nama pelanggan di localStorage untuk kemudahan
        if (res.data.nama_lengkap) {
          localStorage.setItem('dapur_ina_cust_nama', res.data.nama_lengkap);
        }

        setTimeout(() => {
          if (res.data.role === 'admin' || res.data.role === 'kasir') {
            window.location.href = 'index.php';
          } else {
            window.location.href = 'user.php';
          }
        }, 800);

      } catch (err) {
        toast(err.message, 'error');
        btn.disabled = false;
        btn.textContent = '🚀 Masuk Sekarang';
      }
    }

    async function handleRegisterSubmit(e) {
      e.preventDefault();
      const nama     = document.getElementById('reg-nama').value.trim();
      const username = document.getElementById('reg-username').value.trim();
      const no_hp    = document.getElementById('reg-nohp').value.trim();
      const password = document.getElementById('reg-password').value;
      const role     = document.getElementById('reg-role').value;
      const btn      = document.getElementById('btn-submit-register');

      btn.disabled = true;
      btn.textContent = 'Mendaftarkan...';

      try {
        const res = await fetch('api/auth_api.php?aksi=register', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            nama_lengkap: nama,
            username: username,
            password: password,
            no_hp: no_hp,
            role: role
          })
        }).then(r => r.json());

        if (res.status !== 'success') {
          throw new Error(res.message || 'Registrasi gagal');
        }

        toast('Pendaftaran akun berhasil! Mengalihkan...', 'success');
        localStorage.setItem('dapur_ina_cust_nama', nama);

        setTimeout(() => {
          if (role === 'kasir') {
            window.location.href = 'index.php';
          } else {
            window.location.href = 'user.php';
          }
        }, 1000);

      } catch (err) {
        toast(err.message, 'error');
        btn.disabled = false;
        btn.textContent = '✨ Daftarkan Akun Saya';
      }
    }
  </script>
</body>
</html>
