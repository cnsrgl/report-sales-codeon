<?php
/**
 * Plugin Name: WooCommerce Advanced Stock Manager Beta
 * Plugin URI: https://woocommerce.com/products/advanced-stock-manager/
 * Description: Gelişmiş stok yönetim özellikleri ile WooCommerce'i güçlendirir.
 * Version: 1.2.0
 * Author: Codeon
 * Author URI: https://codeon.ch
 * Text Domain: wc-advanced-stock-manager
 * Domain Path: /languages
 * WC requires at least: 3.0.0
 * WC tested up to: 7.1.0
 */

// Doğrudan erişimi engelle
if (!defined('ABSPATH')) {
    exit;
}

// HPOS uyumluluk bildirimi ekle
add_action('before_woocommerce_init', function() {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('product_block_editor', __FILE__, true);
    }
});

// Plugin sabitleri
define('WASM_VERSION', '1.0.0');
define('WASM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WASM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WASM_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Gerekli dosyaları dahil et
require_once WASM_PLUGIN_DIR . 'includes/class-wasm-admin.php';
require_once WASM_PLUGIN_DIR . 'includes/class-wasm-api.php';

// Plugin aktif edildiğinde
register_activation_hook(__FILE__, 'wasm_activate');

// Plugin deaktif edildiğinde
register_deactivation_hook(__FILE__, 'wasm_deactivate');

/**
 * Plugin aktivasyon işlemi
 */
function wasm_activate() {
    // Varsayılan ayarları ekle
    add_option('wasm_settings', array(
        'critical_threshold' => 5,
        'low_threshold' => 15,
        'reorder_threshold' => 1.5,
        'stock_period' => 2
    ));

    // Diğer aktivasyon işlemleri
    flush_rewrite_rules();
}

/**
 * Plugin deaktivasyon işlemi
 */
function wasm_deactivate() {
    // Temizleme işlemleri
    flush_rewrite_rules();
}

/**
 * Plugin başlatma işlemi
 */
function wasm_init() {
    // Admin sınıfını başlat
    global $wasm_admin;
    $wasm_admin = new WASM_Admin();
    
    // Çeviriler
    load_plugin_textdomain('wc-advanced-stock-manager', false, dirname(WASM_PLUGIN_BASENAME) . '/languages');

    // Admin sayfaları ekle
    add_action('admin_menu', 'wasm_add_admin_menu');
    
    // Script ve stilleri ekle
    add_action('admin_enqueue_scripts', 'wasm_admin_scripts');
    
    // Ayarlar sayfası
    add_action('admin_init', 'wasm_register_settings');
}
add_action('plugins_loaded', 'wasm_init');

/**
 * Admin menü sayfalarını ekle
 */
function wasm_add_admin_menu() {
    global $wasm_admin;
    
    // Ana menü
    add_menu_page(
        __('Gelişmiş Stok Yönetimi', 'wc-advanced-stock-manager'),
        __('Stok Yönetimi', 'wc-advanced-stock-manager'),
        'manage_woocommerce',
        'wasm-dashboard',
        array($wasm_admin, 'render_admin_page'),
        'dashicons-chart-area',
        56
    );
    
    // Alt menü: Ayarlar
    add_submenu_page(
        'wasm-dashboard',
        __('Stok Yönetimi Ayarları', 'wc-advanced-stock-manager'),
        __('Ayarlar', 'wc-advanced-stock-manager'),
        'manage_woocommerce',
        'wasm-settings',
        array($wasm_admin, 'render_settings_page')
    );
}

/**
 * Admin script ve stilleri kaydet
 */
function wasm_admin_scripts($hook) {
    // Sadece plugin sayfalarında yükle
    if (strpos($hook, 'wasm-') !== false) {
        // React uygulaması
        wp_enqueue_style('wasm-styles', WASM_PLUGIN_URL . 'assets/css/app.css', array(), WASM_VERSION);
        wp_enqueue_script('wasm-app', WASM_PLUGIN_URL . 'assets/js/app.js', array('wp-api'), WASM_VERSION, true);
        
        // API URL ve nonce gibi ayarları JS'e gönder
        wp_localize_script('wasm-app', 'wasmSettings', array(
            'apiUrl' => rest_url('wc-advanced-stock-manager/v1'),
            'nonce' => wp_create_nonce('wp_rest'),
            'siteUrl' => get_admin_url(),
            'currencySymbol' => get_woocommerce_currency_symbol(),
            'currencyCode' => get_woocommerce_currency(),
            'locale' => get_locale(),
            'translations' => array(
                'title' => __('WooCommerce Gelişmiş Stok Yönetimi', 'wc-advanced-stock-manager'),
                'subtitle' => __('Stok durumunuzu izleyin, satış trendlerini analiz edin ve sipariş planlaması yapın.', 'wc-advanced-stock-manager'),
                'loading' => __('Yükleniyor...', 'wc-advanced-stock-manager'),
                'tryAgain' => __('Tekrar Dene', 'wc-advanced-stock-manager')
                // Diğer çeviriler...
            ),
            'settings' => get_option('wasm_settings')
        ));
    }
}

/**
 * Ayarları kaydet
 */
function wasm_register_settings() {
    register_setting('wasm_settings_group', 'wasm_settings');
    
    add_settings_section(
        'wasm_main_section',
        __('Genel Ayarlar', 'wc-advanced-stock-manager'),
        'wasm_settings_section_callback',
        'wasm_settings'
    );
    
    // Kritik eşik değeri
    add_settings_field(
        'wasm_critical_threshold',
        __('Kritik Stok Eşiği', 'wc-advanced-stock-manager'),
        'wasm_critical_threshold_callback',
        'wasm_settings',
        'wasm_main_section'
    );
    
    // Düşük eşik değeri
    add_settings_field(
        'wasm_low_threshold',
        __('Düşük Stok Eşiği', 'wc-advanced-stock-manager'),
        'wasm_low_threshold_callback',
        'wasm_settings',
        'wasm_main_section'
    );
    
    // Sipariş eşik değeri
    add_settings_field(
        'wasm_reorder_threshold',
        __('Sipariş Eşiği (Ay)', 'wc-advanced-stock-manager'),
        'wasm_reorder_threshold_callback',
        'wasm_settings',
        'wasm_main_section'
    );
    
    // Stok dönem değeri
    add_settings_field(
        'wasm_stock_period',
        __('Stok Dönem Değeri (Ay)', 'wc-advanced-stock-manager'),
        'wasm_stock_period_callback',
        'wasm_settings',
        'wasm_main_section'
    );
}

/**
 * Ayarlar bölümü geri çağırma
 */
function wasm_settings_section_callback() {
    echo '<p>' . __('Stok yönetimi için genel ayarlar.', 'wc-advanced-stock-manager') . '</p>';
}

/**
 * Kritik eşik değeri geri çağırma
 */
function wasm_critical_threshold_callback() {
    $options = get_option('wasm_settings');
    $value = isset($options['critical_threshold']) ? $options['critical_threshold'] : 5;
    
    echo '<input type="number" id="wasm_critical_threshold" name="wasm_settings[critical_threshold]" value="' . esc_attr($value) . '" min="0" /> ' .
         '<p class="description">' . __('Bu değerin altındaki stoklar "Kritik" olarak işaretlenir.', 'wc-advanced-stock-manager') . '</p>';
}

/**
 * Düşük eşik değeri geri çağırma
 */
function wasm_low_threshold_callback() {
    $options = get_option('wasm_settings');
    $value = isset($options['low_threshold']) ? $options['low_threshold'] : 15;
    
    echo '<input type="number" id="wasm_low_threshold" name="wasm_settings[low_threshold]" value="' . esc_attr($value) . '" min="0" /> ' .
         '<p class="description">' . __('Bu değerin altındaki stoklar "Düşük" olarak işaretlenir.', 'wc-advanced-stock-manager') . '</p>';
}

/**
 * Sipariş eşik değeri geri çağırma
 */
function wasm_reorder_threshold_callback() {
    $options = get_option('wasm_settings');
    $value = isset($options['reorder_threshold']) ? $options['reorder_threshold'] : 1.5;
    
    echo '<input type="number" id="wasm_reorder_threshold" name="wasm_settings[reorder_threshold]" value="' . esc_attr($value) . '" min="0" step="0.1" /> ' .
         '<p class="description">' . __('Stok yeterlilik süresi bu değerin altına düştüğünde sipariş önerilecektir.', 'wc-advanced-stock-manager') . '</p>';
}

/**
 * Stok dönem değeri geri çağırma
 */
function wasm_stock_period_callback() {
    $options = get_option('wasm_settings');
    $value = isset($options['stock_period']) ? $options['stock_period'] : 2;
    
    echo '<input type="number" id="wasm_stock_period" name="wasm_settings[stock_period]" value="' . esc_attr($value) . '" min="1" step="0.5" /> ' .
         '<p class="description">' . __('Önerilen stok miktarı kaç aylık satışı kapsamalı.', 'wc-advanced-stock-manager') . '</p>';
}

/**
 * API: Ürünleri getir
 *
 * @param WP_REST_Request $request API isteği
 * @return array Ürün verileri dizisi
 */
function wasm_api_get_products($request) {
    // Parametreleri al
    $params = $request->get_params();
    $start_date = isset($params['start_date']) ? sanitize_text_field($params['start_date']) : '';
    $end_date = isset($params['end_date']) ? sanitize_text_field($params['end_date']) : '';
    $category = isset($params['category']) ? sanitize_text_field($params['category']) : '';
    $stock_status = isset($params['stock_status']) ? sanitize_text_field($params['stock_status']) : '';
    $search = isset($params['search']) ? sanitize_text_field($params['search']) : '';
    
    // Ürünleri sorgulama argümanları
    $args = array(
        'post_type' => 'product',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'fields' => 'ids',
    );
    
    // Kategori filtresi
    if (!empty($category)) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'product_cat',
                'field' => 'name',
                'terms' => $category,
            ),
        );
    }
    
    // Arama filtresi
    if (!empty($search)) {
        $args['s'] = $search;
    }
    
    // Sorguyu çalıştır ve ürün ID'lerini al
    $query = new WP_Query($args);
    $product_ids = $query->posts;
    
    // Ürün verilerini topla
    $products = array();
    
    foreach ($product_ids as $product_id) {
        $wc_product = wc_get_product($product_id);
        
        if (!$wc_product) {
            continue;
        }
        
        // Ürün tipini belirle
        $product_type = $wc_product->get_type();
        
        // DOĞRUDAN DEBUG: Ürün tipini logla
        error_log("Ürün ID: $product_id, Tip: $product_type, Adı: " . $wc_product->get_name());
        
        // Varyasyonlu ürünlerin stok hesaplaması için
        $stock_quantity = 0;
        
        if ($product_type === 'variable') {
            // Varyasyonları doğrudan al
            $variation_ids = $wc_product->get_children();
            $total_stock = 0;
            
            // Her varyasyonun stok değerini topla
            foreach ($variation_ids as $variation_id) {
                $variation = wc_get_product($variation_id);
                if ($variation) {
                    $variation_stock = $variation->get_stock_quantity();
                    if ($variation_stock !== null && $variation_stock !== false) {
                        $total_stock += (int)$variation_stock;
                        // DEBUG: Her varyasyon için stok değerini logla
                        error_log("  Varyasyon ID: $variation_id, Stok: $variation_stock");
                    }
                }
            }
            
            // Ana ürünün stoku = varyasyonların toplamı
            $stock_quantity = $total_stock;
            
            // DEBUG: Toplam stoğu logla
            error_log("  Toplam Varyasyon Stoğu: $stock_quantity");
        } else {
            // Basit ürün için normal stok değerini al
            $stock_quantity = $wc_product->get_stock_quantity();
            $stock_quantity = ($stock_quantity !== null && $stock_quantity !== false) ? (int)$stock_quantity : 0;
        }
        
        // Stok durumunu belirle
        $stock_status_value = wasm_get_stock_status($stock_quantity);
        
        // Eğer stok durum filtresi varsa ve uyuşmuyorsa atla
        if (!empty($stock_status) && $stock_status_value != $stock_status) {
            continue;
        }
        
        // Tarih aralığı için satış verilerini al
        $last_3_months_sales = wasm_get_product_sales_in_period($product_id, $start_date, $end_date);
        
        // Önerilen sipariş miktarını hesapla
        $recommended_order = wasm_calculate_recommended_order($product_id, $stock_quantity, $last_3_months_sales);
        
        // Kategori bilgisini al
        $categories = get_the_terms($product_id, 'product_cat');
        $category_name = '';
        
        if ($categories && !is_wp_error($categories)) {
            $category_name = $categories[0]->name;
        }
        
        // Ürün temel bilgilerini oluştur
        $product_data = array(
            'id' => $product_id,
            'name' => $wc_product->get_name(),
            'sku' => $wc_product->get_sku(),
            'currentStock' => $stock_quantity,
            'stockStatus' => $stock_status_value,
            'last3MonthsSales' => $last_3_months_sales,
            'recommendedOrder' => $recommended_order,
            'category' => $category_name,
            'productType' => $product_type // Ürün tipini ekle: simple, variable, vb.
        );
        
        // Eğer ürün varyasyonlu ise varyasyonları ekle
        if ($product_type === 'variable') {
            // Ürün varyasyonlarını al
            $variations = $wc_product->get_available_variations();
            $product_data['variations'] = array();
            
            foreach ($variations as $variation) {
                $variation_id = $variation['variation_id'];
                $variation_product = wc_get_product($variation_id);
                
                if (!$variation_product) {
                    continue;
                }
                
                // Varyasyon stok miktarını al
                $variation_stock = $variation_product->get_stock_quantity();
                $variation_stock = ($variation_stock !== null && $variation_stock !== false) ? (int)$variation_stock : 0;
                
                // Varyasyon stok durumunu belirle
                $variation_stock_status = wasm_get_stock_status($variation_stock);
                
                // Varyasyon satış verilerini al
                $variation_sales = wasm_get_product_sales_in_period($variation_id, $start_date, $end_date);
                
                // Varyasyon için önerilen sipariş miktarını hesapla
                $variation_recommended_order = wasm_calculate_recommended_order($variation_id, $variation_stock, $variation_sales);
                
                // Varyasyon özelliklerini hazırla
                $attributes = array();
                foreach ($variation['attributes'] as $attr_key => $attr_value) {
                    $taxonomy = str_replace('attribute_', '', $attr_key);
                    $term = get_term_by('slug', $attr_value, $taxonomy);
                    
                    if ($term && !is_wp_error($term)) {
                        $attributes[] = $term->name;
                    } else {
                        $attributes[] = $attr_value; // Özel nitelik değeri
                    }
                }
                
                // Varyasyon verilerini ekle
                $product_data['variations'][] = array(
                    'id' => $variation_id,
                    'title' => implode(', ', $attributes), // Varyasyon başlığını niteliklerden oluştur
                    'sku' => $variation_product->get_sku(),
                    'stock' => $variation_stock,
                    'stockStatus' => $variation_stock_status,
                    'last3MonthsSales' => $variation_sales,
                    'recommendedOrder' => $variation_recommended_order,
                    'attributes' => $attributes
                );
            }
        }
        
        $products[] = $product_data;
    }
    
    return $products;
}

