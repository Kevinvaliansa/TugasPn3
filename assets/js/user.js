// ============================================================
// user.js - Customer / E-Menu Frontend Logic
// Dapur Ina Aina Restaurant System
// ============================================================

const API_PRODUK  = 'api/produk_api.php';
const API_PESANAN = 'api/pesanan_api.php';
const API_TRANSAKSI = 'api/transaksi_api.php';

const EWALLET_LIST = [
  { id: 'GoPay', name: 'GoPay', color: '#00AA13', bg: '#E8F7ED', icon: '🟢', desc: 'Gojek / GoPay' },
  { id: 'OVO', name: 'OVO', color: '#4C3494', bg: '#F2EDFA', icon: '🟣', desc: 'OVO Cash' },
  { id: 'DANA', name: 'DANA', color: '#118EEA', bg: '#E8F4FC', icon: '🔵', desc: 'DANA Dompet Digital' },
  { id: 'ShopeePay', name: 'ShopeePay', color: '#EE4D2D', bg: '#FDF0ED', icon: '🟠', desc: 'ShopeePay' }
];

let userMenu = [];
let userKategori = [];
let activeUserKat = 0;
let userCart = [];
let pendingDeleteId = null;
let activeOrderId = null;
let trackerInterval = null;
let userPayMethod = localStorage.getItem('dapur_ina_last_pay_method') || 'tunai';
let selectedEWallet = localStorage.getItem('dapur_ina_last_ewallet') || 'GoPay';

// ── Format Rupiah Helper ──
function formatRp(n) {
  return 'Rp ' + Number(n).toLocaleString('id-ID');
}

// ── Toast Helper ──
function toast(msg, type = 'info') {
  const icons = { success: '✅', error: '❌', info: 'ℹ️', warning: '⚠️' };
  const c = document.getElementById('toast-container');
  if (!c) return;
  const t = document.createElement('div');
  t.className = `toast ${type}`;
  t.innerHTML = `<span>${icons[type]}</span><span>${msg}</span>`;
  c.appendChild(t);
  setTimeout(() => t.remove(), 3500);
}

// ── Modal Helpers ──
function openModal(id) {
  const el = document.getElementById(id);
  if (el) el.classList.add('show');
}

function closeModal(id) {
  const el = document.getElementById(id);
  if (el) el.classList.remove('show');
  if (id === 'modal-tracking' && trackerInterval) {
    clearInterval(trackerInterval);
    trackerInterval = null;
  }
}

// Dismiss modals on backdrop click or ESC key
document.addEventListener('click', (e) => {
  if (e.target.classList.contains('modal-overlay')) {
    e.target.classList.remove('show');
    if (e.target.id === 'modal-tracking' && trackerInterval) {
      clearInterval(trackerInterval);
      trackerInterval = null;
    }
  }
});

document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') {
    document.querySelectorAll('.modal-overlay.show').forEach(el => {
      el.classList.remove('show');
      if (el.id === 'modal-tracking' && trackerInterval) {
        clearInterval(trackerInterval);
        trackerInterval = null;
      }
    });
  }
});

// ── Init on Page Load ──
document.addEventListener('DOMContentLoaded', () => {
  loadSavedIdentity();
  loadSavedCart();
  loadUserMenu();

  // Check if there is an ongoing order
  const savedOrderId = localStorage.getItem('dapur_ina_active_order');
  if (savedOrderId) {
    activeOrderId = parseInt(savedOrderId);
  }
});

// ── Identity (Nama & Meja) ──
function loadSavedIdentity() {
  const inpNama = document.getElementById('cust-nama');
  const currentVal = inpNama ? inpNama.value.trim() : '';
  const savedNama = localStorage.getItem('dapur_ina_cust_nama') || '';
  const savedMeja = localStorage.getItem('dapur_ina_cust_meja') || '';

  if (currentVal) {
    localStorage.setItem('dapur_ina_cust_nama', currentVal);
  } else if (savedNama && inpNama) {
    inpNama.value = savedNama;
  }

  if (savedMeja && document.getElementById('cust-meja')) {
    document.getElementById('cust-meja').value = savedMeja;
    const lbl = document.getElementById('lbl-active-meja');
    if (lbl) lbl.textContent = 'Meja ' + savedMeja;
  }
}

function updateTableSelection() {
  const inpNama = document.getElementById('cust-nama');
  const nama = inpNama ? inpNama.value.trim() : '';
  const meja = document.getElementById('cust-meja')?.value || '';
  if (nama) localStorage.setItem('dapur_ina_cust_nama', nama);
  if (meja) {
    localStorage.setItem('dapur_ina_cust_meja', meja);
    const lbl = document.getElementById('lbl-active-meja');
    if (lbl) lbl.textContent = 'Meja ' + meja;
  } else {
    const lbl = document.getElementById('lbl-active-meja');
    if (lbl) lbl.textContent = 'Pilih Meja';
  }
}

function openTableModal() {
  const inpNama = document.getElementById('cust-nama');
  const inpMeja = document.getElementById('cust-meja');
  document.getElementById('modal-inp-nama').value = inpNama ? inpNama.value.trim() : '';
  document.getElementById('modal-inp-meja').value = inpMeja ? inpMeja.value : '01';
  openModal('modal-table-select');
}

