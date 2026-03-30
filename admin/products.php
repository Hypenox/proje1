<?php
/**
 * Admin - Urun Yonetimi
 */
$adminTitle = 'Urunler';
require_once __DIR__ . '/includes/admin_header.php';

// Silme islemi
if (isset($_GET['delete'])) {
    $deleteId = (int) $_GET['delete'];
    $db->prepare("DELETE FROM products WHERE id = ?")->execute([$deleteId]);
    setFlash('success', 'Urun silindi.');
    redirect(SITE_URL . '/admin/products.php');
}

// Filtreleme
$search = $_GET['search'] ?? '';
$categoryFilter = $_GET['category'] ?? '';
$brandFilter = $_GET['brand'] ?? '';
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 20;

$where = ["1=1"];
$params = [];

if ($search) {
    $where[] = "(p.name LIKE ? OR p.sku LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($categoryFilter) {
    $where[] = "p.category_id = ?";
    $params[] = (int) $categoryFilter;
}
if ($brandFilter) {
    $where[] = "p.brand_id = ?";
    $params[] = (int) $brandFilter;
}

$whereSQL = implode(' AND ', $where);
$countStmt = $db->prepare("SELECT COUNT(*) FROM products p WHERE $whereSQL");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$pagination = paginate($total, $perPage, $page);

$stmt = $db->prepare("SELECT p.*, b.name as brand_name, c.name as category_name FROM products p LEFT JOIN brands b ON p.brand_id = b.id LEFT JOIN categories c ON p.category_id = c.id WHERE $whereSQL ORDER BY p.created_at DESC LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}");
$stmt->execute($params);
$products = $stmt->fetchAll();

$categories = $db->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY name")->fetchAll();
$brands = $db->query("SELECT * FROM brands WHERE is_active = 1 ORDER BY name")->fetchAll();
?>

<div class="page-header-admin">
    <h1 class="page-title">Urunler <span class="badge"><?= $total ?></span></h1>
    <a href="add-product.php" class="btn btn-primary"><i class="fas fa-plus"></i> Yeni Urun</a>
</div>

<!-- Filtreler -->
<div class="admin-card">
    <div class="card-body">
        <form method="GET" class="filter-form-inline">
            <input type="text" name="search" placeholder="Urun adi veya SKU ara..." value="<?= sanitize($search) ?>" class="form-input">
            <select name="category" class="form-select">
                <option value="">Tum Kategoriler</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= $categoryFilter == $cat['id'] ? 'selected' : '' ?>><?= sanitize($cat['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="brand" class="form-select">
                <option value="">Tum Markalar</option>
                <?php foreach ($brands as $brand): ?>
                    <option value="<?= $brand['id'] ?>" <?= $brandFilter == $brand['id'] ? 'selected' : '' ?>><?= sanitize($brand['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-outline"><i class="fas fa-search"></i> Filtrele</button>
            <?php if ($search || $categoryFilter || $brandFilter): ?>
                <a href="products.php" class="btn btn-sm btn-outline"><i class="fas fa-times"></i></a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Urun Listesi -->
<div class="admin-card">
    <div class="card-body">
        <?php if (empty($products)): ?>
            <p class="empty-text">Urun bulunamadi.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Gorsel</th>
                            <th>Urun Adi</th>
                            <th>Kategori</th>
                            <th>Marka</th>
                            <th>Fiyat</th>
                            <th>Stok</th>
                            <th>One Cikan</th>
                            <th>Durum</th>
                            <th>Islemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $p): ?>
                            <tr>
                                <td>
                                    <?php if ($p['image']): ?>
                                        <img src="<?= UPLOAD_URL . $p['image'] ?>" alt="" class="table-thumb">
                                    <?php else: ?>
                                        <div class="table-thumb-placeholder"><i class="fas fa-image"></i></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?= sanitize($p['name']) ?></strong>
                                    <?php if ($p['sku']): ?><br><small class="text-muted">SKU: <?= sanitize($p['sku']) ?></small><?php endif; ?>
                                </td>
                                <td><?= sanitize($p['category_name'] ?? '-') ?></td>
                                <td><?= sanitize($p['brand_name'] ?? '-') ?></td>
                                <td>
                                    <?= formatPrice($p['price']) ?>
                                    <?php if ($p['old_price']): ?><br><small class="text-muted"><s><?= formatPrice($p['old_price']) ?></s></small><?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?= $p['stock'] <= 5 ? 'badge-error' : 'badge-success' ?>"><?= $p['stock'] ?></span>
                                </td>
                                <td><?= $p['is_featured'] ? '<i class="fas fa-star text-warning"></i>' : '-' ?></td>
                                <td>
                                    <span class="badge <?= $p['is_active'] ? 'badge-success' : 'badge-error' ?>"><?= $p['is_active'] ? 'Aktif' : 'Pasif' ?></span>
                                </td>
                                <td class="actions-cell">
                                    <a href="edit-product.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline" title="Duzenle"><i class="fas fa-edit"></i></a>
                                    <a href="<?= SITE_URL ?>/product.php?slug=<?= $p['slug'] ?>" class="btn btn-sm btn-outline" target="_blank" title="Goruntule"><i class="fas fa-eye"></i></a>
                                    <a href="products.php?delete=<?= $p['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bu urunu silmek istediginize emin misiniz?')" title="Sil"><i class="fas fa-trash"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($pagination['total_pages'] > 1): ?>
                <nav class="admin-pagination">
                    <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                        <?php $qp = $_GET; $qp['page'] = $i; ?>
                        <a href="?<?= http_build_query($qp) ?>" class="page-link <?= $i === $pagination['current_page'] ? 'active' : '' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
