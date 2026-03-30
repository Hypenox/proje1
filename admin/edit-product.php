<?php
/**
 * Admin - Urun Duzenle
 */
$adminTitle = 'Urun Duzenle';
require_once __DIR__ . '/includes/admin_header.php';

$productId = (int) ($_GET['id'] ?? 0);
if (!$productId) { redirect(SITE_URL . '/admin/products.php'); }

$stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    setFlash('error', 'Urun bulunamadi.');
    redirect(SITE_URL . '/admin/products.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $description = $_POST['description'] ?? '';
    $shortDescription = sanitize($_POST['short_description'] ?? '');
    $price = (float) ($_POST['price'] ?? 0);
    $oldPrice = !empty($_POST['old_price']) ? (float) $_POST['old_price'] : null;
    $stock = (int) ($_POST['stock'] ?? 0);
    $sku = sanitize($_POST['sku'] ?? '');
    $categoryId = !empty($_POST['category_id']) ? (int) $_POST['category_id'] : null;
    $brandId = !empty($_POST['brand_id']) ? (int) $_POST['brand_id'] : null;
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    $specKeys = $_POST['spec_key'] ?? [];
    $specValues = $_POST['spec_value'] ?? [];
    $specs = [];
    foreach ($specKeys as $i => $key) {
        $key = trim($key);
        $val = trim($specValues[$i] ?? '');
        if ($key && $val) { $specs[createSlug($key)] = $val; }
    }

    if (!$name) $errors[] = 'Urun adi zorunludur.';
    if ($price <= 0) $errors[] = 'Fiyat sifirdan buyuk olmalidir.';

    $imagePath = $product['image'];
    if (!empty($_FILES['image']['name'])) {
        $newImage = uploadImage($_FILES['image'], 'products');
        if ($newImage) $imagePath = $newImage;
        else $errors[] = 'Gorsel yuklenemedi.';
    }

    if (empty($errors)) {
        $stmt = $db->prepare("UPDATE products SET name = ?, description = ?, short_description = ?, price = ?, old_price = ?, stock = ?, sku = ?, category_id = ?, brand_id = ?, image = ?, specs = ?, is_featured = ?, is_active = ? WHERE id = ?");
        $stmt->execute([
            $name, $description, $shortDescription, $price, $oldPrice,
            $stock, $sku, $categoryId, $brandId, $imagePath,
            !empty($specs) ? json_encode($specs, JSON_UNESCAPED_UNICODE) : null,
            $isFeatured, $isActive, $productId,
        ]);

        setFlash('success', 'Urun basariyla guncellendi.');
        redirect(SITE_URL . '/admin/edit-product.php?id=' . $productId);
    }
}

$categories = $db->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY type, name")->fetchAll();
$brands = $db->query("SELECT * FROM brands WHERE is_active = 1 ORDER BY name")->fetchAll();
$existingSpecs = $product['specs'] ? json_decode($product['specs'], true) : [];
?>

<div class="page-header-admin">
    <h1 class="page-title">Urun Duzenle: <?= sanitize($product['name']) ?></h1>
    <a href="products.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Geri</a>
</div>

<?php if (!empty($errors)): ?>
    <div class="admin-alert admin-alert-error">
        <?php foreach ($errors as $err): ?><p><?= $err ?></p><?php endforeach; ?>
    </div>
<?php endif; ?>

