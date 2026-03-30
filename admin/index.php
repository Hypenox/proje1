<?php
/**
 * Admin Dashboard
 */
$adminTitle = 'Dashboard';
require_once __DIR__ . '/includes/admin_header.php';

// Son siparisler
$recentOrders = $db->query("SELECT o.*, u.first_name, u.last_name FROM orders o LEFT JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT 5")->fetchAll();

// Son kullanicilar
$recentUsers = $db->query("SELECT * FROM users WHERE role = 'customer' ORDER BY created_at DESC LIMIT 5")->fetchAll();

// Aylik gelir
$monthlyRevenue = $db->query("SELECT COALESCE(SUM(total), 0) FROM orders WHERE status != 'cancelled' AND MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())")->fetchColumn();

// Dusuk stok urunler
$lowStock = $db->query("SELECT * FROM products WHERE stock <= 5 AND is_active = 1 ORDER BY stock ASC LIMIT 5")->fetchAll();

$statusLabels = [
    'pending' => ['Beklemede', 'warning'],
    'processing' => ['Hazirlaniyor', 'info'],
    'shipped' => ['Kargoda', 'primary'],
    'delivered' => ['Teslim Edildi', 'success'],
    'cancelled' => ['Iptal Edildi', 'error'],
];
?>

<h1 class="page-title">Dashboard</h1>

<!-- Istatistik Kartlari -->
<div class="stats-grid">
    <div class="stat-card stat-purple">
        <div class="stat-icon"><i class="fas fa-shopping-bag"></i></div>
        <div class="stat-info">
            <span class="stat-value"><?= $totalOrders ?></span>
            <span class="stat-label">Toplam Siparis</span>
        </div>
    </div>
    <div class="stat-card stat-blue">
        <div class="stat-icon"><i class="fas fa-box"></i></div>
        <div class="stat-info">
            <span class="stat-value"><?= $totalProducts ?></span>
            <span class="stat-label">Toplam Urun</span>
        </div>
    </div>
    <div class="stat-card stat-green">
        <div class="stat-icon"><i class="fas fa-lira-sign"></i></div>
        <div class="stat-info">
            <span class="stat-value"><?= formatPrice($totalRevenue) ?></span>
            <span class="stat-label">Toplam Gelir</span>
        </div>
    </div>
    <div class="stat-card stat-orange">
        <div class="stat-icon"><i class="fas fa-users"></i></div>
        <div class="stat-info">
            <span class="stat-value"><?= $totalUsers ?></span>
            <span class="stat-label">Toplam Uye</span>
        </div>
    </div>
</div>

<div class="dashboard-grid">
    <!-- Son Siparisler -->
    <div class="admin-card">
        <div class="card-header">
            <h3><i class="fas fa-shopping-bag"></i> Son Siparisler</h3>
            <a href="<?= SITE_URL ?>/admin/orders.php" class="card-link">Tumunu Gor</a>
        </div>
        <div class="card-body">
            <?php if (empty($recentOrders)): ?>
                <p class="empty-text">Henuz siparis yok.</p>
            <?php else: ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Siparis No</th>
                            <th>Musteri</th>
                            <th>Toplam</th>
                            <th>Durum</th>
                            <th>Tarih</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentOrders as $order): ?>
                            <?php $status = $statusLabels[$order['status']] ?? ['Bilinmiyor', 'default']; ?>
                            <tr>
                                <td><a href="orders.php?id=<?= $order['id'] ?>">#<?= sanitize($order['order_number']) ?></a></td>
                                <td><?= sanitize($order['first_name'] . ' ' . $order['last_name']) ?></td>
                                <td><?= formatPrice($order['total']) ?></td>
                                <td><span class="badge badge-<?= $status[1] ?>"><?= $status[0] ?></span></td>
                                <td><?= date('d.m.Y', strtotime($order['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Dusuk Stok -->
    <div class="admin-card">
        <div class="card-header">
            <h3><i class="fas fa-exclamation-triangle"></i> Dusuk Stok Uyarisi</h3>
        </div>
        <div class="card-body">
            <?php if (empty($lowStock)): ?>
                <p class="empty-text">Dusuk stoklu urun yok.</p>
            <?php else: ?>
                <table class="admin-table">
                    <thead><tr><th>Urun</th><th>Stok</th></tr></thead>
                    <tbody>
                        <?php foreach ($lowStock as $ls): ?>
                            <tr>
                                <td><a href="edit-product.php?id=<?= $ls['id'] ?>"><?= sanitize($ls['name']) ?></a></td>
                                <td><span class="badge badge-error"><?= $ls['stock'] ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Son Uyeler -->
    <div class="admin-card">
        <div class="card-header">
            <h3><i class="fas fa-users"></i> Son Uyeler</h3>
            <a href="<?= SITE_URL ?>/admin/users.php" class="card-link">Tumunu Gor</a>
        </div>
        <div class="card-body">
            <?php if (empty($recentUsers)): ?>
                <p class="empty-text">Henuz uye yok.</p>
            <?php else: ?>
                <table class="admin-table">
                    <thead><tr><th>Isim</th><th>E-posta</th><th>Tarih</th></tr></thead>
                    <tbody>
                        <?php foreach ($recentUsers as $ru): ?>
                            <tr>
                                <td><?= sanitize($ru['first_name'] . ' ' . $ru['last_name']) ?></td>
                                <td><?= sanitize($ru['email']) ?></td>
                                <td><?= date('d.m.Y', strtotime($ru['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Aylik Ozet -->
    <div class="admin-card">
        <div class="card-header">
            <h3><i class="fas fa-chart-bar"></i> Bu Ay</h3>
        </div>
        <div class="card-body">
            <div class="monthly-stats">
                <div class="monthly-stat">
                    <span class="monthly-value"><?= formatPrice($monthlyRevenue) ?></span>
                    <span class="monthly-label">Aylik Gelir</span>
                </div>
                <div class="monthly-stat">
                    <span class="monthly-value"><?= $pendingOrders ?></span>
                    <span class="monthly-label">Bekleyen Siparis</span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
