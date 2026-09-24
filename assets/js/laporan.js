// ============================================================
// laporan.js - Laporan Penjualan & Dashboard
// Dapur Ina Aina Restaurant System
// ============================================================

let grafikChart = null;

// ── Load Dashboard ──
async function loadDashboard() {
  try {
    const res = await apiFetch(`${API.laporan}?aksi=dashboard`);
    const d   = res.data;

    // Stats
    document.getElementById('stat-pesanan').textContent   = d.total_pesanan_hari_ini;
    document.getElementById('stat-pendapatan').textContent = formatRp(d.pendapatan_hari_ini);
    document.getElementById('stat-stok-habis').textContent = d.stok_hampir_habis;

    // Grafik
    renderGrafik(d.grafik_7_hari);

    // Top Produk
    renderTopProduk(d.top_produk, 'top-produk-dashboard');

    // Rekap Kategori
    renderRekapKategori(d.rekap_kategori);

  } catch (e) {
    console.error(e);
    toast('Gagal memuat dashboard: ' + e.message, 'error');
  }
}

function renderGrafik(data) {
  const ctx = document.getElementById('grafik-penjualan');
  if (!ctx) return;

  // Generate 7 days including days without data
  const days   = [];
  const values = [];
  for (let i = 6; i >= 0; i--) {
    const d   = new Date();
    d.setDate(d.getDate() - i);
    const key = d.toISOString().split('T')[0];
    days.push(d.toLocaleDateString('id-ID', { weekday: 'short', day: 'numeric' }));
    const found = data.find(x => x.tanggal === key);
    values.push(found ? parseFloat(found.total) : 0);
  }

  if (grafikChart) grafikChart.destroy();

  const maxVal = Math.max(...values, 0);
  const suggestedMax = maxVal === 0 ? 100000 : Math.ceil(maxVal * 1.2 / 10000) * 10000;

  grafikChart = new Chart(ctx, {
    type: 'line',
    data: {
      labels: days,
      datasets: [{
        label: 'Pendapatan (Rp)',
        data: values,
        borderColor: '#888B90',
        backgroundColor: 'rgba(136,139,144,0.12)',
        borderWidth: 2.5,
        pointBackgroundColor: '#888B90',
        pointRadius: 4,
        pointHoverRadius: 6,
        fill: true,
        tension: 0.35,
      }],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            label: ctx => 'Rp ' + ctx.parsed.y.toLocaleString('id-ID'),
          },
        },
      },
      scales: {
        x: {
          grid: { color: 'rgba(0,0,0,0.05)' },
          ticks: { color: '#4B5563', font: { weight: '600' } },
        },
        y: {
          grid: { color: 'rgba(0,0,0,0.05)' },
          suggestedMin: 0,
          suggestedMax: suggestedMax,
          ticks: {
            color: '#4B5563',
            precision: 0,
            maxTicksLimit: 6,
            callback: function(v) {
              if (v === 0) return 'Rp 0';
              if (v >= 1000000) return 'Rp ' + (v / 1000000).toLocaleString('id-ID') + ' jt';
              if (v >= 1000) return 'Rp ' + (v / 1000).toLocaleString('id-ID') + ' rb';
              return 'Rp ' + v;
            },
          },
          beginAtZero: true,
        },
      },
    },
  });
}

function renderTopProduk(data, containerId) {
  const el = document.getElementById(containerId);
  if (!el) return;
  if (!data || data.length === 0) {
    el.innerHTML = emptyStateHtml('🏆', 'Belum Ada Data Penjualan', 'Data menu terlaris belum tercatat untuk periode ini.');
    return;
  }
  el.innerHTML = data.map((p, i) => `
    <div class="top-item">
      <div class="top-rank">${i + 1}</div>
      <div class="top-info">
        <div class="top-name">${p.nama_produk}</div>
        <div class="top-cat">${p.nama_kategori} • ${p.total_terjual} terjual</div>
      </div>
      <div class="top-amount">${formatRp(p.total_pendapatan)}</div>
    </div>
  `).join('');
}

function renderRekapKategori(data) {
  const el = document.getElementById('rekap-kategori');
  if (!el || !data) return;
  if (data.length === 0) {
    el.innerHTML = emptyStateHtml('📂', 'Belum Ada Data Kategori', 'Belum ada transaksi penjualan per kategori.');
    return;
  }
  el.innerHTML = data.map(k => `
    <div class="top-item">
      <div class="top-rank">${getKatIkon(k.nama_kategori, k.ikon)}</div>
      <div class="top-info">
        <div class="top-name">${k.nama_kategori}</div>
        <div class="top-cat">${k.total_terjual} item terjual</div>
      </div>
      <div class="top-amount">${formatRp(k.total_pendapatan)}</div>
    </div>
  `).join('');
}

// ── Load Laporan Page ──
async function loadLaporan() {
  const bulan = new Date().getMonth() + 1;
  const tahun = new Date().getFullYear();
  document.getElementById('sel-bulan').value = bulan;
  document.getElementById('sel-tahun').value = tahun;
  await loadLaporanBulanan();
}

