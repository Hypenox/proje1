<?php
/**
 * Sepet API
 */
require_once __DIR__ . '/../includes/functions.php';
initSession();

header('Content-Type: application/json; charset=utf-8');

$db = getDB();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'add':
        $productId = (int) ($_POST['product_id'] ?? 0);
        $quantity = max(1, (int) ($_POST['quantity'] ?? 1));

        if (!$productId) {
            jsonResponse(['success' => false, 'message' => 'Gecersiz urun.'], 400);
        }

        // Urun kontrol
        $stmt = $db->prepare("SELECT id, stock, name FROM products WHERE id = ? AND is_active = 1");
        $stmt->execute([$productId]);
        $product = $stmt->fetch();

        if (!$product) {
            jsonResponse(['success' => false, 'message' => 'Urun bulunamadi.'], 404);
        }

        if ($product['stock'] < $quantity) {
            jsonResponse(['success' => false, 'message' => 'Yeterli stok yok.'], 400);
        }

        // Sepette var mi kontrol
        if (isLoggedIn()) {
            $check = $db->prepare("SELECT id, quantity FROM cart_items WHERE user_id = ? AND product_id = ?");
            $check->execute([$_SESSION['user_id'], $productId]);
        } else {
            $check = $db->prepare("SELECT id, quantity FROM cart_items WHERE session_id = ? AND product_id = ?");
            $check->execute([session_id(), $productId]);
        }
        $existing = $check->fetch();

        if ($existing) {
            $newQty = $existing['quantity'] + $quantity;
            if ($newQty > $product['stock']) $newQty = $product['stock'];
            $db->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?")->execute([$newQty, $existing['id']]);
        } else {
            if (isLoggedIn()) {
                $db->prepare("INSERT INTO cart_items (user_id, product_id, quantity) VALUES (?, ?, ?)")->execute([$_SESSION['user_id'], $productId, $quantity]);
            } else {
                $db->prepare("INSERT INTO cart_items (session_id, product_id, quantity) VALUES (?, ?, ?)")->execute([session_id(), $productId, $quantity]);
            }
        }

        jsonResponse([
            'success' => true,
            'message' => $product['name'] . ' sepete eklendi.',
            'cart_count' => getCartCount(),
            'cart_total' => formatPrice(getCartTotal()),
        ]);
        break;

    case 'update':
        $itemId = (int) ($_POST['item_id'] ?? 0);
        $quantity = max(0, (int) ($_POST['quantity'] ?? 0));

        if ($quantity === 0) {
            // Kaldir
            if (isLoggedIn()) {
                $db->prepare("DELETE FROM cart_items WHERE id = ? AND user_id = ?")->execute([$itemId, $_SESSION['user_id']]);
            } else {
                $db->prepare("DELETE FROM cart_items WHERE id = ? AND session_id = ?")->execute([$itemId, session_id()]);
            }
        } else {
            if (isLoggedIn()) {
                $db->prepare("UPDATE cart_items SET quantity = ? WHERE id = ? AND user_id = ?")->execute([$quantity, $itemId, $_SESSION['user_id']]);
            } else {
                $db->prepare("UPDATE cart_items SET quantity = ? WHERE id = ? AND session_id = ?")->execute([$quantity, $itemId, session_id()]);
            }
        }

        jsonResponse([
            'success' => true,
            'cart_count' => getCartCount(),
            'cart_total' => formatPrice(getCartTotal()),
        ]);
        break;

    case 'remove':
        $itemId = (int) ($_POST['item_id'] ?? 0);
        if (isLoggedIn()) {
            $db->prepare("DELETE FROM cart_items WHERE id = ? AND user_id = ?")->execute([$itemId, $_SESSION['user_id']]);
        } else {
            $db->prepare("DELETE FROM cart_items WHERE id = ? AND session_id = ?")->execute([$itemId, session_id()]);
        }

        jsonResponse([
            'success' => true,
            'message' => 'Urun sepetten kaldirildi.',
            'cart_count' => getCartCount(),
            'cart_total' => formatPrice(getCartTotal()),
        ]);
        break;

    case 'count':
        jsonResponse([
            'success' => true,
            'cart_count' => getCartCount(),
            'cart_total' => formatPrice(getCartTotal()),
        ]);
        break;

    default:
        jsonResponse(['success' => false, 'message' => 'Gecersiz islem.'], 400);
}