/**
 * API: Satış trendini getir (HPOS uyumlu)
 *
 * @param WP_REST_Request $request API isteği
 * @return array Satış trend verileri
 */
function wasm_api_get_sales_trend($request) {
    try {
        // Başlangıç ve bitiş aylarını belirle
        $months = $request->get_param('months') ? intval($request->get_param('months')) : 12;
        $months = min(max($months, 1), 24); // 1-24 ay arası sınırla
        
        // Debug için
        error_log("Satış trendi hesaplanıyor: Son $months ay için");
        
        $trend_data = array();
        
        // Şu andan geriye doğru her ay için verileri hesapla
        for ($i = $months - 1; $i >= 0; $i--) {
            $start_date = date('Y-m-01', strtotime("-$i months"));
            $end_date = date('Y-m-t', strtotime("-$i months"));
            
            // Ay bilgisini oluştur
            $month_name = date_i18n('F Y', strtotime($start_date));
            
            // Debug
            error_log("Ay hesaplanıyor: $month_name ($start_date - $end_date)");
            
            // Bu ay için tüm satışları topla
            try {
                $total_sales = wasm_get_total_sales_in_period($start_date, $end_date);
                error_log("$month_name için toplam satış: $total_sales");
            } catch (Exception $e) {
                error_log("Satış sayımı hatası: " . $e->getMessage());
                $total_sales = 0;
            }
            
            // Bu ay için ortalama stok miktarını hesapla
            try {
                $average_stock = wasm_get_average_stock_in_period($start_date, $end_date);
                error_log("$month_name için ortalama stok: $average_stock");
            } catch (Exception $e) {
                error_log("Stok hesaplama hatası: " . $e->getMessage());
                $average_stock = 0;
            }
            
            $trend_data[] = array(
                'month' => $month_name,
                'totalSales' => $total_sales,
                'averageStock' => $average_stock
            );
        }
        
        return $trend_data;
    } catch (Exception $e) {
        error_log("Satış trend hesaplama hatası: " . $e->getMessage() . "\n" . $e->getTraceAsString());
        return new WP_Error('sales_trend_error', $e->getMessage(), array('status' => 500));
    }
}

