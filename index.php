<?php
// index.php - Halaman Utama & Tampilan Data Inventaris (Read)
require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/helpers/functions.php';

$pdo = Database::getInstance();

// -------------------------------------------------------------
// Fitur Bonus: Export Laporan ke Format CSV
// -------------------------------------------------------------
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $exportStmt = $pdo->query("
        SELECT p.kode_produk, p.nama_produk, k.nama_kategori, s.nama_supplier, 
               p.stok, p.satuan, p.harga, (p.stok * p.harga) AS subtotal, p.kondisi, p.created_at
        FROM produk p
        JOIN kategori k ON p.id_kategori = k.id_kategori
        JOIN supplier s ON p.id_supplier = s.id_supplier
        ORDER BY p.id_produk DESC
    ");
    $exportData = $exportStmt->fetchAll();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=laporan_inventaris_' . date('Ymd_His') . '.csv');
    
    $output = fopen('php://output', 'w');
    // Header Kolom CSV
    fputcsv($output, ['Kode Produk', 'Nama Produk', 'Kategori', 'Supplier', 'Stok', 'Satuan', 'Harga Satuan (Rp)', 'Total Nilai (Rp)', 'Kondisi', 'Tanggal Input']);
    
    foreach ($exportData as $row) {
        fputcsv($output, [
            $row['kode_produk'],
            $row['nama_produk'],
            $row['nama_kategori'],
            $row['nama_supplier'],
            $row['stok'],
            $row['satuan'],
            $row['harga'],
            $row['subtotal'],
            $row['kondisi'],
            $row['created_at']
        ]);
    }
    fclose($output);
    exit;
}

// -------------------------------------------------------------
// Fitur Pencarian & Query List Produk (JOIN 2 & 3 Tabel + Prepared Statement)
// -------------------------------------------------------------
$search = trim($_GET['q'] ?? '');

if ($search !== '') {
    // SEMUA query berparameter wajib prepared statements
    $sql = "
        SELECT p.*, k.nama_kategori, s.nama_supplier 
        FROM produk p
        JOIN kategori k ON p.id_kategori = k.id_kategori
        JOIN supplier s ON p.id_supplier = s.id_supplier
        WHERE p.nama_produk LIKE :keyword 
           OR p.kode_produk LIKE :keyword 
           OR k.nama_kategori LIKE :keyword
           OR s.nama_supplier LIKE :keyword
        ORDER BY p.id_produk DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':keyword' => "%{$search}%"]);
} else {
    $sql = "
        SELECT p.*, k.nama_kategori, s.nama_supplier 
        FROM produk p
        JOIN kategori k ON p.id_kategori = k.id_kategori
        JOIN supplier s ON p.id_supplier = s.id_supplier
        ORDER BY p.id_produk DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
}

$produkList = $stmt->fetchAll();

