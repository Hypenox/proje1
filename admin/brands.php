<?php
/**
 * Admin - Marka Yonetimi
 */
$adminTitle = 'Markalar';
require_once __DIR__ . '/includes/admin_header.php';

$errors = [];

// Silme
if (isset($_GET['delete'])) {
    $db->prepare("DELETE FROM brands WHERE id = ?")->execute([(int) $_GET['delete']]);
    setFlash('success', 'Marka silindi.');
    redirect(SITE_URL . '/admin/brands.php');
}

// Ekleme / Guncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    $name = sanitize($_POST['name'] ?? '');
    $slug = createSlug($name);
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    if (!$name) $errors[] = 'Marka adi zorunludur.';

    $logoPath = null;
    if (!empty($_FILES['logo']['name'])) {
        $logoPath = uploadImage($_FILES['logo'], 'brands');
    }

    if (empty($errors)) {
        if ($id > 0) {
            $sql = "UPDATE brands SET name = ?, slug = ?, is_active = ?";
            $params = [$name, $slug, $isActive];
            if ($logoPath) { $sql .= ", logo = ?"; $params[] = $logoPath; }
            $sql .= " WHERE id = ?";
            $params[] = $id;
            $db->prepare($sql)->execute($params);
            setFlash('success', 'Marka guncellendi.');
        } else {
            $checkSlug = $db->prepare("SELECT id FROM brands WHERE slug = ?");
            $checkSlug->execute([$slug]);
            if ($checkSlug->fetch()) $slug .= '-' . time();

            $db->prepare("INSERT INTO brands (name, slug, logo, is_active) VALUES (?, ?, ?, ?)")
                ->execute([$name, $slug, $logoPath, $isActive]);
            setFlash('success', 'Marka eklendi.');
        }
        redirect(SITE_URL . '/admin/brands.php');
    }
}

$editBrand = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM brands WHERE id = ?");
    $stmt->execute([(int) $_GET['edit']]);
    $editBrand = $stmt->fetch();
}

$brands = $db->query("SELECT b.*, (SELECT COUNT(*) FROM products p WHERE p.brand_id = b.id) as product_count FROM brands b ORDER BY b.name")->fetchAll();
?>

<div class="page-header-admin">
    <h1 class="page-title">Markalar</h1>
</div>

<div class="admin-grid-2">
    <div class="admin-card">
        <div class="card-header">
            <h3><?= $editBrand ? 'Marka Duzenle' : 'Yeni Marka Ekle' ?></h3>
        </div>
        <div class="card-body">
            <?php if (!empty($errors)): ?>
                <div class="admin-alert admin-alert-error">
                    <?php foreach ($errors as $err): ?><p><?= $err ?></p><?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <?php if ($editBrand): ?><input type="hidden" name="id" value="<?= $editBrand['id'] ?>"><?php endif; ?>

                <div class="form-group">
                    <label>Marka Adi *</label>
                    <input type="text" name="name" value="<?= sanitize($editBrand['name'] ?? '') ?>" required class="form-input">
                </div>
                <div class="form-group">
                    <label>Logo</label>
                    <input type="file" name="logo" accept="image/*" class="form-input">
                    <?php if ($editBrand && $editBrand['logo']): ?>
                        <img src="<?= UPLOAD_URL . $editBrand['logo'] ?>" class="current-image-sm" alt="">
                    <?php endif; ?>
                </div>
                <label class="checkbox-label">
                    <input type="checkbox" name="is_active" value="1" <?= !$editBrand || $editBrand['is_active'] ? 'checked' : '' ?>>
                    <span>Aktif</span>
                </label>
                <br><br>
                <button type="submit" class="btn btn-primary"><?= $editBrand ? 'Guncelle' : 'Ekle' ?></button>
                <?php if ($editBrand): ?>
                    <a href="brands.php" class="btn btn-outline">Iptal</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <div class="admin-card">
        <div class="card-header"><h3>Mevcut Markalar</h3></div>
        <div class="card-body">
            <table class="admin-table">
                <thead><tr><th>Logo</th><th>Adi</th><th>Urun</th><th>Durum</th><th>Islem</th></tr></thead>
                <tbody>
                    <?php foreach ($brands as $brand): ?>
                        <tr>
                            <td>
                                <?php if ($brand['logo']): ?>
                                    <img src="<?= UPLOAD_URL . $brand['logo'] ?>" class="table-thumb-sm" alt="">
                                <?php else: ?>
                                    <span class="brand-initial"><?= strtoupper(substr($brand['name'], 0, 1)) ?></span>
                                <?php endif; ?>
                            </td>
                            <td><strong><?= sanitize($brand['name']) ?></strong></td>
                            <td><?= $brand['product_count'] ?></td>
                            <td><span class="badge <?= $brand['is_active'] ? 'badge-success' : 'badge-error' ?>"><?= $brand['is_active'] ? 'Aktif' : 'Pasif' ?></span></td>
                            <td class="actions-cell">
                                <a href="brands.php?edit=<?= $brand['id'] ?>" class="btn btn-sm btn-outline"><i class="fas fa-edit"></i></a>
                                <a href="brands.php?delete=<?= $brand['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bu markayi silmek istediginize emin misiniz?')"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