function simpanTableModal() {
  const nama = document.getElementById('modal-inp-nama').value.trim();
  const meja = document.getElementById('modal-inp-meja').value;

  if (!nama) { toast('Silakan isi nama Anda', 'warning'); return; }
  if (!meja) { toast('Silakan pilih nomor meja', 'warning'); return; }

  const inpNama = document.getElementById('cust-nama');
  const inpMeja = document.getElementById('cust-meja');
  if (inpNama) inpNama.value = nama;
  if (inpMeja) inpMeja.value = meja;

  localStorage.setItem('dapur_ina_cust_nama', nama);
  localStorage.setItem('dapur_ina_cust_meja', meja);

  const lbl = document.getElementById('lbl-active-meja');
  if (lbl) lbl.textContent = 'Meja ' + meja;

  closeModal('modal-table-select');
  toast(`Identitas tersimpan untuk Meja ${meja}`, 'success');
}

// ── Load Menu Data ──
async function loadUserMenu() {
  try {
    const [menuRes, katRes] = await Promise.all([
      fetch(`${API_PRODUK}?status=tersedia`).then(r => r.json()),
      fetch(`${API_PRODUK}?aksi=kategori`).then(r => r.json()),
    ]);

    if (menuRes.status === 'success') userMenu = menuRes.data;
    if (katRes.status === 'success')  userKategori = katRes.data;

    renderKategoriPills();
    renderUserMenuGrid(userMenu);
  } catch (err) {
    console.error(err);
    document.getElementById('user-menu-grid').innerHTML = `
      <div style="grid-column:1/-1;text-align:center;padding:40px;color:var(--danger)">
        Gagal memuat katalog menu. Pastikan server lokal aktif.
      </div>
    `;
  }
}

function renderKategoriPills() {
  const tabsWrap = document.getElementById('user-kategori-tabs');
  let html = `<button class="menu-tab active" onclick="filterUserCategory(0)">🍽️ Semua Menu</button>`;

  userKategori.forEach(k => {
    let icon = k.ikon || '🍽️';
    const n = String(k.nama_kategori).toLowerCase();
    if (n.includes('makanan')) icon = '🍛';
    else if (n.includes('appetizer')) icon = '🥗';
    else if (n.includes('minuman')) icon = '🥤';

    html += `<button class="menu-tab" onclick="filterUserCategory(${k.id_kategori})">${icon} ${k.nama_kategori}</button>`;
  });

  tabsWrap.innerHTML = html;
}

function filterUserCategory(idKat) {
  activeUserKat = idKat;
  document.querySelectorAll('#user-kategori-tabs .menu-tab').forEach((btn, idx) => {
    btn.classList.toggle('active', idx === 0 ? idKat === 0 : btn.getAttribute('onclick')?.includes(`(${idKat})`));
  });

  applyFilters();
}

function handleSearchMenu(keyword) {
  applyFilters(keyword);
}

function applyFilters(keyword) {
  const query = (keyword !== undefined ? keyword : document.getElementById('user-search-input').value).toLowerCase().trim();
  let list = activeUserKat === 0 ? userMenu : userMenu.filter(m => m.id_kategori == activeUserKat);

  if (query) {
    list = list.filter(m => 
      m.nama_produk.toLowerCase().includes(query) || 
      (m.deskripsi && m.deskripsi.toLowerCase().includes(query)) ||
      (m.nama_kategori && m.nama_kategori.toLowerCase().includes(query))
    );
  }

  renderUserMenuGrid(list);
}

function renderUserMenuGrid(items) {
  const grid = document.getElementById('user-menu-grid');
  if (!items || items.length === 0) {
    grid.innerHTML = `
      <div style="grid-column:1/-1;text-align:center;padding:60px 20px;background:#fff;border-radius:16px;border:1px solid var(--border)">
        <div style="font-size:44px;margin-bottom:12px">🍽️</div>
        <div style="font-size:16px;font-weight:700;color:var(--text-primary);margin-bottom:6px">Menu Tidak Ditemukan</div>
        <div style="font-size:13px;color:var(--text-muted)">Coba cari dengan kata kunci lain atau pilih kategori berbeda.</div>
      </div>
    `;
    return;
  }

  grid.innerHTML = items.map(m => {
    let icon = '🍽️';
    const n = String(m.nama_kategori || '').toLowerCase();
    if (n.includes('makanan')) icon = '🍛';
    else if (n.includes('appetizer')) icon = '🥗';
    else if (n.includes('minuman')) icon = '🥤';

    const imgTag = m.foto
      ? `<img src="uploads/produk/${m.foto}" alt="${m.nama_produk}" onerror="this.onerror=null;this.src='assets/images/menu/${m.foto}';">`
      : `<div class="user-menu-placeholder">${icon}</div>`;

    const isHabis = m.status === 'habis' || parseInt(m.stok) <= 0;

    return `
      <div class="user-menu-card ${isHabis ? 'habis' : ''}">
        <div class="user-menu-img-wrap">
          ${imgTag}
          <div class="user-menu-badge-cat">
            <span>${icon}</span>
            <span>${m.nama_kategori || 'Menu'}</span>
          </div>
          <div class="user-menu-badge-stok">
            ${isHabis ? 'Habis' : `Stok: ${m.stok}`}
          </div>
        </div>

        <div class="user-menu-body">
          <div class="user-menu-title" title="${m.nama_produk}">${m.nama_produk}</div>
          <div class="user-menu-desc">
            ${m.deskripsi || 'Sajian lezat dengan racikan bumbu khas Dapur Ina Aina.'}
          </div>

          <div class="user-menu-footer">
            <div>
              <div class="user-menu-price-label">Harga</div>
              <div class="user-menu-price">${formatRp(m.harga)}</div>
            </div>
            ${isHabis ? `
              <span class="badge badge-habis" style="padding:7px 16px;border-radius:20px;font-size:12px;font-weight:700">Habis</span>
            ` : `
              <button class="btn-pesan-user" onclick="addUserCart(${m.id_produk})" title="Tambah ${m.nama_produk} ke Keranjang">
                <span>+</span> <span>Pesan</span>
              </button>
            `}
          </div>
        </div>
      </div>
    `;
  }).join('');
}