// -------------------------------------------------------------
// Statistik Ringkasan Inventaris
// -------------------------------------------------------------
$stats = $pdo->query("
    SELECT 
        COUNT(id_produk) AS total_item,
        COALESCE(SUM(stok), 0) AS total_stok,
        COALESCE(SUM(stok * harga), 0) AS total_aset
    FROM produk
")->fetch();

$totalKategori = $pdo->query("SELECT COUNT(id_kategori) FROM kategori")->fetchColumn();

$pageTitle = 'Daftar Inventaris Barang';
require_once __DIR__ . '/views/header.php';
?>

<!-- Kartu Statistik Inventaris -->
<section class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon-wrap emerald">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path></svg>
        </div>
        <div class="stat-info">
            <span class="stat-label">Total Jenis Barang</span>
            <span class="stat-value"><?= number_format($stats['total_item'] ?? 0); ?> Item</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon-wrap indigo">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>
        </div>
        <div class="stat-info">
            <span class="stat-label">Total Kuantitas Fisik</span>
            <span class="stat-value"><?= number_format($stats['total_stok'] ?? 0); ?> Unit</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon-wrap warning">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
        </div>
        <div class="stat-info">
            <span class="stat-label">Estimasi Nilai Aset</span>
            <span class="stat-value"><?= formatRupiah($stats['total_aset'] ?? 0); ?></span>
        </div>
    </div>
</section>

<!-- Toolbar: Pencarian, Tambah Data, Export CSV -->
<div class="toolbar-wrap">
    <form action="index.php" method="GET" class="search-form">
        <div class="input-icon-group">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            <input 
                type="text" 
                name="q" 
                class="input-search" 
                placeholder="Cari kode, nama barang, kategori, supplier..." 
                value="<?= e($search); ?>"
                autocomplete="off"
            >
        </div>
        <button type="submit" class="btn-search">Cari</button>
        <?php if ($search !== ''): ?>
            <a href="index.php" class="btn-secondary" title="Reset filter">Reset</a>
        <?php endif; ?>
    </form>

    <div class="toolbar-actions">
        <a href="index.php?export=csv" class="btn-secondary" title="Export Laporan CSV">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
            <span>Export CSV</span>
        </a>
        <a href="create.php" class="nav-btn-primary">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            <span>Tambah Barang</span>
        </a>
    </div>
</div>

<!-- Tabel Daftar Barang Inventaris -->
<div class="table-card">
    <div class="table-header-title">
        <div class="table-title">Data Inventaris Produk</div>
        <div class="table-count-badge">Menampilkan <?= count($produkList); ?> Item</div>
    </div>

    <div class="table-responsive">
        <table class="custom-table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Kode</th>
                    <th>Nama Barang</th>
                    <th>Kategori</th>
                    <th>Supplier</th>
                    <th>Stok</th>
                    <th>Harga Satuan</th>
                    <th>Kondisi</th>
                    <th style="text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($produkList)): ?>
                    <tr>
                        <td colspan="9">
                            <div class="empty-state">
                                <div class="empty-icon">
                                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                                </div>
                                <div class="empty-title">Data Barang Tidak Ditemukan</div>
                                <div class="empty-desc">
                                    <?= ($search !== '') 
                                        ? 'Tidak ada barang yang cocok dengan kata kunci "' . e($search) . '". Coba kata kunci lain.' 
                                        : 'Belum ada data barang inventaris yang tersimpan. Klik tombol Tambah Barang untuk mulai menambahkan.'; ?>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php 
                    $no = 1;
                    foreach ($produkList as $item): 
                        // Kelas badge kondisi
                        $kondisiClass = 'badge-kondisi-baru';
                        if ($item['kondisi'] === 'Baik') $kondisiClass = 'badge-kondisi-baik';
                        elseif ($item['kondisi'] === 'Perlu Perbaikan') $kondisiClass = 'badge-kondisi-perlu-perbaikan';
                        elseif ($item['kondisi'] === 'Rusak') $kondisiClass = 'badge-kondisi-rusak';
                    ?>
                        <tr>
                            <td><?= $no++; ?></td>
                            <td><span class="code-pill"><?= e($item['kode_produk']); ?></span></td>
                            <td>
                                <strong class="item-name"><?= e($item['nama_produk']); ?></strong>
                            </td>
                            <td><span class="category-tag"><?= e($item['nama_kategori']); ?></span></td>
                            <td><span class="supplier-info"><?= e($item['nama_supplier']); ?></span></td>
                            <td>
                                <span class="stok-text" style="color: <?= ($item['stok'] <= 5) ? '#ef4444' : '#34d399'; ?>;">
                                    <?= number_format($item['stok']); ?>
                                </span> 
                                <small style="color: var(--text-muted);"><?= e($item['satuan']); ?></small>
                            </td>
                            <td><span class="price-text"><?= formatRupiah($item['harga']); ?></span></td>
                            <td>
                                <span class="badge <?= $kondisiClass; ?>">
                                    <?= e($item['kondisi']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons" style="justify-content: center;">
                                    <!-- Tombol Edit -->
                                    <a 
                                        href="edit.php?id=<?= (int)$item['id_produk']; ?>" 
                                        class="btn-icon edit" 
                                        title="Edit barang <?= e($item['nama_produk']); ?>"
                                    >
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path></svg>
                                    </a>
                                    
                                    <!-- Tombol Hapus dengan Konfirmasi -->
                                    <a 
                                        href="delete.php?id=<?= (int)$item['id_produk']; ?>" 
                                        class="btn-icon delete" 
                                        title="Hapus barang"
                                        onclick="return confirmDelete('<?= e(addslashes($item['nama_produk'])); ?>');"
                                    >
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/views/footer.php'; ?>
