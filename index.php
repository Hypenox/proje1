<?php
/**
 * Ana Sayfa
 */
$pageTitle = 'Ana Sayfa';
require_once __DIR__ . '/includes/header.php';

$db = getDB();

// Slider
$sliders = $db->query("SELECT * FROM sliders WHERE is_active = 1 ORDER BY sort_order")->fetchAll();

// One cikan urunler
$featuredProducts = $db->query("SELECT p.*, b.name as brand_name, c.name as category_name FROM products p LEFT JOIN brands b ON p.brand_id = b.id LEFT JOIN categories c ON p.category_id = c.id WHERE p.is_featured = 1 AND p.is_active = 1 ORDER BY p.created_at DESC LIMIT 8")->fetchAll();

// Yeni urunler
$newProducts = $db->query("SELECT p.*, b.name as brand_name, c.name as category_name FROM products p LEFT JOIN brands b ON p.brand_id = b.id LEFT JOIN categories c ON p.category_id = c.id WHERE p.is_active = 1 ORDER BY p.created_at DESC LIMIT 8")->fetchAll();

// Indirimli urunler
$saleProducts = $db->query("SELECT p.*, b.name as brand_name, c.name as category_name FROM products p LEFT JOIN brands b ON p.brand_id = b.id LEFT JOIN categories c ON p.category_id = c.id WHERE p.is_active = 1 AND p.old_price IS NOT NULL AND p.old_price > p.price ORDER BY (p.old_price - p.price) DESC LIMIT 4")->fetchAll();

// Markalar
$allBrands = $db->query("SELECT * FROM brands WHERE is_active = 1 ORDER BY name")->fetchAll();
?>

<!-- Hero Slider -->
<section class="hero-slider" id="heroSlider">
    <div class="slider-wrapper">
        <?php if (!empty($sliders)): ?>
            <?php foreach ($sliders as $i => $slide): ?>
                <div class="slide <?= $i === 0 ? 'active' : '' ?>" style="background: linear-gradient(135deg, #1a1a2e <?= $i * 10 ?>%, #16213e 50%, #0f3460 100%);">
                    <div class="container">
                        <div class="slide-content">
                            <h1 class="slide-title"><?= sanitize($slide['title']) ?></h1>
                            <p class="slide-subtitle"><?= sanitize($slide['subtitle']) ?></p>
                            <a href="<?= sanitize($slide['link']) ?>" class="btn btn-primary btn-lg">
                                Kesfet <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                        <div class="slide-image">
                            <div class="floating-laptop">
                                <i class="fas fa-laptop" style="font-size: 200px; color: rgba(108,60,225,0.3);"></i>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="slide active" style="background: linear-gradient(135deg, #1a1a2e, #16213e, #0f3460);">
                <div class="container">
                    <div class="slide-content">
                        <h1 class="slide-title">Oyun Dunyasina Adim At</h1>
                        <p class="slide-subtitle">En guclu oyun bilgisayarlari burada</p>
                        <a href="products.php?type=computer" class="btn btn-primary btn-lg">
                            Kesfet <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <button class="slider-btn slider-prev" id="sliderPrev"><i class="fas fa-chevron-left"></i></button>
    <button class="slider-btn slider-next" id="sliderNext"><i class="fas fa-chevron-right"></i></button>
    <div class="slider-dots" id="sliderDots">
        <?php for ($i = 0; $i < max(1, count($sliders)); $i++): ?>
            <span class="dot <?= $i === 0 ? 'active' : '' ?>" data-index="<?= $i ?>"></span>
        <?php endfor; ?>
    </div>
</section>

<!-- Ozellikler -->
<section class="features">
    <div class="container">
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-shipping-fast"></i></div>
                <h3>Ucretsiz Kargo</h3>
                <p>Tum siparislerde ucretsiz kargo</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-shield-alt"></i></div>
                <h3>2 Yil Garanti</h3>
                <p>Tum urunlerde 2 yil garanti</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-undo-alt"></i></div>
                <h3>14 Gun Iade</h3>
                <p>Kosulsuz iade garantisi</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-headset"></i></div>
                <h3>7/24 Destek</h3>
                <p>Teknik destek hatti</p>
            </div>
        </div>
    </div>
