<?php
/**
 * Sepet Sayfasi
 */
$pageTitle = 'Sepetim';
require_once __DIR__ . '/includes/header.php';

$cartItems = getCartItems();
$cartTotal = getCartTotal();
?>

<section class="page-header page-header-sm">
    <div class="container">
        <nav class="breadcrumb">
            <a href="<?= SITE_URL ?>">Ana Sayfa</a>
            <span>/</span>
            <span>Sepetim</span>
        </nav>
        <h1>Sepetim</h1>
    </div>
</section>

<section class="cart-section">
    <div class="container">
        <?php if (empty($cartItems)): ?>
            <div class="empty-state">
                <i class="fas fa-shopping-cart"></i>
                <h3>Sepetiniz Bos</h3>
                <p>Henuz sepetinize urun eklemediniz.</p>
                <a href="<?= SITE_URL ?>/products.php" class="btn btn-primary">Alisverise Basla</a>
            </div>
        <?php else: ?>
            <div class="cart-layout">
                <div class="cart-items">
                    <div class="cart-table">
                        <div class="cart-header-row">
                            <span>Urun</span>
                            <span>Fiyat</span>
                            <span>Adet</span>
                            <span>Toplam</span>
                            <span></span>
                        </div>
                        <?php foreach ($cartItems as $item): ?>
                            <div class="cart-row" id="cartRow-<?= $item['id'] ?>">
                                <div class="cart-product">
                                    <div class="cart-product-image">
                                        <?php if ($item['image']): ?>
                                            <img src="<?= UPLOAD_URL . $item['image'] ?>" alt="<?= sanitize($item['name']) ?>">
                                        <?php else: ?>
                                            <div class="product-placeholder-sm"><i class="fas fa-laptop"></i></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="cart-product-info">
                                        <a href="product.php?slug=<?= $item['slug'] ?>" class="cart-product-name"><?= sanitize($item['name']) ?></a>
                                    </div>
                                </div>
                                <div class="cart-price"><?= formatPrice($item['price']) ?></div>
                                <div class="cart-quantity">
                                    <div class="quantity-selector quantity-sm">
                                        <button class="qty-btn" onclick="updateCartQty(<?= $item['id'] ?>, <?= $item['quantity'] - 1 ?>)"><i class="fas fa-minus"></i></button>
                                        <input type="number" value="<?= $item['quantity'] ?>" min="1" max="<?= $item['stock'] ?>" class="qty-input" onchange="updateCartQty(<?= $item['id'] ?>, this.value)" id="qty-<?= $item['id'] ?>">
                                        <button class="qty-btn" onclick="updateCartQty(<?= $item['id'] ?>, <?= $item['quantity'] + 1 ?>)"><i class="fas fa-plus"></i></button>
                                    </div>
                                </div>
                                <div class="cart-total"><?= formatPrice($item['price'] * $item['quantity']) ?></div>
                                <div class="cart-remove">
                                    <button class="remove-btn" onclick="removeFromCart(<?= $item['id'] ?>)" title="Kaldir">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="cart-summary">
                    <h3>Siparis Ozeti</h3>
                    <div class="summary-row">
                        <span>Ara Toplam</span>
                        <span id="subtotal"><?= formatPrice($cartTotal) ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Kargo</span>
                        <span class="free-shipping">Ucretsiz</span>
                    </div>
                    <hr>
                    <div class="summary-row summary-total">
                        <span>Toplam</span>
                        <span id="grandTotal"><?= formatPrice($cartTotal) ?></span>
                    </div>
                    <a href="<?= SITE_URL ?>/checkout.php" class="btn btn-primary btn-block">
                        Siparisi Tamamla <i class="fas fa-arrow-right"></i>
                    </a>
                    <a href="<?= SITE_URL ?>/products.php" class="btn btn-outline btn-block">
                        Alisverise Devam Et
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
