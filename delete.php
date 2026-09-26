<?php
// delete.php - Proses Hapus Barang dengan PDO Transaction & Activity Logging (Bonus Feature)
require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/helpers/functions.php';

$pdo = Database::getInstance();

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    setFlash('danger', 'ID barang tidak valid.');
    redirect('index.php');
}

try {
    // -------------------------------------------------------------
    // FITUR BONUS: PDO Transaction pada Delete + Log Aktivitas
    // -------------------------------------------------------------
    $pdo->beginTransaction();

    // 1. Ambil data barang terlebih dahulu sebelum dihapus
    $stmtSelect = $pdo->prepare("SELECT kode_produk, nama_produk FROM produk WHERE id_produk = :id");
    $stmtSelect->execute([':id' => $id]);
    $produk = $stmtSelect->fetch();

    if (!$produk) {
        $pdo->rollBack();
        setFlash('danger', 'Barang tidak ditemukan atau sudah dihapus sebelumnya.');
        redirect('index.php');
    }

    // 2. Hapus data produk (Prepared Statement)
    $stmtDelete = $pdo->prepare("DELETE FROM produk WHERE id_produk = :id");
    $stmtDelete->execute([':id' => $id]);

    // 3. Catat riwayat penghapusan ke tabel log_aktivitas dalam satu transaksi
    $deskripsiLog = sprintf(
        "Menghapus barang inventaris: %s (Kode: %s)",
        $produk['nama_produk'],
        $produk['kode_produk']
    );
    $stmtLog = $pdo->prepare("INSERT INTO log_aktivitas (aksi, deskripsi) VALUES (:aksi, :deskripsi)");
    $stmtLog->execute([
        ':aksi'      => 'DELETE_PRODUK',
        ':deskripsi' => $deskripsiLog
    ]);

    // Commit transaksi jika semua langkah berhasil
    $pdo->commit();

    setFlash('success', 'Barang "' . $produk['nama_produk'] . '" berhasil dihapus dari inventaris.');
} catch (PDOException $e) {
    // Rollback jika terjadi kegagalan sistem
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    setFlash('danger', 'Gagal menghapus barang: ' . $e->getMessage());
}

redirect('index.php');
