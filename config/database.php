<?php
/**
 * Veritabani Baglanti Ayarlari
 * XAMPP / phpMyAdmin uyumlu
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'techstore');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Site ayarlari
define('SITE_URL', 'http://localhost/proje1');
define('SITE_NAME', 'TechStore');
define('UPLOAD_DIR', __DIR__ . '/../assets/images/uploads/');
define('UPLOAD_URL', SITE_URL . '/assets/images/uploads/');

// E-posta ayarlari (SMTP)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', '');
define('SMTP_PASS', '');
define('SMTP_FROM', 'noreply@techstore.com');
define('SMTP_FROM_NAME', 'TechStore');

// Oturum ayarlari
define('SESSION_LIFETIME', 86400); // 24 saat

// PDO baglantisi
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die('Veritabani baglanti hatasi: ' . $e->getMessage());
        }
    }
    return $pdo;
}
