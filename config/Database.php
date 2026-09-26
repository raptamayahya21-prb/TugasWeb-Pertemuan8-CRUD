<?php
/**
 * Class Database (Singleton Pattern)
 * Memastikan hanya ada satu instance koneksi PDO yang aktif di seluruh aplikasi.
 */

class Database {
    private static ?PDO $instance = null;

    // Konfigurasi Database (Mendukung port custom XAMPP 8111 dengan fallback 3306)
    private const DB_HOST = '127.0.0.1';
    private const DB_NAME = 'inventaris_db';
    private const DB_USER = 'root';
    private const DB_PASS = '';
    private const DB_PORTS = [8111, 3306];

    /**
     * Private constructor untuk mencegah instansiasi langsung via `new Database()`
     */
    private function __construct() {}

    /**
     * Mencegah penggandaan objek melalui clone
     */
    private function __clone() {}

    /**
     * Mencegah deserialisasi objek
     */
    public function __wakeup() {
        throw new Exception("Tidak diizinkan melakukan unserialize pada kelas Singleton.");
    }

    /**
     * Mengambil instance tunggal koneksi PDO
     * @return PDO
     */
    public static function getInstance(): PDO {
        if (self::$instance === null) {
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            $lastException = null;

            foreach (self::DB_PORTS as $port) {
                try {
                    $dsn = sprintf(
                        "mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4",
                        self::DB_HOST,
                        $port,
                        self::DB_NAME
                    );
                    self::$instance = new PDO($dsn, self::DB_USER, self::DB_PASS, $options);
                    break; // Berhasil terhubung
                } catch (PDOException $e) {
                    $lastException = $e;
                }
            }

            if (self::$instance === null) {
                die("<strong>Kesalahan Koneksi Database:</strong> " . htmlspecialchars($lastException?->getMessage() ?? 'Gagal terhubung', ENT_QUOTES, 'UTF-8'));
            }
        }

        return self::$instance;
    }
}