// ── Cart State & Logic ──
function loadSavedCart() {
  const saved = localStorage.getItem('dapur_ina_cart');
  if (saved) {
    try { userCart = JSON.parse(saved); } catch (e) { userCart = []; }
  }
  updateCartCounters();
}

function saveCart() {
  localStorage.setItem('dapur_ina_cart', JSON.stringify(userCart));
  updateCartCounters();
}

function updateCartCounters() {
  const totalQty = userCart.reduce((sum, item) => sum + item.jumlah, 0);
  document.getElementById('header-cart-count').textContent   = totalQty;
  document.getElementById('floating-cart-count').textContent = totalQty;
}

function addUserCart(idProduk) {
  const prod = userMenu.find(p => p.id_produk == idProduk);
  if (!prod) return;

  const stok = parseInt(prod.stok) || 0;
  if (stok <= 0 || prod.status === 'habis') {
    toast(`Maaf, stok untuk "${prod.nama_produk}" sedang habis!`, 'warning');
    return;
  }

  const existing = userCart.find(c => c.id_produk == idProduk);
  if (existing) {
    if (existing.jumlah >= stok) {
      toast(`Pesanan melebihi stok! Stok "${prod.nama_produk}" hanya tersisa ${stok}.`, 'warning');
      return;
    }
    existing.jumlah++;
  } else {
    userCart.push({
      id_produk: prod.id_produk,
      nama: prod.nama_produk,
      harga_satuan: parseFloat(prod.harga),
      jumlah: 1,
      stok: stok,
      foto: prod.foto,
      nama_kategori: prod.nama_kategori
    });
  }

  saveCart();
  toast(`${prod.nama_produk} dimasukkan ke keranjang`, 'success');
}

function selectCartPaymentMethod(metode) {
  userPayMethod = (metode === 'non tunai') ? 'non tunai' : 'tunai';
  localStorage.setItem('dapur_ina_last_pay_method', userPayMethod);
  const inputEl = document.getElementById('user-selected-metode');
  if (inputEl) inputEl.value = userPayMethod;

  const cardTunai = document.getElementById('cart-pay-tunai');
  const cardNonTunai = document.getElementById('cart-pay-nontunai');
  if (cardTunai) cardTunai.classList.toggle('active', userPayMethod === 'tunai');
  if (cardNonTunai) cardNonTunai.classList.toggle('active', userPayMethod === 'non tunai');

  const ewalletBox = document.getElementById('cart-ewallet-selector');
  if (ewalletBox) {
    ewalletBox.style.display = userPayMethod === 'non tunai' ? 'block' : 'none';
  }
}

function selectCartEWallet(ewallet) {
  selectedEWallet = ewallet;
  localStorage.setItem('dapur_ina_last_ewallet', ewallet);
  const inputEl = document.getElementById('user-selected-ewallet');
  if (inputEl) inputEl.value = ewallet;

  EWALLET_LIST.forEach(ew => {
    const el = document.getElementById(`cart-ew-${ew.id}`);
    if (el) {
      if (ew.id === ewallet) {
        el.style.border = `2px solid ${ew.color}`;
        el.style.background = ew.bg;
      } else {
        el.style.border = '2px solid var(--border)';
        el.style.background = '#fff';
      }
    }
  });
}

function openCartDrawer() {
  const meja = document.getElementById('cust-meja').value;
  const nama = document.getElementById('cust-nama').value.trim();
  document.getElementById('cart-meja-sub').textContent = meja 
    ? `Pemesan: ${nama || 'Tamu'} (Meja ${meja})`
    : 'Perhatian: Mohon tentukan nomor meja Anda sebelum memesan';

  renderCartItemsModal();
  selectCartPaymentMethod(userPayMethod);
  selectCartEWallet(selectedEWallet);
  openModal('modal-cart-user');
}

function selectCartPaymentMethod(metode) {
  userPayMethod = metode;
  localStorage.setItem('dapur_ina_last_pay_method', metode);
  const inpMetode = document.getElementById('user-selected-metode');
  if (inpMetode) inpMetode.value = metode;

  const tunaiCard = document.getElementById('cart-pay-tunai');
  const nonTunaiCard = document.getElementById('cart-pay-nontunai');
  if (tunaiCard) tunaiCard.classList.toggle('active', metode === 'tunai');
  if (nonTunaiCard) nonTunaiCard.classList.toggle('active', metode === 'non tunai');

  const ewSelector = document.getElementById('cart-ewallet-selector');
  if (ewSelector) {
    ewSelector.style.display = (metode === 'non tunai') ? 'block' : 'none';
  }
}

function selectCartEWallet(ewalletId) {
  selectedEWallet = ewalletId;
  localStorage.setItem('dapur_ina_last_ewallet', ewalletId);
  const inpEw = document.getElementById('user-selected-ewallet');
  if (inpEw) inpEw.value = ewalletId;

  EWALLET_LIST.forEach(ew => {
    const el = document.getElementById(`cart-ew-${ew.id}`);
    if (el) {
      const isSel = (ew.id === ewalletId);
      el.classList.toggle('active', isSel);
      el.style.border = isSel ? `2px solid ${ew.color}` : '2px solid var(--border)';
      el.style.background = isSel ? ew.bg : '#fff';
      el.style.fontWeight = isSel ? '800' : '600';
    }
  });
}

