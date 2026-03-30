<?php
/**
 * Siparis Tamamlama Sayfasi
 */
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
initSession();

$db = getDB();
$cartItems = getCartItems();
$cartTotal = getCartTotal();

// Sepet bos ise geri don
if (empty($cartItems)) {
    setFlash('error', 'Sepetiniz bos.');
    redirect(SITE_URL . '/cart.php');
}

// Giris yapmamissa yonlendir
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = SITE_URL . '/checkout.php';
    setFlash('info', 'Siparisi tamamlamak icin lutfen giris yapin veya kayit olun.');
    redirect(SITE_URL . '/login.php');
}

$user = currentUser();

// Siparis islemi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRF($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Gecersiz istek.');
        redirect(SITE_URL . '/checkout.php');
    }

    $errors = [];
    $firstName = sanitize($_POST['shipping_first_name'] ?? '');
    $lastName = sanitize($_POST['shipping_last_name'] ?? '');
    $phone = sanitize($_POST['shipping_phone'] ?? '');
    $address = sanitize($_POST['shipping_address'] ?? '');
    $city = sanitize($_POST['shipping_city'] ?? '');
    $district = sanitize($_POST['shipping_district'] ?? '');
    $postalCode = sanitize($_POST['shipping_postal_code'] ?? '');
    $country = sanitize($_POST['shipping_country'] ?? 'Turkiye');
    $paymentMethod = $_POST['payment_method'] ?? 'credit_card';
    $notes = sanitize($_POST['notes'] ?? '');

    if (!$firstName) $errors[] = 'Isim zorunludur.';
    if (!$lastName) $errors[] = 'Soyisim zorunludur.';
    if (!$phone) $errors[] = 'Telefon zorunludur.';
    if (!$address) $errors[] = 'Adres zorunludur.';
    if (!$city) $errors[] = 'Sehir zorunludur.';

    if (empty($errors)) {
        try {
            $db->beginTransaction();

            $orderNumber = generateOrderNumber();
            $taxRate = (float) (getSetting('tax_rate') ?? 20) / 100;
            $subtotal = $cartTotal;
            $tax = $subtotal * $taxRate;
            $total = $subtotal + $tax;

            // Siparis olustur
            $stmt = $db->prepare("INSERT INTO orders (user_id, order_number, subtotal, shipping_cost, tax, total, status, payment_method, shipping_first_name, shipping_last_name, shipping_phone, shipping_address, shipping_city, shipping_district, shipping_postal_code, shipping_country, notes) VALUES (?, ?, ?, 0, ?, ?, 'pending', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $_SESSION['user_id'], $orderNumber, $subtotal, $tax, $total,
                $paymentMethod, $firstName, $lastName, $phone, $address, $city, $district, $postalCode, $country, $notes
            ]);
            $orderId = $db->lastInsertId();

            // Siparis kalemlerini ekle
            foreach ($cartItems as $item) {
                $itemTotal = $item['price'] * $item['quantity'];
                $stmt = $db->prepare("INSERT INTO order_items (order_id, product_id, product_name, quantity, price, total) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$orderId, $item['product_id'], $item['name'], $item['quantity'], $item['price'], $itemTotal]);

                // Stok dusur
                $db->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?")->execute([$item['quantity'], $item['product_id'], $item['quantity']]);
            }

            // Sepeti temizle
            $db->prepare("DELETE FROM cart_items WHERE user_id = ?")->execute([$_SESSION['user_id']]);

            $db->commit();

            setFlash('success', 'Siparisiz basariyla olusturuldu! Siparis No: ' . $orderNumber);
            redirect(SITE_URL . '/orders.php');
        } catch (Exception $e) {
            $db->rollBack();
            $errors[] = 'Siparis olusturulurken bir hata olustu. Lutfen tekrar deneyin.';
        }
    }
}

$pageTitle = 'Siparis Tamamla';
require_once __DIR__ . '/includes/header.php';
?>

<section class="page-header page-header-sm">
    <div class="container">
        <nav class="breadcrumb">
            <a href="<?= SITE_URL ?>">Ana Sayfa</a>
            <span>/</span>
            <a href="<?= SITE_URL ?>/cart.php">Sepetim</a>
            <span>/</span>
            <span>Siparis Tamamla</span>
        </nav>
        <h1>Siparis Tamamla</h1>
    </div>
</section>

