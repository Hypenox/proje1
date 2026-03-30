<?php
/**
 * Kimlik Dogrulama Islemleri
 */

require_once __DIR__ . '/functions.php';

// Kullanici kaydi
function registerUser(array $data): array {
    $db = getDB();

    // Dogrulama
    $errors = [];
    if (empty($data['first_name'])) $errors[] = 'Isim zorunludur.';
    if (empty($data['last_name'])) $errors[] = 'Soyisim zorunludur.';
    if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Gecerli bir e-posta adresi girin.';
    if (empty($data['password']) || strlen($data['password']) < 6) $errors[] = 'Sifre en az 6 karakter olmalidir.';
    if ($data['password'] !== ($data['password_confirm'] ?? '')) $errors[] = 'Sifreler eslesmiyor.';
    if (empty($data['phone'])) $errors[] = 'Telefon numarasi zorunludur.';

    if (!empty($errors)) return ['success' => false, 'errors' => $errors];

    // E-posta kontrol
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$data['email']]);
    if ($stmt->fetch()) {
        return ['success' => false, 'errors' => ['Bu e-posta adresi zaten kayitli.']];
    }

    // Dogrulama tokeni
    $token = bin2hex(random_bytes(32));

    // Kayit
    $stmt = $db->prepare("INSERT INTO users (first_name, last_name, email, password, phone, address, postal_code, city, district, country, verification_token) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        sanitize($data['first_name']),
        sanitize($data['last_name']),
        $data['email'],
        password_hash($data['password'], PASSWORD_DEFAULT),
        sanitize($data['phone']),
        sanitize($data['address'] ?? ''),
        sanitize($data['postal_code'] ?? ''),
        sanitize($data['city'] ?? ''),
        sanitize($data['district'] ?? ''),
        sanitize($data['country'] ?? 'Turkiye'),
        $token,
    ]);

    $userId = (int) $db->lastInsertId();

    // Dogrulama maili gonder
    sendVerificationEmail($data['email'], $token, $data['first_name']);

    // Misafir sepetini aktar
    mergeGuestCart($userId);

    return ['success' => true, 'user_id' => $userId];
}

// Giris
function loginUser(string $email, string $password): array {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        return ['success' => false, 'errors' => ['E-posta veya sifre hatali.']];
    }

    // Oturumu ayarla
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];

    // Misafir sepetini aktar
    mergeGuestCart($user['id']);

    return ['success' => true, 'user' => $user];
}

// Cikis
function logoutUser(): void {
    session_destroy();
    redirect(SITE_URL . '/login.php');
}

// E-posta dogrula
function verifyEmail(string $token): bool {
    $db = getDB();
    $stmt = $db->prepare("UPDATE users SET email_verified = 1, verification_token = NULL WHERE verification_token = ? AND email_verified = 0");
    $stmt->execute([$token]);
    return $stmt->rowCount() > 0;
}

// Profil guncelle
function updateProfile(int $userId, array $data): array {
    $db = getDB();
    $errors = [];

    if (empty($data['first_name'])) $errors[] = 'Isim zorunludur.';
    if (empty($data['last_name'])) $errors[] = 'Soyisim zorunludur.';
    if (empty($data['phone'])) $errors[] = 'Telefon numarasi zorunludur.';

    if (!empty($errors)) return ['success' => false, 'errors' => $errors];

    $stmt = $db->prepare("UPDATE users SET first_name = ?, last_name = ?, phone = ?, address = ?, postal_code = ?, city = ?, district = ?, country = ?, default_payment = ? WHERE id = ?");
    $stmt->execute([
        sanitize($data['first_name']),
        sanitize($data['last_name']),
        sanitize($data['phone']),
        sanitize($data['address'] ?? ''),
        sanitize($data['postal_code'] ?? ''),
        sanitize($data['city'] ?? ''),
        sanitize($data['district'] ?? ''),
        sanitize($data['country'] ?? 'Turkiye'),
        $data['default_payment'] ?? 'credit_card',
        $userId,
    ]);

    // Oturum bilgilerini guncelle
    $_SESSION['user_name'] = sanitize($data['first_name']) . ' ' . sanitize($data['last_name']);

    return ['success' => true];
}

// Sifre degistir
function changePassword(int $userId, string $currentPass, string $newPass, string $confirmPass): array {
    $db = getDB();
    $errors = [];

    if (strlen($newPass) < 6) $errors[] = 'Yeni sifre en az 6 karakter olmalidir.';
    if ($newPass !== $confirmPass) $errors[] = 'Sifreler eslesmiyor.';

    if (!empty($errors)) return ['success' => false, 'errors' => $errors];

    $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($currentPass, $user['password'])) {
        return ['success' => false, 'errors' => ['Mevcut sifreniz hatali.']];
    }

    $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->execute([password_hash($newPass, PASSWORD_DEFAULT), $userId]);

    return ['success' => true];
}
