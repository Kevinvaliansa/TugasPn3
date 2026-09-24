<?php
/**
 * Halaman E-Menu & Pemesanan Mandiri Pelanggan
 * Sistem Informasi Restoran Dapur Ina Aina
 */
require_once __DIR__ . '/classes/User.php';
$userModel = new User();
$currentUser = $userModel->getCurrentUser();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="E-Menu & Pemesanan Mandiri Pelanggan - Dapur Ina Aina. Pesan makanan dan minuman favorit langsung dari meja Anda.">
  <title>E-Menu Pelanggan - Dapur Ina Aina</title>
  <link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">
  <style>
    .user-pay-card {
      display: flex !important;
      flex-direction: column !important;
      align-items: center !important;
      text-align: center !important;
      padding: 14px 10px !important;
      border-radius: 12px !important;
      border: 2px solid #CBD5E1 !important;
      background: #FFFFFF !important;
      cursor: pointer !important;
      transition: all 0.2s ease !important;
      user-select: none !important;
      box-shadow: 0 1px 3px rgba(0,0,0,0.05) !important;
    }
    .user-pay-card:hover {
      border-color: #E0533C !important;
      background: #FFF7ED !important;
      transform: translateY(-2px) !important;
    }
    .user-pay-card.active {
      border-color: #E0533C !important;
      background: #FFF7ED !important;
      box-shadow: 0 4px 12px rgba(224, 83, 60, 0.2) !important;
    }
    .ewallet-chip {
      transition: all 0.2s ease !important;
      user-select: none !important;
    }
    .ewallet-chip:hover {
      transform: translateY(-2px) !important;
    }
    .ewallet-chip.active {
      box-shadow: 0 4px 10px rgba(0,0,0,0.12) !important;
    }
    .ewallet-select-card {
      display: flex !important;
      align-items: center !important;
      gap: 10px !important;
      padding: 10px 12px !important;
      border: 2px solid #CBD5E1 !important;
      border-radius: 12px !important;
      background: #fff !important;
      cursor: pointer !important;
      transition: all 0.2s ease !important;
    }
    .ewallet-select-card:hover {
      transform: translateY(-2px) !important;
      box-shadow: 0 4px 12px rgba(0,0,0,0.06) !important;
    }
    .ewallet-select-card.active {
      border-color: var(--ewallet-color, #E0533C) !important;
      background: var(--ewallet-bg, #F9FAFB) !important;
      box-shadow: 0 4px 12px rgba(0,0,0,0.08) !important;
    }
  </style>
</head>
<body style="background: var(--bg-card2)">

  <!-- Toast Container -->
  <div class="toast-container" id="toast-container"></div>

  <!-- ════════════════════════════════════════
       CUSTOMER NAVBAR
  ════════════════════════════════════════ -->
  <header class="user-header">
    <div class="user-nav">
      <div class="user-logo">
        <div class="user-logo-icon">🍽️</div>
        <div class="user-logo-text">
          <h1>Dapur Ina Aina</h1>
          <p>E-Menu & Pemesanan Meja</p>
        </div>
      </div>

      <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
        <!-- Tombol Nomor Meja -->
        <button class="btn btn-secondary btn-sm" id="table-indicator" onclick="openTableModal()" style="display:inline-flex;align-items:center;gap:6px" title="Atur Nomor Meja Duduk">
          <span>📍</span> <span id="lbl-active-meja">Pilih Meja</span>
        </button>

        <!-- Tombol Lacak Pesanan -->
        <button class="btn btn-outline btn-sm" onclick="openTrackingModal()" style="display:inline-flex;align-items:center;gap:6px" title="Pantau Proses Memasak & Status Meja">
          <span>🔍</span> <span>Lacak Pesanan</span>
        </button>

        <!-- Tombol Keranjang -->
        <button class="btn btn-order btn-sm" onclick="openCartDrawer()" id="btn-header-cart" style="display:inline-flex;align-items:center;gap:8px" title="Buka Keranjang Pesanan">
          <span>🛒</span> <span>Keranjang</span>
          <span class="badge" style="background:#fff;color:var(--accent-terracotta);padding:2px 7px;font-size:11px;font-weight:800;border-radius:10px" id="header-cart-count">0</span>
        </button>

        <!-- Status Autentikasi User -->
        <?php if ($currentUser): ?>
          <div style="display:inline-flex;align-items:center;gap:8px;background:var(--bg-card);padding:5px 14px;border-radius:20px;border:1px solid var(--border)">
            <?php if (in_array($currentUser['role'], ['admin', 'kasir'])): ?>
              <a href="index.php" style="display:inline-flex;align-items:center;gap:6px;font-size:13px;font-weight:700;color:var(--text-primary);text-decoration:none" title="Klik untuk masuk ke Dashboard Admin">
                <span>👤</span> <span><?= htmlspecialchars($currentUser['nama_lengkap']) ?></span>
                <span class="badge badge-makanan" style="font-size:10px;padding:2px 7px;border-radius:10px;cursor:pointer">Admin ↗</span>
              </a>
            <?php else: ?>
              <span style="font-size:13px;font-weight:700;color:var(--text-primary)">
                👤 <?= htmlspecialchars($currentUser['nama_lengkap']) ?>
              </span>
            <?php endif; ?>
            <a href="api/auth_api.php?aksi=logout" class="btn btn-secondary btn-sm" style="padding:4px 10px;font-size:11px;border-radius:14px" title="Keluar dari Akun">
              🚪 Keluar
            </a>
          </div>
        <?php else: ?>
          <a href="login.php" class="btn btn-outline btn-sm" style="display:inline-flex;align-items:center;gap:6px">
            <span>🔑</span> <span>Masuk / Daftar</span>
          </a>
        <?php endif; ?>
      </div>
    </div>
  </header>

  <!-- ════════════════════════════════════════
       MAIN CUSTOMER CONTENT
  ════════════════════════════════════════ -->
  <main class="user-container">

    <!-- Hero / Welcome Banner -->
    <section class="user-hero">
      <div class="user-hero-text">
        <div class="badge badge-makanan" style="margin-bottom:8px">✨ Selamat Datang di Restoran Kami</div>
        <h2>Pesan Makanan & Minuman Langsung dari Meja</h2>
        <p>Pilih menu favorit Anda, tambahkan ke keranjang, dan kirim pesanan langsung ke koki kami. Status pesanan dapat dipantau secara real-time!</p>
      </div>

      <div class="user-table-box">
        <div style="font-size:12px;font-weight:700;color:var(--text-muted);text-transform:uppercase;margin-bottom:8px">Identitas Pemesan</div>
        <div style="margin-bottom:10px">
          <label style="font-size:12px;font-weight:600;display:block;margin-bottom:4px;color:var(--text-secondary)">Nama Anda:</label>
          <input type="text" id="cust-nama" class="form-control" 
                 value="<?= htmlspecialchars($currentUser['nama_lengkap'] ?? '') ?>" 
                 placeholder="Contoh: Budi Santoso" style="padding:8px 12px;font-size:13px">
        </div>
        <div>
          <label style="font-size:12px;font-weight:600;display:block;margin-bottom:4px;color:var(--text-secondary)">Nomor Meja:</label>
          <select id="cust-meja" class="form-control" style="padding:8px 12px;font-size:13px" onchange="updateTableSelection()">
            <option value="">-- Pilih Meja Duduk --</option>
            <option value="01">Meja 01</option>
            <option value="02">Meja 02</option>
            <option value="03">Meja 03</option>
            <option value="04">Meja 04</option>
            <option value="05">Meja 05</option>
            <option value="06">Meja 06</option>
            <option value="07">Meja 07</option>
            <option value="08">Meja 08</option>
            <option value="09">Meja 09</option>
            <option value="10">Meja 10</option>
            <option value="VIP-1">Meja VIP 1</option>
            <option value="VIP-2">Meja VIP 2</option>
          </select>
        </div>
      </div>
    </section>

    <!-- Search & Filter Controls -->
    <section style="margin-bottom:24px">
      <div style="display:flex;gap:16px;flex-wrap:wrap;justify-content:space-between;align-items:center;margin-bottom:16px">
        <!-- Category Tabs -->
        <div class="menu-tabs" id="user-kategori-tabs">
          <!-- Filled by JS -->
          <button class="menu-tab active">🍽️ Semua Menu</button>
        </div>

        <!-- Search Input -->
        <div style="min-width:280px;position:relative">
          <input type="text" id="user-search-input" class="form-control" placeholder="🔍 Cari menu favorit Anda..." oninput="handleSearchMenu(this.value)">
        </div>
      </div>
    </section>

    <!-- Menu Catalog Grid -->
    <section>
      <div class="user-menu-grid" id="user-menu-grid">
        <!-- Rendered by JS -->
        <div style="grid-column:1/-1;text-align:center;padding:40px;color:var(--text-muted)">
          Memuat menu hidangan lezat...
        </div>
      </div>
    </section>

  </main>

  <!-- ════════════════════════════════════════
       FLOATING CART BUTTON (MOBILE)
  ════════════════════════════════════════ -->
  <button class="floating-cart-btn" onclick="openCartDrawer()">
    <span>🛒</span>
    <span>Lihat Pesanan</span>
    <span class="badge" style="background:#fff;color:var(--accent-terracotta);padding:2px 8px;font-size:12px;border-radius:12px" id="floating-cart-count">0</span>
  </button>

  <!-- ════════════════════════════════════════
       MODAL: KERANJANG PESANAN PELANGGAN
  ════════════════════════════════════════ -->
  <div class="modal-overlay" id="modal-cart-user">
    <div class="modal" style="max-width:560px">
      <div class="modal-header">
        <div>
          <h3 style="font-size:18px;font-weight:700;color:var(--text-primary);display:flex;align-items:center;gap:8px">
            <span>🛒</span> <span>Keranjang Pesanan Anda</span>
          </h3>
          <div style="font-size:12px;color:var(--text-muted);margin-top:2px" id="cart-meja-sub">Meja belum dipilih</div>
        </div>
        <button class="btn-close" onclick="closeModal('modal-cart-user')">✕</button>
      </div>

      <div class="modal-body" style="padding:16px 24px">
        <!-- Cart Items List -->
        <div id="user-cart-items-wrap" style="max-height:300px;overflow-y:auto;margin-bottom:16px">
          <!-- Rendered by JS -->
        </div>

        <!-- Catatan Khusus Dapur -->
        <div style="margin-bottom:16px">
          <label style="font-size:12px;font-weight:700;display:block;margin-bottom:4px;color:var(--text-secondary)">
            📝 Catatan Khusus untuk Koki / Dapur (Opsional):
          </label>
          <textarea id="user-cart-catatan" class="form-control" rows="2" placeholder="Contoh: Sambal dipisah, tidak pedas, es teh gulanya sedikit..." style="font-size:13px"></textarea>
        </div>

        <!-- Pilihan Metode Pembayaran -->
        <div style="margin-bottom:16px">
          <label style="font-size:12px;font-weight:700;display:block;margin-bottom:8px;color:var(--text-secondary)">
            💳 Metode Pembayaran:
          </label>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px">
            <div class="user-pay-card active" id="cart-pay-tunai" onclick="selectCartPaymentMethod('tunai')">
              <div style="font-size:22px;margin-bottom:2px">💵</div>
              <div style="font-weight:700;font-size:13px;color:var(--text-primary)">Tunai</div>
              <div style="font-size:11px;color:var(--text-muted)">Uang tunai ke kasir / waiter</div>
            </div>
            <div class="user-pay-card" id="cart-pay-nontunai" onclick="selectCartPaymentMethod('non tunai')">
              <div style="font-size:22px;margin-bottom:2px">📱</div>
              <div style="font-weight:700;font-size:13px;color:var(--text-primary)">E-Wallet</div>
              <div style="font-size:11px;color:var(--text-muted)">GoPay, OVO, DANA, ShopeePay</div>
            </div>
          </div>

          <!-- Pilihan E-Wallet jika E-Wallet dipilih -->
          <div id="cart-ewallet-selector" style="display:none;background:var(--bg-card2);padding:12px;border-radius:12px;border:1px solid var(--border);margin-bottom:12px">
            <div style="font-size:12px;font-weight:700;color:var(--text-secondary);margin-bottom:8px">Pilih E-Wallet:</div>
            <div style="display:grid;grid-template-columns:repeat(4, 1fr);gap:8px" id="cart-ewallet-chips">
              <div class="ewallet-chip active" id="cart-ew-GoPay" onclick="selectCartEWallet('GoPay')" style="padding:8px 4px;border:2px solid #00AA13;background:#E8F7ED;border-radius:8px;text-align:center;cursor:pointer;font-weight:700;font-size:12px;color:#00AA13">
                🟢 GoPay
              </div>
              <div class="ewallet-chip" id="cart-ew-OVO" onclick="selectCartEWallet('OVO')" style="padding:8px 4px;border:2px solid var(--border);background:#fff;border-radius:8px;text-align:center;cursor:pointer;font-weight:700;font-size:12px;color:#4C3494">
                🟣 OVO
              </div>
              <div class="ewallet-chip" id="cart-ew-DANA" onclick="selectCartEWallet('DANA')" style="padding:8px 4px;border:2px solid var(--border);background:#fff;border-radius:8px;text-align:center;cursor:pointer;font-weight:700;font-size:12px;color:#118EEA">
                🔵 DANA
              </div>
              <div class="ewallet-chip" id="cart-ew-ShopeePay" onclick="selectCartEWallet('ShopeePay')" style="padding:8px 4px;border:2px solid var(--border);background:#fff;border-radius:8px;text-align:center;cursor:pointer;font-weight:700;font-size:12px;color:#EE4D2D">
                🟠 Shopee
              </div>
            </div>
          </div>
          <input type="hidden" id="user-selected-metode" value="tunai">
          <input type="hidden" id="user-selected-ewallet" value="GoPay">
        </div>

        <!-- Total Breakdown -->
        <div style="background:var(--bg-card2);padding:14px;border-radius:var(--radius-md);border:1px solid var(--border)">
          <div class="flex justify-between" style="font-size:13px;color:var(--text-secondary);margin-bottom:6px">
            <span>Subtotal Menu:</span>
            <span id="user-cart-subtotal" class="fw-bold">Rp 0</span>
          </div>
          <div class="flex justify-between" style="font-size:13px;color:var(--text-secondary);margin-bottom:8px">
            <span>Pajak & Layanan:</span>
            <span style="color:var(--success);font-weight:600">Gratis (Termasuk)</span>
          </div>
          <div class="flex justify-between" style="font-size:16px;font-weight:800;color:var(--text-primary);border-top:1px dashed var(--border);padding-top:8px">
            <span>Total Tagihan:</span>
            <span class="price" id="user-cart-total" style="font-size:18px">Rp 0</span>
          </div>
        </div>
      </div>

      <div class="modal-footer" style="padding:16px 24px 24px;display:flex;justify-content:space-between">
        <button class="btn btn-secondary" onclick="closeModal('modal-cart-user')">Lanjut Pilih Menu</button>
        <button class="btn btn-order" onclick="kirimPesananUser()" id="btn-kirim-pesanan" style="padding:10px 24px">
          🚀 Kirim Pesanan ke Dapur
        </button>
      </div>
    </div>
  </div>

  <!-- ════════════════════════════════════════
       MODAL: LIVE TRACKING STATUS PESANAN
  ════════════════════════════════════════ -->
  <div class="modal-overlay" id="modal-tracking">
    <div class="modal modal-lg" style="max-width:580px">
      <div class="modal-header">
        <h3 style="font-size:18px;font-weight:700;color:var(--text-primary);display:flex;align-items:center;gap:8px">
          <span>📍</span> <span>Status Pesanan Meja Anda</span>
        </h3>
        <button class="btn-close" onclick="closeModal('modal-tracking')">✕</button>
      </div>

      <div class="modal-body" style="padding:20px 24px" id="tracking-body">
        <!-- Rendered by JS -->
      </div>

      <div class="modal-footer" style="padding:16px 24px 24px;display:flex;justify-content:space-between">
        <button class="btn btn-secondary" onclick="closeModal('modal-tracking')">Tutup</button>
        <button class="btn btn-outline" onclick="cekStatusPesananAktif()">🔄 Refresh Status</button>
      </div>
    </div>
  </div>

  <!-- ════════════════════════════════════════
       MODAL: STRUK PEMBAYARAN PELANGGAN
  ════════════════════════════════════════ -->
  <div class="modal-overlay" id="modal-struk">
    <div class="modal" style="max-width:440px">
      <div class="modal-header">
        <h3 style="font-size:17px;font-weight:700">🧾 Struk Pembayaran</h3>
        <button class="btn-close" onclick="closeModal('modal-struk')">✕</button>
      </div>
      <div class="modal-body" style="padding:16px 20px">
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
              <p>Terima kasih atas pesanan Anda di</p>
              <p><strong>Dapur Ina Aina</strong></p>
              <p>Selamat menikmati hidangan! 😊</p>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer" style="padding:12px 20px;display:flex;justify-content:space-between">
        <button class="btn btn-secondary" onclick="closeModal('modal-struk')">Tutup</button>
        <button class="btn btn-primary" onclick="window.print()">🖨️ Cetak / Simpan</button>
      </div>
    </div>
  </div>

  <!-- ════════════════════════════════════════
       MODAL: KONFIRMASI HAPUS MENU DARI KERANJANG
  ════════════════════════════════════════ -->
  <div class="modal-overlay" id="modal-confirm-delete-user">
    <div class="modal" style="max-width:400px;text-align:center">
      <div class="modal-body" style="padding:32px 24px 24px">
        <div style="font-size:44px;margin-bottom:12px">🗑️</div>
        <h3 style="font-size:17px;font-weight:700;color:var(--text-primary);margin-bottom:8px">Hapus dari Pesanan?</h3>
        <p style="font-size:13px;color:var(--text-muted);margin-bottom:20px" id="lbl-confirm-delete-text">
          Apakah Anda yakin ingin membatalkan menu ini dari keranjang pesanan?
        </p>
        <div style="display:flex;gap:12px;justify-content:center">
          <button class="btn btn-secondary" onclick="closeModal('modal-confirm-delete-user')">Batal</button>
          <button class="btn btn-danger" id="btn-confirm-delete-yes">Ya, Hapus</button>
        </div>
      </div>
    </div>
  </div>

  <!-- ════════════════════════════════════════
       MODAL: ATUR NAMA & MEJA
  ════════════════════════════════════════ -->
  <div class="modal-overlay" id="modal-table-select">
    <div class="modal" style="max-width:420px">
      <div class="modal-header">
        <h3 style="font-size:18px;font-weight:700;color:var(--text-primary);display:flex;align-items:center;gap:8px">
          <span>📍</span> <span>Atur Identitas Meja</span>
        </h3>
        <button class="btn-close" onclick="closeModal('modal-table-select')">✕</button>
      </div>
      <div class="modal-body" style="padding:16px 24px">
        <div style="margin-bottom:14px">
          <label class="form-label" style="font-weight:600;margin-bottom:6px;display:block">Nama Lengkap Anda:</label>
          <input type="text" id="modal-inp-nama" class="form-control" placeholder="Contoh: Rina Wahyuni">
        </div>
        <div style="margin-bottom:16px">
          <label class="form-label" style="font-weight:600;margin-bottom:6px;display:block">Pilih Nomor Meja Duduk:</label>
          <select id="modal-inp-meja" class="form-control">
            <option value="01">Meja 01</option>
            <option value="02">Meja 02</option>
            <option value="03">Meja 03</option>
            <option value="04">Meja 04</option>
            <option value="05">Meja 05</option>
            <option value="06">Meja 06</option>
            <option value="07">Meja 07</option>
            <option value="08">Meja 08</option>
            <option value="09">Meja 09</option>
            <option value="10">Meja 10</option>
            <option value="VIP-1">Meja VIP 1</option>
            <option value="VIP-2">Meja VIP 2</option>
          </select>
        </div>
      </div>
      <div class="modal-footer" style="padding:14px 24px 24px;display:flex;justify-content:flex-end;gap:10px">
        <button class="btn btn-secondary" onclick="closeModal('modal-table-select')">Batal</button>
        <button class="btn btn-order" onclick="simpanTableModal()">Simpan Meja</button>
      </div>
    </div>
  </div>

  <!-- JavaScript -->
  <script src="assets/js/user.js?v=<?= time() ?>"></script>
</body>
</html>