/**
 * API: Kategorileri getir
 *
 * @return array Kategori verileri
 */
function wasm_api_get_categories() {
    $categories = get_terms(array(
        'taxonomy' => 'product_cat',
        'hide_empty' => true
    ));
    
    $result = array();
    
    if (!is_wp_error($categories)) {
        foreach ($categories as $category) {
            // Kategori içindeki toplam ürün sayısını hesapla
            $args = array(
                'post_type' => 'product',
                'posts_per_page' => -1,
                'fields' => 'ids',
                'tax_query' => array(
                    array(
                        'taxonomy' => 'product_cat',
                        'field' => 'term_id',
                        'terms' => $category->term_id
                    )
                )
            );
            
            $query = new WP_Query($args);
            $count = $query->post_count;
            
            // Sadece ürün içeren kategorileri ekle
            if ($count > 0) {
                $result[] = array(
                    'id' => $category->term_id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'count' => $count
                );
            }
        }
    }
    
    return $result;
}

/**
 * API: Özet bilgileri getir
 *
 * @return array Özet veriler
 */
function wasm_api_get_summary() {
    // Toplam ürün sayısı
    $args = array(
        'post_type' => 'product',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'post_status' => 'publish'
    );
    
    $query = new WP_Query($args);
    $total_products = $query->post_count;
    
    // Düşük stoklu ürün sayısı
    $low_stock_count = 0;
    $total_stock_value = 0;
    
    // Ayarlardan eşik değerlerini al
    $settings = get_option('wasm_settings', array(
        'critical_threshold' => 5,
        'low_threshold' => 15
    ));
    
    $critical_threshold = isset($settings['critical_threshold']) ? intval($settings['critical_threshold']) : 5;
    $low_threshold = isset($settings['low_threshold']) ? intval($settings['low_threshold']) : 15;
    
    foreach ($query->posts as $product_id) {
        $product = wc_get_product($product_id);
        
        if (!$product) {
            continue;
        }
        
        // Stok miktarını al
        $stock = $product->get_stock_quantity();
        
        // Varyasyonlu ürünler için toplam stok hesapla
        if ($product->is_type('variable')) {
            $variations = $product->get_children();
            $total_stock = 0;
            
            foreach ($variations as $variation_id) {
                $variation = wc_get_product($variation_id);
                if ($variation) {
                    $variation_stock = $variation->get_stock_quantity();
                    if ($variation_stock !== null && $variation_stock !== false) {
                        $total_stock += (int)$variation_stock;
                    }
                }
            }
            
            $stock = $total_stock;
        }
        
        // Stok değeri hesapla
        if ($stock !== null && $stock !== false) {
            $stock = (int)$stock;
            $price = $product->get_price();
            
            if ($price) {
                $total_stock_value += $stock * floatval($price);
            }
            
            // Düşük stok kontrolü
            if ($stock <= $low_threshold) {
                $low_stock_count++;
            }
        }
    }
    
    // Son 30 gündeki satışlar
    $sold_items = wasm_get_total_sales_in_period(
        date('Y-m-d', strtotime('-30 days')),
        date('Y-m-d')
    );
    
    return array(
        'totalProducts' => $total_products,
        'lowStockCount' => $low_stock_count,
        'totalStockValue' => $total_stock_value,
        'soldItems' => $sold_items
    );
}

