<?php
/**
 * Arama API - Canli arama sonuclari
 */
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

$query = trim($_GET['q'] ?? '');

if (strlen($query) < 2) {
    jsonResponse(['success' => true, 'products' => []]);
}

$db = getDB();
$searchParam = "%$query%";

$stmt = $db->prepare("SELECT p.id, p.name, p.slug, p.price, p.old_price, p.image, b.name as brand_name FROM products p LEFT JOIN brands b ON p.brand_id = b.id WHERE p.is_active = 1 AND (p.name LIKE ? OR b.name LIKE ? OR p.short_description LIKE ?) ORDER BY p.is_featured DESC, p.name ASC LIMIT 8");
$stmt->execute([$searchParam, $searchParam, $searchParam]);
$products = $stmt->fetchAll();

$results = [];
foreach ($products as $p) {
    $results[] = [
        'id' => $p['id'],
        'name' => $p['name'],
        'slug' => $p['slug'],
        'price' => formatPrice($p['price']),
        'old_price' => $p['old_price'] ? formatPrice($p['old_price']) : null,
        'image' => $p['image'] ? UPLOAD_URL . $p['image'] : null,
        'brand' => $p['brand_name'],
    ];
}

jsonResponse(['success' => true, 'products' => $results]);
