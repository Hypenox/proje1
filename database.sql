-- =====================================================
-- TechStore E-Ticaret Veritabani Semasi
-- XAMPP / phpMyAdmin uyumlu
-- =====================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+03:00";

CREATE DATABASE IF NOT EXISTS `techstore` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `techstore`;

-- =====================================================
-- KULLANICILAR
-- =====================================================
CREATE TABLE `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `first_name` VARCHAR(100) NOT NULL,
    `last_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `address` TEXT DEFAULT NULL,
    `postal_code` VARCHAR(10) DEFAULT NULL,
    `city` VARCHAR(100) DEFAULT NULL,
    `district` VARCHAR(100) DEFAULT NULL,
    `country` VARCHAR(100) DEFAULT 'Turkiye',
    `default_payment` ENUM('credit_card','bank_transfer','crypto') DEFAULT 'credit_card',
    `email_verified` TINYINT(1) DEFAULT 0,
    `verification_token` VARCHAR(255) DEFAULT NULL,
    `role` ENUM('customer','admin') DEFAULT 'customer',
    `avatar` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_email` (`email`),
    INDEX `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- KATEGORILER
-- =====================================================
CREATE TABLE `categories` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL,
    `slug` VARCHAR(150) NOT NULL UNIQUE,
    `type` ENUM('computer','accessory') NOT NULL DEFAULT 'computer',
    `description` TEXT DEFAULT NULL,
    `image` VARCHAR(255) DEFAULT NULL,
    `parent_id` INT UNSIGNED DEFAULT NULL,
    `sort_order` INT DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_type` (`type`),
    INDEX `idx_slug` (`slug`),
    INDEX `idx_parent` (`parent_id`),
    FOREIGN KEY (`parent_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- MARKALAR
-- =====================================================
CREATE TABLE `brands` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL,
    `slug` VARCHAR(150) NOT NULL UNIQUE,
    `logo` VARCHAR(255) DEFAULT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- URUNLER
-- =====================================================
CREATE TABLE `products` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL UNIQUE,
    `description` TEXT DEFAULT NULL,
    `short_description` VARCHAR(500) DEFAULT NULL,
    `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `old_price` DECIMAL(10,2) DEFAULT NULL,
    `stock` INT UNSIGNED DEFAULT 0,
    `sku` VARCHAR(50) DEFAULT NULL,
    `category_id` INT UNSIGNED DEFAULT NULL,
    `brand_id` INT UNSIGNED DEFAULT NULL,
    `image` VARCHAR(255) DEFAULT NULL,
    `specs` JSON DEFAULT NULL,
    `is_featured` TINYINT(1) DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `view_count` INT UNSIGNED DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_slug` (`slug`),
    INDEX `idx_category` (`category_id`),
    INDEX `idx_brand` (`brand_id`),
    INDEX `idx_featured` (`is_featured`),
    INDEX `idx_price` (`price`),
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`brand_id`) REFERENCES `brands`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- URUN GORSELLERI
-- =====================================================
CREATE TABLE `product_images` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `product_id` INT UNSIGNED NOT NULL,
    `image` VARCHAR(255) NOT NULL,
    `sort_order` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SEPET
-- =====================================================
CREATE TABLE `cart_items` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED DEFAULT NULL,
    `session_id` VARCHAR(255) DEFAULT NULL,
    `product_id` INT UNSIGNED NOT NULL,
    `quantity` INT UNSIGNED NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_user` (`user_id`),
    INDEX `idx_session` (`session_id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SIPARISLER