function renderCartItemsModal() {
  const wrap = document.getElementById('user-cart-items-wrap');
  if (userCart.length === 0) {
    wrap.innerHTML = `
      <div style="text-align:center;padding:36px 12px;color:var(--text-muted)">
        <div style="font-size:36px;margin-bottom:8px">🛒</div>
        <div style="font-size:15px;font-weight:700;color:var(--text-primary);margin-bottom:4px">Keranjang Masih Kosong</div>
        <div style="font-size:12px">Pilih menu lezat kami untuk mulai memesan.</div>
      </div>
    `;
    document.getElementById('user-cart-subtotal').textContent = formatRp(0);
    document.getElementById('user-cart-total').textContent    = formatRp(0);
    document.getElementById('btn-kirim-pesanan').disabled     = true;
    return;
  }

  document.getElementById('btn-kirim-pesanan').disabled = false;

  let subtotal = 0;
  wrap.innerHTML = userCart.map(item => {
    const itemTotal = item.harga_satuan * item.jumlah;
    subtotal += itemTotal;

    return `
      <div style="display:flex;align-items:center;justify-content:space-between;padding:12px;border-bottom:1px solid var(--border);gap:12px">
        <div style="flex:1">
          <div style="font-weight:700;font-size:14px;color:var(--text-primary)">${item.nama}</div>
          <div style="font-size:12px;color:var(--price-color);font-weight:700">${formatRp(item.harga_satuan)}</div>
        </div>

        <div class="qty-control" style="margin:0">
          <button class="qty-btn" type="button" onclick="userChangeQty(${item.id_produk}, -1)">−</button>
          <input type="number" class="qty-input" value="${item.jumlah}" min="1" max="${item.stok}"
                 oninput="userOnInputQty(${item.id_produk}, this)"
                 onchange="userSetQtyDirect(${item.id_produk}, this.value)"
                 onkeydown="if(event.key==='Enter') this.blur()"
                 onclick="this.select()"
                 title="Ketik jumlah pesanan (Maks. ${item.stok})">
          <button class="qty-btn" type="button" onclick="userChangeQty(${item.id_produk}, 1)">+</button>
        </div>

        <div style="text-align:right;min-width:80px">
          <div id="cart-item-total-${item.id_produk}" style="font-size:13px;font-weight:800;color:var(--text-primary)">${formatRp(itemTotal)}</div>
          <button style="border:none;background:none;color:var(--danger);font-size:11px;cursor:pointer;margin-top:2px" onclick="confirmDeleteFromCart(${item.id_produk})">
            Hapus
          </button>
        </div>
      </div>
    `;
  }).join('');

  document.getElementById('user-cart-subtotal').textContent = formatRp(subtotal);
  document.getElementById('user-cart-total').textContent    = formatRp(subtotal);
}

function userOnInputQty(idProduk, inputEl) {
  const item = userCart.find(c => c.id_produk == idProduk);
  if (!item) return;

  const rawVal = inputEl.value;
  if (!rawVal) return;

  let qty = parseInt(rawVal);
  if (isNaN(qty) || qty < 1) return;

  const prod = userMenu.find(p => p.id_produk == idProduk);
  const stok = prod ? parseInt(prod.stok) : item.stok;

  if (qty > stok) {
    toast(`Pesanan melebihi stok! Stok "${item.nama}" hanya tersedia ${stok}.`, 'warning');
    qty = stok;
    inputEl.value = stok;
  }

  item.jumlah = qty;
  saveCart();

  const lineTotalEl = document.getElementById(`cart-item-total-${idProduk}`);
  if (lineTotalEl) {
    lineTotalEl.textContent = formatRp(item.harga_satuan * qty);
  }

  let grandTotal = 0;
  userCart.forEach(c => {
    grandTotal += c.harga_satuan * c.jumlah;
  });
  const subtotalEl = document.getElementById('user-cart-subtotal');
  const totalEl = document.getElementById('user-cart-total');
  if (subtotalEl) subtotalEl.textContent = formatRp(grandTotal);
  if (totalEl) totalEl.textContent = formatRp(grandTotal);
}

function userSetQtyDirect(idProduk, val) {
  const item = userCart.find(c => c.id_produk == idProduk);
  if (!item) return;

  let qty = parseInt(val);
  const prod = userMenu.find(p => p.id_produk == idProduk);
  const stok = prod ? parseInt(prod.stok) : item.stok;

  if (isNaN(qty) || qty <= 0) {
    confirmDeleteFromCart(idProduk);
    return;
  }

  if (qty > stok) {
    toast(`Pesanan melebihi stok! Stok "${item.nama}" hanya tersedia ${stok}.`, 'warning');
    qty = stok;
  }

  item.jumlah = qty;
  saveCart();
  renderCartItemsModal();
}

function userChangeQty(idProduk, delta) {
  const item = userCart.find(c => c.id_produk == idProduk);
  if (!item) return;

  if (delta === -1 && item.jumlah <= 1) {
    confirmDeleteFromCart(idProduk);
    return;
  }

  const prod = userMenu.find(p => p.id_produk == idProduk);
  const stok = prod ? parseInt(prod.stok) : item.stok;

  if (delta === 1 && item.jumlah >= stok) {
    toast(`Pesanan melebihi stok! Stok "${item.nama}" hanya tersedia ${stok}.`, 'warning');
    return;
  }

  item.jumlah += delta;
  saveCart();
  renderCartItemsModal();
}

