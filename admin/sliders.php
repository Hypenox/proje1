<?php
/**
 * Admin - Slider Yonetimi
 */
$adminTitle = 'Slider';
require_once __DIR__ . '/includes/admin_header.php';

// Sil
if (isset($_GET['delete'])) {
    $db->prepare("DELETE FROM sliders WHERE id = ?")->execute([(int) $_GET['delete']]);
    setFlash('success', 'Slider silindi.');
    redirect(SITE_URL . '/admin/sliders.php');
}

// Ekle/Guncelle
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    $title = sanitize($_POST['title'] ?? '');
    $subtitle = sanitize($_POST['subtitle'] ?? '');
    $link = sanitize($_POST['link'] ?? '');
    $sortOrder = (int) ($_POST['sort_order'] ?? 0);
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    $imagePath = null;
    if (!empty($_FILES['image']['name'])) {
        $imagePath = uploadImage($_FILES['image'], 'sliders');
    }

    if ($id > 0) {
        $sql = "UPDATE sliders SET title = ?, subtitle = ?, link = ?, sort_order = ?, is_active = ?";
        $params = [$title, $subtitle, $link, $sortOrder, $isActive];
        if ($imagePath) { $sql .= ", image = ?"; $params[] = $imagePath; }
        $sql .= " WHERE id = ?";
        $params[] = $id;
        $db->prepare($sql)->execute($params);
        setFlash('success', 'Slider guncellendi.');
    } else {
        $db->prepare("INSERT INTO sliders (title, subtitle, image, link, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?)")
            ->execute([$title, $subtitle, $imagePath ?? '', $link, $sortOrder, $isActive]);
        setFlash('success', 'Slider eklendi.');
    }
    redirect(SITE_URL . '/admin/sliders.php');
}

$editSlider = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM sliders WHERE id = ?");
    $stmt->execute([(int) $_GET['edit']]);
    $editSlider = $stmt->fetch();
}

$sliders = $db->query("SELECT * FROM sliders ORDER BY sort_order")->fetchAll();
?>

<div class="page-header-admin">
    <h1 class="page-title">Slider Yonetimi</h1>
</div>

<div class="admin-grid-2">
    <div class="admin-card">
        <div class="card-header"><h3><?= $editSlider ? 'Slider Duzenle' : 'Yeni Slider' ?></h3></div>
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <?php if ($editSlider): ?><input type="hidden" name="id" value="<?= $editSlider['id'] ?>"><?php endif; ?>
                <div class="form-group"><label>Baslik</label><input type="text" name="title" value="<?= sanitize($editSlider['title'] ?? '') ?>" class="form-input"></div>
                <div class="form-group"><label>Alt Baslik</label><input type="text" name="subtitle" value="<?= sanitize($editSlider['subtitle'] ?? '') ?>" class="form-input"></div>
                <div class="form-group"><label>Gorsel <?= $editSlider ? '' : '*' ?></label><input type="file" name="image" accept="image/*" class="form-input" <?= $editSlider ? '' : 'required' ?>></div>
                <div class="form-group"><label>Link</label><input type="text" name="link" value="<?= sanitize($editSlider['link'] ?? '') ?>" class="form-input"></div>
                <div class="form-group"><label>Sira</label><input type="number" name="sort_order" value="<?= $editSlider['sort_order'] ?? 0 ?>" class="form-input"></div>
                <label class="checkbox-label"><input type="checkbox" name="is_active" value="1" <?= !$editSlider || $editSlider['is_active'] ? 'checked' : '' ?>><span>Aktif</span></label>
                <br><br>
                <button type="submit" class="btn btn-primary"><?= $editSlider ? 'Guncelle' : 'Ekle' ?></button>
                <?php if ($editSlider): ?><a href="sliders.php" class="btn btn-outline">Iptal</a><?php endif; ?>
            </form>
        </div>
    </div>

    <div class="admin-card">
        <div class="card-header"><h3>Mevcut Sliderlar</h3></div>
        <div class="card-body">
            <table class="admin-table">
                <thead><tr><th>Gorsel</th><th>Baslik</th><th>Sira</th><th>Durum</th><th>Islem</th></tr></thead>
                <tbody>
                    <?php foreach ($sliders as $s): ?>
                        <tr>
                            <td>
                                <?php if ($s['image']): ?>
                                    <img src="<?= UPLOAD_URL . $s['image'] ?>" class="table-thumb" alt="">
                                <?php else: ?>
                                    <div class="table-thumb-placeholder"><i class="fas fa-image"></i></div>
                                <?php endif; ?>
                            </td>
                            <td><?= sanitize($s['title'] ?: 'Baslaksiz') ?></td>
                            <td><?= $s['sort_order'] ?></td>
                            <td><span class="badge <?= $s['is_active'] ? 'badge-success' : 'badge-error' ?>"><?= $s['is_active'] ? 'Aktif' : 'Pasif' ?></span></td>
                            <td class="actions-cell">
                                <a href="sliders.php?edit=<?= $s['id'] ?>" class="btn btn-sm btn-outline"><i class="fas fa-edit"></i></a>
                                <a href="sliders.php?delete=<?= $s['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Silmek istediginize emin misiniz?')"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
