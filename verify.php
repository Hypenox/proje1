<?php
/**
 * E-posta Dogrulama Sayfasi
 */
require_once __DIR__ . '/includes/auth.php';
initSession();

$token = $_GET['token'] ?? '';

if ($token && verifyEmail($token)) {
    setFlash('success', 'E-posta adresiniz basariyla dogrulandi! Artik giris yapabilirsiniz.');
} else {
    setFlash('error', 'Gecersiz veya suresi dolmus dogrulama linki.');
}

redirect(SITE_URL . '/login.php');