</section>

<!-- One Cikan Urunler -->
<?php if (!empty($featuredProducts)): ?>
<section class="section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title"><i class="fas fa-star"></i> One Cikanlar</h2>
            <a href="products.php?featured=1" class="section-link">Tumunu Gor <i class="fas fa-arrow-right"></i></a>
        </div>
        <div class="products-grid">
            <?php foreach ($featuredProducts as $product): ?>
                <article class="product-card" data-aos="fade-up">
                    <a href="product.php?slug=<?= $product['slug'] ?>" class="product-link">
                        <div class="product-image">
                            <?php if ($product['image']): ?>
                                <img src="<?= UPLOAD_URL . $product['image'] ?>" alt="<?= sanitize($product['name']) ?>" loading="lazy">
                            <?php else: ?>
                                <div class="product-placeholder"><i class="fas fa-laptop"></i></div>
                            <?php endif; ?>
                            <?php if ($product['old_price'] && $product['old_price'] > $product['price']): ?>
                                <span class="product-badge badge-sale">%<?= calcDiscount($product['old_price'], $product['price']) ?></span>
                            <?php endif; ?>
                            <div class="product-overlay">
                                <button class="overlay-btn add-to-cart-btn" data-id="<?= $product['id'] ?>" onclick="event.preventDefault(); addToCart(<?= $product['id'] ?>)">
                                    <i class="fas fa-cart-plus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="product-info">
                            <span class="product-brand"><?= sanitize($product['brand_name'] ?? '') ?></span>
                            <h3 class="product-name"><?= sanitize($product['name']) ?></h3>
                            <p class="product-desc"><?= sanitize($product['short_description'] ?? '') ?></p>
                            <div class="product-pricing">
                                <?php if ($product['old_price'] && $product['old_price'] > $product['price']): ?>
                                    <span class="old-price"><?= formatPrice($product['old_price']) ?></span>
                                <?php endif; ?>
                                <span class="current-price"><?= formatPrice($product['price']) ?></span>
                            </div>
                        </div>
                    </a>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Indirimli Urunler Banner -->
<?php if (!empty($saleProducts)): ?>
<section class="section sale-section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title"><i class="fas fa-bolt"></i> Firsatlari Kacirmayin</h2>
            <a href="products.php?sale=1" class="section-link">Tumunu Gor <i class="fas fa-arrow-right"></i></a>
        </div>
        <div class="sale-grid">
            <?php foreach ($saleProducts as $product): ?>
                <article class="sale-card">
                    <a href="product.php?slug=<?= $product['slug'] ?>">
                        <div class="sale-image">
                            <?php if ($product['image']): ?>
                                <img src="<?= UPLOAD_URL . $product['image'] ?>" alt="<?= sanitize($product['name']) ?>" loading="lazy">
                            <?php else: ?>
                                <div class="product-placeholder"><i class="fas fa-laptop"></i></div>
                            <?php endif; ?>
                            <div class="sale-badge">%<?= calcDiscount($product['old_price'], $product['price']) ?> INDIRIM</div>
                        </div>
                        <div class="sale-info">
                            <h3><?= sanitize($product['name']) ?></h3>
                            <div class="sale-pricing">
                                <span class="old-price"><?= formatPrice($product['old_price']) ?></span>
                                <span class="current-price"><?= formatPrice($product['price']) ?></span>
                            </div>
                        </div>
                    </a>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Kategoriler -->