/**
 * Stok durumunu belirler (iyi, düşük, kritik)
 *
 * @param int $stock_quantity Stok miktarı
 * @return string Stok durumu ('good', 'low', 'critical')
 */
function wasm_get_stock_status($stock_quantity) {
    // Ayarlardan eşik değerlerini al
    $settings = get_option('wasm_settings', array(
        'critical_threshold' => 5,
        'low_threshold' => 15
    ));
    
    $critical_threshold = isset($settings['critical_threshold']) ? intval($settings['critical_threshold']) : 5;
    $low_threshold = isset($settings['low_threshold']) ? intval($settings['low_threshold']) : 15;
    
    if ($stock_quantity <= $critical_threshold) {
        return 'critical';
    } elseif ($stock_quantity <= $low_threshold) {
        return 'low';
    } else {
        return 'good';
    }
}

/**
 * Belirli bir zaman aralığında ürün satışlarını hesaplar - Klasik WooCommerce tabloları ile
 *
 * @param int $product_id Ürün ID
 * @param string $start_date Başlangıç tarihi (YYYY-MM-DD)
 * @param string $end_date Bitiş tarihi (YYYY-MM-DD)
 * @return int Satış miktarı
 */
function wasm_get_product_sales_in_period($product_id, $start_date = '', $end_date = '') {
    // Tarih aralığı yoksa son 3 ayı kullan
    if (empty($start_date)) {
        $start_date = date('Y-m-d', strtotime('-3 months'));
    }
    
    if (empty($end_date)) {
        $end_date = date('Y-m-d');
    }
    
    try {
        // Satış verilerini getir
        global $wpdb;
        
        // Önce tablo varlığını kontrol et
        $post_table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->posts}'") === $wpdb->posts;
        $order_items_table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}woocommerce_order_items'") === $wpdb->prefix . 'woocommerce_order_items';
        $order_itemmeta_table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}woocommerce_order_itemmeta'") === $wpdb->prefix . 'woocommerce_order_itemmeta';
        
        if (!$post_table_exists || !$order_items_table_exists || !$order_itemmeta_table_exists) {
            error_log("WASM: WooCommerce tablo kontrolünde eksik tablolar bulundu. post_table: " . 
                      ($post_table_exists ? "var" : "yok") . ", order_items_table: " . 
                      ($order_items_table_exists ? "var" : "yok") . ", order_itemmeta_table: " . 
                      ($order_itemmeta_table_exists ? "var" : "yok"));
            return 0;
        }
        
        // Ana ürün satışları
        $sales_query = $wpdb->prepare(
            "SELECT SUM(order_item_meta__qty.meta_value) as qty 
            FROM {$wpdb->posts} AS posts
            INNER JOIN {$wpdb->prefix}woocommerce_order_items AS order_items ON posts.ID = order_items.order_id
            INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS order_item_meta__product_id ON order_items.order_item_id = order_item_meta__product_id.order_item_id
            INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS order_item_meta__qty ON order_items.order_item_id = order_item_meta__qty.order_item_id
            WHERE posts.post_type = 'shop_order'
            AND posts.post_status IN ('wc-completed', 'wc-processing')
            AND order_item_meta__product_id.meta_key = '_product_id'
            AND order_item_meta__product_id.meta_value = %d
            AND order_item_meta__qty.meta_key = '_qty'
            AND posts.post_date BETWEEN %s AND %s",
            $product_id,
            $start_date . ' 00:00:00',
            $end_date . ' 23:59:59'
        );
        
        // Varyasyonlar
        $variation_sales_query = $wpdb->prepare(
            "SELECT SUM(order_item_meta__qty.meta_value) as qty 
            FROM {$wpdb->posts} AS posts
            INNER JOIN {$wpdb->prefix}woocommerce_order_items AS order_items ON posts.ID = order_items.order_id
            INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS order_item_meta__variation_id ON order_items.order_item_id = order_item_meta__variation_id.order_item_id
            INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS order_item_meta__qty ON order_items.order_item_id = order_item_meta__qty.order_item_id
            WHERE posts.post_type = 'shop_order'
            AND posts.post_status IN ('wc-completed', 'wc-processing')
            AND order_item_meta__variation_id.meta_key = '_variation_id'
            AND order_item_meta__variation_id.meta_value = %d
            AND order_item_meta__qty.meta_key = '_qty'
            AND posts.post_date BETWEEN %s AND %s",
            $product_id,
            $start_date . ' 00:00:00',
            $end_date . ' 23:59:59'
        );
        
        // Debug amaçlı sorguları logla (ilk kez çalıştırırken)
        if ($product_id == get_option('wasm_first_run_check', 0)) {
            error_log("WASM: Ürün satışı sorgusu (ürün {$product_id}): " . $sales_query);
            update_option('wasm_first_run_check', 0); // Bir kez logla
        }
        
        $product_sales = $wpdb->get_var($sales_query);
        $variation_sales = $wpdb->get_var($variation_sales_query);
        
        // Null değerler için 0 kullan
        $product_sales = $product_sales ? intval($product_sales) : 0;
        $variation_sales = $variation_sales ? intval($variation_sales) : 0;
        
        // Ürün tipine göre uygun değeri döndür
        $product = wc_get_product($product_id);
        
        if ($product && $product->is_type('variation')) {
            // Varyasyon ise sadece varyasyon satışlarını döndür
            return $variation_sales;
        } elseif ($product && $product->is_type('variable')) {
            // Değişken ürün ise ana ürün + varyasyonların satışlarını döndür
            return $product_sales + $variation_sales;
        } else {
            // Basit ürün ise normal satışları döndür
            return $product_sales;
        }
    } catch (Exception $e) {
        error_log("WASM: Ürün satışı hesaplama hatası: " . $e->getMessage());
        return 0;
    }
}

