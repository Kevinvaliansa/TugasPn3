// ============================================================
// app.js - Main Application Logic
// Dapur Ina Aina Restaurant System
// ============================================================

// ── API Base ──
const API = {
  produk:    'api/produk_api.php',
  pesanan:   'api/pesanan_api.php',
  transaksi: 'api/transaksi_api.php',
  laporan:   'api/laporan_api.php',
};

// ── Page Routing ──
const pages = ['dashboard', 'antrian', 'pesanan', 'produk', 'laporan'];
let currentPage = 'dashboard';

function navigateTo(page) {
  if (!pages.includes(page)) return;
  currentPage = page;

  // Update nav active state
  document.querySelectorAll('.nav-item').forEach(el => {
    el.classList.toggle('active', el.dataset.page === page);
  });

  // Update topbar
  const titles = {
    dashboard: ['🏠 Dashboard',           'Selamat datang di Dapur Ina Aina - Ringkasan performa restoran'],
    antrian:   ['📋 Pesanan Masuk',        'Pantau antrian pesanan meja pelanggan secara real-time'],
    pesanan:   ['🛒 Kasir & Pemesanan',   'Buat pesanan langsung di kasir'],
    produk:    ['📦 Manajemen Produk',     'Kelola stok makanan & minuman'],
    laporan:   ['📊 Laporan Penjualan',    'Rekap transaksi berkala'],
  };
  document.getElementById('page-title').textContent   = titles[page][0];
  document.getElementById('page-subtitle').textContent = titles[page][1];

  // Show/hide sections
  document.querySelectorAll('.page-section').forEach(el => {
    el.style.display = el.id === `section-${page}` ? 'block' : 'none';
  });

  // Load page data
  switch (page) {
    case 'dashboard': loadDashboard(); break;
    case 'antrian':   loadAntrian();   break;
    case 'pesanan':   loadPesanan();   break;
    case 'produk':    loadProduk();    break;
    case 'laporan':   loadLaporan();   break;
  }
}

// ── Fetch Helper ──
async function apiFetch(url, options = {}) {
  const res = await fetch(url, {
    headers: { 'Content-Type': 'application/json' },
    ...options,
  });
  const data = await res.json();
  if (data.status === 'error') throw new Error(data.message);
  return data;
}

// ── Helper Ikon Kategori ──
function getKatIkon(nama, ikon) {
  if (nama) {
    const n = String(nama).toLowerCase();
    if (n.includes('makanan')) return '🍛';
    if (n.includes('appetizer')) return '🥗';
    if (n.includes('minuman')) return '🥤';
  }
  return (ikon && ikon.length <= 4 && !ikon.includes('ƒ') && !ikon.includes('ì') && !ikon.includes('Ñ')) ? ikon : '🍽️';
}

// ── Empty State Component Helper ──
function emptyStateHtml(icon, title, desc) {
  return `
    <div class="empty-state">
      <div class="empty-icon-wrap">${icon}</div>
      <div class="empty-title">${title}</div>
      <div class="empty-desc">${desc}</div>
    </div>
  `;
}

// ── Format Currency ──
function formatRp(n) {
  return 'Rp ' + Number(n).toLocaleString('id-ID');
}

// ── Format Date ──
function formatDate(d) {
  return new Date(d).toLocaleString('id-ID', {
    day: '2-digit', month: 'short', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  });
}

// ── Toast Notification ──
function toast(msg, type = 'info') {
  const icons = { success: '✅', error: '❌', info: 'ℹ️', warning: '⚠️' };
  const c = document.getElementById('toast-container');
  const t = document.createElement('div');
  t.className = `toast ${type}`;
  t.innerHTML = `<span>${icons[type]}</span><span>${msg}</span>`;
  c.appendChild(t);
  setTimeout(() => t.remove(), 3500);
}

// ── Update Clock ──
function updateClock() {
  const el = document.getElementById('clock');
  if (el) {
    el.textContent = new Date().toLocaleString('id-ID', {
      weekday: 'short', day: '2-digit', month: 'short',
      hour: '2-digit', minute: '2-digit', second: '2-digit',
    });
  }
}