<section class="checkout-section">
    <div class="container">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <ul>
                    <?php foreach ($errors as $err): ?>
                        <li><?= $err ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="checkout-form" id="checkoutForm">
            <input type="hidden" name="csrf_token" value="<?= generateCSRF() ?>">

            <div class="checkout-layout">
                <div class="checkout-fields">
                    <h3>Teslimat Bilgileri</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="shipping_first_name">Isim *</label>
                            <input type="text" name="shipping_first_name" id="shipping_first_name" value="<?= sanitize($user['first_name'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="shipping_last_name">Soyisim *</label>
                            <input type="text" name="shipping_last_name" id="shipping_last_name" value="<?= sanitize($user['last_name'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="shipping_phone">Telefon *</label>
                            <input type="tel" name="shipping_phone" id="shipping_phone" value="<?= sanitize($user['phone'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="shipping_country">Ulke *</label>
                            <input type="text" name="shipping_country" id="shipping_country" value="<?= sanitize($user['country'] ?? 'Turkiye') ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="shipping_city">Sehir *</label>
                            <input type="text" name="shipping_city" id="shipping_city" value="<?= sanitize($user['city'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="shipping_district">Ilce</label>
                            <input type="text" name="shipping_district" id="shipping_district" value="<?= sanitize($user['district'] ?? '') ?>">
                        </div>
                        <div class="form-group form-group-full">
                            <label for="shipping_address">Adres *</label>
                            <textarea name="shipping_address" id="shipping_address" rows="3" required><?= sanitize($user['address'] ?? '') ?></textarea>
                        </div>
                        <div class="form-group">
                            <label for="shipping_postal_code">Posta Kodu</label>
                            <input type="text" name="shipping_postal_code" id="shipping_postal_code" value="<?= sanitize($user['postal_code'] ?? '') ?>">
                        </div>
                    </div>

                    <h3>Odeme Yontemi</h3>
                    <div class="payment-options">
                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="credit_card" <?= ($user['default_payment'] ?? 'credit_card') === 'credit_card' ? 'checked' : '' ?>>
                            <div class="payment-option-content">
                                <i class="fas fa-credit-card"></i>
                                <span>Kredi Karti</span>
                            </div>
                        </label>
                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="bank_transfer" <?= ($user['default_payment'] ?? '') === 'bank_transfer' ? 'checked' : '' ?>>
                            <div class="payment-option-content">
                                <i class="fas fa-university"></i>
                                <span>Banka Transferi</span>
                            </div>
                        </label>
                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="crypto" <?= ($user['default_payment'] ?? '') === 'crypto' ? 'checked' : '' ?>>
                            <div class="payment-option-content">
                                <i class="fab fa-bitcoin"></i>
                                <span>Kripto Para</span>
                            </div>
                        </label>
                    </div>

                    <h3>Siparis Notu</h3>
                    <div class="form-group">
                        <textarea name="notes" rows="3" placeholder="Siparisiz icin eklemek istediginiz not..."></textarea>
                    </div>
                </div>

                <div class="checkout-summary">
                    <h3>Siparis Ozeti</h3>
                    <div class="checkout-items">
                        <?php foreach ($cartItems as $item): ?>
                            <div class="checkout-item">
                                <div class="checkout-item-info">
                                    <span class="checkout-item-name"><?= sanitize($item['name']) ?></span>
                                    <span class="checkout-item-qty">x<?= $item['quantity'] ?></span>
                                </div>
                                <span class="checkout-item-price"><?= formatPrice($item['price'] * $item['quantity']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <hr>
                    <div class="summary-row">
                        <span>Ara Toplam</span>
                        <span><?= formatPrice($cartTotal) ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Kargo</span>
                        <span class="free-shipping">Ucretsiz</span>
                    </div>
                    <div class="summary-row">
                        <span>KDV (%<?= getSetting('tax_rate') ?? '20' ?>)</span>
                        <span><?= formatPrice($cartTotal * ((float)(getSetting('tax_rate') ?? 20) / 100)) ?></span>
                    </div>
                    <hr>
                    <div class="summary-row summary-total">
                        <span>Toplam</span>
                        <span><?= formatPrice($cartTotal + $cartTotal * ((float)(getSetting('tax_rate') ?? 20) / 100)) ?></span>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block btn-lg">
                        <i class="fas fa-lock"></i> Siparisi Onayla
                    </button>
                    <p class="checkout-secure"><i class="fas fa-shield-alt"></i> 256-bit SSL ile guvenli odeme</p>
                </div>
            </div>
        </form>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