/**
 * Belirli bir zaman aralığında toplam satışları hesaplar - HPOS uyumlu versiyon
 *
 * @param string $start_date Başlangıç tarihi (YYYY-MM-DD)
 * @param string $end_date Bitiş tarihi (YYYY-MM-DD)
 * @return int Toplam satış miktarı
 */
function wasm_get_total_sales_in_period($start_date, $end_date) {
    global $wpdb;
    
    // Önce HPOS modunu tamamen devre dışı bırakalım - normal WooCommerce tablolarını kullanacağız
    $is_hpos_enabled = false;
    
    /* 
    // Şu anda HPOS kontrolünü devre dışı bırakıyoruz çünkü tablolar mevcut değil
    $is_hpos_enabled = class_exists('\Automattic\WooCommerce\Utilities\OrderUtil') && 
                        \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
    
    // HPOS etkinse, önce tabloların var olduğunu kontrol et
    if ($is_hpos_enabled) {
        $table_exists = false;
        try {
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}wc_order_items'") === "{$wpdb->prefix}wc_order_items";
        } catch (Exception $e) {
            error_log("WASM: HPOS tablo kontrolü hatası: " . $e->getMessage());
            $table_exists = false;
        }
        
        if (!$table_exists) {
            error_log("WASM: HPOS tablosu bulunamadı: {$wpdb->prefix}wc_order_items - varsayılan tablolara geçiliyor");
            $is_hpos_enabled = false;
        }
    }
    */
    
    try {
        // HPOS devre dışı, klasik tabloları kullan
        $query = $wpdb->prepare(
            "SELECT SUM(order_item_meta__qty.meta_value) as qty 
            FROM {$wpdb->posts} AS posts
            INNER JOIN {$wpdb->prefix}woocommerce_order_items AS order_items ON posts.ID = order_items.order_id
            INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS order_item_meta__qty ON order_items.order_item_id = order_item_meta__qty.order_item_id
            WHERE posts.post_type = 'shop_order'
            AND posts.post_status IN ('wc-completed', 'wc-processing')
            AND order_item_meta__qty.meta_key = '_qty'
            AND posts.post_date BETWEEN %s AND %s",
            $start_date . ' 00:00:00',
            $end_date . ' 23:59:59'
        );
        
        // Sorguyu logla
        error_log("WASM: Toplam satış sorgusu (Klasik WooCommerce Tabloları): " . $query);
        
        // Sorgu çalıştırılmadan önce tabloların varlığını kontrol et
        $post_table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->posts}'") === $wpdb->posts;
        $order_items_table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}woocommerce_order_items'") === $wpdb->prefix . 'woocommerce_order_items';
        $order_itemmeta_table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}woocommerce_order_itemmeta'") === $wpdb->prefix . 'woocommerce_order_itemmeta';
        
        if (!$post_table_exists || !$order_items_table_exists || !$order_itemmeta_table_exists) {
            error_log("WASM: WooCommerce tablo kontrolünde eksik tablolar bulundu. post_table: " . 
                      ($post_table_exists ? "var" : "yok") . ", order_items_table: " . 
                      ($order_items_table_exists ? "var" : "yok") . ", order_itemmeta_table: " . 
                      ($order_itemmeta_table_exists ? "var" : "yok"));
            return 0;
        }
        
        $result = $wpdb->get_var($query);
        error_log("WASM: Sorgu sonucu: " . ($result !== null ? $result : "NULL"));
        
        return $result ? intval($result) : 0;
    } catch (Exception $e) {
        error_log("WASM: Satış hesaplama hatası: " . $e->getMessage());
        return 0;
    }
}