function confirmDeleteFromCart(idProduk) {
  const item = userCart.find(c => c.id_produk == idProduk);
  if (!item) return;

  pendingDeleteId = idProduk;
  document.getElementById('lbl-confirm-delete-text').textContent = 
    `Apakah Anda yakin ingin menghapus "${item.nama}" dari daftar pesanan?`;

  document.getElementById('btn-confirm-delete-yes').onclick = () => {
    userCart = userCart.filter(c => c.id_produk != pendingDeleteId);
    pendingDeleteId = null;
    saveCart();
    closeModal('modal-confirm-delete-user');
    renderCartItemsModal();
    toast('Menu dihapus dari keranjang', 'info');
  };

  openModal('modal-confirm-delete-user');
}

// ── Submit Order to Kitchen/Cashier ──
async function kirimPesananUser() {
  const nama  = document.getElementById('cust-nama').value.trim();
  const meja  = document.getElementById('cust-meja').value;
  const catatan = document.getElementById('user-cart-catatan').value.trim();

  if (!nama) {
    toast('Mohon masukkan nama pemesan terlebih dahulu', 'warning');
    openTableModal();
    return;
  }
  if (!meja) {
    toast('Mohon pilih nomor meja tempat Anda duduk', 'warning');
    openTableModal();
    return;
  }
  if (userCart.length === 0) {
    toast('Keranjang pesanan masih kosong', 'warning');
    return;
  }

  // Validasi stok sebelum kirim
  for (const item of userCart) {
    const prod = userMenu.find(p => p.id_produk == item.id_produk);
    const stok = prod ? parseInt(prod.stok) : item.stok;
    if (stok <= 0) {
      toast(`Pesanan gagal! Menu "${item.nama}" sudah habis.`, 'error');
      return;
    }
    if (item.jumlah > stok) {
      toast(`Pesanan melebihi stok! Menu "${item.nama}" hanya tersedia ${stok} porsi. Mohon kurangi jumlah pesanan.`, 'error');
      return;
    }
  }

  const btnKirim = document.getElementById('btn-kirim-pesanan');
  btnKirim.disabled = true;
  btnKirim.textContent = 'Mengirim Pesanan...';

  try {
    const res = await fetch(API_PESANAN, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        pelanggan: { nama, no_meja: meja },
        items: userCart,
        catatan: catatan || null
      })
    }).then(r => r.json());

    if (res.status !== 'success') {
      throw new Error(res.message || 'Gagal mengirim pesanan');
    }

    // Success
    activeOrderId = res.id_pesanan;
    localStorage.setItem('dapur_ina_active_order', activeOrderId);

    // Clear cart
    userCart = [];
    saveCart();
    document.getElementById('user-cart-catatan').value = '';
    closeModal('modal-cart-user');

    toast(`Pesanan #${activeOrderId} berhasil dikirim ke dapur!`, 'success');

    // Open Live Order Tracking
    setTimeout(() => {
      openTrackingModal();
    }, 400);

  } catch (err) {
    toast(err.message, 'error');
  } finally {
    btnKirim.disabled = false;
    btnKirim.textContent = '🚀 Kirim Pesanan ke Dapur';
  }
}

// ── Live Order Tracker ──
function openTrackingModal() {
  if (!activeOrderId) {
    const saved = localStorage.getItem('dapur_ina_active_order');
    if (saved) activeOrderId = parseInt(saved);
  }

  if (!activeOrderId) {
    document.getElementById('tracking-body').innerHTML = `
      <div style="text-align:center;padding:40px 10px;color:var(--text-muted)">
        <div style="font-size:44px;margin-bottom:10px">📋</div>
        <div style="font-size:16px;font-weight:700;color:var(--text-primary);margin-bottom:6px">Belum Ada Pesanan Aktif</div>
        <p style="font-size:13px;max-width:320px;margin:0 auto 16px auto">
          Anda belum membuat pesanan. Pilih menu lezat kami dan kirim pesanan untuk melacak statusnya di sini.
        </p>
        <button class="btn btn-order btn-sm" onclick="closeModal('modal-tracking')">Pilih Menu Sekarang</button>
      </div>
    `;
    openModal('modal-tracking');
    return;
  }

  cekStatusPesananAktif();
  openModal('modal-tracking');

  // Start polling every 6 seconds if not already running
  if (!trackerInterval) {
    trackerInterval = setInterval(cekStatusPesananAktif, 6000);
  }
}

async function cekStatusPesananAktif() {
  if (!activeOrderId) return;

  try {
    const res = await fetch(`${API_PESANAN}?id=${activeOrderId}`).then(r => r.json());
    if (res.status !== 'success' || !res.data) {
      document.getElementById('tracking-body').innerHTML = `
        <div class="alert alert-warning">Pesanan #${activeOrderId} tidak ditemukan atau telah diarsipkan.</div>
      `;
      return;
    }

    renderTrackerContent(res.data);
  } catch (e) {
    console.error(e);
  }
}

