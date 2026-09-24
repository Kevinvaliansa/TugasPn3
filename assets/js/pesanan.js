// ============================================================
// pesanan.js - Pemesanan & Cart Logic
// Dapur Ina Aina Restaurant System
// ============================================================

let cart = [];
let allMenu = [];
let activeKategori = 0; // 0 = semua

// ── Load Menu Data ──
async function loadPesanan() {
  try {
    const [menuRes, katRes] = await Promise.all([
      apiFetch(`${API.produk}?status=tersedia`),
      apiFetch(`${API.produk}?aksi=kategori`),
    ]);
    allMenu = menuRes.data;
    renderKategoriTabs(katRes.data);
    renderMenu();
    renderCart();
    loadPesananAktif();
  } catch (e) {
    toast(e.message, 'error');
  }
}

function renderKategoriTabs(kategori) {
  const wrap = document.getElementById('kategori-tabs');
  wrap.innerHTML = `<button class="menu-tab active" onclick="filterMenu(0)">🍽️ Semua</button>`;
  kategori.forEach(k => {
    const icon = getKatIkon(k.nama_kategori, k.ikon);
    wrap.innerHTML += `<button class="menu-tab" onclick="filterMenu(${k.id_kategori})">${icon} ${k.nama_kategori}</button>`;
  });
}

function filterMenu(id_kategori) {
  activeKategori = id_kategori;
  document.querySelectorAll('#kategori-tabs .menu-tab').forEach((btn, i) => {
    btn.classList.toggle('active', i === 0 ? id_kategori === 0 : parseInt(btn.getAttribute('onclick').match(/\d+/)?.[0]) === id_kategori);
  });
  renderMenu();
}

function renderMenu() {
  const grid    = document.getElementById('menu-grid');
  const filtered = activeKategori === 0 ? allMenu : allMenu.filter(m => m.id_kategori == activeKategori);

  if (filtered.length === 0) {
    grid.innerHTML = `<div style="grid-column:1/-1">${emptyStateHtml('🍽️', 'Tidak Ada Menu Tersedia', 'Menu untuk kategori ini belum tersedia atau sedang kosong.')}</div>`;
    return;
  }

  grid.innerHTML = filtered.map(m => {
    const katIkon = getKatIkon(m.nama_kategori, m.ikon);
    const imgHtml = m.foto
      ? `<img src="uploads/produk/${m.foto}" alt="${m.nama_produk}" class="menu-item-img">`
      : `<div class="menu-item-icon-wrap">${katIkon}</div>`;

    const katBadgeClass = m.nama_kategori === 'Makanan Utama' ? 'badge-makanan' : (m.nama_kategori === 'Appetizer' ? 'badge-appetizer' : 'badge-minuman');

    return `
    <div class="menu-item ${m.status === 'habis' ? 'habis' : ''}" onclick="addToCart(${m.id_produk})">
      ${imgHtml}
      <div class="menu-item-name">${m.nama_produk}</div>
      <div class="menu-item-price">${formatRp(m.harga)}</div>
      <div class="menu-item-stok">
        <span>Stok: ${m.stok}</span>
        <span class="badge ${katBadgeClass}">${katIkon} ${m.nama_kategori}</span>
      </div>
      <div class="menu-item-add" title="Tambah ke keranjang">+</div>
      ${m.status === 'habis' ? '<div class="badge badge-habis" style="position:absolute;bottom:10px;right:10px">Habis</div>' : ''}
    </div>
  `;
  }).join('');
}

function addToCart(id_produk) {
  const produk = allMenu.find(m => m.id_produk == id_produk);
  if (!produk) return;

  const stok = parseInt(produk.stok) || 0;
  if (stok <= 0 || produk.status === 'habis') {
    toast(`Maaf, stok "${produk.nama_produk}" sudah habis!`, 'warning');
    return;
  }

  const existing = cart.find(c => c.id_produk == id_produk);
  if (existing) {
    if (existing.jumlah >= stok) {
      toast(`Pesanan melebihi stok! Stok "${produk.nama_produk}" hanya tersedia ${stok}`, 'warning');
      return;
    }
    existing.jumlah++;
  } else {
    cart.push({ id_produk: produk.id_produk, nama: produk.nama_produk, harga_satuan: parseFloat(produk.harga), jumlah: 1, stok: stok });
  }
  renderCart();
  toast(`${produk.nama_produk} ditambahkan ke keranjang`, 'success');
}

