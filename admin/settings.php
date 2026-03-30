<?php
/**
 * Admin - Site Ayarlari
 */
$adminTitle = 'Ayarlar';
require_once __DIR__ . '/includes/admin_header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings = [
        'site_name' => sanitize($_POST['site_name'] ?? ''),
        'site_description' => sanitize($_POST['site_description'] ?? ''),
        'site_email' => sanitize($_POST['site_email'] ?? ''),
        'site_phone' => sanitize($_POST['site_phone'] ?? ''),
        'site_address' => sanitize($_POST['site_address'] ?? ''),
        'shipping_cost' => (float) ($_POST['shipping_cost'] ?? 0),
        'tax_rate' => (float) ($_POST['tax_rate'] ?? 20),
        'currency' => sanitize($_POST['currency'] ?? 'TL'),
        'currency_symbol' => sanitize($_POST['currency_symbol'] ?? 'TL'),
    ];

    foreach ($settings as $key => $value) {
        $check = $db->prepare("SELECT id FROM settings WHERE setting_key = ?");
        $check->execute([$key]);
        if ($check->fetch()) {
            $db->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?")->execute([$value, $key]);
        } else {
            $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)")->execute([$key, $value]);
        }
    }

    setFlash('success', 'Ayarlar basariyla kaydedildi.');
    redirect(SITE_URL . '/admin/settings.php');
}

// Mevcut ayarlar
$currentSettings = [];
$settingsResult = $db->query("SELECT * FROM settings")->fetchAll();
foreach ($settingsResult as $s) {
    $currentSettings[$s['setting_key']] = $s['setting_value'];
}
?>

<div class="page-header-admin">
    <h1 class="page-title">Site Ayarlari</h1>
</div>

<form method="POST">
    <div class="admin-grid-2">
        <div class="admin-card">
            <div class="card-header"><h3>Genel Ayarlar</h3></div>
            <div class="card-body">
                <div class="form-group"><label>Site Adi</label><input type="text" name="site_name" value="<?= sanitize($currentSettings['site_name'] ?? 'TechStore') ?>" class="form-input"></div>
                <div class="form-group"><label>Site Aciklamasi</label><textarea name="site_description" rows="3" class="form-input"><?= sanitize($currentSettings['site_description'] ?? '') ?></textarea></div>
                <div class="form-group"><label>E-posta</label><input type="email" name="site_email" value="<?= sanitize($currentSettings['site_email'] ?? '') ?>" class="form-input"></div>
                <div class="form-group"><label>Telefon</label><input type="text" name="site_phone" value="<?= sanitize($currentSettings['site_phone'] ?? '') ?>" class="form-input"></div>
                <div class="form-group"><label>Adres</label><textarea name="site_address" rows="2" class="form-input"><?= sanitize($currentSettings['site_address'] ?? '') ?></textarea></div>
            </div>
        </div>

        <div class="admin-card">
            <div class="card-header"><h3>Odeme & Kargo</h3></div>
            <div class="card-body">
                <div class="form-group"><label>Kargo Ucreti (TL)</label><input type="number" name="shipping_cost" step="0.01" value="<?= $currentSettings['shipping_cost'] ?? 0 ?>" class="form-input"></div>
                <div class="form-group"><label>KDV Orani (%)</label><input type="number" name="tax_rate" step="0.01" value="<?= $currentSettings['tax_rate'] ?? 20 ?>" class="form-input"></div>
                <div class="form-group"><label>Para Birimi</label><input type="text" name="currency" value="<?= sanitize($currentSettings['currency'] ?? 'TL') ?>" class="form-input"></div>
                <div class="form-group"><label>Para Birimi Sembolu</label><input type="text" name="currency_symbol" value="<?= sanitize($currentSettings['currency_symbol'] ?? 'TL') ?>" class="form-input"></div>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Ayarlari Kaydet</button>
</form>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