-- =====================================================
CREATE TABLE `orders` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `order_number` VARCHAR(20) NOT NULL UNIQUE,
    `subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `shipping_cost` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `tax` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `total` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `status` ENUM('pending','processing','shipped','delivered','cancelled') DEFAULT 'pending',
    `payment_method` ENUM('credit_card','bank_transfer','crypto') DEFAULT 'credit_card',
    `shipping_first_name` VARCHAR(100) NOT NULL,
    `shipping_last_name` VARCHAR(100) NOT NULL,
    `shipping_phone` VARCHAR(20) DEFAULT NULL,
    `shipping_address` TEXT NOT NULL,
    `shipping_city` VARCHAR(100) NOT NULL,
    `shipping_district` VARCHAR(100) DEFAULT NULL,
    `shipping_postal_code` VARCHAR(10) DEFAULT NULL,
    `shipping_country` VARCHAR(100) DEFAULT 'Turkiye',
    `notes` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_user` (`user_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_order_number` (`order_number`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SIPARIS KALEMLERI
-- =====================================================
CREATE TABLE `order_items` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT UNSIGNED NOT NULL,
    `product_id` INT UNSIGNED NOT NULL,
    `product_name` VARCHAR(255) NOT NULL,
    `quantity` INT UNSIGNED NOT NULL DEFAULT 1,
    `price` DECIMAL(10,2) NOT NULL,
    `total` DECIMAL(10,2) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SITE AYARLARI
-- =====================================================
CREATE TABLE `settings` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(100) NOT NULL UNIQUE,
    `setting_value` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SLIDER / BANNER
-- =====================================================
CREATE TABLE `sliders` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) DEFAULT NULL,
    `subtitle` VARCHAR(255) DEFAULT NULL,
    `image` VARCHAR(255) NOT NULL,
    `link` VARCHAR(255) DEFAULT NULL,
    `sort_order` INT DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- VARSAYILAN VERILER
-- =====================================================

-- Admin kullanici (sifre: Admin123!)
INSERT INTO `users` (`first_name`, `last_name`, `email`, `password`, `phone`, `role`, `email_verified`, `country`) VALUES
('Admin', 'User', 'admin@techstore.com', '$2y$10$QOr276UmUjXNlE0Cpe4zCect5uU33tuNLeH1Ll/GqLMc2CTSLv7Ti', '5551234567', 'admin', 1, 'Turkiye');

-- Varsayilan markalar
INSERT INTO `brands` (`name`, `slug`, `logo`, `is_active`) VALUES
('Monster', 'monster', NULL, 1),
('HP', 'hp', NULL, 1),
('Casper', 'casper', NULL, 1),
('Lenovo', 'lenovo', NULL, 1),
('Excalibur', 'excalibur', NULL, 1),
('Asus', 'asus', NULL, 1),
('MSI', 'msi', NULL, 1),
('Dell', 'dell', NULL, 1),
('Acer', 'acer', NULL, 1),
('Apple', 'apple', NULL, 1);

-- Varsayilan kategoriler
INSERT INTO `categories` (`name`, `slug`, `type`, `description`, `sort_order`, `is_active`) VALUES
('Oyun Bilgisayarlari', 'oyun-bilgisayarlari', 'computer', 'Yuksek performansli oyun bilgisayarlari', 1, 1),
('Is Bilgisayarlari', 'is-bilgisayarlari', 'computer', 'Profesyonel is bilgisayarlari', 2, 1),
('Ogrenci Bilgisayarlari', 'ogrenci-bilgisayarlari', 'computer', 'Ogrenciler icin uygun fiyatli bilgisayarlar', 3, 1),
('Masaustu Bilgisayarlar', 'masaustu-bilgisayarlar', 'computer', 'Masaustu oyun ve is bilgisayarlari', 4, 1),
('Ultrabook', 'ultrabook', 'computer', 'Ince ve hafif laptoplar', 5, 1),
('Klavye', 'klavye', 'accessory', 'Mekanik ve membran klavyeler', 10, 1),
('Mouse', 'mouse', 'accessory', 'Oyun ve ofis mouselari', 11, 1),
('Kulaklik', 'kulaklik', 'accessory', 'Oyun ve muzik kulakliklari', 12, 1),
('Monitor', 'monitor', 'accessory', 'Oyun ve profesyonel monitorler', 13, 1),
('Canta & Kilif', 'canta-kilif', 'accessory', 'Laptop cantalari ve kiliflari', 14, 1),
('Mouse Pad', 'mouse-pad', 'accessory', 'Oyuncu mouse padleri', 15, 1),
('Sogutucu', 'sogutucu', 'accessory', 'Laptop sogutucular', 16, 1);

-- Ornek urunler
INSERT INTO `products` (`name`, `slug`, `description`, `short_description`, `price`, `old_price`, `stock`, `sku`, `category_id`, `brand_id`, `specs`, `is_featured`, `is_active`) VALUES
('Monster Abra A5 V21.3', 'monster-abra-a5-v21-3', 'Monster Abra A5 V21.3 oyun bilgisayari, Intel Core i7-13700H islemci, NVIDIA GeForce RTX 4060 ekran karti, 16GB RAM ve 512GB SSD ile donanimldir. 15.6 inc Full HD IPS ekran ve 144Hz yenileme hizi ile akici oyun deneyimi sunar.', 'Intel i7-13700H | RTX 4060 | 16GB RAM | 512GB SSD', 42999.00, 47999.00, 25, 'MNS-ABRA-A5', 1, 1, '{"islemci": "Intel Core i7-13700H", "ekran_karti": "NVIDIA GeForce RTX 4060 8GB", "ram": "16GB DDR5", "depolama": "512GB NVMe SSD", "ekran": "15.6 inc FHD IPS 144Hz", "isletim_sistemi": "FreeDOS", "agirlik": "2.2 kg"}', 1, 1),

('Monster Tulpar T7 V21.4', 'monster-tulpar-t7-v21-4', 'Monster Tulpar T7 V21.4, Intel Core i9-13900H ve NVIDIA RTX 4070 ekran karti ile donatilmis premium oyun laptopu. 17.3 inc QHD 165Hz ekran, 32GB DDR5 RAM ve 1TB SSD.', 'Intel i9-13900H | RTX 4070 | 32GB RAM | 1TB SSD', 64999.00, 72999.00, 15, 'MNS-TULPAR-T7', 1, 1, '{"islemci": "Intel Core i9-13900H", "ekran_karti": "NVIDIA GeForce RTX 4070 8GB", "ram": "32GB DDR5", "depolama": "1TB NVMe SSD", "ekran": "17.3 inc QHD IPS 165Hz", "isletim_sistemi": "FreeDOS", "agirlik": "2.6 kg"}', 1, 1),

('HP Victus 15', 'hp-victus-15', 'HP Victus 15, AMD Ryzen 7 7840HS islemci ve NVIDIA RTX 4050 ekran karti ile uygun fiyatli oyun deneyimi sunar. 15.6 inc FHD 144Hz ekran.', 'AMD Ryzen 7 7840HS | RTX 4050 | 16GB RAM | 512GB SSD', 34999.00, 39999.00, 30, 'HP-VICTUS-15', 1, 2, '{"islemci": "AMD Ryzen 7 7840HS", "ekran_karti": "NVIDIA GeForce RTX 4050 6GB", "ram": "16GB DDR5", "depolama": "512GB NVMe SSD", "ekran": "15.6 inc FHD IPS 144Hz", "isletim_sistemi": "FreeDOS", "agirlik": "2.3 kg"}', 1, 1),

('Casper Excalibur G870', 'casper-excalibur-g870', 'Casper Excalibur G870, Intel Core i7-13700H islemci, RTX 4060 ekran karti, 16GB RAM, 1TB SSD. 15.6 inc FHD 144Hz ekran ile akici oyun deneyimi.', 'Intel i7-13700H | RTX 4060 | 16GB RAM | 1TB SSD', 38999.00, 44999.00, 20, 'CSP-EXC-G870', 1, 4, '{"islemci": "Intel Core i7-13700H", "ekran_karti": "NVIDIA GeForce RTX 4060 8GB", "ram": "16GB DDR5", "depolama": "1TB NVMe SSD", "ekran": "15.6 inc FHD IPS 144Hz", "isletim_sistemi": "FreeDOS", "agirlik": "2.4 kg"}', 1, 1),

('Lenovo Legion 5 Pro', 'lenovo-legion-5-pro', 'Lenovo Legion 5 Pro, AMD Ryzen 9 7945HX ve RTX 4070 ile ust duzey oyun performansi. 16 inc WQXGA 165Hz ekran, 32GB RAM, 1TB SSD.', 'AMD Ryzen 9 7945HX | RTX 4070 | 32GB RAM | 1TB SSD', 59999.00, 67999.00, 12, 'LNV-LEGION-5P', 1, 4, '{"islemci": "AMD Ryzen 9 7945HX", "ekran_karti": "NVIDIA GeForce RTX 4070 8GB", "ram": "32GB DDR5", "depolama": "1TB NVMe SSD", "ekran": "16 inc WQXGA IPS 165Hz", "isletim_sistemi": "Windows 11 Home", "agirlik": "2.5 kg"}', 1, 1),

('MSI Katana 15', 'msi-katana-15', 'MSI Katana 15, Intel Core i7-13620H ve RTX 4050 ile guclu oyun performansi. 15.6 inc FHD 144Hz ekran.', 'Intel i7-13620H | RTX 4050 | 16GB RAM | 512GB SSD', 31999.00, 36999.00, 35, 'MSI-KATANA-15', 1, 7, '{"islemci": "Intel Core i7-13620H", "ekran_karti": "NVIDIA GeForce RTX 4050 6GB", "ram": "16GB DDR5", "depolama": "512GB NVMe SSD", "ekran": "15.6 inc FHD IPS 144Hz", "isletim_sistemi": "FreeDOS", "agirlik": "2.25 kg"}', 1, 1),

('Asus ROG Strix G16', 'asus-rog-strix-g16', 'Asus ROG Strix G16, Intel Core i9-13980HX ve RTX 4080 ile en ust duzey oyun deneyimi. 16 inc QHD+ 240Hz ekran.', 'Intel i9-13980HX | RTX 4080 | 32GB RAM | 1TB SSD', 84999.00, 94999.00, 8, 'ASUS-ROG-G16', 1, 6, '{"islemci": "Intel Core i9-13980HX", "ekran_karti": "NVIDIA GeForce RTX 4080 12GB", "ram": "32GB DDR5", "depolama": "1TB NVMe SSD", "ekran": "16 inc QHD+ IPS 240Hz", "isletim_sistemi": "Windows 11 Home", "agirlik": "2.5 kg"}', 1, 1),

('Dell G15 5530', 'dell-g15-5530', 'Dell G15 5530, Intel Core i7-13650HX ve RTX 4060 ile guvenilir oyun performansi. 15.6 inc FHD 165Hz ekran.', 'Intel i7-13650HX | RTX 4060 | 16GB RAM | 512GB SSD', 36999.00, 42999.00, 22, 'DELL-G15-5530', 1, 8, '{"islemci": "Intel Core i7-13650HX", "ekran_karti": "NVIDIA GeForce RTX 4060 8GB", "ram": "16GB DDR5", "depolama": "512GB NVMe SSD", "ekran": "15.6 inc FHD IPS 165Hz", "isletim_sistemi": "Ubuntu 22.04", "agirlik": "2.65 kg"}', 1, 1),

('HP Omen 16', 'hp-omen-16', 'HP Omen 16 premium oyun laptopu. AMD Ryzen 9 7940HS, RTX 4070, 32GB RAM, 1TB SSD ve 16.1 inc QHD 165Hz ekran.', 'AMD Ryzen 9 7940HS | RTX 4070 | 32GB RAM | 1TB SSD', 62999.00, 69999.00, 10, 'HP-OMEN-16', 1, 2, '{"islemci": "AMD Ryzen 9 7940HS", "ekran_karti": "NVIDIA GeForce RTX 4070 8GB", "ram": "32GB DDR5", "depolama": "1TB NVMe SSD", "ekran": "16.1 inc QHD IPS 165Hz", "isletim_sistemi": "Windows 11 Home", "agirlik": "2.37 kg"}', 1, 1),

('Lenovo IdeaPad Slim 5', 'lenovo-ideapad-slim-5', 'Lenovo IdeaPad Slim 5, Intel Core i5-1335U islemci ile is ve okul icin ideal ultrabook. 14 inc FHD IPS ekran, ince ve hafif tasarim.', 'Intel i5-1335U | Intel Iris Xe | 8GB RAM | 256GB SSD', 17999.00, 21999.00, 40, 'LNV-IDEAPAD-S5', 3, 4, '{"islemci": "Intel Core i5-1335U", "ekran_karti": "Intel Iris Xe", "ram": "8GB DDR4", "depolama": "256GB NVMe SSD", "ekran": "14 inc FHD IPS", "isletim_sistemi": "FreeDOS", "agirlik": "1.46 kg"}', 0, 1),

('Acer Nitro 5 AN515', 'acer-nitro-5-an515', 'Acer Nitro 5, Intel Core i5-13500H ve RTX 4050 ile giris seviyesi oyun laptopu. 15.6 inc FHD 144Hz ekran.', 'Intel i5-13500H | RTX 4050 | 16GB RAM | 512GB SSD', 28999.00, 33999.00, 28, 'ACR-NITRO-5', 1, 9, '{"islemci": "Intel Core i5-13500H", "ekran_karti": "NVIDIA GeForce RTX 4050 6GB", "ram": "16GB DDR5", "depolama": "512GB NVMe SSD", "ekran": "15.6 inc FHD IPS 144Hz", "isletim_sistemi": "FreeDOS", "agirlik": "2.5 kg"}', 0, 1),

('Monster Huma H4 V6.1', 'monster-huma-h4-v6-1', 'Monster Huma H4 masaustu oyun bilgisayari. Intel Core i7-13700F, RTX 4070, 32GB RAM, 1TB SSD ile ust duzey masaustu performansi.', 'Intel i7-13700F | RTX 4070 | 32GB RAM | 1TB SSD', 54999.00, 62999.00, 18, 'MNS-HUMA-H4', 4, 1, '{"islemci": "Intel Core i7-13700F", "ekran_karti": "NVIDIA GeForce RTX 4070 12GB", "ram": "32GB DDR5", "depolama": "1TB NVMe SSD", "kasa": "Mid-Tower RGB", "guc_kaynagi": "650W 80+ Gold", "isletim_sistemi": "FreeDOS"}', 1, 1);

-- Ornek aksesuarlar
INSERT INTO `products` (`name`, `slug`, `description`, `short_description`, `price`, `old_price`, `stock`, `sku`, `category_id`, `brand_id`, `specs`, `is_featured`, `is_active`) VALUES
('Monster Pusat V20 Klavye', 'monster-pusat-v20-klavye', 'Monster Pusat V20 mekanik oyun klavyesi. RGB aydinlatma, Cherry MX Red switchler, makro tuslari.', 'Mekanik | Cherry MX Red | RGB | Turkce Q', 3499.00, 4299.00, 50, 'MNS-PUSAT-V20', 6, 1, '{"tip": "Mekanik", "switch": "Cherry MX Red", "aydinlatma": "RGB", "layout": "Turkce Q", "baglanti": "USB-C"}', 0, 1),

('Logitech G Pro X Superlight', 'logitech-g-pro-x-superlight', 'Logitech G Pro X Superlight kablosuz oyun mouseu. 25K HERO sensoru, 63g agirlik.', 'Kablosuz | 25K DPI | 63g | 70 saat pil', 2999.00, 3499.00, 45, 'LOG-GPROX-SL', 7, NULL, '{"sensor": "HERO 25K", "dpi": "25600", "agirlik": "63g", "baglanti": "Lightspeed Kablosuz", "pil_omru": "70 saat"}', 0, 1),

('HyperX Cloud III', 'hyperx-cloud-iii', 'HyperX Cloud III oyun kulakligi. DTS Headphone:X surround ses, 53mm suruculer, hafif ve rahat tasarim.', 'DTS Surround | 53mm | Mikrofon | USB/3.5mm', 2199.00, 2799.00, 60, 'HPX-CLOUD-III', 8, NULL, '{"surucu": "53mm", "frekans": "10Hz-21kHz", "mikrofon": "Cikarilabilir", "baglanti": "USB / 3.5mm", "ses": "DTS Headphone:X"}', 0, 1),

('Samsung Odyssey G7 27"', 'samsung-odyssey-g7-27', 'Samsung Odyssey G7 27 inc WQHD oyun monitoru. 240Hz, 1ms, 1000R kavisli VA paneli.', '27" WQHD | 240Hz | 1ms | 1000R Kavisli', 12999.00, 15999.00, 15, 'SAM-ODYSSEY-G7', 9, NULL, '{"boyut": "27 inc", "cozunurluk": "2560x1440 WQHD", "panel": "VA", "yenileme": "240Hz", "tepki": "1ms", "kavis": "1000R"}', 0, 1),

('Monster Pusat KM2 Mouse Pad', 'monster-pusat-km2-mouse-pad', 'Monster Pusat KM2 RGB oyuncu mouse padi. XXL boyut, kaucuk taban, RGB aydinlatma.', 'XXL | 900x400mm | RGB | Kaucuk Taban', 699.00, 899.00, 80, 'MNS-PUSAT-KM2', 11, 1, '{"boyut": "900x400x4mm", "malzeme": "Mikro dokuma kumas", "taban": "Kaucuk", "aydinlatma": "RGB", "baglanti": "USB"}', 0, 1);

-- Site ayarlari
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('site_name', 'TechStore'),
('site_description', 'En iyi bilgisayar ve aksesuarlar'),
('site_email', 'info@techstore.com'),
('site_phone', '+90 212 555 0000'),
('site_address', 'Istanbul, Turkiye'),
('shipping_cost', '0'),
('tax_rate', '20'),
('currency', 'TL'),
('currency_symbol', 'TL');

-- Ornek slider
INSERT INTO `sliders` (`title`, `subtitle`, `image`, `link`, `sort_order`, `is_active`) VALUES
('Oyun Dunyasina Adim At', 'En guclu oyun bilgisayarlari burada', 'slider-1.jpg', 'products.php?category=oyun-bilgisayarlari', 1, 1),
('Yeni Nesil Performans', 'RTX 4000 serisi ekran kartlari ile tanisma', 'slider-2.jpg', 'products.php?category=oyun-bilgisayarlari', 2, 1),
('Aksesuarlarini Tamamla', 'Klavye, mouse, kulaklik ve daha fazlasi', 'slider-3.jpg', 'products.php?type=accessory', 3, 1);

COMMIT;