function removeFromCart(id_produk) {
  const item = cart.find(c => c.id_produk == id_produk);
  if (!item) return;
  if (!confirm(`Yakin untuk menghapus "${item.nama}" dari keranjang?`)) return;
  cart = cart.filter(c => c.id_produk != id_produk);
  renderCart();
  toast(`${item.nama} dihapus dari keranjang`, 'info');
}

function changeQty(id_produk, delta) {
  const item = cart.find(c => c.id_produk == id_produk);
  if (!item) return;

  // Jika jumlah saat ini 1 dan diklik minus (-), beri pop up konfirmasi hapus
  if (delta === -1 && item.jumlah <= 1) {
    if (confirm(`Yakin untuk menghapus "${item.nama}" dari keranjang?`)) {
      cart = cart.filter(c => c.id_produk != id_produk);
      renderCart();
      toast(`${item.nama} dihapus dari keranjang`, 'info');
    }
    return;
  }

  // Jika menambah melebihi stok
  const prod = allMenu.find(m => m.id_produk == id_produk);
  const stok = prod ? parseInt(prod.stok) : item.stok;

  if (delta === 1 && item.jumlah >= stok) {
    toast(`Pesanan melebihi stok! Stok "${item.nama}" hanya tersedia ${stok}`, 'warning');
    return;
  }

  item.jumlah = Math.max(1, Math.min(stok, item.jumlah + delta));
  renderCart();
}

function renderCart() {
  const wrap  = document.getElementById('cart-items');
  const total = cart.reduce((s, c) => s + c.harga_satuan * c.jumlah, 0);
  document.getElementById('cart-total').textContent = formatRp(total);
  document.getElementById('cart-count').textContent = cart.reduce((s, c) => s + c.jumlah, 0);

  if (cart.length === 0) {
    wrap.classList.add('is-empty');
    wrap.innerHTML = emptyStateHtml('🛒', 'Keranjang Masih Kosong', 'Pilih menu makanan atau minuman di samping untuk menambahkan ke pesanan.');
    return;
  }

  wrap.classList.remove('is-empty');
  wrap.innerHTML = cart.map(item => `
    <div class="cart-item">
      <div class="cart-item-name">
        ${item.nama}
        <div class="cart-item-price">${formatRp(item.harga_satuan)}</div>
      </div>
      <div class="qty-control">
        <button class="qty-btn" type="button" onclick="changeQty(${item.id_produk}, -1)">−</button>
        <input type="number" class="qty-input" value="${item.jumlah}" min="1" max="${item.stok}"
               onchange="setPosQtyDirect(${item.id_produk}, this.value)"
               onkeydown="if(event.key==='Enter') this.blur()"
               onclick="this.select()"
               title="Ketik jumlah (Maks. ${item.stok})">
        <button class="qty-btn" type="button" onclick="changeQty(${item.id_produk}, 1)">+</button>
      </div>
      <button class="btn btn-icon btn-danger btn-sm" onclick="removeFromCart(${item.id_produk})">🗑</button>
    </div>
  `).join('');
}

function setPosQtyDirect(id_produk, val) {
  const item = cart.find(c => c.id_produk == id_produk);
  if (!item) return;

  let qty = parseInt(val);
  const prod = allMenu.find(m => m.id_produk == id_produk);
  const stok = prod ? parseInt(prod.stok) : item.stok;

  if (isNaN(qty) || qty <= 0) {
    if (confirm(`Yakin untuk menghapus "${item.nama}" dari keranjang?`)) {
      cart = cart.filter(c => c.id_produk != id_produk);
      renderCart();
      toast(`${item.nama} dihapus dari keranjang`, 'info');
    } else {
      renderCart();
    }
    return;
  }

  if (qty > stok) {
    toast(`Pesanan melebihi stok! Stok "${item.nama}" hanya tersedia ${stok}`, 'warning');
    qty = stok;
  }

  item.jumlah = qty;
  renderCart();
}

function clearCart() {
  if (!confirm('Kosongkan keranjang?')) return;
  cart = [];
  renderCart();
}

// ── Submit Pesanan ──
function submitPesanan() {
  if (cart.length === 0) { toast('Keranjang masih kosong', 'warning'); return; }

  // Validasi stok
  for (const item of cart) {
    const prod = allMenu.find(p => p.id_produk == item.id_produk);
    const stok = prod ? parseInt(prod.stok) : item.stok;
    if (stok <= 0) {
      toast(`Menu "${item.nama}" stoknya sudah habis!`, 'error');
      return;
    }
    if (item.jumlah > stok) {
      toast(`Pesanan melebihi stok! Menu "${item.nama}" hanya tersedia ${stok}.`, 'error');
      return;
    }
  }

  // Fill modal summary
  const total = cart.reduce((s, c) => s + c.harga_satuan * c.jumlah, 0);
  document.getElementById('konfirmasi-total').textContent = formatRp(total);
  document.getElementById('konfirmasi-items').innerHTML = cart.map(c =>
    `<div class="struk-row"><span>${c.nama} x${c.jumlah}</span><span>${formatRp(c.harga_satuan * c.jumlah)}</span></div>`
  ).join('');
  openModal('modal-konfirmasi');
}

