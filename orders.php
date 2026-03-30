<?php
/**
 * Siparislerim Sayfasi
 */
require_once __DIR__ . '/includes/functions.php';
initSession();

if (!isLoggedIn()) {
    redirect(SITE_URL . '/login.php');
}

$db = getDB();

// Siparis detay
$orderId = $_GET['id'] ?? null;

if ($orderId) {
    $stmt = $db->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
    $stmt->execute([$orderId, $_SESSION['user_id']]);
    $order = $stmt->fetch();

    if (!$order) {
        setFlash('error', 'Siparis bulunamadi.');
        redirect(SITE_URL . '/orders.php');
    }

    $itemsStmt = $db->prepare("SELECT oi.*, p.slug, p.image FROM order_items oi LEFT JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
    $itemsStmt->execute([$orderId]);
    $orderItems = $itemsStmt->fetchAll();
}

// Siparis listesi
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;

$countStmt = $db->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ?");
$countStmt->execute([$_SESSION['user_id']]);
$total = (int) $countStmt->fetchColumn();
$pagination = paginate($total, $perPage, $page);

$ordersStmt = $db->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?");
$ordersStmt->execute([$_SESSION['user_id'], $pagination['per_page'], $pagination['offset']]);
$orders = $ordersStmt->fetchAll();

$statusLabels = [
    'pending' => ['Beklemede', 'warning'],
    'processing' => ['Hazirlaniyor', 'info'],
    'shipped' => ['Kargoda', 'primary'],
    'delivered' => ['Teslim Edildi', 'success'],
    'cancelled' => ['Iptal Edildi', 'error'],
];

$paymentLabels = [
    'credit_card' => 'Kredi Karti',
    'bank_transfer' => 'Banka Transferi',
    'crypto' => 'Kripto Para',
];

$pageTitle = 'Siparislerim';
require_once __DIR__ . '/includes/header.php';
?>

<section class="page-header page-header-sm">
    <div class="container">
        <nav class="breadcrumb">
            <a href="<?= SITE_URL ?>">Ana Sayfa</a>
            <span>/</span>
            <?php if ($orderId && isset($order)): ?>
                <a href="<?= SITE_URL ?>/orders.php">Siparislerim</a>
                <span>/</span>
                <span><?= sanitize($order['order_number']) ?></span>
            <?php else: ?>
                <span>Siparislerim</span>
            <?php endif; ?>
        </nav>
        <h1><?= $orderId && isset($order) ? 'Siparis Detayi' : 'Siparislerim' ?></h1>
    </div>
</section>

<section class="orders-section">
    <div class="container">
        <?php if ($orderId && isset($order)): ?>
            <!-- Siparis Detay -->
            <div class="order-detail">
                <div class="order-detail-header">
                    <div>
                        <h2>Siparis #<?= sanitize($order['order_number']) ?></h2>
                        <p>Tarih: <?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></p>
                    </div>
                    <?php $status = $statusLabels[$order['status']] ?? ['Bilinmiyor', 'default']; ?>
                    <span class="status-badge status-<?= $status[1] ?>"><?= $status[0] ?></span>
                </div>

                <div class="order-detail-grid">
                    <div class="order-items-card">
                        <h3>Siparis Kalemleri</h3>
                        <?php foreach ($orderItems as $oi): ?>
                            <div class="order-item-row">
                                <div class="order-item-image">
                                    <?php if ($oi['image']): ?>
                                        <img src="<?= UPLOAD_URL . $oi['image'] ?>" alt="<?= sanitize($oi['product_name']) ?>">
                                    <?php else: ?>
                                        <div class="product-placeholder-sm"><i class="fas fa-laptop"></i></div>
                                    <?php endif; ?>
                                </div>
                                <div class="order-item-info">
                                    <a href="product.php?slug=<?= $oi['slug'] ?>"><?= sanitize($oi['product_name']) ?></a>
                                    <span>Adet: <?= $oi['quantity'] ?> x <?= formatPrice($oi['price']) ?></span>
                                </div>
                                <span class="order-item-total"><?= formatPrice($oi['total']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="order-summary-card">
                        <h3>Siparis Ozeti</h3>
                        <div class="summary-row"><span>Ara Toplam</span><span><?= formatPrice($order['subtotal']) ?></span></div>
                        <div class="summary-row"><span>Kargo</span><span><?= $order['shipping_cost'] > 0 ? formatPrice($order['shipping_cost']) : 'Ucretsiz' ?></span></div>
                        <div class="summary-row"><span>KDV</span><span><?= formatPrice($order['tax']) ?></span></div>
                        <hr>
                        <div class="summary-row summary-total"><span>Toplam</span><span><?= formatPrice($order['total']) ?></span></div>
                        <hr>
                        <p><strong>Odeme:</strong> <?= $paymentLabels[$order['payment_method']] ?? $order['payment_method'] ?></p>
                        <p><strong>Teslimat:</strong><br>
                            <?= sanitize($order['shipping_first_name'] . ' ' . $order['shipping_last_name']) ?><br>
                            <?= sanitize($order['shipping_address']) ?><br>
                            <?= sanitize($order['shipping_district'] . ', ' . $order['shipping_city']) ?><br>
                            <?= sanitize($order['shipping_country']) ?> <?= sanitize($order['shipping_postal_code']) ?>
                        </p>
                    </div>
                </div>

                <a href="<?= SITE_URL ?>/orders.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Siparislerime Don</a>
            </div>

        <?php else: ?>
            <!-- Siparis Listesi -->
            <?php if (empty($orders)): ?>
                <div class="empty-state">
                    <i class="fas fa-box-open"></i>
                    <h3>Henuz Siparisiniz Yok</h3>
                    <p>Ilk siparisizi vermek icin alisverise baslayin.</p>
                    <a href="<?= SITE_URL ?>/products.php" class="btn btn-primary">Alisverise Basla</a>
                </div>
            <?php else: ?>
                <div class="orders-table">
                    <?php foreach ($orders as $ord): ?>
                        <?php $status = $statusLabels[$ord['status']] ?? ['Bilinmiyor', 'default']; ?>
                        <div class="order-row">
                            <div class="order-row-info">
                                <strong>#<?= sanitize($ord['order_number']) ?></strong>
                                <span><?= date('d.m.Y H:i', strtotime($ord['created_at'])) ?></span>
                            </div>
                            <span class="status-badge status-<?= $status[1] ?>"><?= $status[0] ?></span>
                            <span class="order-row-total"><?= formatPrice($ord['total']) ?></span>
                            <a href="orders.php?id=<?= $ord['id'] ?>" class="btn btn-sm btn-outline">Detay</a>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($pagination['total_pages'] > 1): ?>
                    <nav class="pagination">
                        <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                            <a href="?page=<?= $i ?>" class="page-link <?= $i === $pagination['current_page'] ? 'active' : '' ?>"><?= $i ?></a>
                        <?php endfor; ?>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
