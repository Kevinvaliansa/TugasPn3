// ============================================================
// produk.js - Manajemen Produk & Stok
// Dapur Ina Aina Restaurant System
// ============================================================

let produkList    = [];
let kategoriList  = [];
let editingProduk = null;

// ── Load Data ──
async function loadProduk() {
  try {
    const [pRes, kRes] = await Promise.all([
      apiFetch(API.produk),
      apiFetch(`${API.produk}?aksi=kategori`),
    ]);
    produkList   = pRes.data;
    kategoriList = kRes.data;
    renderProdukTable();
    populateKategoriSelect();
  } catch (e) {
    toast(e.message, 'error');
  }
}

function renderProdukTable(filter = '') {
  const tbody   = document.getElementById('tbl-produk');
  let   list    = produkList;

  if (filter) {
    const q = filter.toLowerCase();
    list = list.filter(p =>
      p.nama_produk.toLowerCase().includes(q) ||
      p.nama_kategori.toLowerCase().includes(q)
    );
  }

  if (list.length === 0) {
    tbody.innerHTML = `<tr><td colspan="7">${emptyStateHtml('📦', 'Produk Tidak Ditemukan', 'Belum ada produk yang cocok dengan pencarian atau filter kategori.')}</td></tr>`;
    return;
  }

  tbody.innerHTML = list.map(p => {
    const katIkon  = getKatIkon(p.nama_kategori, p.ikon);
    const katClass = p.nama_kategori === 'Makanan Utama' ? 'badge-makanan' : (p.nama_kategori === 'Appetizer' ? 'badge-appetizer' : 'badge-minuman');

    // Tentukan tampilan foto / icon
    let fotoHtml;
    if (p.foto) {
      fotoHtml = `<img src="uploads/produk/${p.foto}" alt="${p.nama_produk}" class="produk-img">`;
    } else {
      fotoHtml = `<div class="produk-img-placeholder">${katIkon}</div>`;
    }

    // Warna stok
    const stokColor = p.stok <= 5 ? 'var(--danger)' : (p.stok <= 10 ? 'var(--warning)' : 'var(--success)');

    return `
    <tr>
      <td>${fotoHtml}</td>
      <td>
        <div class="fw-bold">${p.nama_produk}</div>
        <div class="text-muted" style="font-size:12px">${p.deskripsi || '-'}</div>
      </td>
      <td><span class="badge ${katClass}">${katIkon} ${p.nama_kategori}</span></td>
      <td class="price">${formatRp(p.harga)}</td>
      <td>
        <strong style="color:${stokColor};font-size:15px">${p.stok}</strong>
      </td>
      <td><span class="badge badge-${p.status}">${p.status === 'tersedia' ? '✅ Tersedia' : '❌ Habis'}</span></td>
      <td>
        <div class="action-btns">
          <button class="btn btn-secondary btn-sm" onclick="editProduk(${p.id_produk})">✏️ Edit</button>
          <button class="btn btn-danger btn-sm" onclick="hapusProduk(${p.id_produk})">🗑️ Hapus</button>
        </div>
      </td>
    </tr>
  `;
  }).join('');
}

function populateKategoriSelect() {
  const selects = document.querySelectorAll('.sel-kategori');
  selects.forEach(sel => {
    sel.innerHTML = '<option value="">-- Pilih Kategori --</option>' +
      kategoriList.map(k => `<option value="${k.id_kategori}">${getKatIkon(k.nama_kategori, k.ikon)} ${k.nama_kategori}</option>`).join('');
  });
}

// ── Preview Foto ──
function previewFoto(input) {
  const file = input.files[0];
  if (!file) return;

  if (file.size > 2 * 1024 * 1024) {
    toast('Ukuran foto maksimal 2MB', 'error');
    input.value = '';
    return;
  }

  const reader = new FileReader();
  reader.onload = (e) => {
    const preview     = document.getElementById('foto-preview');
    const placeholder = document.getElementById('foto-placeholder');
    const actionWrap  = document.getElementById('foto-action-wrap');
    preview.src       = e.target.result;
    preview.classList.add('show');
    placeholder.style.display = 'none';
    if (actionWrap) actionWrap.style.display = 'block';
  };
  reader.readAsDataURL(file);
}

