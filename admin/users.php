<?php
/**
 * Admin - Kullanici Yonetimi
 */
$adminTitle = 'Kullanicilar';
require_once __DIR__ . '/includes/admin_header.php';

// Sil
if (isset($_GET['delete'])) {
    $deleteId = (int) $_GET['delete'];
    if ($deleteId !== $_SESSION['user_id']) {
        $db->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'")->execute([$deleteId]);
        setFlash('success', 'Kullanici silindi.');
    }
    redirect(SITE_URL . '/admin/users.php');
}

// Rol degistir
if (isset($_POST['toggle_role'])) {
    $uid = (int) $_POST['user_id'];
    $newRole = $_POST['new_role'];
    if ($uid !== $_SESSION['user_id']) {
        $db->prepare("UPDATE users SET role = ? WHERE id = ?")->execute([$newRole, $uid]);
        setFlash('success', 'Kullanici rolu guncellendi.');
    }
    redirect(SITE_URL . '/admin/users.php');
}

$search = $_GET['search'] ?? '';
$roleFilter = $_GET['role'] ?? '';
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 20;

$where = ["1=1"];
$params = [];
if ($search) { $where[] = "(first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($roleFilter) { $where[] = "role = ?"; $params[] = $roleFilter; }
$whereSQL = implode(' AND ', $where);

$countStmt = $db->prepare("SELECT COUNT(*) FROM users WHERE $whereSQL");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$pagination = paginate($total, $perPage, $page);

$usersStmt = $db->prepare("SELECT u.*, (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id) as order_count FROM users u WHERE $whereSQL ORDER BY u.created_at DESC LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}");
$usersStmt->execute($params);
$users = $usersStmt->fetchAll();
?>

<div class="page-header-admin">
    <h1 class="page-title">Kullanicilar <span class="badge"><?= $total ?></span></h1>
</div>

<div class="admin-card">
    <div class="card-body">
        <form method="GET" class="filter-form-inline">
            <input type="text" name="search" placeholder="Isim veya e-posta ara..." value="<?= sanitize($search) ?>" class="form-input">
            <select name="role" class="form-select">
                <option value="">Tum Roller</option>
                <option value="customer" <?= $roleFilter === 'customer' ? 'selected' : '' ?>>Musteri</option>
                <option value="admin" <?= $roleFilter === 'admin' ? 'selected' : '' ?>>Admin</option>
            </select>
            <button type="submit" class="btn btn-outline"><i class="fas fa-search"></i></button>
        </form>
    </div>
</div>

<div class="admin-card">
    <div class="card-body">
        <?php if (empty($users)): ?>
            <p class="empty-text">Kullanici bulunamadi.</p>
        <?php else: ?>
            <table class="admin-table">
                <thead><tr><th>Isim</th><th>E-posta</th><th>Telefon</th><th>Siparis</th><th>Rol</th><th>Dogrulama</th><th>Kayit</th><th>Islem</th></tr></thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td><strong><?= sanitize($u['first_name'] . ' ' . $u['last_name']) ?></strong></td>
                            <td><?= sanitize($u['email']) ?></td>
                            <td><?= sanitize($u['phone'] ?? '-') ?></td>
                            <td><?= $u['order_count'] ?></td>
                            <td>
                                <span class="badge <?= $u['role'] === 'admin' ? 'badge-primary' : 'badge-info' ?>">
                                    <?= $u['role'] === 'admin' ? 'Admin' : 'Musteri' ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($u['email_verified']): ?>
                                    <span class="badge badge-success">Dogrulandi</span>
                                <?php else: ?>
                                    <span class="badge badge-warning">Bekliyor</span>
                                <?php endif; ?>
                            </td>
                            <td><?= date('d.m.Y', strtotime($u['created_at'])) ?></td>
                            <td class="actions-cell">
                                <?php if ($u['id'] !== $_SESSION['user_id']): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="toggle_role" value="1">
                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                        <input type="hidden" name="new_role" value="<?= $u['role'] === 'admin' ? 'customer' : 'admin' ?>">
                                        <button type="submit" class="btn btn-sm btn-outline" title="<?= $u['role'] === 'admin' ? 'Musteri Yap' : 'Admin Yap' ?>" onclick="return confirm('Emin misiniz?')">
                                            <i class="fas fa-user-cog"></i>
                                        </button>
                                    </form>
                                    <a href="users.php?delete=<?= $u['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bu kullaniciyi silmek istediginize emin misiniz?')"><i class="fas fa-trash"></i></a>
                                <?php endif; ?>
                            </td>
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

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