function renderTrackerContent(p) {
  const status = p.status; // pending, diproses, selesai, dibatalkan

  let step1Class = 'stepper-step';
  let step2Class = 'stepper-step';
  let step3Class = 'stepper-step';
  let statusBanner = '';

  if (status === 'pending') {
    step1Class += ' active';
    statusBanner = `
      <div class="alert alert-warning" style="margin-bottom:20px;display:flex;align-items:center;gap:10px">
        <span style="font-size:22px">⏳</span>
        <div>
          <strong>Pesanan Diterima di Sistem</strong><br>
          <small>Menunggu konfirmasi dapur untuk mulai dimasak.</small>
        </div>
      </div>
    `;
  } else if (status === 'diproses') {
    step1Class += ' completed';
    step2Class += ' active';
    statusBanner = `
      <div class="alert alert-info" style="margin-bottom:20px;display:flex;align-items:center;gap:10px">
        <span style="font-size:22px">🍳</span>
        <div>
          <strong>Sedang Dimasak oleh Koki Dapur</strong><br>
          <small>Hidangan Anda sedang disiapkan dengan higienis dan hangat.</small>
        </div>
      </div>
    `;
  } else if (status === 'selesai') {
    step1Class += ' completed';
    step2Class += ' completed';
    step3Class += ' completed';
    statusBanner = `
      <div class="alert alert-success" style="margin-bottom:20px;display:flex;align-items:center;gap:10px">
        <span style="font-size:22px">🍽️</span>
        <div>
          <strong>Pesanan Selesai & Siap Disajikan!</strong><br>
          <small>Selamat menikmati hidangan lezat Dapur Ina Aina. Silakan ke kasir jika ingin melakukan pembayaran.</small>
        </div>
      </div>
    `;
  } else if (status === 'dibatalkan') {
    statusBanner = `
      <div class="alert alert-danger" style="margin-bottom:20px">
        <strong>❌ Pesanan Dibatalkan</strong><br>
        <small>Pesanan ini telah dibatalkan oleh pihak kasir/restoran.</small>
      </div>
    `;
  }

  const itemsHtml = (p.items || []).map(i => `
    <div class="struk-row" style="font-size:13px;padding:6px 0;border-bottom:1px dashed var(--border)">
      <span>${i.nama_produk} <strong>x${i.jumlah}</strong></span>
      <span class="price">${formatRp(i.subtotal)}</span>
    </div>
  `).join('');

  document.getElementById('tracking-body').innerHTML = `
    <!-- Status Banner -->
    ${statusBanner}

    <!-- Stepper Timeline -->
    <div class="order-stepper">
      <div class="${step1Class}">
        <div class="step-circle">⏳</div>
        <div class="step-label">Diterima</div>
      </div>
      <div class="${step2Class}">
        <div class="step-circle">🍳</div>
        <div class="step-label">Dimasak</div>
      </div>
      <div class="${step3Class}">
        <div class="step-circle">🍽️</div>
        <div class="step-label">Selesai</div>
      </div>
    </div>

    <!-- Info Box -->
    <div style="background:var(--bg-card2);padding:14px;border-radius:var(--radius-md);border:1px solid var(--border);margin-bottom:16px">
      <div class="flex justify-between" style="font-size:13px;margin-bottom:6px">
        <span style="color:var(--text-muted)">Nomor Pesanan:</span>
        <strong style="color:var(--text-primary)">#ORD-${p.id_pesanan}</strong>
      </div>
      <div class="flex justify-between" style="font-size:13px;margin-bottom:6px">
        <span style="color:var(--text-muted)">Pemesan / Meja:</span>
        <strong>${p.nama_pelanggan} (Meja ${p.no_meja})</strong>
      </div>
      <div class="flex justify-between" style="font-size:13px">
        <span style="color:var(--text-muted)">Waktu Pemesanan:</span>
        <span>${new Date(p.created_at).toLocaleTimeString('id-ID', {hour:'2-digit', minute:'2-digit'})} WIB</span>
      </div>
      ${p.catatan ? `<div style="margin-top:8px;font-size:12px;color:#92400E;background:#FFFBEB;padding:6px 10px;border-radius:6px;border:1px solid #FDE68A">📝 Catatan: ${p.catatan}</div>` : ''}
    </div>

    <!-- Items List -->
    <div style="margin-bottom:14px">
      <div style="font-size:12px;font-weight:700;color:var(--text-muted);text-transform:uppercase;margin-bottom:6px">Rincian Menu yang Dipesan</div>
      ${itemsHtml}
    </div>

    <!-- Total -->
    <div class="flex justify-between" style="font-size:16px;font-weight:800;padding-top:10px;border-top:2px solid var(--border)">
      <span>Total Pembayaran:</span>
      <span class="price" style="font-size:18px">${formatRp(p.total_harga)}</span>
    </div>

    <!-- Area Pembayaran Pelanggan (Tunai & Non Tunai) -->
    ${renderPaymentSection(p)}
  `;
}