// ── Reset Foto Preview ──
function resetFotoPreview(fotoPath = '') {
  const preview     = document.getElementById('foto-preview');
  const placeholder = document.getElementById('foto-placeholder');
  const input       = document.getElementById('inp-foto');
  const actionWrap  = document.getElementById('foto-action-wrap');

  if (fotoPath) {
    preview.src = `uploads/produk/${fotoPath}`;
    preview.classList.add('show');
    placeholder.style.display = 'none';
    if (actionWrap) actionWrap.style.display = 'block';
  } else {
    preview.src = '';
    preview.classList.remove('show');
    placeholder.style.display = '';
    if (actionWrap) actionWrap.style.display = 'none';
  }
  input.value = '';
  document.getElementById('inp-foto-path').value = fotoPath;
}

function clearFoto(e) {
  if (e) e.stopPropagation();
  resetFotoPreview('');
}

// ── Open Add Modal ──
function openTambahProduk() {
  editingProduk = null;
  document.getElementById('modal-produk-title').textContent = '➕ Tambah Produk';
  document.getElementById('inp-id-kategori').value = '';
  document.getElementById('inp-nama-produk').value = '';
  document.getElementById('inp-deskripsi').value   = '';
  document.getElementById('inp-harga').value        = '';
  document.getElementById('inp-stok').value         = '';
  resetFotoPreview('');
  openModal('modal-produk');
}

// ── Edit Produk ──
function editProduk(id) {
  const p = produkList.find(x => x.id_produk == id);
  if (!p) return;
  editingProduk = p;
  document.getElementById('modal-produk-title').textContent = '✏️ Edit Produk';
  document.getElementById('inp-id-kategori').value  = p.id_kategori;
  document.getElementById('inp-nama-produk').value  = p.nama_produk;
  document.getElementById('inp-deskripsi').value    = p.deskripsi || '';
  document.getElementById('inp-harga').value        = p.harga;
  document.getElementById('inp-stok').value         = p.stok;
  resetFotoPreview(p.foto || '');
  openModal('modal-produk');
}

// ── Save Produk ──
async function saveProduk() {
  const nama    = document.getElementById('inp-nama-produk').value.trim();
  const katId   = document.getElementById('inp-id-kategori').value;
  const harga   = document.getElementById('inp-harga').value;
  const stok    = document.getElementById('inp-stok').value;
  const deskrip = document.getElementById('inp-deskripsi').value.trim();
  const fotoInput = document.getElementById('inp-foto');
  const fotoFile  = fotoInput.files[0];

  if (!katId || !nama || !harga) {
    toast('Data wajib belum lengkap', 'warning');
    return;
  }

  try {
    // Upload foto dulu jika ada file baru
    let fotoPath = document.getElementById('inp-foto-path').value || '';
    if (fotoFile) {
      const fd = new FormData();
      fd.append('foto', fotoFile);
      const upRes = await fetch('api/upload_foto.php', { method: 'POST', body: fd });
      const upJson = await upRes.json();
      if (upJson.status !== 'success') throw new Error(upJson.message || 'Upload foto gagal');
      fotoPath = upJson.filename;
    }

    const data = {
      id_kategori: katId,
      nama_produk: nama,
      deskripsi:   deskrip,
      harga:       harga,
      stok:        stok,
      foto:        fotoPath,
    };

    if (editingProduk) {
      await apiFetch(`${API.produk}?id=${editingProduk.id_produk}`, {
        method: 'PUT',
        body: JSON.stringify(data),
      });
      toast('Produk berhasil diperbarui', 'success');
    } else {
      await apiFetch(API.produk, {
        method: 'POST',
        body: JSON.stringify(data),
      });
      toast('Produk berhasil ditambahkan', 'success');
    }
    closeModal('modal-produk');
    loadProduk();
  } catch (e) {
    toast(e.message, 'error');
  }
}

// ── Hapus Produk ──
async function hapusProduk(id) {
  const p = produkList.find(x => x.id_produk == id);
  if (!confirm(`Hapus produk "${p?.nama_produk}"? Tindakan ini tidak bisa dibatalkan.`)) return;
  try {
    await apiFetch(`${API.produk}?id=${id}`, { method: 'DELETE' });
    toast('Produk dihapus', 'success');
    loadProduk();
  } catch (e) {
    toast(e.message, 'error');
  }
}

// ── Search ──
document.addEventListener('DOMContentLoaded', () => {
  const searchInput = document.getElementById('search-produk');
  if (searchInput) {
    searchInput.addEventListener('input', e => renderProdukTable(e.target.value));
  }
});