<section class="section categories-section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title"><i class="fas fa-th-large"></i> Kategoriler</h2>
        </div>
        <div class="categories-grid">
            <?php
            $catIcons = [
                'oyun-bilgisayarlari' => 'fas fa-gamepad',
                'is-bilgisayarlari' => 'fas fa-briefcase',
                'ogrenci-bilgisayarlari' => 'fas fa-graduation-cap',
                'masaustu-bilgisayarlar' => 'fas fa-desktop',
                'ultrabook' => 'fas fa-feather',
                'klavye' => 'fas fa-keyboard',
                'mouse' => 'fas fa-mouse',
                'kulaklik' => 'fas fa-headphones',
                'monitor' => 'fas fa-tv',
                'canta-kilif' => 'fas fa-bag-shopping',
                'mouse-pad' => 'fas fa-expand',
                'sogutucu' => 'fas fa-fan',
            ];
            $allCats = array_merge($computerCats, $accessoryCats);
            foreach (array_slice($allCats, 0, 8) as $cat):
                $icon = $catIcons[$cat['slug']] ?? 'fas fa-tag';
            ?>
                <a href="products.php?category=<?= $cat['slug'] ?>" class="category-card">
                    <div class="category-icon"><i class="<?= $icon ?>"></i></div>
                    <h3><?= sanitize($cat['name']) ?></h3>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Yeni Urunler -->
<?php if (!empty($newProducts)): ?>
<section class="section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title"><i class="fas fa-sparkles"></i> Yeni Urunler</h2>
            <a href="products.php" class="section-link">Tumunu Gor <i class="fas fa-arrow-right"></i></a>
        </div>
        <div class="products-grid">
            <?php foreach ($newProducts as $product): ?>
                <article class="product-card">
                    <a href="product.php?slug=<?= $product['slug'] ?>" class="product-link">
                        <div class="product-image">
                            <?php if ($product['image']): ?>
                                <img src="<?= UPLOAD_URL . $product['image'] ?>" alt="<?= sanitize($product['name']) ?>" loading="lazy">
                            <?php else: ?>
                                <div class="product-placeholder"><i class="fas fa-laptop"></i></div>
                            <?php endif; ?>
                            <?php if ($product['old_price'] && $product['old_price'] > $product['price']): ?>
                                <span class="product-badge badge-sale">%<?= calcDiscount($product['old_price'], $product['price']) ?></span>
                            <?php endif; ?>
                            <div class="product-overlay">
                                <button class="overlay-btn add-to-cart-btn" data-id="<?= $product['id'] ?>" onclick="event.preventDefault(); addToCart(<?= $product['id'] ?>)">
                                    <i class="fas fa-cart-plus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="product-info">
                            <span class="product-brand"><?= sanitize($product['brand_name'] ?? '') ?></span>
                            <h3 class="product-name"><?= sanitize($product['name']) ?></h3>
                            <p class="product-desc"><?= sanitize($product['short_description'] ?? '') ?></p>
                            <div class="product-pricing">
                                <?php if ($product['old_price'] && $product['old_price'] > $product['price']): ?>
                                    <span class="old-price"><?= formatPrice($product['old_price']) ?></span>
                                <?php endif; ?>
                                <span class="current-price"><?= formatPrice($product['price']) ?></span>
                            </div>
                        </div>
                    </a>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Markalar -->
<section class="section brands-section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title"><i class="fas fa-award"></i> Markalar</h2>
        </div>
        <div class="brands-slider">
            <?php foreach ($allBrands as $brand): ?>
                <a href="products.php?brand=<?= $brand['slug'] ?>" class="brand-item">
                    <?php if ($brand['logo']): ?>
                        <img src="<?= UPLOAD_URL . $brand['logo'] ?>" alt="<?= sanitize($brand['name']) ?>" loading="lazy">
                    <?php else: ?>
                        <span class="brand-name-text"><?= sanitize($brand['name']) ?></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Newsletter -->
<section class="newsletter-section">
    <div class="container">
        <div class="newsletter-content">
            <h2>Firsatlari Kacirmayin!</h2>
            <p>Kampanya ve yeni urunlerden haberdar olun</p>
            <form class="newsletter-form" onsubmit="event.preventDefault(); alert('Basariyla abone oldunuz!');">
                <input type="email" placeholder="E-posta adresiniz" required>
                <button type="submit" class="btn btn-primary">Abone Ol</button>
            </form>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