function renderPaymentSection(p) {
  if (p.transaksi && p.transaksi.status === 'berhasil') {
    const isTunai = p.transaksi.metode_bayar === 'tunai';
    return `
      <div style="margin-top:16px;padding:16px;background:#F0FDF4;border:1px solid #BBF7D0;border-radius:12px">
        <div class="flex justify-between" style="align-items:center;flex-wrap:wrap;gap:10px">
          <div>
            <div style="font-weight:700;color:#166534;font-size:15px;display:flex;align-items:center;gap:6px">
              <span>✅</span> <span>Pembayaran Lunas</span>
              <span class="badge ${isTunai ? 'badge-tunai' : 'badge-non-tunai'}" style="font-size:11px">
                ${isTunai ? '💵 Tunai' : '💳 Non Tunai'}
              </span>
            </div>
            <div style="font-size:12px;color:#15803D;margin-top:4px">
              Kode Transaksi: <strong>${p.transaksi.kode_transaksi}</strong>
            </div>
            ${isTunai && parseFloat(p.transaksi.kembalian) > 0 ? `<div style="font-size:12px;color:#15803D">Kembalian: <strong>${formatRp(p.transaksi.kembalian)}</strong></div>` : ''}
          </div>
          <button class="btn btn-outline btn-sm" onclick="lihatStrukUser(${p.transaksi.id_transaksi})" style="background:#fff;white-space:nowrap;font-size:12px">
            🧾 Lihat Struk
          </button>
        </div>
      </div>
    `;
  }

  return `
    <div style="margin-top:16px;padding:16px;background:var(--bg-card2);border:1px solid var(--border);border-radius:12px" id="user-payment-card-box">
      <div class="flex justify-between" style="align-items:center;margin-bottom:12px">
        <div style="font-weight:700;font-size:14px;color:var(--text-primary)">💳 Pembayaran Pesanan</div>
        <span class="badge badge-pending">Belum Dibayar</span>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:14px">
        <div class="user-pay-card ${userPayMethod === 'tunai' ? 'active' : ''}" onclick="switchUserPayMethod('tunai', ${p.id_pesanan}, ${p.total_harga})">
          <div style="font-size:22px;margin-bottom:2px">💵</div>
          <div style="font-weight:700;font-size:13px;color:var(--text-primary)">Tunai</div>
          <div style="font-size:11px;color:var(--text-muted)">Uang tunai ke kasir</div>
        </div>
        <div class="user-pay-card ${userPayMethod === 'non tunai' ? 'active' : ''}" onclick="switchUserPayMethod('non tunai', ${p.id_pesanan}, ${p.total_harga})">
          <div style="font-size:22px;margin-bottom:2px">📱</div>
          <div style="font-weight:700;font-size:13px;color:var(--text-primary)">E-Wallet</div>
          <div style="font-size:11px;color:var(--text-muted)">GoPay, OVO, DANA, Shopee</div>
        </div>
      </div>

      <div id="user-payment-form-area">
        ${renderUserPayForm(userPayMethod, p.id_pesanan, p.total_harga)}
      </div>
    </div>
  `;
}

function switchUserPayMethod(metode, id_pesanan, total) {
  userPayMethod = metode;
  localStorage.setItem('dapur_ina_last_pay_method', metode);
  const area = document.getElementById('user-payment-form-area');
  if (area) {
    area.innerHTML = renderUserPayForm(metode, id_pesanan, total);
  }
  const box = document.getElementById('user-payment-card-box');
  if (box) {
    const cards = box.querySelectorAll('.user-pay-card');
    if (cards.length >= 2) {
      cards[0].classList.toggle('active', metode === 'tunai');
      cards[1].classList.toggle('active', metode === 'non tunai');
    }
  }
}

function selectTrackingEWallet(ewalletId, id_pesanan, total) {
  selectedEWallet = ewalletId;
  localStorage.setItem('dapur_ina_last_ewallet', ewalletId);
  const area = document.getElementById('user-payment-form-area');
  if (area) {
    area.innerHTML = renderUserPayForm('non tunai', id_pesanan, total);
  }
}

function renderUserPayForm(metode, id_pesanan, total) {
  if (metode === 'tunai') {
    return `
      <div style="font-size:12px;color:var(--text-secondary);margin-bottom:12px">
        💵 Pembayaran tunai dapat diserahkan ke kasir atau kepada pelayan yang mengantarkan makanan ke meja Anda.
      </div>
      <div style="margin-bottom:12px">
        <label style="font-size:12px;font-weight:700;color:var(--text-secondary);display:block;margin-bottom:4px">Nominal Uang Bayar (Rp):</label>
        <input type="number" id="inp-user-uang-bayar" class="form-control" value="${total}" min="${total}" placeholder="Nominal uang" style="font-size:14px;font-weight:700">
        <small style="color:var(--text-muted);font-size:11px">*Otomatis diset uang pas (${formatRp(total)}). Anda juga bisa mengubah nominal jika membutuhkan kembalian.</small>
      </div>
      <button class="btn btn-order" onclick="prosesBayarUser(${id_pesanan}, 'tunai')" id="btn-user-bayar" style="width:100%;padding:12px;font-size:14px;font-weight:700">
        💵 Bayar Tunai (${formatRp(total)})
      </button>
    `;
  } else {
    const curEw = EWALLET_LIST.find(e => e.id === selectedEWallet) || EWALLET_LIST[0];
    return `
      <div style="font-size:12px;font-weight:700;color:var(--text-secondary);margin-bottom:8px">
        Pilih E-Wallet Pembayaran:
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:14px">
        ${EWALLET_LIST.map(ew => {
          const isAct = selectedEWallet === ew.id;
          return `
            <div class="ewallet-select-card ${isAct ? 'active' : ''}" 
                 style="--ewallet-color:${ew.color};--ewallet-bg:${ew.bg}"
                 onclick="selectTrackingEWallet('${ew.id}', ${id_pesanan}, ${total})">
              <div class="ewallet-icon-badge">${ew.icon}</div>
              <div style="flex:1">
                <div style="font-weight:700;font-size:13px;color:${isAct ? ew.color : 'var(--text-primary)'}">${ew.name}</div>
                <div style="font-size:11px;color:var(--text-muted)">${ew.desc}</div>
              </div>
              ${isAct ? `<span style="color:${ew.color};font-weight:800;font-size:15px">✓</span>` : ''}
            </div>
          `;
        }).join('')}
      </div>

      <div style="background:#fff;border:2px solid ${curEw.color};border-radius:14px;padding:18px 16px;margin-bottom:16px;text-align:center;box-shadow:0 4px 14px rgba(0,0,0,0.04)">
        <div style="display:flex;align-items:center;justify-content:center;gap:8px;font-size:15px;font-weight:800;color:${curEw.color};margin-bottom:12px">
          <span style="font-size:20px">${curEw.icon}</span> <span>Transfer ${curEw.name}</span>
        </div>

        <div style="background:${curEw.bg};border-radius:10px;padding:14px;margin-bottom:12px;border:1px dashed ${curEw.color}">
          <div style="font-size:12px;color:var(--text-muted);margin-bottom:4px;font-weight:600">Nomor Akun / Handphone:</div>
          <div style="display:flex;align-items:center;justify-content:center;gap:10px">
            <span style="font-size:24px;font-weight:900;letter-spacing:1px;color:var(--text-primary);font-family:monospace">0812345678</span>
            <button type="button" class="btn btn-sm" onclick="navigator.clipboard.writeText('0812345678');toast('Nomor 0812345678 berhasil disalin!', 'success')" style="padding:4px 10px;font-size:12px;background:#fff;border:1px solid var(--border);border-radius:6px;cursor:pointer">
              📋 Salin
            </button>
          </div>
          <div style="font-size:12px;color:var(--text-secondary);margin-top:6px;font-weight:600">a.n. Dapur Ina Aina</div>
        </div>

        <div style="font-size:12px;color:var(--text-secondary);margin-bottom:6px">
          Silakan transfer sesuai total tagihan ke nomor di atas via aplikasi <strong>${curEw.name}</strong>:
        </div>
        <div class="price" style="font-size:22px;font-weight:900;color:var(--price-color)">${formatRp(total)}</div>
      </div>

      <button class="btn btn-order" onclick="prosesBayarUser(${id_pesanan}, 'non tunai', '${curEw.name}')" id="btn-user-bayar" style="width:100%;padding:14px;font-size:15px;font-weight:700;background:${curEw.color}">
        ✅ Konfirmasi Sudah Bayar via ${curEw.name} (${formatRp(total)})
      </button>
    `;
  }
}

