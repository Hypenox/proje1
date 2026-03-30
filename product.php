<?php
/**
 * Urun Detay Sayfasi
 */
require_once __DIR__ . '/includes/functions.php';
initSession();

$db = getDB();
$slug = $_GET['slug'] ?? '';

if (!$slug) { redirect(SITE_URL . '/products.php'); }

// Urun bilgileri
$stmt = $db->prepare("SELECT p.*, b.name as brand_name, b.slug as brand_slug, c.name as category_name, c.slug as category_slug FROM products p LEFT JOIN brands b ON p.brand_id = b.id LEFT JOIN categories c ON p.category_id = c.id WHERE p.slug = ? AND p.is_active = 1");
$stmt->execute([$slug]);
$product = $stmt->fetch();

if (!$product) {
    setFlash('error', 'Urun bulunamadi.');
    redirect(SITE_URL . '/products.php');
}

// Goruntulenme sayisini artir
$db->prepare("UPDATE products SET view_count = view_count + 1 WHERE id = ?")->execute([$product['id']]);

// Ek gorseller
$images = $db->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order");
$images->execute([$product['id']]);
$productImages = $images->fetchAll();

// Benzer urunler
$relatedStmt = $db->prepare("SELECT p.*, b.name as brand_name FROM products p LEFT JOIN brands b ON p.brand_id = b.id WHERE p.category_id = ? AND p.id != ? AND p.is_active = 1 ORDER BY RAND() LIMIT 4");
$relatedStmt->execute([$product['category_id'], $product['id']]);
$relatedProducts = $relatedStmt->fetchAll();

// Spesifikasyonlar
$specs = $product['specs'] ? json_decode($product['specs'], true) : [];

$pageTitle = $product['name'];
require_once __DIR__ . '/includes/header.php';
?>

<section class="page-header page-header-sm">
    <div class="container">
        <nav class="breadcrumb">
            <a href="<?= SITE_URL ?>">Ana Sayfa</a>
            <span>/</span>
            <?php if ($product['category_name']): ?>
                <a href="<?= SITE_URL ?>/products.php?category=<?= $product['category_slug'] ?>"><?= sanitize($product['category_name']) ?></a>
                <span>/</span>
            <?php endif; ?>
            <span><?= sanitize($product['name']) ?></span>
        </nav>
    </div>
</section>