<form method="POST" action="" enctype="multipart/form-data" class="product-form">
    <div class="form-layout">
        <div class="form-main">
            <div class="admin-card">
                <div class="card-header"><h3>Genel Bilgiler</h3></div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="name">Urun Adi *</label>
                        <input type="text" name="name" id="name" value="<?= sanitize($product['name']) ?>" required class="form-input">
                    </div>
                    <div class="form-group">
                        <label for="short_description">Kisa Aciklama</label>
                        <input type="text" name="short_description" id="short_description" value="<?= sanitize($product['short_description'] ?? '') ?>" class="form-input">
                    </div>
                    <div class="form-group">
                        <label for="description">Detayli Aciklama</label>
                        <textarea name="description" id="description" rows="6" class="form-input"><?= sanitize($product['description'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <div class="admin-card">
                <div class="card-header"><h3>Teknik Ozellikler</h3></div>
                <div class="card-body">
                    <div id="specsContainer">
                        <?php if (!empty($existingSpecs)): ?>
                            <?php foreach ($existingSpecs as $key => $val): ?>
                                <div class="spec-row">
                                    <input type="text" name="spec_key[]" value="<?= sanitize($key) ?>" class="form-input">
                                    <input type="text" name="spec_value[]" value="<?= sanitize($val) ?>" class="form-input">
                                    <button type="button" class="btn btn-sm btn-danger" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="spec-row">
                                <input type="text" name="spec_key[]" placeholder="Ozellik adi" class="form-input">
                                <input type="text" name="spec_value[]" placeholder="Deger" class="form-input">
                                <button type="button" class="btn btn-sm btn-danger" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
                            </div>
                        <?php endif; ?>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline" onclick="addSpecRow()"><i class="fas fa-plus"></i> Ozellik Ekle</button>
                </div>
            </div>
        </div>

        <div class="form-sidebar">
            <div class="admin-card">
                <div class="card-header"><h3>Fiyat & Stok</h3></div>
                <div class="card-body">
                    <div class="form-group"><label>Fiyat (TL) *</label><input type="number" name="price" step="0.01" min="0" value="<?= $product['price'] ?>" required class="form-input"></div>
                    <div class="form-group"><label>Eski Fiyat (TL)</label><input type="number" name="old_price" step="0.01" min="0" value="<?= $product['old_price'] ?? '' ?>" class="form-input"></div>
                    <div class="form-group"><label>Stok *</label><input type="number" name="stock" min="0" value="<?= $product['stock'] ?>" required class="form-input"></div>
                    <div class="form-group"><label>SKU</label><input type="text" name="sku" value="<?= sanitize($product['sku'] ?? '') ?>" class="form-input"></div>
                </div>
            </div>

            <div class="admin-card">
                <div class="card-header"><h3>Kategori & Marka</h3></div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Kategori</label>
                        <select name="category_id" class="form-select">
                            <option value="">Kategori Secin</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= $product['category_id'] == $cat['id'] ? 'selected' : '' ?>><?= sanitize($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Marka</label>
                        <select name="brand_id" class="form-select">
                            <option value="">Marka Secin</option>
                            <?php foreach ($brands as $brand): ?>
                                <option value="<?= $brand['id'] ?>" <?= $product['brand_id'] == $brand['id'] ? 'selected' : '' ?>><?= sanitize($brand['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="admin-card">
                <div class="card-header"><h3>Gorsel</h3></div>
                <div class="card-body">
                    <?php if ($product['image']): ?>
                        <img src="<?= UPLOAD_URL . $product['image'] ?>" class="current-image" alt="">
                    <?php endif; ?>
                    <div class="form-group">
                        <label>Yeni Gorsel Yukle</label>
                        <input type="file" name="image" accept="image/*" class="form-input" onchange="previewImage(this)">
                        <img id="imagePreview" class="image-preview" style="display:none;">
                    </div>
                </div>
            </div>

            <div class="admin-card">
                <div class="card-header"><h3>Durum</h3></div>
                <div class="card-body">
                    <label class="checkbox-label"><input type="checkbox" name="is_active" value="1" <?= $product['is_active'] ? 'checked' : '' ?>><span>Aktif</span></label>
                    <label class="checkbox-label"><input type="checkbox" name="is_featured" value="1" <?= $product['is_featured'] ? 'checked' : '' ?>><span>One Cikan</span></label>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-save"></i> Degisiklikleri Kaydet</button>
        </div>
    </div>
</form>

<script>
function addSpecRow() {
    const container = document.getElementById('specsContainer');
    const row = document.createElement('div');
    row.className = 'spec-row';
    row.innerHTML = '<input type="text" name="spec_key[]" placeholder="Ozellik adi" class="form-input"><input type="text" name="spec_value[]" placeholder="Deger" class="form-input"><button type="button" class="btn btn-sm btn-danger" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>';
    container.appendChild(row);
}
function previewImage(input) {
    const preview = document.getElementById('imagePreview');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) { preview.src = e.target.result; preview.style.display = 'block'; };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