async function prosesPesanan() {
  const nama  = document.getElementById('inp-nama').value.trim();
  const meja  = document.getElementById('inp-meja').value.trim();
  const catat = document.getElementById('inp-catatan').value.trim();

  if (!nama || !meja) { toast('Nama pelanggan & nomor meja wajib diisi', 'warning'); return; }

  try {
    const res = await apiFetch(API.pesanan, {
      method: 'POST',
      body: JSON.stringify({
        pelanggan: { nama, no_meja: meja },
        items: cart,
        catatan: catat,
      }),
    });
    toast(`Pesanan #${res.id_pesanan} berhasil dibuat!`, 'success');
    closeModal('modal-konfirmasi');
    cart = [];
    renderCart();
    document.getElementById('inp-nama').value  = '';
    document.getElementById('inp-meja').value  = '';
    document.getElementById('inp-catatan').value = '';
    loadPesananAktif();
    // Auto redirect ke billing
    setTimeout(() => prosesKeBilling(res.id_pesanan), 1500);
  } catch (e) {
    toast(e.message, 'error');
  }
}

// ── Load Daftar Pesanan Aktif ──
async function loadPesananAktif() {
  try {
    const res = await apiFetch(`${API.pesanan}?status=pending`);
    const tbody = document.getElementById('tbl-pesanan-aktif');
    if (res.data.length === 0) {
      tbody.innerHTML = `<tr><td colspan="5">${emptyStateHtml('📋', 'Tidak Ada Pesanan Aktif', 'Semua pesanan saat ini telah diselesaikan atau dibayar.')}</td></tr>`;
      return;
    }
    tbody.innerHTML = res.data.map(p => `
      <tr>
        <td><strong>#${p.id_pesanan}</strong></td>
        <td>${p.nama_pelanggan}</td>
        <td>Meja ${p.no_meja}</td>
        <td class="price">${formatRp(p.total_harga)}</td>
        <td>
          <div class="action-btns">
            <button class="btn btn-order btn-sm" onclick="prosesKeBilling(${p.id_pesanan})">🔍 Konfirmasi Masuk</button>
            <button class="btn btn-secondary btn-sm" onclick="lihatDetailPesanan(${p.id_pesanan})">👁 Detail</button>
          </div>
        </td>
      </tr>
    `).join('');
  } catch (e) {
    console.error(e);
  }
}

// ── Lihat Detail Pesanan ──
async function lihatDetailPesanan(id) {
  try {
    const res = await apiFetch(`${API.pesanan}?id=${id}`);
    const p   = res.data;
    document.getElementById('detail-pesanan-body').innerHTML = `
      <div class="alert alert-info">
        <strong>Pesanan #${p.id_pesanan}</strong> | ${p.nama_pelanggan} | Meja ${p.no_meja} | ${formatDate(p.created_at)}
      </div>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Produk</th><th>Kategori</th><th>Harga</th><th>Qty</th><th>Subtotal</th></tr></thead>
          <tbody>
            ${p.items.map(i => `
              <tr>
                <td>${i.nama_produk}</td>
                <td>${i.nama_kategori}</td>
                <td>${formatRp(i.harga_satuan)}</td>
                <td>${i.jumlah}</td>
                <td class="price">${formatRp(i.subtotal)}</td>
              </tr>
            `).join('')}
          </tbody>
          <tfoot>
            <tr><td colspan="4" class="text-right fw-bold">Total</td><td class="price fw-bold">${formatRp(p.total_harga)}</td></tr>
          </tfoot>
        </table>
      </div>
      ${p.catatan ? `<div class="alert alert-warning mt-2">📝 Catatan: ${p.catatan}</div>` : ''}
    `;
    openModal('modal-detail-pesanan');
  } catch (e) {
    toast(e.message, 'error');
  }
}

// ── Proses ke Billing ──
function prosesKeBilling(id_pesanan) {
  closeModal('modal-detail-pesanan');
  closeModal('modal-konfirmasi');
  // Store pesanan id and navigate to billing page via produk section
  openBillingModal(id_pesanan);
}
