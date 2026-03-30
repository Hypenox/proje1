    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-top">
            <div class="container">
                <div class="footer-grid">
                    <div class="footer-col">
                        <a href="<?= SITE_URL ?>" class="footer-logo">
                            <span class="logo-icon"><i class="fas fa-laptop"></i></span>
                            <span class="logo-text">Tech<span>Store</span></span>
                        </a>
                        <p class="footer-desc">En iyi oyun bilgisayarlari ve aksesuarlar. Yuksek performans, uygun fiyat garantisi.</p>
                        <div class="footer-social">
                            <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                            <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                            <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                            <a href="#" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
                            <a href="#" aria-label="Discord"><i class="fab fa-discord"></i></a>
                        </div>
                    </div>
                    <div class="footer-col">
                        <h4>Bilgisayarlar</h4>
                        <ul>
                            <?php
                            $footerDb = getDB();
                            $footerCats = $footerDb->query("SELECT name, slug FROM categories WHERE type = 'computer' AND is_active = 1 ORDER BY sort_order LIMIT 6")->fetchAll();
                            foreach ($footerCats as $fcat):
                            ?>
                            <li><a href="<?= SITE_URL ?>/products.php?category=<?= $fcat['slug'] ?>"><?= sanitize($fcat['name']) ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div class="footer-col">
                        <h4>Aksesuarlar</h4>
                        <ul>
                            <?php
                            $footerAccCats = $footerDb->query("SELECT name, slug FROM categories WHERE type = 'accessory' AND is_active = 1 ORDER BY sort_order LIMIT 6")->fetchAll();
                            foreach ($footerAccCats as $fcat):
                            ?>
                            <li><a href="<?= SITE_URL ?>/products.php?category=<?= $fcat['slug'] ?>"><?= sanitize($fcat['name']) ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div class="footer-col">
                        <h4>Hesabim</h4>
                        <ul>
                            <li><a href="<?= SITE_URL ?>/profile.php">Profilim</a></li>
                            <li><a href="<?= SITE_URL ?>/orders.php">Siparislerim</a></li>
                            <li><a href="<?= SITE_URL ?>/cart.php">Sepetim</a></li>
                        </ul>
                    </div>
                    <div class="footer-col">
                        <h4>Iletisim</h4>
                        <ul class="footer-contact">
                            <li><i class="fas fa-map-marker-alt"></i> Istanbul, Turkiye</li>
                            <li><i class="fas fa-phone-alt"></i> +90 212 555 0000</li>
                            <li><i class="fas fa-envelope"></i> info@techstore.com</li>
                            <li><i class="fas fa-clock"></i> Pzt-Cuma: 09:00 - 18:00</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="footer-middle">
            <div class="container">
                <div class="payment-methods">
                    <span>Odeme Yontemleri:</span>
                    <div class="payment-icons">
                        <i class="fab fa-cc-visa"></i>
                        <i class="fab fa-cc-mastercard"></i>
                        <i class="fab fa-cc-amex"></i>
                        <i class="fab fa-bitcoin"></i>
                        <i class="fas fa-university"></i>
                    </div>
                </div>
                <div class="security-badges">
                    <span><i class="fas fa-lock"></i> 256-bit SSL</span>
                    <span><i class="fas fa-shield-alt"></i> Guvenli Odeme</span>
                    <span><i class="fas fa-undo"></i> 14 Gun Iade</span>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <div class="container">
                <p>&copy; <?= date('Y') ?> TechStore. Tum haklari saklidir.</p>
            </div>
        </div>
    </footer>

    <!-- Yukari Git -->
    <button class="scroll-top" id="scrollTop" aria-label="Yukari git">
        <i class="fas fa-chevron-up"></i>
    </button>

    <script src="<?= SITE_URL ?>/assets/js/main.js"></script>
</body>
</html>