async function loadLaporanBulanan() {
  const bulan = document.getElementById('sel-bulan').value;
  const tahun = document.getElementById('sel-tahun').value;
  try {
    const res = await apiFetch(`${API.laporan}?aksi=bulanan&bulan=${bulan}&tahun=${tahun}`);
    const d   = res.data;

    // Ringkasan
    const r = d.ringkasan;
    document.getElementById('lap-total-transaksi').textContent = r.jumlah_transaksi;
    document.getElementById('lap-total-pendapatan').textContent = formatRp(r.total_pendapatan);
    document.getElementById('lap-rata-transaksi').textContent   = formatRp(r.rata_transaksi);
    document.getElementById('lap-bayar-tunai').textContent      = r.bayar_tunai || 0;
    const lapNonTunai = document.getElementById('lap-bayar-nontunai');
    if (lapNonTunai) {
      lapNonTunai.textContent = (r.bayar_nontunai !== undefined) ? r.bayar_nontunai : ((parseInt(r.bayar_debit)||0) + (parseInt(r.bayar_kredit)||0));
    }

    // Tabel transaksi
    const tbody = document.getElementById('tbl-laporan');
    if (d.transaksi.length === 0) {
      tbody.innerHTML = `<tr><td colspan="6">${emptyStateHtml('📋', 'Tidak Ada Transaksi', 'Tidak ditemukan riwayat transaksi pada periode bulan ini.')}</td></tr>`;
    } else {
      tbody.innerHTML = d.transaksi.map(t => `
        <tr>
          <td><strong>${t.kode_transaksi}</strong></td>
          <td>${formatDate(t.created_at)}</td>
          <td>${t.nama_pelanggan} (Meja ${t.no_meja})</td>
          <td><span class="badge badge-${t.metode_bayar}">${t.metode_bayar.charAt(0).toUpperCase() + t.metode_bayar.slice(1)}</span></td>
          <td class="price">${formatRp(t.total_bayar)}</td>
          <td><button class="btn btn-secondary btn-sm" onclick="lihatStruk(${t.id_transaksi})">🧾 Struk</button></td>
        </tr>
      `).join('');
    }

    // Top produk
    renderTopProduk(d.top_produk, 'top-produk-laporan');

    // Rekap kategori
    const rekap = document.getElementById('rekap-kategori-laporan');
    if (rekap) renderRekapKategoriLaporan(d.rekap_kategori, rekap);

  } catch (e) {
    toast(e.message, 'error');
  }
}

function renderRekapKategoriLaporan(data, el) {
  if (!data || data.length === 0) {
    el.innerHTML = emptyStateHtml('📂', 'Belum Ada Data Kategori', 'Belum ada transaksi penjualan per kategori pada periode ini.');
    return;
  }
  el.innerHTML = data.map(k => `
    <div class="top-item">
      <div class="top-rank">${getKatIkon(k.nama_kategori, k.ikon)}</div>
      <div class="top-info">
        <div class="top-name">${k.nama_kategori}</div>
        <div class="top-cat">${k.total_terjual} item terjual</div>
      </div>
      <div class="top-amount">${formatRp(k.total_pendapatan)}</div>
    </div>
  `).join('');
}

// ── Billing Modal ──
let currentPesananId = null;

async function openBillingModal(id_pesanan) {
  currentPesananId = id_pesanan;
  try {
    const res = await apiFetch(`${API.pesanan}?id=${id_pesanan}`);
    const p   = res.data;

    // Jika pesanan sudah lunas, langsung buka struk
    if (p.transaksi && p.transaksi.status === 'berhasil') {
      const payLabel = p.transaksi.metode_bayar === 'tunai' ? 'Tunai' : (p.transaksi.nama_bank || 'E-Wallet');
      toast(`Pesanan #${id_pesanan} sudah dikonfirmasi lunas via ${payLabel}!`, 'info');
      lihatStruk(p.transaksi.id_transaksi);
      return;
    }

    document.getElementById('billing-nama').textContent = p.nama_pelanggan;
    document.getElementById('billing-meja').textContent = 'Meja ' + p.no_meja;
    document.getElementById('billing-id').textContent   = '#' + p.id_pesanan;
    document.getElementById('billing-waktu').textContent = formatDate(p.created_at);

    const itemsHtml = p.items.map(i => `
      <div class="struk-row">
        <span>${i.nama_produk} x${i.jumlah}</span>
        <span>${formatRp(i.subtotal)}</span>
      </div>
    `).join('');

    document.getElementById('billing-items').innerHTML = itemsHtml;
    document.getElementById('billing-total').textContent = formatRp(p.total_harga);
    document.getElementById('billing-total-bayar').value = p.total_harga;

    // Reset form ke tunai dengan default uang pas
    selectMetodeBayar('tunai');
    document.getElementById('inp-uang-bayar').value = p.total_harga;
    hitungKembalian();

    openModal('modal-billing');
  } catch (e) {
    toast(e.message, 'error');
  }
}

