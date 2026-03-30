<?php
/**
 * Profil Sayfasi
 */
require_once __DIR__ . '/includes/auth.php';
initSession();

if (!isLoggedIn()) {
    redirect(SITE_URL . '/login.php');
}

$user = currentUser();
$errors = [];
$passwordErrors = [];
$activeTab = $_GET['tab'] ?? 'profile';

// Profil guncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    if (!verifyCSRF($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Gecersiz istek.';
    } else {
        $result = updateProfile($_SESSION['user_id'], $_POST);
        if ($result['success']) {
            setFlash('success', 'Profiliniz basariyla guncellendi.');
            redirect(SITE_URL . '/profile.php');
        } else {
            $errors = $result['errors'];
        }
    }
}

// Sifre degistirme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    if (!verifyCSRF($_POST['csrf_token'] ?? '')) {
        $passwordErrors[] = 'Gecersiz istek.';
    } else {
        $result = changePassword(
            $_SESSION['user_id'],
            $_POST['current_password'] ?? '',
            $_POST['new_password'] ?? '',
            $_POST['confirm_password'] ?? ''
        );
        if ($result['success']) {
            setFlash('success', 'Sifreniz basariyla degistirildi.');
            redirect(SITE_URL . '/profile.php?tab=security');
        } else {
            $passwordErrors = $result['errors'];
            $activeTab = 'security';
        }
    }
}

$pageTitle = 'Profilim';
require_once __DIR__ . '/includes/header.php';
?>

<section class="page-header page-header-sm">
    <div class="container">
        <nav class="breadcrumb">
            <a href="<?= SITE_URL ?>">Ana Sayfa</a>
            <span>/</span>
            <span>Profilim</span>
        </nav>
        <h1>Profilim</h1>
    </div>
</section>

<section class="profile-section">
    <div class="container">
        <div class="profile-layout">
            <aside class="profile-sidebar">
                <div class="profile-card">
                    <div class="profile-avatar">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    <h3><?= sanitize($user['first_name'] . ' ' . $user['last_name']) ?></h3>
                    <p><?= sanitize($user['email']) ?></p>
                    <?php if ($user['email_verified']): ?>
                        <span class="verified-badge"><i class="fas fa-check-circle"></i> Dogrulanmis</span>
                    <?php else: ?>
                        <span class="unverified-badge"><i class="fas fa-exclamation-circle"></i> Dogrulanmamis</span>
                    <?php endif; ?>
                </div>
                <nav class="profile-nav">
                    <a href="?tab=profile" class="<?= $activeTab === 'profile' ? 'active' : '' ?>"><i class="fas fa-user"></i> Profil Bilgileri</a>
                    <a href="?tab=security" class="<?= $activeTab === 'security' ? 'active' : '' ?>"><i class="fas fa-lock"></i> Guvenlik</a>
                    <a href="<?= SITE_URL ?>/orders.php"><i class="fas fa-box"></i> Siparislerim</a>
                    <a href="<?= SITE_URL ?>/logout.php" class="nav-danger"><i class="fas fa-sign-out-alt"></i> Cikis Yap</a>
                </nav>
            </aside>

            <div class="profile-main">
                <?php if ($activeTab === 'profile'): ?>
                    <div class="profile-form-card">
                        <h2>Profil Bilgileri</h2>

                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-error">
                                <?php foreach ($errors as $err): ?>
                                    <p><?= $err ?></p>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="" id="profileForm">
                            <input type="hidden" name="csrf_token" value="<?= generateCSRF() ?>">
                            <input type="hidden" name="update_profile" value="1">

                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="first_name">Isim *</label>
                                    <input type="text" name="first_name" id="first_name" value="<?= sanitize($user['first_name']) ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="last_name">Soyisim *</label>
                                    <input type="text" name="last_name" id="last_name" value="<?= sanitize($user['last_name']) ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="email">E-posta</label>
                                    <input type="email" value="<?= sanitize($user['email']) ?>" disabled>
                                </div>
                                <div class="form-group">
                                    <label for="phone">Telefon *</label>
                                    <input type="tel" name="phone" id="phone" value="<?= sanitize($user['phone'] ?? '') ?>" required>
                                </div>
                                <div class="form-group form-group-full">
                                    <label for="address">Adres</label>
                                    <textarea name="address" id="address" rows="3"><?= sanitize($user['address'] ?? '') ?></textarea>
                                </div>
                                <div class="form-group">
                                    <label for="country">Ulke</label>
                                    <input type="text" name="country" id="country" value="<?= sanitize($user['country'] ?? 'Turkiye') ?>">
                                </div>
                                <div class="form-group">
                                    <label for="city">Sehir</label>
                                    <input type="text" name="city" id="city" value="<?= sanitize($user['city'] ?? '') ?>">
                                </div>
                                <div class="form-group">
                                    <label for="district">Ilce</label>
                                    <input type="text" name="district" id="district" value="<?= sanitize($user['district'] ?? '') ?>">
                                </div>
                                <div class="form-group">
                                    <label for="postal_code">Posta Kodu</label>
                                    <input type="text" name="postal_code" id="postal_code" value="<?= sanitize($user['postal_code'] ?? '') ?>">
                                </div>
                            </div>

                            <h3>Varsayilan Odeme Yontemi</h3>
                            <div class="form-group">
                                <select name="default_payment" id="default_payment" class="form-select">
                                    <option value="credit_card" <?= ($user['default_payment'] ?? '') === 'credit_card' ? 'selected' : '' ?>>Kredi Karti</option>
                                    <option value="bank_transfer" <?= ($user['default_payment'] ?? '') === 'bank_transfer' ? 'selected' : '' ?>>Banka Transferi</option>
                                    <option value="crypto" <?= ($user['default_payment'] ?? '') === 'crypto' ? 'selected' : '' ?>>Kripto Para</option>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-primary">Kaydet</button>
                        </form>
                    </div>

                <?php elseif ($activeTab === 'security'): ?>
                    <div class="profile-form-card">
                        <h2>Sifre Degistir</h2>

                        <?php if (!empty($passwordErrors)): ?>
                            <div class="alert alert-error">
                                <?php foreach ($passwordErrors as $err): ?>
                                    <p><?= $err ?></p>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="" id="passwordForm">
                            <input type="hidden" name="csrf_token" value="<?= generateCSRF() ?>">
                            <input type="hidden" name="change_password" value="1">

                            <div class="form-group">
                                <label for="current_password">Mevcut Sifre *</label>
                                <div class="input-icon">
                                    <i class="fas fa-lock"></i>
                                    <input type="password" name="current_password" id="current_password" required>
                                    <button type="button" class="toggle-password" onclick="togglePassword('current_password')"><i class="fas fa-eye"></i></button>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="new_password">Yeni Sifre *</label>
                                <div class="input-icon">
                                    <i class="fas fa-lock"></i>
                                    <input type="password" name="new_password" id="new_password" required minlength="6">
                                    <button type="button" class="toggle-password" onclick="togglePassword('new_password')"><i class="fas fa-eye"></i></button>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="confirm_password">Yeni Sifre Tekrar *</label>
                                <div class="input-icon">
                                    <i class="fas fa-lock"></i>
                                    <input type="password" name="confirm_password" id="confirm_password" required>
                                    <button type="button" class="toggle-password" onclick="togglePassword('confirm_password')"><i class="fas fa-eye"></i></button>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">Sifreyi Degistir</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
