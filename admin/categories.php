<?php
/**
 * Admin - Kategori Yonetimi
 */
$adminTitle = 'Kategoriler';
require_once __DIR__ . '/includes/admin_header.php';

$errors = [];

// Silme
if (isset($_GET['delete'])) {
    $db->prepare("DELETE FROM categories WHERE id = ?")->execute([(int) $_GET['delete']]);
    setFlash('success', 'Kategori silindi.');
    redirect(SITE_URL . '/admin/categories.php');
}

// Ekleme / Guncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    $name = sanitize($_POST['name'] ?? '');
    $slug = createSlug($name);
    $type = $_POST['type'] ?? 'computer';
    $description = sanitize($_POST['description'] ?? '');
    $sortOrder = (int) ($_POST['sort_order'] ?? 0);
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    if (!$name) $errors[] = 'Kategori adi zorunludur.';

    $imagePath = null;
    if (!empty($_FILES['image']['name'])) {
        $imagePath = uploadImage($_FILES['image'], 'categories');
    }

    if (empty($errors)) {
        if ($id > 0) {
            $sql = "UPDATE categories SET name = ?, slug = ?, type = ?, description = ?, sort_order = ?, is_active = ?";
            $params = [$name, $slug, $type, $description, $sortOrder, $isActive];
            if ($imagePath) { $sql .= ", image = ?"; $params[] = $imagePath; }
            $sql .= " WHERE id = ?";
            $params[] = $id;
            $db->prepare($sql)->execute($params);
            setFlash('success', 'Kategori guncellendi.');
        } else {
            $checkSlug = $db->prepare("SELECT id FROM categories WHERE slug = ?");
            $checkSlug->execute([$slug]);
            if ($checkSlug->fetch()) $slug .= '-' . time();

            $db->prepare("INSERT INTO categories (name, slug, type, description, image, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)")
                ->execute([$name, $slug, $type, $description, $imagePath, $sortOrder, $isActive]);
            setFlash('success', 'Kategori eklendi.');
        }
        redirect(SITE_URL . '/admin/categories.php');
    }
}

// Duzenleme icin kategori getir
$editCat = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([(int) $_GET['edit']]);
    $editCat = $stmt->fetch();
}

$typeFilter = $_GET['type'] ?? '';
$catWhere = $typeFilter ? "WHERE type = '" . ($typeFilter === 'accessory' ? 'accessory' : 'computer') . "'" : "";
$categories = $db->query("SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id) as product_count FROM categories c $catWhere ORDER BY c.type, c.sort_order, c.name")->fetchAll();
?>

<div class="page-header-admin">
    <h1 class="page-title">Kategoriler</h1>
</div>

<div class="admin-grid-2">
    <!-- Kategori Formu -->
    <div class="admin-card">
        <div class="card-header">
            <h3><?= $editCat ? 'Kategori Duzenle' : 'Yeni Kategori Ekle' ?></h3>
        </div>
        <div class="card-body">
            <?php if (!empty($errors)): ?>
                <div class="admin-alert admin-alert-error">
                    <?php foreach ($errors as $err): ?><p><?= $err ?></p><?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <?php if ($editCat): ?><input type="hidden" name="id" value="<?= $editCat['id'] ?>"><?php endif; ?>

                <div class="form-group">
                    <label>Kategori Adi *</label>
                    <input type="text" name="name" value="<?= sanitize($editCat['name'] ?? '') ?>" required class="form-input">
                </div>
                <div class="form-group">
                    <label>Tip *</label>
                    <select name="type" class="form-select">
                        <option value="computer" <?= ($editCat['type'] ?? '') === 'computer' ? 'selected' : '' ?>>Bilgisayar</option>
                        <option value="accessory" <?= ($editCat['type'] ?? '') === 'accessory' ? 'selected' : '' ?>>Aksesuar</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Aciklama</label>
                    <textarea name="description" rows="3" class="form-input"><?= sanitize($editCat['description'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label>Gorsel</label>
                    <input type="file" name="image" accept="image/*" class="form-input">
                    <?php if ($editCat && $editCat['image']): ?>
                        <img src="<?= UPLOAD_URL . $editCat['image'] ?>" class="current-image-sm" alt="">
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label>Sira</label>
                    <input type="number" name="sort_order" value="<?= $editCat['sort_order'] ?? 0 ?>" class="form-input">
                </div>
                <label class="checkbox-label">
                    <input type="checkbox" name="is_active" value="1" <?= !$editCat || $editCat['is_active'] ? 'checked' : '' ?>>
                    <span>Aktif</span>
                </label>
                <br><br>
                <button type="submit" class="btn btn-primary"><?= $editCat ? 'Guncelle' : 'Ekle' ?></button>
                <?php if ($editCat): ?>
                    <a href="categories.php" class="btn btn-outline">Iptal</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Kategori Listesi -->
    <div class="admin-card">
        <div class="card-header">
            <h3>Mevcut Kategoriler</h3>
            <div class="card-actions">
                <a href="categories.php" class="btn btn-sm <?= !$typeFilter ? 'btn-primary' : 'btn-outline' ?>">Tumu</a>
                <a href="categories.php?type=computer" class="btn btn-sm <?= $typeFilter === 'computer' ? 'btn-primary' : 'btn-outline' ?>">Bilgisayar</a>
                <a href="categories.php?type=accessory" class="btn btn-sm <?= $typeFilter === 'accessory' ? 'btn-primary' : 'btn-outline' ?>">Aksesuar</a>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($categories)): ?>
                <p class="empty-text">Kategori bulunamadi.</p>
            <?php else: ?>
                <table class="admin-table">
                    <thead>
                        <tr><th>Adi</th><th>Tip</th><th>Urun</th><th>Sira</th><th>Durum</th><th>Islem</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $cat): ?>
                            <tr>
                                <td><strong><?= sanitize($cat['name']) ?></strong></td>
                                <td><span class="badge <?= $cat['type'] === 'computer' ? 'badge-primary' : 'badge-info' ?>"><?= $cat['type'] === 'computer' ? 'Bilgisayar' : 'Aksesuar' ?></span></td>
                                <td><?= $cat['product_count'] ?></td>
                                <td><?= $cat['sort_order'] ?></td>
                                <td><span class="badge <?= $cat['is_active'] ? 'badge-success' : 'badge-error' ?>"><?= $cat['is_active'] ? 'Aktif' : 'Pasif' ?></span></td>
                                <td class="actions-cell">
                                    <a href="categories.php?edit=<?= $cat['id'] ?>" class="btn btn-sm btn-outline"><i class="fas fa-edit"></i></a>
                                    <a href="categories.php?delete=<?= $cat['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bu kategoriyi silmek istediginize emin misiniz?')"><i class="fas fa-trash"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
