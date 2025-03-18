<?php
/**
 * Admin sınıfı
 * 
 * Eklentinin admin arayüzüyle ilgili işlevleri yönetir
 * 
 * @package WooCommerce Advanced Stock Manager
 */

// Doğrudan erişimi engelle
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin Sınıfı
 */
class WASM_Admin {
    /**
     * Sınıfı başlat
     */
    public function __construct() {
        // Burada admin özelliklerini tanımlayabilirsiniz
    }
    
    /**
     * Admin arayüzünü oluştur
     */
    public function render_admin_page() {
        ?>
        <div class="wrap">
            <div id="wasm-app"></div>
        </div>
        <?php
    }
    
    /**
     * Ayarlar sayfasını oluştur
     */
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('WooCommerce Gelişmiş Stok Yönetimi Ayarları', 'wc-advanced-stock-manager'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('wasm_settings_group');
                do_settings_sections('wasm_settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
}