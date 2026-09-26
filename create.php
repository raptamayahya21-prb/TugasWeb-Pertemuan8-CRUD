<?php
// create.php - Form & Proses Tambah Produk Baru
require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/helpers/functions.php';

$pdo = Database::getInstance();

// -------------------------------------------------------------
// Ambil Data Kategori & Supplier untuk Dropdown Dinamis
// -------------------------------------------------------------
$kategoriList = $pdo->query("SELECT id_kategori, nama_kategori FROM kategori ORDER BY nama_kategori ASC")->fetchAll();
$supplierList = $pdo->query("SELECT id_supplier, nama_supplier FROM supplier ORDER BY nama_supplier ASC")->fetchAll();

// Nilai default untuk form
$formData = [
    'kode_produk' => '',
    'nama_produk' => '',
    'id_kategori' => '',
    'id_supplier' => '',
    'stok'        => '',
    'harga'       => '',
    'satuan'      => 'Unit',
    'kondisi'     => 'Baru'
];

// -------------------------------------------------------------
// Tangani Pengiriman Form (POST)
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['kode_produk'] = trim($_POST['kode_produk'] ?? '');
    $formData['nama_produk'] = trim($_POST['nama_produk'] ?? '');
    $formData['id_kategori'] = (int)($_POST['id_kategori'] ?? 0);
    $formData['id_supplier'] = (int)($_POST['id_supplier'] ?? 0);
    $formData['stok']        = trim($_POST['stok'] ?? '');
    $formData['harga']       = trim($_POST['harga'] ?? '');
    $formData['satuan']      = trim($_POST['satuan'] ?? 'Unit');
    $formData['kondisi']     = trim($_POST['kondisi'] ?? 'Baru');

    // Validasi Sederhana
    $errors = [];
    if (empty($formData['kode_produk'])) {
        $errors[] = 'Kode produk wajib diisi.';
    }
    if (empty($formData['nama_produk'])) {
        $errors[] = 'Nama barang wajib diisi.';
    }
    if ($formData['id_kategori'] <= 0) {
        $errors[] = 'Silakan pilih kategori barang.';
    }
    if ($formData['id_supplier'] <= 0) {
        $errors[] = 'Silakan pilih supplier barang.';
    }
    if ($formData['stok'] === '' || !is_numeric($formData['stok']) || (int)$formData['stok'] < 0) {
        $errors[] = 'Stok barang harus berupa angka >= 0.';
    }
    if ($formData['harga'] === '' || !is_numeric($formData['harga']) || (float)$formData['harga'] < 0) {
        $errors[] = 'Harga barang harus berupa angka valid >= 0.';
    }

    // Cek apakah kode_produk sudah digunakan (Prepared Statement)
    if (empty($errors)) {
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM produk WHERE kode_produk = :kode");
        $checkStmt->execute([':kode' => $formData['kode_produk']]);
        if ($checkStmt->fetchColumn() > 0) {
            $errors[] = 'Kode produk "' . e($formData['kode_produk']) . '" sudah digunakan. Masukkan kode lain.';
        }
    }

    // Jika tidak ada error, eksekusi INSERT dengan Prepared Statements
    if (empty($errors)) {
        try {
            $insertSql = "
                INSERT INTO produk (kode_produk, nama_produk, id_kategori, id_supplier, stok, harga, satuan, kondisi)
                VALUES (:kode, :nama, :kategori, :supplier, :stok, :harga, :satuan, :kondisi)
            ";
            $stmt = $pdo->prepare($insertSql);
            $stmt->execute([
                ':kode'     => $formData['kode_produk'],
                ':nama'     => $formData['nama_produk'],
                ':kategori' => $formData['id_kategori'],
                ':supplier' => $formData['id_supplier'],
                ':stok'     => (int)$formData['stok'],
                ':harga'    => (float)$formData['harga'],
                ':satuan'   => $formData['satuan'],
                ':kondisi'  => $formData['kondisi'],
            ]);

            // Set Flash Message & Redirect (PRG Pattern)
            setFlash('success', 'Barang "' . $formData['nama_produk'] . '" berhasil ditambahkan ke inventaris!');
            redirect('index.php');
        } catch (PDOException $e) {
            setFlash('danger', 'Gagal menyimpan barang: ' . $e->getMessage());
        }
    } else {
        // Tampilkan error validasi
        setFlash('danger', implode('<br>', $errors));
    }
}

$pageTitle = 'Tambah Barang Baru';
require_once __DIR__ . '/views/header.php';
?>