<section class="product-detail">
    <div class="container">
        <div class="product-detail-grid">
            <!-- Gorsel -->
            <div class="product-gallery">
                <div class="gallery-main">
                    <?php if ($product['image']): ?>
                        <img src="<?= UPLOAD_URL . $product['image'] ?>" alt="<?= sanitize($product['name']) ?>" id="mainImage">
                    <?php else: ?>
                        <div class="product-placeholder product-placeholder-lg"><i class="fas fa-laptop"></i></div>
                    <?php endif; ?>
                    <?php if ($product['old_price'] && $product['old_price'] > $product['price']): ?>
                        <span class="product-badge badge-sale badge-lg">%<?= calcDiscount($product['old_price'], $product['price']) ?> INDIRIM</span>
                    <?php endif; ?>
                </div>
                <?php if (!empty($productImages)): ?>
                    <div class="gallery-thumbs">
                        <?php if ($product['image']): ?>
                            <div class="thumb active" onclick="changeImage('<?= UPLOAD_URL . $product['image'] ?>', this)">
                                <img src="<?= UPLOAD_URL . $product['image'] ?>" alt="Ana gorsel">
                            </div>
                        <?php endif; ?>
                        <?php foreach ($productImages as $img): ?>
                            <div class="thumb" onclick="changeImage('<?= UPLOAD_URL . $img['image'] ?>', this)">
                                <img src="<?= UPLOAD_URL . $img['image'] ?>" alt="Urun gorseli">
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Bilgiler -->
            <div class="product-detail-info">
                <?php if ($product['brand_name']): ?>
                    <a href="<?= SITE_URL ?>/products.php?brand=<?= $product['brand_slug'] ?>" class="detail-brand"><?= sanitize($product['brand_name']) ?></a>
                <?php endif; ?>
                <h1 class="detail-title"><?= sanitize($product['name']) ?></h1>

                <?php if ($product['short_description']): ?>
                    <p class="detail-short-desc"><?= sanitize($product['short_description']) ?></p>
                <?php endif; ?>

                <div class="detail-pricing">
                    <?php if ($product['old_price'] && $product['old_price'] > $product['price']): ?>
                        <span class="detail-old-price"><?= formatPrice($product['old_price']) ?></span>
                        <span class="detail-discount">%<?= calcDiscount($product['old_price'], $product['price']) ?> indirim</span>
                    <?php endif; ?>
                    <span class="detail-price"><?= formatPrice($product['price']) ?></span>
                </div>

                <div class="detail-stock">
                    <?php if ($product['stock'] > 0): ?>
                        <span class="stock-badge stock-in"><i class="fas fa-check-circle"></i> Stokta (<?= $product['stock'] ?> adet)</span>
                    <?php else: ?>
                        <span class="stock-badge stock-out"><i class="fas fa-times-circle"></i> Stokta Yok</span>
                    <?php endif; ?>
                </div>

                <?php if ($product['sku']): ?>
                    <p class="detail-sku">SKU: <?= sanitize($product['sku']) ?></p>
                <?php endif; ?>

                <!-- Sepete Ekle -->
                <?php if ($product['stock'] > 0): ?>
                    <div class="detail-actions">
                        <div class="quantity-selector">
                            <button class="qty-btn" onclick="changeQty(-1)"><i class="fas fa-minus"></i></button>
                            <input type="number" id="quantity" value="1" min="1" max="<?= $product['stock'] ?>" class="qty-input">
                            <button class="qty-btn" onclick="changeQty(1)"><i class="fas fa-plus"></i></button>
                        </div>
                        <button class="btn btn-primary btn-lg add-to-cart-detail" onclick="addToCart(<?= $product['id'] ?>, document.getElementById('quantity').value)">
                            <i class="fas fa-cart-plus"></i> Sepete Ekle
                        </button>
                    </div>
                <?php endif; ?>

                <!-- Ozellikler -->
                <div class="detail-features">
                    <div class="detail-feature"><i class="fas fa-truck"></i> Ucretsiz Kargo</div>
                    <div class="detail-feature"><i class="fas fa-shield-alt"></i> 2 Yil Garanti</div>
                    <div class="detail-feature"><i class="fas fa-undo"></i> 14 Gun Iade</div>
                </div>
            </div>
        </div>

        <!-- Sekmeler -->
        <div class="product-tabs">
            <div class="tabs-header">
                <button class="tab-btn active" onclick="switchTab('description', this)">Aciklama</button>
                <button class="tab-btn" onclick="switchTab('specs', this)">Teknik Ozellikler</button>
            </div>
            <div class="tabs-content">
                <div class="tab-pane active" id="tab-description">
                    <div class="tab-content-inner">
                        <?= nl2br(sanitize($product['description'] ?? 'Urun aciklamasi henuz eklenmedi.')) ?>
                    </div>
                </div>
                <div class="tab-pane" id="tab-specs">
                    <?php if (!empty($specs)): ?>
                        <table class="specs-table">
                            <?php
                            $specLabels = [
                                'islemci' => 'Islemci',
                                'ekran_karti' => 'Ekran Karti',
                                'ram' => 'RAM',
                                'depolama' => 'Depolama',
                                'ekran' => 'Ekran',
                                'isletim_sistemi' => 'Isletim Sistemi',
                                'agirlik' => 'Agirlik',
                                'kasa' => 'Kasa',
                                'guc_kaynagi' => 'Guc Kaynagi',
                                'tip' => 'Tip',
                                'switch' => 'Switch',
                                'aydinlatma' => 'Aydinlatma',
                                'layout' => 'Layout',
                                'baglanti' => 'Baglanti',
                                'sensor' => 'Sensor',
                                'dpi' => 'DPI',
                                'pil_omru' => 'Pil Omru',
                                'surucu' => 'Surucu',
                                'frekans' => 'Frekans',
                                'mikrofon' => 'Mikrofon',
                                'ses' => 'Ses',
                                'boyut' => 'Boyut',
                                'cozunurluk' => 'Cozunurluk',
                                'panel' => 'Panel Tipi',
                                'yenileme' => 'Yenileme Hizi',
                                'tepki' => 'Tepki Suresi',
                                'kavis' => 'Kavis',
                                'malzeme' => 'Malzeme',
                                'taban' => 'Taban',
                            ];
                            foreach ($specs as $key => $value):
                                $label = $specLabels[$key] ?? ucfirst(str_replace('_', ' ', $key));
                            ?>
                                <tr>
                                    <th><?= sanitize($label) ?></th>
                                    <td><?= sanitize($value) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    <?php else: ?>
                        <p>Teknik ozellikler henuz eklenmedi.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Benzer Urunler -->
        <?php if (!empty($relatedProducts)): ?>
            <div class="related-products">
                <h2 class="section-title">Benzer Urunler</h2>
                <div class="products-grid">
                    <?php foreach ($relatedProducts as $rp): ?>
                        <article class="product-card">
                            <a href="product.php?slug=<?= $rp['slug'] ?>" class="product-link">
                                <div class="product-image">
                                    <?php if ($rp['image']): ?>
                                        <img src="<?= UPLOAD_URL . $rp['image'] ?>" alt="<?= sanitize($rp['name']) ?>" loading="lazy">
                                    <?php else: ?>
                                        <div class="product-placeholder"><i class="fas fa-laptop"></i></div>
                                    <?php endif; ?>
                                    <div class="product-overlay">
                                        <button class="overlay-btn" onclick="event.preventDefault(); addToCart(<?= $rp['id'] ?>)">
                                            <i class="fas fa-cart-plus"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="product-info">
                                    <span class="product-brand"><?= sanitize($rp['brand_name'] ?? '') ?></span>
                                    <h3 class="product-name"><?= sanitize($rp['name']) ?></h3>
                                    <div class="product-pricing">
                                        <span class="current-price"><?= formatPrice($rp['price']) ?></span>
                                    </div>
                                </div>
                            </a>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<script>
function changeImage(src, el) {
    document.getElementById('mainImage').src = src;
    document.querySelectorAll('.thumb').forEach(t => t.classList.remove('active'));
    el.classList.add('active');
}
function changeQty(delta) {
    const input = document.getElementById('quantity');
    let val = parseInt(input.value) + delta;
    val = Math.max(1, Math.min(val, parseInt(input.max)));
    input.value = val;
}
function switchTab(tab, btn) {
    document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + tab).classList.add('active');
    btn.classList.add('active');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