// ── Modal Helper ──
function openModal(id)  { document.getElementById(id).classList.add('show'); }
function closeModal(id) { document.getElementById(id).classList.remove('show'); }

// Close modal on overlay click
document.addEventListener('click', e => {
  if (e.target.classList.contains('modal-overlay')) {
    e.target.classList.remove('show');
  }
});

// ── Antrian / Live Order Monitor (Admin) ──
let antrianActiveFilter = '';

async function loadAntrian() {
  const grid = document.getElementById('antrian-grid');
  if (!grid) return;

  try {
    const url = `${API.pesanan}?with_items=1${antrianActiveFilter ? '&status=' + antrianActiveFilter : ''}`;
    const res = await apiFetch(url);
    const orders = res.data;

    if (!orders || orders.length === 0) {
      grid.innerHTML = `
        <div style="grid-column:1/-1">
          ${emptyStateHtml('📋', 'Tidak Ada Antrian Pesanan', 'Saat ini tidak ada pesanan pelanggan dengan status yang dipilih.')}
        </div>
      `;
      return;
    }

    grid.innerHTML = orders.map(p => {
      const statusLabels = {
        pending:    '⏳ Menunggu Dapur',
        diproses:   '🍳 Sedang Dimasak',
        selesai:    '✅ Selesai / Siap',
        dibatalkan: '❌ Dibatalkan',
      };

      const statusBadges = {
        pending:    'badge-warning',
        diproses:   'badge-info',
        selesai:    'badge-success',
        dibatalkan: 'badge-danger',
      };

      const itemsHtml = (p.items || []).map(i => `
        <div class="order-item-row">
          <span><strong>${i.jumlah}x</strong> ${i.nama_produk}</span>
          <span style="font-weight:600">${formatRp(i.subtotal)}</span>
        </div>
      `).join('');

      const isPaid = p.transaksi && p.transaksi.status === 'berhasil';
      const trxMetode = isPaid 
        ? (p.transaksi.metode_bayar === 'tunai' ? 'Tunai' : (p.transaksi.nama_bank || 'E-Wallet'))
        : '';

      const payConfirmButton = isPaid 
        ? `
          <button class="btn btn-sm" style="background:#DCFCE7;color:#15803D;border:1px solid #86EFAC;font-weight:700" onclick="lihatStruk(${p.transaksi.id_transaksi})" title="Uang sudah masuk - Klik untuk lihat struk">
            ✅ Sudah Masuk (${trxMetode})
          </button>
        `
        : `
          <button class="btn btn-order btn-sm" onclick="adminBayarPesanan(${p.id_pesanan})" style="font-weight:700" title="Periksa dan konfirmasi apakah uang pembayaran sudah masuk atau belum">
            🔍 Konfirmasi Masuk
          </button>
        `;

      let actionButtons = '';
      if (p.status === 'pending') {
        actionButtons = `
          <button class="btn btn-sm" style="background:var(--info);color:#fff;font-weight:700" onclick="adminUbahStatus(${p.id_pesanan}, 'diproses')">
            🍳 Mulai Masak
          </button>
          ${payConfirmButton}
          <button class="btn btn-secondary btn-sm" onclick="adminBatalkanPesanan(${p.id_pesanan})">
            ✕ Batal
          </button>
        `;
      } else if (p.status === 'diproses') {
        actionButtons = `
          <button class="btn btn-sm btn-success" style="font-weight:700" onclick="adminUbahStatus(${p.id_pesanan}, 'selesai')">
            ✅ Selesai Masak
          </button>
          ${payConfirmButton}
        `;
      } else if (p.status === 'selesai') {
        actionButtons = `
          ${payConfirmButton}
        `;
      } else if (p.status === 'dibatalkan') {
        actionButtons = `
          <span class="badge badge-danger" style="font-size:11px">Dibatalkan</span>
        `;
      }

      return `
        <div class="order-card status-${p.status}">
          <div>
            <div class="order-card-header">
              <div>
                <div class="order-id-tag">#ORD-${p.id_pesanan}</div>
                <div class="order-time-tag">${formatDate(p.created_at)}</div>
              </div>
              <span class="badge ${statusBadges[p.status] || 'badge-secondary'}">
                ${statusLabels[p.status] || p.status}
              </span>
            </div>

            <div class="order-customer-info">
              <span class="order-table-badge">📍 Meja ${p.no_meja}</span>
              <span class="order-customer-name">${p.nama_pelanggan}</span>
            </div>

            ${p.catatan ? `
              <div class="order-catatan-box">
                <strong>📝 Catatan Dapur:</strong> ${p.catatan}
              </div>
            ` : ''}

            <div class="order-items-list">
              ${itemsHtml || '<div style="font-size:12px;color:var(--text-muted)">Item pesanan tidak tersedia</div>'}
            </div>
          </div>

          <div class="order-card-footer">
            <div>
              <div style="font-size:11px;color:var(--text-muted);font-weight:600">Total Tagihan</div>
              <div class="price" style="font-size:16px">${formatRp(p.total_harga)}</div>
              <div style="margin-top:3px">
                ${isPaid 
                  ? `<span class="badge badge-success" style="font-size:10px;padding:2px 6px">✅ Uang Masuk (${trxMetode})</span>` 
                  : `<span class="badge badge-warning" style="font-size:10px;padding:2px 6px">⚠️ Belum Masuk</span>`
                }
              </div>
            </div>

            <div class="order-actions">
              ${actionButtons}
              <button class="btn btn-secondary btn-sm" onclick="lihatDetailPesanan(${p.id_pesanan})" title="Lihat Rincian">
                👁
              </button>
            </div>
          </div>
        </div>
      `;
    }).join('');

  } catch (err) {
    grid.innerHTML = `<div class="alert alert-danger" style="grid-column:1/-1">Gagal memuat antrian: ${err.message}</div>`;
  }
}

