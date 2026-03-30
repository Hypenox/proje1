<?php
/**
 * Genel Yardimci Fonksiyonlar
 */

require_once __DIR__ . '/../config/database.php';

// Oturumu baslat
function initSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

// CSRF token olustur
function generateCSRF(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// CSRF token dogrula
function verifyCSRF(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Girdi temizleme
function sanitize(string $input): string {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Slug olustur
function createSlug(string $text): string {
    $tr = ['ş'=>'s','Ş'=>'S','ı'=>'i','İ'=>'I','ç'=>'c','Ç'=>'C','ü'=>'u','Ü'=>'U','ö'=>'o','Ö'=>'O','ğ'=>'g','Ğ'=>'G'];
    $text = strtr($text, $tr);
    $text = mb_strtolower($text, 'UTF-8');
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim($text, '-');
}

// Fiyat formatlama
function formatPrice(float $price): string {
    return number_format($price, 2, ',', '.') . ' TL';
}

// Indirim yuzdesi hesapla
function calcDiscount(float $oldPrice, float $newPrice): int {
    if ($oldPrice <= 0) return 0;
    return (int) round((($oldPrice - $newPrice) / $oldPrice) * 100);
}

// Flash mesaj ayarla
function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

// Flash mesaj goster
function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// Kullanici giris kontrol
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

// Admin kontrol
function isAdmin(): bool {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

// Mevcut kullanici bilgileri
function currentUser(): ?array {
    if (!isLoggedIn()) return null;
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}

// Sepet urun sayisi
function getCartCount(): int {
    $db = getDB();
    if (isLoggedIn()) {
        $stmt = $db->prepare("SELECT COALESCE(SUM(quantity), 0) FROM cart_items WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
    } else {
        $stmt = $db->prepare("SELECT COALESCE(SUM(quantity), 0) FROM cart_items WHERE session_id = ?");
        $stmt->execute([session_id()]);
    }
    return (int) $stmt->fetchColumn();
}

// Sepet toplam tutar
function getCartTotal(): float {
    $db = getDB();
    if (isLoggedIn()) {
        $stmt = $db->prepare("SELECT COALESCE(SUM(ci.quantity * p.price), 0) FROM cart_items ci JOIN products p ON ci.product_id = p.id WHERE ci.user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
    } else {
        $stmt = $db->prepare("SELECT COALESCE(SUM(ci.quantity * p.price), 0) FROM cart_items ci JOIN products p ON ci.product_id = p.id WHERE ci.session_id = ?");
        $stmt->execute([session_id()]);
    }
    return (float) $stmt->fetchColumn();
}

// Sepet urunleri getir
function getCartItems(): array {
    $db = getDB();
    if (isLoggedIn()) {
        $stmt = $db->prepare("SELECT ci.*, p.name, p.slug, p.price, p.old_price, p.image, p.stock FROM cart_items ci JOIN products p ON ci.product_id = p.id WHERE ci.user_id = ? ORDER BY ci.created_at DESC");
        $stmt->execute([$_SESSION['user_id']]);
    } else {
        $stmt = $db->prepare("SELECT ci.*, p.name, p.slug, p.price, p.old_price, p.image, p.stock FROM cart_items ci JOIN products p ON ci.product_id = p.id WHERE ci.session_id = ? ORDER BY ci.created_at DESC");
        $stmt->execute([session_id()]);
    }
    return $stmt->fetchAll();
}

// Misafir sepetini kullaniciya aktar
function mergeGuestCart(int $userId): void {
    $db = getDB();
    $sessionId = session_id();
    $stmt = $db->prepare("SELECT * FROM cart_items WHERE session_id = ?");
    $stmt->execute([$sessionId]);
    $guestItems = $stmt->fetchAll();

    foreach ($guestItems as $item) {
        $check = $db->prepare("SELECT id, quantity FROM cart_items WHERE user_id = ? AND product_id = ?");
        $check->execute([$userId, $item['product_id']]);
        $existing = $check->fetch();

        if ($existing) {
            $update = $db->prepare("UPDATE cart_items SET quantity = quantity + ? WHERE id = ?");
            $update->execute([$item['quantity'], $existing['id']]);
        } else {
            $insert = $db->prepare("UPDATE cart_items SET user_id = ?, session_id = NULL WHERE id = ?");
            $insert->execute([$userId, $item['id']]);
        }
    }
    $db->prepare("DELETE FROM cart_items WHERE session_id = ?")->execute([$sessionId]);
}

// Siparis numarasi olustur
function generateOrderNumber(): string {
    return 'TS-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

// Site ayarlarini getir
function getSetting(string $key): ?string {
    $db = getDB();
    $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $result = $stmt->fetchColumn();
    return $result !== false ? $result : null;
}

// Gorsel yukle
function uploadImage(array $file, string $folder = ''): ?string {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $maxSize = 5 * 1024 * 1024; // 5MB

    if ($file['error'] !== UPLOAD_ERR_OK) return null;
    if (!in_array($file['type'], $allowedTypes)) return null;
    if ($file['size'] > $maxSize) return null;

    $uploadDir = UPLOAD_DIR . ($folder ? $folder . '/' : '');
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $ext;
    $filepath = $uploadDir . $filename;

    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ($folder ? $folder . '/' : '') . $filename;
    }
    return null;
}

// E-posta gonder (basit)
function sendMail(string $to, string $subject, string $body): bool {
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM . ">\r\n";
    return @mail($to, $subject, $body, $headers);
}

// Dogrulama e-postasi gonder
function sendVerificationEmail(string $email, string $token, string $name): bool {
    $verifyUrl = SITE_URL . '/verify.php?token=' . urlencode($token);
    $subject = 'E-posta Dogrulama - ' . SITE_NAME;
    $body = '
    <!DOCTYPE html>
    <html>
    <head><meta charset="UTF-8"></head>
    <body style="font-family:Arial,sans-serif;background:#f4f4f4;padding:20px;">
        <div style="max-width:600px;margin:0 auto;background:#fff;border-radius:12px;padding:40px;box-shadow:0 2px 10px rgba(0,0,0,0.1);">
            <h1 style="color:#6C3CE1;text-align:center;">TechStore</h1>
            <h2 style="color:#333;">Merhaba ' . sanitize($name) . ',</h2>
            <p style="color:#666;font-size:16px;">Hesabinizi dogrulamak icin asagidaki butona tiklayin:</p>
            <div style="text-align:center;margin:30px 0;">
                <a href="' . $verifyUrl . '" style="background:linear-gradient(135deg,#6C3CE1,#4A1FB8);color:#fff;padding:14px 40px;text-decoration:none;border-radius:8px;font-size:16px;display:inline-block;">Hesabimi Dogrula</a>
            </div>
            <p style="color:#999;font-size:13px;">Bu link 24 saat icerisinde gecerliliğini yitirecektir.</p>
            <hr style="border:none;border-top:1px solid #eee;margin:30px 0;">
            <p style="color:#999;font-size:12px;text-align:center;">Bu e-postayi siz talep etmediyseniz, lutfen dikkate almayin.</p>
        </div>
    </body>
    </html>';
    return sendMail($email, $subject, $body);
}

// Sayfalama
function paginate(int $total, int $perPage, int $currentPage): array {
    $totalPages = max(1, (int) ceil($total / $perPage));
    $currentPage = max(1, min($currentPage, $totalPages));
    $offset = ($currentPage - 1) * $perPage;
    return [
        'total' => $total,
        'per_page' => $perPage,
        'current_page' => $currentPage,
        'total_pages' => $totalPages,
        'offset' => $offset,
    ];
}

// Yonlendirme
function redirect(string $url): void {
    header("Location: $url");
    exit;
}

// JSON yanit
function jsonResponse(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
