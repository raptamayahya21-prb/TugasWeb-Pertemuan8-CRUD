<?php
/**
 * Helper Functions & Utilitas Keamanan Aplikasi Inventaris
 */

// Inisialisasi session jika belum berjalan (diperlukan untuk flash message)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Sanitasi output teks untuk mencegah Cross-Site Scripting (XSS)
 * Memenuhi Requirement: "Output HTML pakai htmlspecialchars()"
 *
 * @param string|null $value
 * @return string
 */
function e(?string $value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Menyimpan notifikasi Flash Message ke dalam session (PRG Pattern)
 * Memenuhi Requirement: "Flash message sukses/gagal (redirect pattern)"
 *
 * @param string $type 'success' | 'danger' | 'warning' | 'info'
 * @param string $message Pesan notifikasi
 */
function setFlash(string $type, string $message): void {
    $_SESSION['flash_message'] = [
        'type'    => $type,
        'message' => $message
    ];
}

/**
 * Mengambil dan langsung menghapus Flash Message (sekali tayang)
 *
 * @return string HTML tag alert siap render
 */
function displayFlash(): string {
    if (isset($_SESSION['flash_message'])) {
        $flash = $_SESSION['flash_message'];
        $type = e($flash['type']);
        $message = e($flash['message']);
        unset($_SESSION['flash_message']);

        $iconSvg = '';
        if ($type === 'success') {
            $iconSvg = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>';
        } elseif ($type === 'danger' || $type === 'error') {
            $type = 'danger';
            $iconSvg = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>';
        } else {
            $iconSvg = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>';
        }

        return sprintf(
            '<div class="alert alert-%s" role="alert">
                <div class="alert-content">
                    <span class="alert-icon">%s</span>
                    <span class="alert-text">%s</span>
                </div>
                <button type="button" class="alert-close" onclick="this.closest(\'.alert\').remove()" aria-label="Tutup notifikasi">&times;</button>
            </div>',
            $type,
            $iconSvg,
            $message
        );
    }
    return '';
}

/**
 * Mengalihkan halaman (Redirect) dan menghentikan eksekusi script
 *
 * @param string $url
 */
function redirect(string $url): void {
    header("Location: " . $url);
    exit;
}

/**
 * Format angka ke format mata uang Rupiah
 *
 * @param float|int|string $angka
 * @return string Contoh: "Rp 19.500.000"
 */
function formatRupiah(float|int|string $angka): string {
    return 'Rp ' . number_format((float)$angka, 0, ',', '.');
}

/**
 * Format tanggal Indonesia ramah pengguna
 *
 * @param string|null $dateString
 * @return string Contoh: "26 Sep 2026, 15:30"
 */
function formatTanggal(?string $dateString): string {
    if (!$dateString) return '-';
    $timestamp = strtotime($dateString);
    if (!$timestamp) return '-';
    return date('d M Y, H:i', $timestamp);
}
