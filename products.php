<?php
/**
 * Urun Listeleme Sayfasi
 */
require_once __DIR__ . '/includes/functions.php';
initSession();

$db = getDB();

// Filtreler
$type = $_GET['type'] ?? '';
$categorySlug = $_GET['category'] ?? '';
$brandSlug = $_GET['brand'] ?? '';
$search = $_GET['search'] ?? '';
$featured = $_GET['featured'] ?? '';
$sale = $_GET['sale'] ?? '';
$sort = $_GET['sort'] ?? 'newest';
$minPrice = $_GET['min_price'] ?? '';
$maxPrice = $_GET['max_price'] ?? '';
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 12;

// Sorgu olustur
$where = ["p.is_active = 1"];
$params = [];

if ($type === 'computer') {
    $where[] = "c.type = 'computer'";
} elseif ($type === 'accessory') {
    $where[] = "c.type = 'accessory'";
}

if ($categorySlug) {
    $where[] = "c.slug = ?";
    $params[] = $categorySlug;
}

if ($brandSlug) {
    $where[] = "b.slug = ?";
    $params[] = $brandSlug;
}

if ($search) {
    $where[] = "(p.name LIKE ? OR p.description LIKE ? OR b.name LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if ($featured) {
    $where[] = "p.is_featured = 1";
}

if ($sale) {
    $where[] = "p.old_price IS NOT NULL AND p.old_price > p.price";
}

if ($minPrice !== '') {
    $where[] = "p.price >= ?";
    $params[] = (float) $minPrice;
}

if ($maxPrice !== '') {
    $where[] = "p.price <= ?";
    $params[] = (float) $maxPrice;
}

$whereSQL = implode(' AND ', $where);

// Siralama
$orderBy = match ($sort) {
    'price_asc' => 'p.price ASC',
    'price_desc' => 'p.price DESC',
    'name_asc' => 'p.name ASC',
    'popular' => 'p.view_count DESC',
    default => 'p.created_at DESC',
};

// Toplam urun sayisi
$countStmt = $db->prepare("SELECT COUNT(*) FROM products p LEFT JOIN categories c ON p.category_id = c.id LEFT JOIN brands b ON p.brand_id = b.id WHERE $whereSQL");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$pagination = paginate($total, $perPage, $page);

// Urunleri getir
$stmt = $db->prepare("SELECT p.*, b.name as brand_name, c.name as category_name FROM products p LEFT JOIN brands b ON p.brand_id = b.id LEFT JOIN categories c ON p.category_id = c.id WHERE $whereSQL ORDER BY $orderBy LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}");
$stmt->execute($params);
$products = $stmt->fetchAll();

// Sidebar kategoriler ve markalar
$sideCategories = $db->query("SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id AND p.is_active = 1) as product_count FROM categories c WHERE c.is_active = 1 ORDER BY c.type, c.sort_order")->fetchAll();
$sideBrands = $db->query("SELECT b.*, (SELECT COUNT(*) FROM products p WHERE p.brand_id = b.id AND p.is_active = 1) as product_count FROM brands b WHERE b.is_active = 1 ORDER BY b.name")->fetchAll();

// Sayfa basligi
$pageTitle = 'Urunler';
if ($categorySlug) {
    $catInfo = $db->prepare("SELECT name FROM categories WHERE slug = ?");
    $catInfo->execute([$categorySlug]);
    $catName = $catInfo->fetchColumn();
    if ($catName) $pageTitle = $catName;
}
if ($brandSlug) {
    $brandInfo = $db->prepare("SELECT name FROM brands WHERE slug = ?");
    $brandInfo->execute([$brandSlug]);
    $brandName = $brandInfo->fetchColumn();
    if ($brandName) $pageTitle = $brandName . ' Bilgisayarlar';
}
if ($search) $pageTitle = '"' . sanitize($search) . '" Arama Sonuclari';
if ($featured) $pageTitle = 'One Cikan Urunler';
if ($sale) $pageTitle = 'Indirimli Urunler';

require_once __DIR__ . '/includes/header.php';
?>

<section class="page-header">
    <div class="container">
        <nav class="breadcrumb">
            <a href="<?= SITE_URL ?>">Ana Sayfa</a>
            <span>/</span>
            <span><?= sanitize($pageTitle) ?></span>
        </nav>
        <h1><?= sanitize($pageTitle) ?></h1>
        <p class="results-count"><?= $total ?> urun bulundu</p>
    </div>
</section>

