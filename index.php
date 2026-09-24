<?php
/**
 * Sistem Informasi Restoran Dapur Ina Aina
 * Dashboard Admin, Kasir, Manajemen Produk, Stok & Laporan
 */
require_once __DIR__ . '/classes/User.php';
$userModel = new User();
$currentUser = $userModel->getCurrentUser();

// Jika belum login atau role adalah pelanggan, arahkan ke login.php
if (!$currentUser || !in_array($currentUser['role'], ['admin', 'kasir'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Sistem Informasi Manajemen Restoran Dapur Ina Aina - Pemesanan, Transaksi, Stok, dan Laporan Penjualan">
  <title>Dapur Ina Aina - Sistem Informasi Restoran</title>
  <link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">
  <!-- Chart.js CDN -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>

<!-- Toast Container -->
<div class="toast-container" id="toast-container"></div>

<div class="app-layout">

  <!-- ════════════════════════════════════════
       SIDEBAR
  ════════════════════════════════════════ -->
  <aside class="sidebar">
    <div class="sidebar-logo">
      <div class="logo-icon">🍽️</div>
      <h2>Dapur Ina Aina</h2>
      <p>Sistem Informasi Restoran</p>
    </div>

    <nav class="sidebar-nav">
      <div class="nav-label">Menu Utama</div>

      <div class="nav-item active" data-page="dashboard">
        <span class="nav-icon">🏠</span>
        <span>Dashboard</span>
      </div>

      <div class="nav-item" data-page="antrian">
        <span class="nav-icon">📋</span>
        <span>Pesanan Masuk</span>
        <span class="badge-counter" id="badge-pending-orders" style="display:none">0</span>
      </div>

      <div class="nav-item" data-page="pesanan">
        <span class="nav-icon">🛒</span>
        <span>Kasir / POS</span>
      </div>

      <div class="nav-item" data-page="produk">
        <span class="nav-icon">📦</span>
        <span>Manajemen Produk</span>
      </div>

      <div class="nav-label">Keuangan</div>

      <div class="nav-item" data-page="laporan">
        <span class="nav-icon">📊</span>
        <span>Laporan Penjualan</span>
      </div>
    </nav>

    <div style="padding:16px;border-top:1px solid var(--border)">
      <div style="font-size:12px;color:#4B5563;font-weight:500;text-align:center">
        © 2024 Dapur Ina Aina<br>
        <span style="color:var(--primary-dark);font-weight:600">v1.0.0</span>
      </div>
    </div>
  </aside>

  <!-- ════════════════════════════════════════
       MAIN CONTENT
  ════════════════════════════════════════ -->
  <main class="main-content">

    <!-- Topbar -->
    <div class="topbar">
      <div class="topbar-title">
        <h1 id="page-title">🏠 Dashboard</h1>
        <p id="page-subtitle">Selamat datang di Dapur Ina Aina</p>
      </div>
      <div class="topbar-right" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
        <div style="display:inline-flex;align-items:center;gap:8px;background:var(--bg-card);padding:5px 14px;border-radius:20px;border:1px solid var(--border);box-shadow:var(--shadow-sm)">
          <span style="font-size:13px;font-weight:700;color:var(--text-primary)">
            👤 <?= htmlspecialchars($currentUser['nama_lengkap']) ?>
          </span>
          <span class="badge <?= $currentUser['role'] === 'admin' ? 'badge-makanan' : 'badge-minuman' ?>" style="font-size:11px">
            <?= htmlspecialchars(strtoupper($currentUser['role'])) ?>
          </span>
          <a href="api/auth_api.php?aksi=logout" class="btn btn-secondary btn-sm" style="padding:4px 10px;font-size:11px;border-radius:14px" title="Keluar dari Sistem">
            🚪 Keluar
          </a>
        </div>
        <a href="user.php" target="_blank" class="btn btn-outline btn-sm" style="display:inline-flex;align-items:center;gap:6px;text-decoration:none;border-radius:20px;padding:6px 14px" title="Buka E-Menu Pelanggan di tab baru">
          <span>🍽️</span> <span>E-Menu Pelanggan ↗</span>
        </a>
        <div class="badge-time" id="clock">--:--:--</div>
      </div>
    </div>

    <!-- ═══════════════════════════════
         SECTION: DASHBOARD
    ═══════════════════════════════ -->
    <div class="page-content page-section" id="section-dashboard">

      <!-- Stat Cards -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon orange">🛒</div>
          <div class="stat-info">
            <div class="stat-value" id="stat-pesanan">0</div>
            <div class="stat-label">Pesanan Hari Ini</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon green">💰</div>
          <div class="stat-info">
            <div class="stat-value" style="font-size:18px" id="stat-pendapatan">Rp 0</div>
            <div class="stat-label">Pendapatan Hari Ini</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon yellow">⚠️</div>
          <div class="stat-info">
            <div class="stat-value" id="stat-stok-habis">0</div>
            <div class="stat-label">Stok Hampir Habis</div>
          </div>
        </div>
        <div class="stat-card" style="cursor:pointer" onclick="navigateTo('pesanan')">
          <div class="stat-icon blue">➕</div>
          <div class="stat-info">
            <div class="stat-value" style="font-size:16px">Buat Pesanan</div>
            <div class="stat-label">Klik untuk mulai</div>
          </div>
        </div>
      </div>

      <!-- Grafik + Top Produk -->
      <div class="section-grid-2" style="margin-bottom:24px">
        <div class="card">
          <div class="card-header">
            <div class="card-title">📈 Penjualan 7 Hari Terakhir</div>
          </div>
          <div class="chart-container">
            <canvas id="grafik-penjualan"></canvas>
          </div>
        </div>

        <div class="card">
          <div class="card-header">
            <div class="card-title">🏆 Top 5 Produk Terlaris</div>
          </div>
          <div id="top-produk-dashboard">
            <div class="loading"><div class="spinner"></div> Memuat data...</div>
          </div>
        </div>
      </div>

      <!-- Rekap Kategori -->
      <div class="card">
        <div class="card-header">
          <div class="card-title">🍽️ Rekap Per Kategori</div>
        </div>
        <div id="rekap-kategori">
          <div class="loading"><div class="spinner"></div> Memuat data...</div>
        </div>
      </div>

    </div><!-- /section-dashboard -->


    <!-- ═══════════════════════════════
         SECTION: PESANAN MASUK (LIVE ORDER MONITOR)
    ═══════════════════════════════ -->
    <div class="page-content page-section" id="section-antrian" style="display:none">
      <div class="card" style="margin-bottom:20px">
        <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px">
          <div>
            <div class="card-title">📋 Antrian Pesanan Pelanggan (Live Orders)</div>
            <p style="font-size:13px;color:var(--text-muted);margin-top:2px">
              Pantau pesanan yang masuk secara real-time dari meja pelanggan, mulai memasak, dan selesaikan transaksi kasir.
            </p>
          </div>
          <div style="display:flex;gap:10px">
            <button class="btn btn-secondary btn-sm" onclick="loadAntrian()">🔄 Segarkan Data</button>
            <button class="btn btn-order btn-sm" onclick="navigateTo('pesanan')">+ Buat Pesanan Manual</button>
          </div>
        </div>

        <!-- Filter Status Tabs -->
        <div style="padding:14px 20px;border-bottom:1px solid var(--border);display:flex;gap:10px;flex-wrap:wrap;align-items:center" id="antrian-filter-tabs">
          <button class="menu-tab active" onclick="filterAntrian('')">Semua Pesanan</button>
          <button class="menu-tab" onclick="filterAntrian('pending')">⏳ Menunggu (Pending)</button>
          <button class="menu-tab" onclick="filterAntrian('diproses')">🍳 Dimasak (Diproses)</button>
          <button class="menu-tab" onclick="filterAntrian('selesai')">✅ Selesai</button>
          <button class="menu-tab" onclick="filterAntrian('dibatalkan')">❌ Dibatalkan</button>
        </div>

        <div style="padding:20px">
          <!-- Order Cards Grid -->
          <div class="order-monitor-grid" id="antrian-grid">
            <div class="loading"><div class="spinner"></div> Memuat antrian pesanan...</div>
          </div>
        </div>
      </div>
    </div>


    <!-- ═══════════════════════════════
         SECTION: PESANAN
    ═══════════════════════════════ -->
    <div class="page-content page-section" id="section-pesanan" style="display:none">

      <!-- Input Pelanggan -->
      <div class="card" style="margin-bottom:20px">
        <div class="card-header">
          <div class="card-title">👤 Data Pelanggan</div>
        </div>
        <div class="form-row">
          <div class="form-group" style="margin:0">
            <label for="inp-nama">Nama Pelanggan *</label>
            <input type="text" id="inp-nama" placeholder="Masukkan nama pelanggan">
          </div>
          <div class="form-group" style="margin:0">
            <label for="inp-meja">Nomor Meja *</label>
            <input type="text" id="inp-meja" placeholder="Contoh: A1, B2, 05">
          </div>
        </div>
        <div class="form-group" style="margin-top:14px;margin-bottom:0">
          <label for="inp-catatan">Catatan Pesanan</label>
          <textarea id="inp-catatan" rows="2" placeholder="Catatan khusus (opsional)..."></textarea>
        </div>
      </div>

      <!-- Pesanan Layout -->
      <div class="pesanan-layout">

        <!-- Menu -->
        <div>
          <div class="card">
            <div class="card-header">
              <div class="card-title">🍽️ Menu</div>
            </div>
            <div id="kategori-tabs" class="menu-tabs">
              <div class="loading"><div class="spinner"></div></div>
            </div>
            <div id="menu-grid" class="menu-grid">
              <div class="loading" style="grid-column:1/-1"><div class="spinner"></div> Memuat menu...</div>
            </div>
          </div>

          <!-- Pesanan Aktif -->
          <div class="card" style="margin-top:20px">
            <div class="card-header">
              <div class="card-title">📋 Pesanan Aktif (Belum Dibayar)</div>
              <button class="btn btn-secondary btn-sm" onclick="loadPesananAktif()">🔄 Refresh</button>
            </div>
            <div class="table-wrap">
              <table>
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Pelanggan</th>
                    <th>Meja</th>
                    <th>Total</th>
                    <th>Aksi</th>
                  </tr>
                </thead>
                <tbody id="tbl-pesanan-aktif">
                  <tr><td colspan="5" class="text-center text-muted" style="padding:20px">Memuat...</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Cart -->
        <div class="cart-sidebar">
          <div class="card" style="position:sticky;top:80px">
            <div class="card-header">
              <div class="card-title">🛒 Keranjang <span id="cart-count" style="background:var(--primary);color:#fff;border-radius:50px;padding:1px 8px;font-size:12px">0</span></div>
              <button class="btn btn-secondary btn-sm" onclick="clearCart()">🗑 Kosongkan</button>
            </div>
            <div id="cart-items" class="cart-items-wrap is-empty">
              <div class="empty-state">
                <div class="empty-icon-wrap">🛒</div>
                <div class="empty-title">Keranjang Masih Kosong</div>
                <div class="empty-desc">Pilih menu makanan atau minuman di samping untuk menambahkan ke pesanan.</div>
              </div>
            </div>
            <div style="margin-top:16px;padding-top:16px;border-top:1px solid var(--border)">
              <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
                <span style="font-size:15px;font-weight:600">Total</span>
                <span class="price" style="font-size:20px" id="cart-total">Rp 0</span>
              </div>
              <button class="btn btn-order btn-full btn-lg" onclick="submitPesanan()">
                💳 Buat Pesanan & Bayar
              </button>
            </div>
          </div>
        </div>

      </div>
    </div><!-- /section-pesanan -->


    <!-- ═══════════════════════════════
         SECTION: PRODUK
    ═══════════════════════════════ -->
    <div class="page-content page-section" id="section-produk" style="display:none">
      <div class="card">
        <div class="card-header">
          <div class="card-title">📦 Daftar Produk & Stok</div>
          <button class="btn btn-primary" onclick="openTambahProduk()">➕ Tambah Produk</button>
        </div>
        <div class="search-bar">
          <div class="search-input">
            <input type="text" id="search-produk" placeholder="Cari produk atau kategori...">
          </div>
        </div>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Foto</th>
                <th>Nama Produk</th>
                <th>Kategori</th>
                <th>Harga</th>
                <th>Stok</th>
                <th>Status</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody id="tbl-produk">
              <tr><td colspan="7" class="text-center"><div class="loading"><div class="spinner"></div> Memuat...</div></td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div><!-- /section-produk -->


    <!-- ═══════════════════════════════
         SECTION: LAPORAN
    ═══════════════════════════════ -->
    <div class="page-content page-section" id="section-laporan" style="display:none">

      <!-- Filter -->
      <div class="card" style="margin-bottom:20px">
        <div class="card-header">
          <div class="card-title">🗓️ Filter Laporan</div>
        </div>
        <div style="display:flex;gap:16px;align-items:flex-end;flex-wrap:wrap">
          <div class="form-group" style="margin:0;min-width:150px">
            <label for="sel-bulan">Bulan</label>
            <select id="sel-bulan">
              <option value="1">Januari</option><option value="2">Februari</option>
              <option value="3">Maret</option><option value="4">April</option>
              <option value="5">Mei</option><option value="6">Juni</option>
              <option value="7">Juli</option><option value="8">Agustus</option>
              <option value="9">September</option><option value="10">Oktober</option>
              <option value="11">November</option><option value="12">Desember</option>
            </select>
          </div>
          <div class="form-group" style="margin:0;min-width:120px">
            <label for="sel-tahun">Tahun</label>
            <select id="sel-tahun">
              <option value="2024">2024</option>
              <option value="2025">2025</option>
              <option value="2026" selected>2026</option>
            </select>
          </div>
          <button class="btn btn-primary" onclick="loadLaporanBulanan()">🔍 Tampilkan Laporan</button>
        </div>
      </div>

      <!-- Ringkasan -->
      <div class="stats-grid" style="margin-bottom:20px">
        <div class="stat-card">
          <div class="stat-icon blue">📊</div>
          <div class="stat-info">
            <div class="stat-value" id="lap-total-transaksi">0</div>
            <div class="stat-label">Total Transaksi</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon green">💰</div>
          <div class="stat-info">
            <div class="stat-value" style="font-size:18px" id="lap-total-pendapatan">Rp 0</div>
            <div class="stat-label">Total Pendapatan</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon orange">📈</div>
          <div class="stat-info">
            <div class="stat-value" style="font-size:18px" id="lap-rata-transaksi">Rp 0</div>
            <div class="stat-label">Rata-Rata/Transaksi</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon yellow">💳</div>
          <div class="stat-info">
            <div style="display:flex;gap:16px;font-size:13px;margin-top:4px">
              <span>💵 Tunai: <strong id="lap-bayar-tunai">0</strong></span>
              <span>💳 Non Tunai: <strong id="lap-bayar-nontunai">0</strong></span>
            </div>
            <div class="stat-label">Metode Pembayaran</div>
          </div>
        </div>
      </div>

      <!-- Detail Laporan -->
      <div class="section-grid-2" style="margin-bottom:20px">
        <div class="card">
          <div class="card-title" style="margin-bottom:16px">🏆 Top Produk Terlaris</div>
          <div id="top-produk-laporan">
            <div class="loading"><div class="spinner"></div></div>
          </div>
        </div>
        <div class="card">
          <div class="card-title" style="margin-bottom:16px">🍽️ Rekap Per Kategori</div>
          <div id="rekap-kategori-laporan">
            <div class="loading"><div class="spinner"></div></div>
          </div>
        </div>
      </div>

      <!-- Tabel Transaksi -->
      <div class="card">
        <div class="card-header">
          <div class="card-title">📋 Riwayat Transaksi</div>
        </div>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Kode</th>
                <th>Waktu</th>
                <th>Pelanggan</th>
                <th>Metode</th>
                <th>Total</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody id="tbl-laporan">
              <tr><td colspan="6" class="text-center text-muted" style="padding:24px">Pilih periode lalu klik "Tampilkan Laporan"</td></tr>
            </tbody>
          </table>
        </div>
      </div>

    </div><!-- /section-laporan -->

  </main>
</div><!-- /app-layout -->


<!-- ════════════════════════════════════════
     MODALS
════════════════════════════════════════ -->

<!-- Modal: Konfirmasi Pesanan -->
<div class="modal-overlay" id="modal-konfirmasi">
  <div class="modal">
    <div class="modal-header">
      <h3>✅ Konfirmasi Pesanan</h3>
      <button class="btn-close" onclick="closeModal('modal-konfirmasi')">✕</button>
    </div>
    <div class="modal-body">
      <div class="alert alert-info">Pastikan pesanan sudah sesuai sebelum mengkonfirmasi.</div>
      <div id="konfirmasi-items" style="margin-bottom:12px"></div>
      <div style="display:flex;justify-content:space-between;font-size:16px;font-weight:700;padding-top:12px;border-top:1px solid var(--border)">
        <span>Total</span>
        <span class="price" id="konfirmasi-total">Rp 0</span>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('modal-konfirmasi')">Batal</button>
      <button class="btn btn-primary" onclick="prosesPesanan()">✅ Konfirmasi & Buat Pesanan</button>
    </div>
  </div>
</div>

<!-- Modal: Detail Pesanan -->
<div class="modal-overlay" id="modal-detail-pesanan">
  <div class="modal modal-lg">
    <div class="modal-header">
      <h3>📋 Detail Pesanan</h3>
      <button class="btn-close" onclick="closeModal('modal-detail-pesanan')">✕</button>
    </div>
    <div class="modal-body" id="detail-pesanan-body"></div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('modal-detail-pesanan')">Tutup</button>
    </div>
  </div>
</div>

<!-- Modal: Tambah/Edit Produk -->
<div class="modal-overlay" id="modal-produk">
  <div class="modal">
    <div class="modal-header">
      <h3 id="modal-produk-title">➕ Tambah Produk</h3>
      <button class="btn-close" onclick="closeModal('modal-produk')">✕</button>
    </div>
    <div class="modal-body">
      <div id="form-produk">

        <!-- Foto Produk -->
        <div class="form-group">
          <label>Foto Produk</label>
          <div class="foto-upload-wrap" id="foto-upload-wrap" onclick="document.getElementById('inp-foto').click()">
            <img id="foto-preview" class="foto-preview" src="" alt="Preview">
            <div id="foto-placeholder">
              <div class="foto-placeholder-icon">📷</div>
              <div class="foto-placeholder-text">Klik untuk pilih foto produk<br><small style="color:#aaa">JPG, PNG, WEBP (maks. 2MB)</small></div>
            </div>
            <input type="file" id="inp-foto" accept="image/*" style="display:none" onchange="previewFoto(this)">
          </div>
          <div id="foto-action-wrap" style="display:none;margin-top:8px;text-align:right">
            <button type="button" class="btn btn-secondary btn-sm" onclick="clearFoto(event)">🗑 Hapus Foto</button>
          </div>
          <input type="hidden" id="inp-foto-path" value="">
        </div>

        <div class="form-group">
          <label for="inp-id-kategori">Kategori *</label>
          <select id="inp-id-kategori" class="sel-kategori">
            <option value="">-- Pilih Kategori --</option>
          </select>
        </div>
        <div class="form-group">
          <label for="inp-nama-produk">Nama Produk *</label>
          <input type="text" id="inp-nama-produk" placeholder="Nama produk">
        </div>
        <div class="form-group">
          <label for="inp-deskripsi">Deskripsi</label>
          <textarea id="inp-deskripsi" rows="2" placeholder="Deskripsi singkat produk..."></textarea>
        </div>
        <div class="form-row">
          <div class="form-group" style="margin:0">
            <label for="inp-harga">Harga (Rp) *</label>
            <input type="number" id="inp-harga" placeholder="0" min="0">
          </div>
          <div class="form-group" style="margin:0">
            <label for="inp-stok">Stok *</label>
            <input type="number" id="inp-stok" placeholder="0" min="0">
          </div>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('modal-produk')">Batal</button>
      <button class="btn btn-primary" onclick="saveProduk()">💾 Simpan</button>
    </div>
  </div>
</div>

<!-- Modal: Billing/Pembayaran -->
<div class="modal-overlay" id="modal-billing">
  <div class="modal modal-lg">
    <div class="modal-header">
      <h3>💳 Konfirmasi Pembayaran Masuk</h3>
      <button class="btn-close" onclick="closeModal('modal-billing')">✕</button>
    </div>
    <div class="modal-body">

      <!-- Info Pesanan -->
      <div class="alert alert-info" style="margin-bottom:16px">
        <span>🧾 Pesanan <strong id="billing-id">#0</strong> | 
        <strong id="billing-nama">-</strong> | 
        <strong id="billing-meja">-</strong> | 
        <span id="billing-waktu">-</span></span>
      </div>

      <!-- Item List -->
      <div id="billing-items" style="margin-bottom:12px"></div>

      <div style="display:flex;justify-content:space-between;font-size:18px;font-weight:800;padding:14px;background:var(--bg-card2);border:1px solid var(--border);border-radius:var(--radius-md);margin-bottom:14px">
        <span>Total Tagihan</span>
        <span class="price" id="billing-total">Rp 0</span>
      </div>
      <input type="hidden" id="billing-total-bayar" value="0">
      <input type="hidden" id="sel-metode-bayar" value="tunai">

      <div style="background:#FFFBEB;border:1px solid #FDE68A;border-radius:10px;padding:12px;margin-bottom:16px;font-size:13px;color:#92400E">
        <strong>⚠️ Pengecekan Admin / Kasir:</strong><br>
        Pastikan dana transfer E-Wallet sudah masuk ke akun <strong>0812345678</strong> (Dapur Ina Aina) atau uang tunai sudah diserahkan oleh pelanggan.
      </div>

      <!-- Pilih Metode Bayar -->
      <div style="margin-bottom:8px;font-size:13px;font-weight:600;color:var(--text-secondary)">Pilih Jalur Pembayaran yang Diterima:</div>
      <div class="payment-methods">
        <div class="payment-card active" data-metode="tunai" onclick="selectMetodeBayar('tunai')">
          <div class="payment-card-icon">💵</div>
          <div class="payment-card-name">Tunai</div>
        </div>
        <div class="payment-card" data-metode="non tunai" onclick="selectMetodeBayar('non tunai')">
          <div class="payment-card-icon">📱</div>
          <div class="payment-card-name">E-Wallet (0812345678)</div>
        </div>
      </div>

      <!-- Form Tunai -->
      <div id="section-tunai">
        <div class="form-group">
          <label for="inp-uang-bayar">💵 Uang Tunai Diterima (Rp)</label>
          <input type="number" id="inp-uang-bayar" placeholder="Masukkan nominal uang" min="0" oninput="hitungKembalian()">
        </div>
        <div style="display:flex;justify-content:space-between;padding:12px;background:var(--bg-card2);border:1px solid var(--border);border-radius:var(--radius-md);font-weight:700">
          <span>Kembalian</span>
          <span id="billing-kembalian" style="color:var(--success)">Rp 0</span>
        </div>
      </div>

      <!-- Form Non-Tunai (E-Wallet) -->
      <div id="section-nontunai" style="display:none">
        <div class="form-group">
          <label for="sel-admin-ewallet">Jenis E-Wallet Penerima</label>
          <select id="sel-admin-ewallet" class="form-control" style="font-size:14px;font-weight:600;padding:10px">
            <option value="GoPay">🟢 GoPay (0812345678)</option>
            <option value="OVO">🟣 OVO (0812345678)</option>
            <option value="DANA">🔵 DANA (0812345678)</option>
            <option value="ShopeePay">🟠 ShopeePay (0812345678)</option>
          </select>
        </div>
        <div class="form-group">
          <label for="inp-no-referensi">Nomor Referensi / Kode Transaksi (Opsional)</label>
          <input type="text" id="inp-no-referensi" placeholder="REF-..." style="font-size:13px">
        </div>
        <div class="alert alert-info" style="font-size:12px">
          💡 Cek notifikasi transaksi masuk pada nomor <strong>0812345678</strong> atas nama <strong>Dapur Ina Aina</strong>.
        </div>
      </div>

    </div>
    <div class="modal-footer" style="display:flex;justify-content:space-between;gap:10px">
      <button class="btn btn-secondary" onclick="closeModal('modal-billing');toast('Pembayaran belum dikonfirmasi masuk', 'info')">
        ⏳ Belum Masuk (Tutup)
      </button>
      <button class="btn btn-success btn-lg" onclick="prosesBayar()">
        ✅ Konfirmasi Uang Sudah Masuk
      </button>
    </div>
  </div>
</div>

<!-- Modal: Struk -->
<div class="modal-overlay" id="modal-struk">
  <div class="modal">
    <div class="modal-header">
      <h3>🧾 Struk Pembayaran</h3>
      <button class="btn-close" onclick="closeModal('modal-struk')">✕</button>
    </div>
    <div class="modal-body">
      <div id="struk-print">
        <div class="struk">
          <div class="struk-header">
            <h2>🍽️ Dapur Ina Aina</h2>
            <p>Restoran Masakan Nusantara</p>
            <p>Jl. Kuliner No. 1, Kota</p>
            <hr class="struk-divider">
            <p id="struk-kode" style="font-weight:700">TRX-...</p>
            <p id="struk-waktu" style="font-size:12px;color:#666"></p>
          </div>
          <div class="struk-divider"></div>
          <div class="struk-row">
            <span>Pelanggan</span><span id="struk-nama">-</span>
          </div>
          <div class="struk-row">
            <span>Meja</span><span id="struk-meja">-</span>
          </div>
          <div class="struk-row">
            <span>Metode Bayar</span><span id="struk-metode">-</span>
          </div>
          <div class="struk-divider"></div>
          <div id="struk-items"></div>
          <div class="struk-divider"></div>
          <div class="struk-row total">
            <span>TOTAL</span><span id="struk-total">-</span>
          </div>
          <div class="struk-divider"></div>
          <div id="struk-bayar-section"></div>
          <div class="struk-footer">
            <p>Terima kasih sudah makan di</p>
            <p><strong>Dapur Ina Aina</strong></p>
            <p>Selamat menikmati! 😊</p>
          </div>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('modal-struk')">Tutup</button>
      <button class="btn btn-primary" onclick="printStruk()">🖨️ Cetak Struk</button>
    </div>
  </div>
</div>


<!-- ════════════════════════════════════════
     SCRIPTS
════════════════════════════════════════ -->
<script src="assets/js/app.js?v=<?= time() ?>"></script>
<script src="assets/js/pesanan.js?v=<?= time() ?>"></script>
<script src="assets/js/produk.js?v=<?= time() ?>"></script>
<script src="assets/js/laporan.js?v=<?= time() ?>"></script>

</body>
</html>
