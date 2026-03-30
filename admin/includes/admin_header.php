<?php
require_once __DIR__ . '/../../includes/functions.php';
initSession();

if (!isAdmin()) {
    setFlash('error', 'Bu sayfaya erisim yetkiniz yok.');
    redirect(SITE_URL . '/login.php');
}

$db = getDB();
$adminPage = basename($_SERVER['PHP_SELF'], '.php');

// Istatistikler
$totalOrders = $db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$pendingOrders = $db->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();
$totalProducts = $db->query("SELECT COUNT(*) FROM products")->fetchColumn();
$totalUsers = $db->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn();
$totalRevenue = $db->query("SELECT COALESCE(SUM(total), 0) FROM orders WHERE status != 'cancelled'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($adminTitle) ? sanitize($adminTitle) . ' | Admin' : 'Admin Panel' ?> - TechStore</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/admin.css">
</head>
<body class="admin-body">
    <!-- Sidebar -->
    <aside class="admin-sidebar" id="adminSidebar">
        <div class="sidebar-header">
            <a href="<?= SITE_URL ?>/admin/" class="sidebar-logo">
                <i class="fas fa-laptop"></i>
                <span>TechStore</span>
            </a>
            <button class="sidebar-close" id="sidebarClose"><i class="fas fa-times"></i></button>
        </div>
        <nav class="sidebar-nav">
            <a href="<?= SITE_URL ?>/admin/" class="nav-item <?= $adminPage === 'index' ? 'active' : '' ?>">
                <i class="fas fa-tachometer-alt"></i> <span>Dashboard</span>
            </a>
            <a href="<?= SITE_URL ?>/admin/products.php" class="nav-item <?= $adminPage === 'products' || $adminPage === 'add-product' || $adminPage === 'edit-product' ? 'active' : '' ?>">
                <i class="fas fa-box"></i> <span>Urunler</span>
            </a>
            <a href="<?= SITE_URL ?>/admin/categories.php" class="nav-item <?= $adminPage === 'categories' ? 'active' : '' ?>">
                <i class="fas fa-th-large"></i> <span>Kategoriler</span>
            </a>
            <a href="<?= SITE_URL ?>/admin/brands.php" class="nav-item <?= $adminPage === 'brands' ? 'active' : '' ?>">
                <i class="fas fa-tags"></i> <span>Markalar</span>
            </a>
            <a href="<?= SITE_URL ?>/admin/orders.php" class="nav-item <?= $adminPage === 'orders' ? 'active' : '' ?>">
                <i class="fas fa-shopping-bag"></i> <span>Siparisler</span>
                <?php if ($pendingOrders > 0): ?>
                    <span class="nav-badge"><?= $pendingOrders ?></span>
                <?php endif; ?>
            </a>
            <a href="<?= SITE_URL ?>/admin/users.php" class="nav-item <?= $adminPage === 'users' ? 'active' : '' ?>">
                <i class="fas fa-users"></i> <span>Kullanicilar</span>
            </a>
            <a href="<?= SITE_URL ?>/admin/sliders.php" class="nav-item <?= $adminPage === 'sliders' ? 'active' : '' ?>">
                <i class="fas fa-images"></i> <span>Slider</span>
            </a>
            <a href="<?= SITE_URL ?>/admin/settings.php" class="nav-item <?= $adminPage === 'settings' ? 'active' : '' ?>">
                <i class="fas fa-cog"></i> <span>Ayarlar</span>
            </a>
            <hr class="nav-divider">
            <a href="<?= SITE_URL ?>" class="nav-item" target="_blank">
                <i class="fas fa-external-link-alt"></i> <span>Siteyi Gor</span>
            </a>
            <a href="<?= SITE_URL ?>/logout.php" class="nav-item nav-danger">
                <i class="fas fa-sign-out-alt"></i> <span>Cikis Yap</span>
            </a>
        </nav>
    </aside>

    <!-- Main Content -->
    <div class="admin-main">
        <header class="admin-topbar">
            <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            <div class="topbar-right">
                <span class="admin-user"><i class="fas fa-user-circle"></i> <?= sanitize($_SESSION['user_name']) ?></span>
            </div>
        </header>

        <!-- Flash Mesajlar -->
        <?php $flash = getFlash(); if ($flash): ?>
        <div class="admin-alert admin-alert-<?= $flash['type'] ?>">
            <?= $flash['message'] ?>
            <button class="alert-close" onclick="this.parentElement.remove()">&times;</button>
        </div>
        <?php endif; ?>

        <div class="admin-content">