function selectMetodeBayar(metode) {
  document.querySelectorAll('.payment-card').forEach(c => {
    c.classList.toggle('active', c.dataset.metode === metode);
  });
  document.getElementById('section-tunai').style.display   = metode === 'tunai' ? 'block' : 'none';
  document.getElementById('section-nontunai').style.display = metode !== 'tunai' ? 'block' : 'none';
  document.getElementById('sel-metode-bayar').value = metode;
}

function hitungKembalian() {
  const total = parseFloat(document.getElementById('billing-total-bayar').value) || 0;
  const bayar = parseFloat(document.getElementById('inp-uang-bayar').value) || 0;
  const kembalian = bayar - total;
  document.getElementById('billing-kembalian').textContent = formatRp(Math.max(0, kembalian));
  document.getElementById('billing-kembalian').style.color = kembalian < 0 ? 'var(--danger)' : 'var(--success)';
}

async function prosesBayar() {
  const metode = document.getElementById('sel-metode-bayar').value;
  const body   = { id_pesanan: currentPesananId, metode_bayar: metode };

  if (metode === 'tunai') {
    const total = parseFloat(document.getElementById('billing-total-bayar').value) || 0;
    const uang = parseFloat(document.getElementById('inp-uang-bayar').value) || total;
    if (uang < total) {
      toast(`Uang bayar kurang! Total tagihan adalah ${formatRp(total)}`, 'warning');
      return;
    }
    body.uang_bayar = uang;
  } else {
    const selEw = document.getElementById('sel-admin-ewallet');
    const bank = selEw ? selEw.value : 'GoPay';
    const ref  = document.getElementById('inp-no-referensi') ? document.getElementById('inp-no-referensi').value.trim() : '';
    body.no_referensi = ref || bank.toUpperCase() + '-' + Date.now().toString().slice(-6);
    body.nama_bank    = bank;
  }

  try {
    const res = await apiFetch(API.transaksi, {
      method: 'POST',
      body: JSON.stringify(body),
    });
    closeModal('modal-billing');
    toast(`Pembayaran pesanan #${currentPesananId} berhasil dikonfirmasi masuk!`, 'success');
    // Tampilkan struk
    setTimeout(() => lihatStruk(res.id_transaksi), 500);
    if (typeof loadPesananAktif === 'function') loadPesananAktif();
    if (typeof loadAntrian === 'function') loadAntrian();
  } catch (e) {
    toast(e.message, 'error');
  }
}

// ── Tampilkan Struk ──
async function lihatStruk(id_transaksi) {
  try {
    const res = await apiFetch(`${API.transaksi}?id=${id_transaksi}`);
    const t   = res.data;

    document.getElementById('struk-kode').textContent   = t.kode_transaksi;
    document.getElementById('struk-waktu').textContent  = formatDate(t.created_at);
    document.getElementById('struk-nama').textContent   = t.nama_pelanggan;
    document.getElementById('struk-meja').textContent   = 'Meja ' + t.no_meja;
    document.getElementById('struk-metode').textContent = t.metode_bayar.toUpperCase();

    const items = t.items.map(i => `
      <div class="struk-row">
        <span>${i.nama_produk}<br><small style="color:#999">${i.jumlah} x ${formatRp(i.harga_satuan)}</small></span>
        <span>${formatRp(i.subtotal)}</span>
      </div>
    `).join('');
    document.getElementById('struk-items').innerHTML = items;
    document.getElementById('struk-total').textContent = formatRp(t.total_bayar);

    const bayarSection = document.getElementById('struk-bayar-section');
    if (t.metode_bayar === 'tunai') {
      bayarSection.innerHTML = `
        <div class="struk-row"><span>Uang Bayar</span><span>${formatRp(t.uang_bayar)}</span></div>
        <div class="struk-row bold"><span>Kembalian</span><span>${formatRp(t.kembalian)}</span></div>
      `;
    } else {
      bayarSection.innerHTML = `
        <div class="struk-row"><span>No. Referensi</span><span>${t.no_referensi}</span></div>
        <div class="struk-row"><span>Bank</span><span>${t.nama_bank || '-'}</span></div>
      `;
    }

    openModal('modal-struk');
  } catch (e) {
    toast(e.message, 'error');
  }
}

function printStruk() {
  const content = document.getElementById('struk-print').innerHTML;
  const win = window.open('', '', 'width=400,height=600');
  win.document.write(`
    <html><head><title>Struk - Dapur Ina Aina</title>
    <style>
      body { font-family: 'Courier New', monospace; max-width: 360px; margin: 0 auto; padding: 20px; }
      .struk-row { display: flex; justify-content: space-between; margin-bottom: 6px; font-size: 13px; }
      .struk-divider { border: 1px dashed #ccc; margin: 10px 0; }
      .struk-header { text-align: center; margin-bottom: 16px; }
      .struk-header h2 { color: #e8531a; font-size: 18px; }
      .struk-footer { text-align: center; font-size: 11px; color: #777; margin-top: 16px; }
      .bold { font-weight: 700; }
    </style></head>
    <body>${content}</body></html>
  `);
  win.print();
  win.close();
}
