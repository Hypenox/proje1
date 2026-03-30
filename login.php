<?php
/**
 * Giris Sayfasi
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
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!$email || !$password) {
            $errors[] = 'E-posta ve sifre zorunludur.';
        } else {
            $result = loginUser($email, $password);
            if ($result['success']) {
                $redirectUrl = $_SESSION['redirect_after_login'] ?? SITE_URL;
                unset($_SESSION['redirect_after_login']);
                setFlash('success', 'Basariyla giris yapildi!');
                redirect($redirectUrl);
            } else {
                $errors = $result['errors'];
            }
        }
    }
}

$pageTitle = 'Giris Yap';
require_once __DIR__ . '/includes/header.php';
?>

<section class="auth-section">
    <div class="container">
        <div class="auth-card">
            <div class="auth-header">
                <h1>Giris Yap</h1>
                <p>Hesabiniza giris yapin</p>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <?php foreach ($errors as $err): ?>
                        <p><?= $err ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="auth-form" id="loginForm" novalidate>
                <input type="hidden" name="csrf_token" value="<?= generateCSRF() ?>">

                <div class="form-group">
                    <label for="email">E-posta Adresi</label>
                    <div class="input-icon">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" id="email" placeholder="ornek@email.com" value="<?= sanitize($_POST['email'] ?? '') ?>" required>
                    </div>
                    <span class="form-error" id="emailError"></span>
                </div>

                <div class="form-group">
                    <label for="password">Sifre</label>
                    <div class="input-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" id="password" placeholder="Sifreniz" required>
                        <button type="button" class="toggle-password" onclick="togglePassword('password')"><i class="fas fa-eye"></i></button>
                    </div>
                    <span class="form-error" id="passwordError"></span>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg">Giris Yap</button>
            </form>

            <div class="auth-footer">
                <p>Hesabiniz yok mu? <a href="<?= SITE_URL ?>/register.php">Kayit Olun</a></p>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
