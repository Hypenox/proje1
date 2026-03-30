<?php
/**
 * Kayit Sayfasi
 */
require_once __DIR__ . '/includes/auth.php';
initSession();

if (isLoggedIn()) {
    redirect(SITE_URL . '/profile.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRF($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Gecersiz istek.';
    } else {
        $result = registerUser($_POST);
        if ($result['success']) {
            setFlash('success', 'Kayit basarili! E-posta adresinize dogrulama linki gonderildi. Lutfen e-postanizi kontrol edin.');
            redirect(SITE_URL . '/login.php');
        } else {
            $errors = $result['errors'];
        }
    }
}

$pageTitle = 'Kayit Ol';
require_once __DIR__ . '/includes/header.php';
?>

<section class="auth-section">
    <div class="container">
        <div class="auth-card auth-card-wide">
            <div class="auth-header">
                <h1>Kayit Ol</h1>
                <p>Yeni hesap olusturun</p>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <?php foreach ($errors as $err): ?>
                        <p><?= $err ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="auth-form" id="registerForm" novalidate>
                <input type="hidden" name="csrf_token" value="<?= generateCSRF() ?>">

                <div class="form-grid">
                    <div class="form-group">
                        <label for="first_name">Isim *</label>
                        <div class="input-icon">
                            <i class="fas fa-user"></i>
                            <input type="text" name="first_name" id="first_name" placeholder="Adiniz" value="<?= sanitize($_POST['first_name'] ?? '') ?>" required>
                        </div>
                        <span class="form-error" id="firstNameError"></span>
                    </div>

                    <div class="form-group">
                        <label for="last_name">Soyisim *</label>
                        <div class="input-icon">
                            <i class="fas fa-user"></i>
                            <input type="text" name="last_name" id="last_name" placeholder="Soyadiniz" value="<?= sanitize($_POST['last_name'] ?? '') ?>" required>
                        </div>
                        <span class="form-error" id="lastNameError"></span>
                    </div>

                    <div class="form-group">
                        <label for="email">E-posta Adresi *</label>
                        <div class="input-icon">
                            <i class="fas fa-envelope"></i>
                            <input type="email" name="email" id="email" placeholder="ornek@email.com" value="<?= sanitize($_POST['email'] ?? '') ?>" required>
                        </div>
                        <span class="form-error" id="emailError"></span>
                    </div>

                    <div class="form-group">
                        <label for="phone">Telefon *</label>
                        <div class="input-icon">
                            <i class="fas fa-phone"></i>
                            <input type="tel" name="phone" id="phone" placeholder="05XX XXX XXXX" value="<?= sanitize($_POST['phone'] ?? '') ?>" required>
                        </div>
                        <span class="form-error" id="phoneError"></span>
                    </div>

                    <div class="form-group">
                        <label for="password">Sifre *</label>
                        <div class="input-icon">
                            <i class="fas fa-lock"></i>
                            <input type="password" name="password" id="password" placeholder="En az 6 karakter" required minlength="6">
                            <button type="button" class="toggle-password" onclick="togglePassword('password')"><i class="fas fa-eye"></i></button>
                        </div>
                        <span class="form-error" id="passwordError"></span>
                    </div>

                    <div class="form-group">
                        <label for="password_confirm">Sifre Tekrar *</label>
                        <div class="input-icon">
                            <i class="fas fa-lock"></i>
                            <input type="password" name="password_confirm" id="password_confirm" placeholder="Sifrenizi tekrar girin" required>
                        </div>
                        <span class="form-error" id="passwordConfirmError"></span>
                    </div>

                    <div class="form-group form-group-full">
                        <label for="address">Adres</label>
                        <div class="input-icon">
                            <i class="fas fa-map-marker-alt"></i>
                            <input type="text" name="address" id="address" placeholder="Acik adresiniz" value="<?= sanitize($_POST['address'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="country">Ulke</label>
                        <div class="input-icon">
                            <i class="fas fa-globe"></i>
                            <input type="text" name="country" id="country" placeholder="Ulke" value="<?= sanitize($_POST['country'] ?? 'Turkiye') ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="city">Sehir</label>
                        <div class="input-icon">
                            <i class="fas fa-city"></i>
                            <input type="text" name="city" id="city" placeholder="Sehir" value="<?= sanitize($_POST['city'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="district">Ilce</label>
                        <div class="input-icon">
                            <i class="fas fa-map"></i>
                            <input type="text" name="district" id="district" placeholder="Ilce" value="<?= sanitize($_POST['district'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="postal_code">Posta Kodu</label>
                        <div class="input-icon">
                            <i class="fas fa-mail-bulk"></i>
                            <input type="text" name="postal_code" id="postal_code" placeholder="Posta Kodu" value="<?= sanitize($_POST['postal_code'] ?? '') ?>">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="terms" required>
                        <span><a href="#" target="_blank">Kullanim Sartlari</a>'ni ve <a href="#" target="_blank">Gizlilik Politikasi</a>'ni okudum ve kabul ediyorum.</span>
                    </label>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg">Kayit Ol</button>
            </form>

            <div class="auth-footer">
                <p>Zaten hesabiniz var mi? <a href="<?= SITE_URL ?>/login.php">Giris Yapin</a></p>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