/**
 * Belirli bir zaman aralığında ortalama stok miktarını hesaplar (HPOS uyumlu)
 *
 * @param string $start_date Başlangıç tarihi (YYYY-MM-DD)
 * @param string $end_date Bitiş tarihi (YYYY-MM-DD)
 * @return int Ortalama stok miktarı
 */
function wasm_get_average_stock_in_period($start_date, $end_date) {
    try {
        // Debug
        error_log("Ortalama stok hesaplanıyor: $start_date - $end_date");
        
        // Bu fonksiyon şu anda stok geçmişini tutmadığımız için, mevcut stok değerini kullanır
        // İleride stok geçmişi eklenirse burada geçmiş ortalama stok değeri hesaplanabilir
        
        // Ürünleri sorgula
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'post_status' => 'publish'
        );
        
        $query = new WP_Query($args);
        $total_stock = 0;
        $product_count = 0;
        
        foreach ($query->posts as $product_id) {
            $product = wc_get_product($product_id);
            
            if (!$product) {
                continue;
            }
            
            // Ürünün stoğa sahip olup olmadığını kontrol et
            $manages_stock = $product->get_manage_stock();
            
            if (!$manages_stock) {
                continue;
            }
            
            $stock = $product->get_stock_quantity();
            
            // Varyasyonlu ürünler için toplam stok hesapla
            if ($product->is_type('variable')) {
                $variations = $product->get_children();
                $product_stock = 0;
                
                foreach ($variations as $variation_id) {
                    $variation = wc_get_product($variation_id);
                    if ($variation && $variation->get_manage_stock()) {
                        $variation_stock = $variation->get_stock_quantity();
                        if ($variation_stock !== null && $variation_stock !== false) {
                            $product_stock += intval($variation_stock);
                        }
                    }
                }
                
                $stock = $product_stock;
            }
            
            if ($stock !== null && $stock !== false) {
                $total_stock += intval($stock);
                $product_count++;
            }
        }
        
        $result = $product_count > 0 ? round($total_stock / $product_count) : 0;
        error_log("Hesaplanan ortalama stok: $result (Toplam: $total_stock, Ürün sayısı: $product_count)");
        
        return $result;
    } catch (Exception $e) {
        error_log("Ortalama stok hesaplama hatası: " . $e->getMessage());
        return 0;
    }
}