async function prosesBayarUser(id_pesanan, metode, ewalletName = null) {
  const btn = document.getElementById('btn-user-bayar');
  if (btn) {
    btn.disabled = true;
    btn.textContent = 'Memproses Pembayaran...';
  }

  const payload = {
    id_pesanan: id_pesanan,
    metode_bayar: metode
  };

  if (metode === 'tunai') {
    const inp = document.getElementById('inp-user-uang-bayar');
    if (inp && inp.value) {
      payload.uang_bayar = parseFloat(inp.value);
    }
  } else {
    const targetEw = ewalletName || selectedEWallet || 'GoPay';
    payload.nama_bank = targetEw;
    payload.no_referensi = targetEw.toUpperCase() + '-' + Date.now().toString().slice(-6);
  }

  try {
    const res = await fetch(API_TRANSAKSI, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    }).then(r => r.json());

    if (res.status !== 'success') {
      throw new Error(res.message || 'Pembayaran gagal');
    }

    const payLabel = metode === 'tunai' ? 'Tunai' : (payload.nama_bank || 'E-Wallet');
    toast(`Pembayaran via ${payLabel} berhasil dicatat! Terima kasih.`, 'success');
    await cekStatusPesananAktif();
    setTimeout(() => {
      lihatStrukUser(res.id_transaksi);
    }, 400);

  } catch (err) {
    toast(err.message, 'error');
    if (btn) {
      btn.disabled = false;
      btn.textContent = 'Coba Lagi';
    }
  }
}

async function lihatStrukUser(id_transaksi) {
  try {
    const res = await fetch(`${API_TRANSAKSI}?id=${id_transaksi}`).then(r => r.json());
    if (res.status !== 'success') throw new Error(res.message);

    const t = res.data;
    document.getElementById('struk-kode').textContent = t.kode_transaksi;
    document.getElementById('struk-waktu').textContent = new Date(t.created_at).toLocaleString('id-ID');
    document.getElementById('struk-nama').textContent = t.nama_pelanggan;
    document.getElementById('struk-meja').textContent = `Meja ${t.no_meja}`;
    document.getElementById('struk-metode').textContent = (t.metode_bayar === 'tunai' ? 'TUNAI' : 'NON TUNAI') + (t.nama_bank && t.nama_bank !== '-' ? ` (${t.nama_bank})` : '');
    document.getElementById('struk-total').textContent = formatRp(t.total_bayar);

    let itemsHtml = '';
    if (t.items && t.items.length > 0) {
      itemsHtml = t.items.map(item => `
        <div class="struk-row">
          <span>${item.nama_produk}<br><small style="color:#888">${item.jumlah} x ${formatRp(item.harga_satuan)}</small></span>
          <span>${formatRp(item.subtotal)}</span>
        </div>
      `).join('');
    }
    document.getElementById('struk-items').innerHTML = itemsHtml;

    let bayarSection = '';
    if (t.metode_bayar === 'tunai') {
      bayarSection = `
        <div class="struk-row">
          <span>Uang Bayar</span><span>${formatRp(t.uang_bayar || t.total_bayar)}</span>
        </div>
        <div class="struk-row">
          <span>Kembalian</span><span>${formatRp(t.kembalian || 0)}</span>
        </div>
      `;
    } else {
      bayarSection = `
        <div class="struk-row">
          <span>No. Referensi</span><span>${t.no_referensi || '-'}</span>
        </div>
      `;
    }
    document.getElementById('struk-bayar-section').innerHTML = bayarSection;

    openModal('modal-struk');
  } catch (err) {
    toast(err.message, 'error');
  }
}
