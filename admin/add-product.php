<?php
/**
 * Admin - Urun Ekle
 */
$adminTitle = 'Urun Ekle';
require_once __DIR__ . '/includes/admin_header.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $slug = createSlug($name);
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

    // Specs
    $specKeys = $_POST['spec_key'] ?? [];
    $specValues = $_POST['spec_value'] ?? [];
    $specs = [];
    foreach ($specKeys as $i => $key) {
        $key = trim($key);
        $val = trim($specValues[$i] ?? '');
        if ($key && $val) {
            $specs[createSlug($key)] = $val;
        }
    }

    if (!$name) $errors[] = 'Urun adi zorunludur.';
    if ($price <= 0) $errors[] = 'Fiyat sifirdan buyuk olmalidir.';

    // Slug benzersizlik
    $checkSlug = $db->prepare("SELECT id FROM products WHERE slug = ?");
    $checkSlug->execute([$slug]);
    if ($checkSlug->fetch()) {
        $slug .= '-' . time();
    }

    // Gorsel yukle
    $imagePath = null;
    if (!empty($_FILES['image']['name'])) {
        $imagePath = uploadImage($_FILES['image'], 'products');
        if (!$imagePath) $errors[] = 'Gorsel yuklenemedi. (Max 5MB, JPEG/PNG/WEBP)';
    }

    if (empty($errors)) {
        $stmt = $db->prepare("INSERT INTO products (name, slug, description, short_description, price, old_price, stock, sku, category_id, brand_id, image, specs, is_featured, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $name, $slug, $description, $shortDescription, $price, $oldPrice,
            $stock, $sku, $categoryId, $brandId, $imagePath,
            !empty($specs) ? json_encode($specs, JSON_UNESCAPED_UNICODE) : null,
            $isFeatured, $isActive,
        ]);

        setFlash('success', 'Urun basariyla eklendi.');
        redirect(SITE_URL . '/admin/products.php');
    }
}

$categories = $db->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY type, name")->fetchAll();
$brands = $db->query("SELECT * FROM brands WHERE is_active = 1 ORDER BY name")->fetchAll();
?>

<div class="page-header-admin">
    <h1 class="page-title">Yeni Urun Ekle</h1>
    <a href="products.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Geri</a>
</div>

<?php if (!empty($errors)): ?>
    <div class="admin-alert admin-alert-error">
        <?php foreach ($errors as $err): ?>
            <p><?= $err ?></p>
        <?php endforeach; ?>
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
                        <input type="text" name="name" id="name" value="<?= sanitize($_POST['name'] ?? '') ?>" required class="form-input">
                    </div>
                    <div class="form-group">
                        <label for="short_description">Kisa Aciklama</label>
                        <input type="text" name="short_description" id="short_description" value="<?= sanitize($_POST['short_description'] ?? '') ?>" class="form-input" maxlength="500">
                    </div>
                    <div class="form-group">
                        <label for="description">Detayli Aciklama</label>
                        <textarea name="description" id="description" rows="6" class="form-input"><?= sanitize($_POST['description'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <div class="admin-card">
                <div class="card-header"><h3>Teknik Ozellikler</h3></div>
                <div class="card-body">
                    <div id="specsContainer">
                        <div class="spec-row">
                            <input type="text" name="spec_key[]" placeholder="Ozellik adi (orn: islemci)" class="form-input">
                            <input type="text" name="spec_value[]" placeholder="Deger (orn: Intel Core i7)" class="form-input">
                            <button type="button" class="btn btn-sm btn-danger" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline" onclick="addSpecRow()"><i class="fas fa-plus"></i> Ozellik Ekle</button>
                </div>
            </div>
        </div>

        <div class="form-sidebar">
            <div class="admin-card">
                <div class="card-header"><h3>Fiyat & Stok</h3></div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="price">Fiyat (TL) *</label>
                        <input type="number" name="price" id="price" step="0.01" min="0" value="<?= $_POST['price'] ?? '' ?>" required class="form-input">
                    </div>
                    <div class="form-group">
                        <label for="old_price">Eski Fiyat (TL)</label>
                        <input type="number" name="old_price" id="old_price" step="0.01" min="0" value="<?= $_POST['old_price'] ?? '' ?>" class="form-input">
                    </div>
                    <div class="form-group">
                        <label for="stock">Stok *</label>
                        <input type="number" name="stock" id="stock" min="0" value="<?= $_POST['stock'] ?? '0' ?>" required class="form-input">
                    </div>
                    <div class="form-group">
                        <label for="sku">SKU</label>
                        <input type="text" name="sku" id="sku" value="<?= sanitize($_POST['sku'] ?? '') ?>" class="form-input">
                    </div>
                </div>
            </div>

            <div class="admin-card">
                <div class="card-header"><h3>Kategori & Marka</h3></div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="category_id">Kategori</label>
                        <select name="category_id" id="category_id" class="form-select">
                            <option value="">Kategori Secin</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= ($_POST['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>><?= sanitize($cat['name']) ?> (<?= $cat['type'] === 'computer' ? 'Bilgisayar' : 'Aksesuar' ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="brand_id">Marka</label>
                        <select name="brand_id" id="brand_id" class="form-select">
                            <option value="">Marka Secin</option>
                            <?php foreach ($brands as $brand): ?>
                                <option value="<?= $brand['id'] ?>" <?= ($_POST['brand_id'] ?? '') == $brand['id'] ? 'selected' : '' ?>><?= sanitize($brand['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="admin-card">
                <div class="card-header"><h3>Gorsel</h3></div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="image">Urun Gorseli</label>
                        <input type="file" name="image" id="image" accept="image/*" class="form-input" onchange="previewImage(this)">
                        <img id="imagePreview" class="image-preview" style="display:none;">
                    </div>
                </div>
            </div>

            <div class="admin-card">
                <div class="card-header"><h3>Durum</h3></div>
                <div class="card-body">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_active" value="1" <?= !isset($_POST['is_active']) || $_POST['is_active'] ? 'checked' : '' ?>>
                        <span>Aktif</span>
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_featured" value="1" <?= ($_POST['is_featured'] ?? false) ? 'checked' : '' ?>>
                        <span>One Cikan</span>
                    </label>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-save"></i> Urunu Kaydet</button>
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