<div class="form-card">
    <div class="form-header">
        <h1 class="form-title">Tambah Barang Inventaris</h1>
        <p class="form-subtitle">Lengkapi formulir di bawah ini untuk mendaftarkan barang baru ke dalam sistem.</p>
    </div>

    <form action="create.php" method="POST" autocomplete="off">
        <div class="form-body">
            <!-- Row 1: Kode Produk & Nama Barang -->
            <div class="form-row-2">
                <div class="form-group">
                    <label class="form-label" for="kode_produk">Kode Produk <span class="required">*</span></label>
                    <input 
                        type="text" 
                        id="kode_produk" 
                        name="kode_produk" 
                        class="form-control" 
                        placeholder="Contoh: PRD-NB-007" 
                        value="<?= e($formData['kode_produk']); ?>" 
                        required
                    >
                    <span class="form-hint">Harus unik untuk setiap jenis barang</span>
                </div>

                <div class="form-group">
                    <label class="form-label" for="nama_produk">Nama Barang <span class="required">*</span></label>
                    <input 
                        type="text" 
                        id="nama_produk" 
                        name="nama_produk" 
                        class="form-control" 
                        placeholder="Contoh: Asus ROG Strix G16" 
                        value="<?= e($formData['nama_produk']); ?>" 
                        required
                    >
                </div>
            </div>

            <!-- Row 2: Dropdown Kategori & Supplier (Dinamis dari Database) -->
            <div class="form-row-2">
                <div class="form-group">
                    <label class="form-label" for="id_kategori">Kategori Barang <span class="required">*</span></label>
                    <select id="id_kategori" name="id_kategori" class="form-select" required>
                        <option value="">-- Pilih Kategori --</option>
                        <?php foreach ($kategoriList as $kat): ?>
                            <option 
                                value="<?= (int)$kat['id_kategori']; ?>"
                                <?= ($formData['id_kategori'] == $kat['id_kategori']) ? 'selected' : ''; ?>
                            >
                                <?= e($kat['nama_kategori']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="id_supplier">Supplier / Rekanan <span class="required">*</span></label>
                    <select id="id_supplier" name="id_supplier" class="form-select" required>
                        <option value="">-- Pilih Supplier --</option>
                        <?php foreach ($supplierList as $sup): ?>
                            <option 
                                value="<?= (int)$sup['id_supplier']; ?>"
                                <?= ($formData['id_supplier'] == $sup['id_supplier']) ? 'selected' : ''; ?>
                            >
                                <?= e($sup['nama_supplier']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Row 3: Stok, Satuan, Harga -->
            <div class="form-row-2">
                <div class="form-group">
                    <label class="form-label" for="stok">Kuantitas Stok <span class="required">*</span></label>
                    <input 
                        type="number" 
                        id="stok" 
                        name="stok" 
                        class="form-control" 
                        min="0" 
                        placeholder="Contoh: 10" 
                        value="<?= e((string)$formData['stok']); ?>" 
                        required
                    >
                </div>

                <div class="form-group">
                    <label class="form-label" for="satuan">Satuan Unit</label>
                    <input 
                        type="text" 
                        id="satuan" 
                        name="satuan" 
                        class="form-control" 
                        placeholder="Unit, Pcs, Box, Set" 
                        value="<?= e($formData['satuan']); ?>"
                    >
                </div>
            </div>

            <!-- Row 4: Harga Satuan & Kondisi Fisik -->
            <div class="form-row-2">
                <div class="form-group">
                    <label class="form-label" for="harga">Harga Satuan (Rp) <span class="required">*</span></label>
                    <input 
                        type="number" 
                        id="harga" 
                        name="harga" 
                        class="form-control" 
                        min="0" 
                        step="500" 
                        placeholder="Contoh: 15000000" 
                        value="<?= e((string)$formData['harga']); ?>" 
                        required
                    >
                    <span class="form-hint">Masukkan angka tanpa titik atau koma</span>
                </div>

                <div class="form-group">
                    <label class="form-label" for="kondisi">Kondisi Barang</label>
                    <select id="kondisi" name="kondisi" class="form-select">
                        <option value="Baru" <?= ($formData['kondisi'] === 'Baru') ? 'selected' : ''; ?>>Baru</option>
                        <option value="Baik" <?= ($formData['kondisi'] === 'Baik') ? 'selected' : ''; ?>>Baik</option>
                        <option value="Perlu Perbaikan" <?= ($formData['kondisi'] === 'Perlu Perbaikan') ? 'selected' : ''; ?>>Perlu Perbaikan</option>
                        <option value="Rusak" <?= ($formData['kondisi'] === 'Rusak') ? 'selected' : ''; ?>>Rusak</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="form-footer">
            <a href="index.php" class="btn-cancel">Batal</a>
            <button type="submit" class="btn-submit">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                <span>Simpan Barang</span>
            </button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/views/footer.php'; ?>