/**
 * Önerilen sipariş miktarını hesaplar
 *
 * @param int $product_id Ürün ID
 * @param int $current_stock Mevcut stok
 * @param int $last_period_sales Son dönem satışları
 * @return int Önerilen sipariş miktarı
 */
function wasm_calculate_recommended_order($product_id, $current_stock, $last_period_sales) {
    // Ayarlardan eşik değerlerini ve katsayıları al
    $settings = get_option('wasm_settings', array(
        'reorder_threshold' => 1.5,
        'stock_period' => 2
    ));
    
    $reorder_threshold = isset($settings['reorder_threshold']) ? floatval($settings['reorder_threshold']) : 1.5;
    $stock_period = isset($settings['stock_period']) ? intval($settings['stock_period']) : 2;
    
    // Son dönemdeki satışlardan aylık satış tahmin et (son 3 ay varsayalım)
    $monthly_sales = $last_period_sales / 3;
    
    // Stok yeterlilik süresi = mevcut stok / aylık satış
    if ($monthly_sales <= 0) {
        return 0; // Satış yoksa sipariş önerme
    }
    
    $stock_sufficiency = $current_stock / $monthly_sales; // ay cinsinden
    
    // Eğer stok yeterlilik süresi eşik değerinden düşükse, sipariş öner
    if ($stock_sufficiency < $reorder_threshold) {
        // Önerilen stok = stok dönemi * aylık satış
        $recommended_stock = ceil($stock_period * $monthly_sales);
        
        // Sipariş miktarı = önerilen stok - mevcut stok
        $order_amount = $recommended_stock - $current_stock;
        
        return ($order_amount > 0) ? $order_amount : 0;
    }
    
    return 0; // Yeterli stok var, sipariş önerme
}