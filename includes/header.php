<?php
require_once __DIR__ . '/functions.php';
initSession();

$cartCount = getCartCount();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

// Kategorileri getir
$db = getDB();
$computerCats = $db->query("SELECT * FROM categories WHERE type = 'computer' AND is_active = 1 ORDER BY sort_order")->fetchAll();
$accessoryCats = $db->query("SELECT * FROM categories WHERE type = 'accessory' AND is_active = 1 ORDER BY sort_order")->fetchAll();
$brands = $db->query("SELECT * FROM brands WHERE is_active = 1 ORDER BY name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="TechStore - En iyi oyun bilgisayarlari ve aksesuarlar. Monster, HP, Lenovo, Asus ve daha fazlasi.">
    <meta name="keywords" content="oyun bilgisayari, laptop, masaustu, aksesuar, monster, gaming">
    <meta name="author" content="TechStore">
    <meta name="robots" content="index, follow">
    <meta property="og:title" content="TechStore - Oyun Bilgisayarlari ve Aksesuarlar">
    <meta property="og:description" content="En guclu oyun bilgisayarlari ve aksesuarlar TechStore'da.">
    <meta property="og:type" content="website">
    <title><?= isset($pageTitle) ? sanitize($pageTitle) . ' | TechStore' : 'TechStore - Oyun Bilgisayarlari ve Aksesuarlar' ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
</head>
<body>
    <!-- Top Bar -->
    <div class="top-bar">
        <div class="container">
            <div class="top-bar-left">
                <span><i class="fas fa-phone-alt"></i> +90 212 555 0000</span>
                <span><i class="fas fa-envelope"></i> info@techstore.com</span>
            </div>
            <div class="top-bar-right">
                <span><i class="fas fa-truck"></i> Ucretsiz Kargo</span>
                <span><i class="fas fa-shield-alt"></i> Guvenli Alisveris</span>
            </div>
        </div>
    </div>

    <!-- Header -->
    <header class="header" id="header">
        <div class="container">
            <div class="header-inner">
                <!-- Logo -->
                <a href="<?= SITE_URL ?>" class="logo">
                    <span class="logo-icon"><i class="fas fa-laptop"></i></span>
                    <span class="logo-text">Tech<span>Store</span></span>
                </a>

                <!-- Arama -->
                <div class="search-bar">
                    <form action="<?= SITE_URL ?>/products.php" method="GET" class="search-form">
                        <input type="text" name="search" placeholder="Urun ara..." autocomplete="off" id="searchInput">
                        <button type="submit"><i class="fas fa-search"></i></button>
                    </form>
                    <div class="search-results" id="searchResults"></div>
                </div>

                <!-- Sag Ikonlar -->
                <div class="header-actions">
                    <?php if (isLoggedIn()): ?>
                        <div class="header-action dropdown">
                            <a href="<?= SITE_URL ?>/profile.php" class="action-btn">
                                <i class="fas fa-user"></i>
                                <span><?= sanitize($_SESSION['user_name']) ?></span>
                            </a>
                            <div class="dropdown-menu">
                                <a href="<?= SITE_URL ?>/profile.php"><i class="fas fa-user-circle"></i> Profilim</a>
                                <a href="<?= SITE_URL ?>/orders.php"><i class="fas fa-box"></i> Siparislerim</a>
                                <?php if (isAdmin()): ?>
                                    <a href="<?= SITE_URL ?>/admin/"><i class="fas fa-cog"></i> Admin Panel</a>
                                <?php endif; ?>
                                <hr>
                                <a href="<?= SITE_URL ?>/logout.php"><i class="fas fa-sign-out-alt"></i> Cikis Yap</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="<?= SITE_URL ?>/login.php" class="action-btn">
                            <i class="fas fa-user"></i>
                            <span>Giris Yap</span>
                        </a>
                    <?php endif; ?>

                    <a href="<?= SITE_URL ?>/cart.php" class="action-btn cart-btn">
                        <i class="fas fa-shopping-cart"></i>
                        <span>Sepetim</span>
                        <span class="cart-badge" id="cartBadge" style="<?= $cartCount > 0 ? '' : 'display:none' ?>"><?= $cartCount ?></span>
                    </a>

                    <button class="mobile-menu-btn" id="mobileMenuBtn">
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Navigasyon -->
        <nav class="main-nav" id="mainNav">
            <div class="container">
                <ul class="nav-list">
                    <li class="<?= $currentPage === 'index' ? 'active' : '' ?>">
                        <a href="<?= SITE_URL ?>">Ana Sayfa</a>
                    </li>
                    <li class="has-dropdown <?= $currentPage === 'products' && ($_GET['type'] ?? '') !== 'accessory' ? 'active' : '' ?>">
                        <a href="<?= SITE_URL ?>/products.php?type=computer">
                            Bilgisayarlar <i class="fas fa-chevron-down"></i>
                        </a>
                        <div class="mega-menu">
                            <div class="mega-menu-col">
                                <h4>Kategoriler</h4>
                                <?php foreach ($computerCats as $cat): ?>
                                    <a href="<?= SITE_URL ?>/products.php?category=<?= $cat['slug'] ?>"><?= sanitize($cat['name']) ?></a>
                                <?php endforeach; ?>
                            </div>
                            <div class="mega-menu-col">
                                <h4>Markalar</h4>
                                <?php foreach (array_slice($brands, 0, 10) as $brand): ?>
                                    <a href="<?= SITE_URL ?>/products.php?brand=<?= $brand['slug'] ?>"><?= sanitize($brand['name']) ?></a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </li>
                    <li class="has-dropdown <?= $currentPage === 'products' && ($_GET['type'] ?? '') === 'accessory' ? 'active' : '' ?>">
                        <a href="<?= SITE_URL ?>/products.php?type=accessory">
                            Aksesuarlar <i class="fas fa-chevron-down"></i>
                        </a>
                        <div class="mega-menu">
                            <div class="mega-menu-col">
                                <h4>Kategoriler</h4>
                                <?php foreach ($accessoryCats as $cat): ?>
                                    <a href="<?= SITE_URL ?>/products.php?category=<?= $cat['slug'] ?>"><?= sanitize($cat['name']) ?></a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </li>
                    <li><a href="<?= SITE_URL ?>/products.php?featured=1">One Cikanlar</a></li>
                    <li><a href="<?= SITE_URL ?>/products.php?sale=1">Firsatlar</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <!-- Flash Mesajlar -->
    <?php $flash = getFlash(); if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?>" id="flashAlert">
        <div class="container">
            <span><?= $flash['message'] ?></span>
            <button class="alert-close" onclick="this.parentElement.parentElement.remove()">&times;</button>
        </div>
    </div>
    <?php endif; ?>

    <main class="main-content">