<section class="products-page">
    <div class="container">
        <div class="products-layout">
            <!-- Sidebar Filtreler -->
            <aside class="products-sidebar" id="filterSidebar">
                <button class="filter-close-btn" id="filterCloseBtn"><i class="fas fa-times"></i></button>

                <form action="products.php" method="GET" id="filterForm">
                    <?php if ($type): ?><input type="hidden" name="type" value="<?= sanitize($type) ?>"><?php endif; ?>

                    <!-- Kategoriler -->
                    <div class="filter-group">
                        <h4 class="filter-title">Kategoriler</h4>
                        <div class="filter-list">
                            <?php foreach ($sideCategories as $sc): ?>
                                <label class="filter-item">
                                    <a href="products.php?category=<?= $sc['slug'] ?><?= $type ? '&type=' . $type : '' ?>" class="<?= $categorySlug === $sc['slug'] ? 'active' : '' ?>">
                                        <?= sanitize($sc['name']) ?> <span>(<?= $sc['product_count'] ?>)</span>
                                    </a>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Markalar -->
                    <div class="filter-group">
                        <h4 class="filter-title">Markalar</h4>
                        <div class="filter-list">
                            <?php foreach ($sideBrands as $sb): ?>
                                <label class="filter-item">
                                    <a href="products.php?brand=<?= $sb['slug'] ?><?= $type ? '&type=' . $type : '' ?>" class="<?= $brandSlug === $sb['slug'] ? 'active' : '' ?>">
                                        <?= sanitize($sb['name']) ?> <span>(<?= $sb['product_count'] ?>)</span>
                                    </a>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Fiyat Araligi -->
                    <div class="filter-group">
                        <h4 class="filter-title">Fiyat Araligi</h4>
                        <div class="price-range">
                            <input type="number" name="min_price" placeholder="Min" value="<?= sanitize($minPrice) ?>" class="price-input">
                            <span>-</span>
                            <input type="number" name="max_price" placeholder="Max" value="<?= sanitize($maxPrice) ?>" class="price-input">
                        </div>
                        <button type="submit" class="btn btn-sm btn-outline">Filtrele</button>
                    </div>

                    <?php if ($categorySlug || $brandSlug || $minPrice || $maxPrice || $search): ?>
                        <a href="products.php<?= $type ? '?type=' . $type : '' ?>" class="btn btn-sm btn-outline clear-filters">
                            <i class="fas fa-times"></i> Filtreleri Temizle
                        </a>
                    <?php endif; ?>
                </form>
            </aside>

            <!-- Urun Listesi -->
            <div class="products-main">
                <div class="products-toolbar">
                    <button class="filter-toggle-btn" id="filterToggleBtn">
                        <i class="fas fa-filter"></i> Filtrele
                    </button>
                    <div class="sort-options">
                        <label>Sirala:</label>
                        <select id="sortSelect" onchange="applySort(this.value)">
                            <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>En Yeni</option>
                            <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Fiyat: Dusukten Yuksege</option>
                            <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Fiyat: Yuksekten Dusuge</option>
                            <option value="name_asc" <?= $sort === 'name_asc' ? 'selected' : '' ?>>A-Z</option>
                            <option value="popular" <?= $sort === 'popular' ? 'selected' : '' ?>>Populer</option>
                        </select>
                    </div>
                </div>

                <?php if (empty($products)): ?>
                    <div class="empty-state">
                        <i class="fas fa-search"></i>
                        <h3>Urun Bulunamadi</h3>
                        <p>Arama kriterlerinize uygun urun bulunamadi. Filtreleri degistirerek tekrar deneyin.</p>
                        <a href="products.php" class="btn btn-primary">Tum Urunleri Gor</a>
                    </div>
                <?php else: ?>
                    <div class="products-grid">
                        <?php foreach ($products as $product): ?>
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
                                            <button class="overlay-btn" onclick="event.preventDefault(); addToCart(<?= $product['id'] ?>)">
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

                    <!-- Sayfalama -->
                    <?php if ($pagination['total_pages'] > 1): ?>
                        <nav class="pagination">
                            <?php
                            $queryParams = $_GET;
                            if ($pagination['current_page'] > 1):
                                $queryParams['page'] = $pagination['current_page'] - 1;
                            ?>
                                <a href="?<?= http_build_query($queryParams) ?>" class="page-link"><i class="fas fa-chevron-left"></i></a>
                            <?php endif; ?>

                            <?php for ($i = max(1, $pagination['current_page'] - 2); $i <= min($pagination['total_pages'], $pagination['current_page'] + 2); $i++):
                                $queryParams['page'] = $i;
                            ?>
                                <a href="?<?= http_build_query($queryParams) ?>" class="page-link <?= $i === $pagination['current_page'] ? 'active' : '' ?>"><?= $i ?></a>
                            <?php endfor; ?>

                            <?php if ($pagination['current_page'] < $pagination['total_pages']):
                                $queryParams['page'] = $pagination['current_page'] + 1;
                            ?>
                                <a href="?<?= http_build_query($queryParams) ?>" class="page-link"><i class="fas fa-chevron-right"></i></a>
                            <?php endif; ?>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<script>
function applySort(val) {
    const url = new URL(window.location.href);
    url.searchParams.set('sort', val);
    url.searchParams.delete('page');
    window.location.href = url.toString();
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
