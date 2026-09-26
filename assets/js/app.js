/**
 * JavaScript Pendukung Aplikasi InventarisPro
 * - Auto-dismiss flash message
 * - Konfirmasi penghapusan data
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Auto-dismiss Flash Message setelah 4 detik
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach((alert) => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-8px)';
            setTimeout(() => alert.remove(), 500);
        }, 4000);
    });

    // 2. Event listener untuk form pencarian (hilangkan spasi berlebih)
    const searchInput = document.querySelector('.input-search');
    if (searchInput) {
        searchInput.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                searchInput.value = '';
                searchInput.closest('form').submit();
            }
        });
    }
});

/**
 * Konfirmasi dialog penghapusan barang
 * @param {string} itemName Nama barang yang akan dihapus
 * @returns {boolean}
 */
function confirmDelete(itemName) {
    return confirm(`Apakah Anda yakin ingin menghapus barang: "${itemName}"?\nTindakan ini tidak dapat dibatalkan.`);
}