function filterAntrian(status) {
  antrianActiveFilter = status;
  document.querySelectorAll('#antrian-filter-tabs .menu-tab').forEach(btn => {
    btn.classList.toggle('active', btn.getAttribute('onclick')?.includes(`'${status}'`));
  });
  loadAntrian();
}

async function adminUbahStatus(id_pesanan, statusBaru) {
  try {
    await apiFetch(`${API.pesanan}?id=${id_pesanan}`, {
      method: 'PUT',
      body: JSON.stringify({ status: statusBaru }),
    });
    toast(`Pesanan #${id_pesanan} diperbarui ke status "${statusBaru}"`, 'success');
    loadAntrian();
    pollPendingOrders();
  } catch (e) {
    toast(e.message, 'error');
  }
}

function adminBayarPesanan(id_pesanan) {
  openBillingModal(id_pesanan);
}

function adminBatalkanPesanan(id_pesanan) {
  if (!confirm(`Batalkan pesanan #${id_pesanan}? Tindakan ini tidak dapat diurungkan.`)) return;
  adminUbahStatus(id_pesanan, 'dibatalkan');
}

// ── Background Polling for Incoming Orders ──
async function pollPendingOrders() {
  try {
    const res = await apiFetch(`${API.pesanan}?status=pending`);
    const pendingCount = res.data ? res.data.length : 0;
    const badge = document.getElementById('badge-pending-orders');

    if (badge) {
      if (pendingCount > 0) {
        badge.textContent = pendingCount;
        badge.style.display = 'inline-block';
      } else {
        badge.style.display = 'none';
      }
    }

    // Update stat card on dashboard if present
    const statP = document.getElementById('stat-pesanan');
    if (statP && currentPage === 'dashboard') {
      // Keep dashboard stat updated
    }

    // Auto-refresh antrian grid if admin is currently viewing it
    if (currentPage === 'antrian') {
      loadAntrian();
    }
  } catch (e) {
    // Silent fail in background polling
  }
}

// ── Init ──
document.addEventListener('DOMContentLoaded', () => {
  updateClock();
  setInterval(updateClock, 1000);
  navigateTo('dashboard');

  document.querySelectorAll('.nav-item').forEach(el => {
    el.addEventListener('click', () => navigateTo(el.dataset.page));
  });

  // Start order polling every 8 seconds
  pollPendingOrders();
  setInterval(pollPendingOrders, 8000);
});
