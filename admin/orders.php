<?php
/**
 * Admin - Siparis Yonetimi
 */
$adminTitle = 'Siparisler';
require_once __DIR__ . '/includes/admin_header.php';

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

// Durum guncelle
if (isset($_POST['update_status'])) {
    $orderId = (int) $_POST['order_id'];
    $newStatus = $_POST['status'];
    $db->prepare("UPDATE orders SET status = ? WHERE id = ?")->execute([$newStatus, $orderId]);
    setFlash('success', 'Siparis durumu guncellendi.');
    redirect(SITE_URL . '/admin/orders.php' . (isset($_GET['id']) ? '?id=' . $_GET['id'] : ''));
}

// Siparis detay
$orderId = $_GET['id'] ?? null;
if ($orderId) {
    $stmt = $db->prepare("SELECT o.*, u.first_name, u.last_name, u.email, u.phone as user_phone FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE o.id = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();

    if (!$order) {
        setFlash('error', 'Siparis bulunamadi.');
        redirect(SITE_URL . '/admin/orders.php');
    }

    $itemsStmt = $db->prepare("SELECT oi.*, p.slug, p.image FROM order_items oi LEFT JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
    $itemsStmt->execute([$orderId]);
    $orderItems = $itemsStmt->fetchAll();
}

// Siparis listesi
$statusFilter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 20;

$where = ["1=1"];
$params = [];
if ($statusFilter) { $where[] = "o.status = ?"; $params[] = $statusFilter; }
if ($search) { $where[] = "(o.order_number LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%"; }
$whereSQL = implode(' AND ', $where);

$countStmt = $db->prepare("SELECT COUNT(*) FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE $whereSQL");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$pagination = paginate($total, $perPage, $page);

$ordersStmt = $db->prepare("SELECT o.*, u.first_name, u.last_name FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE $whereSQL ORDER BY o.created_at DESC LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}");
$ordersStmt->execute($params);
$orders = $ordersStmt->fetchAll();
?>

<?php if ($orderId && isset($order)): ?>
    <!-- Siparis Detay -->
    <div class="page-header-admin">
        <h1 class="page-title">Siparis #<?= sanitize($order['order_number']) ?></h1>
        <a href="orders.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Geri</a>
    </div>

    <div class="admin-grid-2">
        <div class="admin-card">
            <div class="card-header"><h3>Siparis Kalemleri</h3></div>
            <div class="card-body">
                <table class="admin-table">
                    <thead><tr><th>Gorsel</th><th>Urun</th><th>Adet</th><th>Fiyat</th><th>Toplam</th></tr></thead>
                    <tbody>
                        <?php foreach ($orderItems as $oi): ?>
                            <tr>
                                <td>
                                    <?php if ($oi['image']): ?>
                                        <img src="<?= UPLOAD_URL . $oi['image'] ?>" class="table-thumb-sm" alt="">
                                    <?php else: ?>
                                        <div class="table-thumb-placeholder"><i class="fas fa-image"></i></div>
                                    <?php endif; ?>
                                </td>
                                <td><?= sanitize($oi['product_name']) ?></td>
                                <td><?= $oi['quantity'] ?></td>
                                <td><?= formatPrice($oi['price']) ?></td>
                                <td><?= formatPrice($oi['total']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div>
            <div class="admin-card">
                <div class="card-header"><h3>Siparis Bilgileri</h3></div>
                <div class="card-body">
                    <div class="info-row"><span>Ara Toplam:</span><span><?= formatPrice($order['subtotal']) ?></span></div>
                    <div class="info-row"><span>Kargo:</span><span><?= $order['shipping_cost'] > 0 ? formatPrice($order['shipping_cost']) : 'Ucretsiz' ?></span></div>
                    <div class="info-row"><span>KDV:</span><span><?= formatPrice($order['tax']) ?></span></div>
                    <div class="info-row info-total"><span>Toplam:</span><span><?= formatPrice($order['total']) ?></span></div>
                    <hr>
                    <div class="info-row"><span>Odeme:</span><span><?= $paymentLabels[$order['payment_method']] ?? $order['payment_method'] ?></span></div>
                    <div class="info-row"><span>Tarih:</span><span><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></span></div>

                    <form method="POST" class="status-form">
                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                        <input type="hidden" name="update_status" value="1">
                        <label>Durum:</label>
                        <select name="status" class="form-select" onchange="this.form.submit()">
                            <?php foreach ($statusLabels as $key => $val): ?>
                                <option value="<?= $key ?>" <?= $order['status'] === $key ? 'selected' : '' ?>><?= $val[0] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>
            </div>

            <div class="admin-card">
                <div class="card-header"><h3>Musteri & Teslimat</h3></div>
                <div class="card-body">
                    <p><strong>Musteri:</strong> <?= sanitize($order['first_name'] . ' ' . $order['last_name']) ?></p>
                    <p><strong>E-posta:</strong> <?= sanitize($order['email']) ?></p>
                    <p><strong>Telefon:</strong> <?= sanitize($order['shipping_phone']) ?></p>
                    <hr>
                    <p><strong>Adres:</strong><br>
                        <?= sanitize($order['shipping_first_name'] . ' ' . $order['shipping_last_name']) ?><br>
                        <?= sanitize($order['shipping_address']) ?><br>
                        <?= sanitize($order['shipping_district'] . ', ' . $order['shipping_city']) ?><br>
                        <?= sanitize($order['shipping_country']) ?> <?= sanitize($order['shipping_postal_code']) ?>
                    </p>
                    <?php if ($order['notes']): ?>
                        <hr><p><strong>Not:</strong> <?= sanitize($order['notes']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- Siparis Listesi -->
    <div class="page-header-admin">
        <h1 class="page-title">Siparisler <span class="badge"><?= $total ?></span></h1>
    </div>

    <div class="admin-card">
        <div class="card-body">
            <form method="GET" class="filter-form-inline">
                <input type="text" name="search" placeholder="Siparis no veya musteri adi..." value="<?= sanitize($search) ?>" class="form-input">
                <select name="status" class="form-select">
                    <option value="">Tum Durumlar</option>
                    <?php foreach ($statusLabels as $key => $val): ?>
                        <option value="<?= $key ?>" <?= $statusFilter === $key ? 'selected' : '' ?>><?= $val[0] ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-outline"><i class="fas fa-search"></i></button>
            </form>
        </div>
    </div>

    <div class="admin-card">
        <div class="card-body">
            <?php if (empty($orders)): ?>
                <p class="empty-text">Siparis bulunamadi.</p>
            <?php else: ?>
                <table class="admin-table">
                    <thead><tr><th>Siparis No</th><th>Musteri</th><th>Toplam</th><th>Odeme</th><th>Durum</th><th>Tarih</th><th>Islem</th></tr></thead>
                    <tbody>
                        <?php foreach ($orders as $o): ?>
                            <?php $status = $statusLabels[$o['status']] ?? ['Bilinmiyor', 'default']; ?>
                            <tr>
                                <td><a href="orders.php?id=<?= $o['id'] ?>"><strong>#<?= sanitize($o['order_number']) ?></strong></a></td>
                                <td><?= sanitize($o['first_name'] . ' ' . $o['last_name']) ?></td>
                                <td><?= formatPrice($o['total']) ?></td>
                                <td><?= $paymentLabels[$o['payment_method']] ?? $o['payment_method'] ?></td>
                                <td><span class="badge badge-<?= $status[1] ?>"><?= $status[0] ?></span></td>
                                <td><?= date('d.m.Y H:i', strtotime($o['created_at'])) ?></td>
                                <td><a href="orders.php?id=<?= $o['id'] ?>" class="btn btn-sm btn-outline"><i class="fas fa-eye"></i></a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php if ($pagination['total_pages'] > 1): ?>
                    <nav class="admin-pagination">
                        <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                            <?php $qp = $_GET; $qp['page'] = $i; ?>
                            <a href="?<?= http_build_query($qp) ?>" class="page-link <?= $i === $pagination['current_page'] ? 'active' : '' ?>"><?= $i ?></a>
                        <?php endfor; ?>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
