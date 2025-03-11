<?php
/**
 * Plugin Name: WooCommerce Gelişmiş Stok Yönetimi
 * Plugin URI: https://example.com/woocommerce-advanced-stock-manager
 * Description: WooCommerce için gelişmiş stok yönetimi, analizi ve sipariş planlaması eklentisi.
 * Version: 1.0.0
 * Author: Codeon
 * Author URI: https://codeon.ch
 * Text Domain: wc-advanced-stock-manager
 * Domain Path: /languages
 * Requires at least: 5.7
 * Requires PHP: 7.3
 * WC requires at least: 5.0
 */

// Doğrudan erişimi engelle
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Eklenti sabitlerini tanımla
 */
define('WASM_VERSION', '1.0.0');
define('WASM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WASM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WASM_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * WooCommerce'in yüklü olup olmadığını kontrol et
 */
function wasm_check_woocommerce_active() {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', 'wasm_woocommerce_missing_notice');
        return false;
    }
    return true;
}

/**
 * WooCommerce eksik uyarısı
 */
function wasm_woocommerce_missing_notice() {
    ?>
    <div class="notice notice-error">
        <p><?php esc_html_e('WooCommerce Gelişmiş Stok Yönetimi eklentisi için WooCommerce eklentisinin yüklü ve aktif olması gerekir.', 'wc-advanced-stock-manager'); ?></p>
    </div>
    <?php
}

/**
 * Ana sınıfı yükle
 */
function wasm_load_classes() {
    require_once WASM_PLUGIN_DIR . 'includes/class-wasm-admin.php';
    require_once WASM_PLUGIN_DIR . 'includes/class-wasm-api.php';
}

/**
 * Admin menü öğeleri ekle
 */
function wasm_add_admin_menu() {
    if (!wasm_check_woocommerce_active()) {
        return;
    }

    add_submenu_page(
        'woocommerce',
        __('Gelişmiş Stok Yönetimi', 'wc-advanced-stock-manager'),
        __('Gelişmiş Stok Yönetimi', 'wc-advanced-stock-manager'),
        'manage_woocommerce',
        'wc-advanced-stock-manager',
        'wasm_render_admin_page'
    );

    add_submenu_page(
        'woocommerce',
        __('Stok Yönetimi Ayarları', 'wc-advanced-stock-manager'),
        __('Stok Yönetimi Ayarları', 'wc-advanced-stock-manager'),
        'manage_woocommerce',
        'wc-advanced-stock-manager-settings',
        'wasm_render_settings_page'
    );
}
add_action('admin_menu', 'wasm_add_admin_menu');

/**
 * Admin sayfasını oluştur
 */
function wasm_render_admin_page() {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('WooCommerce Gelişmiş Stok Yönetimi', 'wc-advanced-stock-manager'); ?></h1>
        <div id="wasm-app"></div>
    </div>
    <?php
}

/**
 * Ayarlar sayfasını oluştur
 */
function wasm_render_settings_page() {
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

/**
 * Settings API kurulumu
 */
function wasm_register_settings() {
    register_setting('wasm_settings_group', 'wasm_settings');

    add_settings_section(
        'wasm_general_section',
        __('Genel Ayarlar', 'wc-advanced-stock-manager'),
        'wasm_general_section_callback',
        'wasm_settings'
    );

    add_settings_field(
        'wasm_reorder_threshold',
        __('Sipariş Eşiği Hesaplama Faktörü', 'wc-advanced-stock-manager'),
        'wasm_reorder_threshold_callback',
        'wasm_settings',
        'wasm_general_section'
    );

    add_settings_field(
        'wasm_stock_period',
        __('Stok Hesaplama Periyodu (Ay)', 'wc-advanced-stock-manager'),
        'wasm_stock_period_callback',
        'wasm_settings',
        'wasm_general_section'
    );
}
add_action('admin_init', 'wasm_register_settings');

/**
 * Genel bölüm açıklaması
 */
function wasm_general_section_callback() {
    echo '<p>' . esc_html__('Stok yönetimi ve sipariş önerilerini özelleştirme ayarları.', 'wc-advanced-stock-manager') . '</p>';
}

/**
 * Sipariş eşiği ayarı
 */
function wasm_reorder_threshold_callback() {
    $options = get_option('wasm_settings', array(
        'reorder_threshold' => 1.5,
        'stock_period' => 2
    ));
    $value = isset($options['reorder_threshold']) ? $options['reorder_threshold'] : 1.5;
    ?>
    <input type="number" id="wasm_reorder_threshold" name="wasm_settings[reorder_threshold]" value="<?php echo esc_attr($value); ?>" min="0.1" max="5" step="0.1" />
    <p class="description">
        <?php esc_html_e('Aylık satışların kaç katı stok tutmak istediğinizi belirler. Örneğin 2, iki aylık satışa yetecek stok tutmayı hedefler.', 'wc-advanced-stock-manager'); ?>
    </p>
    <?php
}

/**
 * Stok periyodu ayarı
 */
function wasm_stock_period_callback() {
    $options = get_option('wasm_settings', array(
        'reorder_threshold' => 1.5,
        'stock_period' => 2
    ));
    $value = isset($options['stock_period']) ? $options['stock_period'] : 2;
    ?>
    <input type="number" id="wasm_stock_period" name="wasm_settings[stock_period]" value="<?php echo esc_attr($value); ?>" min="1" max="12" step="1" />
    <p class="description">
        <?php esc_html_e('Satış analizini kaç aylık veriye dayandırmak istediğinizi belirler. Örneğin 3, son 3 ayın satış verilerini kullanır.', 'wc-advanced-stock-manager'); ?>
    </p>
    <?php
}

/**
 * Admin scriptleri ve stilleri
 */
function wasm_enqueue_admin_scripts($hook) {
    if ('woocommerce_page_wc-advanced-stock-manager' !== $hook) {
        return;
    }

    // Sadece CSS ve JS dosyalarımızı yükleyelim, harici kütüphaneleri yüklemeyelim
    wp_enqueue_style('wasm-styles', WASM_PLUGIN_URL . 'assets/css/app.css', array(), WASM_VERSION);
    wp_enqueue_script('wasm-app', WASM_PLUGIN_URL . 'assets/js/app.js', array('wp-api-fetch'), WASM_VERSION, true);

    // Script verilerini lokalize et
    wp_localize_script('wasm-app', 'wasmSettings', array(
        'apiUrl' => rest_url('wc-advanced-stock-manager/v1'),
        'nonce' => wp_create_nonce('wp_rest'),
        'currencySymbol' => get_woocommerce_currency_symbol(),
        'currencyCode' => get_woocommerce_currency(),
        'locale' => get_locale(),
        'siteUrl' => site_url(),
        'settings' => get_option('wasm_settings', array(
            'reorder_threshold' => 1.5,
            'stock_period' => 2
        )),
        'translations' => array(
            'title' => __('WooCommerce Gelişmiş Stok Yönetimi', 'wc-advanced-stock-manager'),
            'subtitle' => __('Stok durumunuzu izleyin, satış trendlerini analiz edin ve sipariş planlaması yapın.', 'wc-advanced-stock-manager'),
            'totalProducts' => __('Toplam Ürün', 'wc-advanced-stock-manager'),
            'lowStock' => __('Düşük Stok', 'wc-advanced-stock-manager'),
            'reorderNeeded' => __('Sipariş Edilecek', 'wc-advanced-stock-manager'),
            'stockValue' => __('Stok Değeri', 'wc-advanced-stock-manager'),
            'salesAndStockTrend' => __('Satış ve Stok Trendi', 'wc-advanced-stock-manager'),
            'filters' => __('Filtreler', 'wc-advanced-stock-manager'),
            'clearFilters' => __('Filtreleri Temizle', 'wc-advanced-stock-manager'),
            'dateRange' => __('Tarih Aralığı', 'wc-advanced-stock-manager'),
            'category' => __('Kategori', 'wc-advanced-stock-manager'),
            'allCategories' => __('Tüm Kategoriler', 'wc-advanced-stock-manager'),
            'stockStatus' => __('Stok Durumu', 'wc-advanced-stock-manager'),
            'all' => __('Tümü', 'wc-advanced-stock-manager'),
            'criticalStock' => __('Kritik Stok', 'wc-advanced-stock-manager'),
            'search' => __('Arama', 'wc-advanced-stock-manager'),
            'searchPlaceholder' => __('Ürün adı veya SKU ile ara...', 'wc-advanced-stock-manager'),
            'categoryDistribution' => __('Kategori Dağılımı', 'wc-advanced-stock-manager'),
            'productStockList' => __('Ürün Stok Listesi', 'wc-advanced-stock-manager'),
            'showingProducts' => __('ürün gösteriliyor', 'wc-advanced-stock-manager'),
            'productName' => __('Ürün Adı', 'wc-advanced-stock-manager'),
            'sku' => __('SKU', 'wc-advanced-stock-manager'),
            'currentStock' => __('Mevcut Stok', 'wc-advanced-stock-manager'),
            'sales3Months' => __('Son 3 Ay Satış', 'wc-advanced-stock-manager'),
            'stockStatus' => __('Stok Durumu', 'wc-advanced-stock-manager'),
            'recommendedOrder' => __('Önerilen Sipariş', 'wc-advanced-stock-manager'),
            'critical' => __('Kritik', 'wc-advanced-stock-manager'),
            'low' => __('Düşük', 'wc-advanced-stock-manager'),
            'good' => __('İyi', 'wc-advanced-stock-manager'),
            'noProductsFound' => __('Filtrelere uygun ürün bulunamadı.', 'wc-advanced-stock-manager'),
            'totalSales' => __('Toplam Satış', 'wc-advanced-stock-manager'),
            'averageStock' => __('Ortalama Stok', 'wc-advanced-stock-manager'),
            'loading' => __('Yükleniyor...', 'wc-advanced-stock-manager'),
            'tryAgain' => __('Tekrar Dene', 'wc-advanced-stock-manager')
        )
    ));
}
add_action('admin_enqueue_scripts', 'wasm_enqueue_admin_scripts');

/**
 * REST API route'larını kaydet
 */
function wasm_register_rest_routes() {
    register_rest_route('wc-advanced-stock-manager/v1', '/products', array(
        'methods' => 'GET',
        'callback' => 'wasm_api_get_products',
        'permission_callback' => function() {
            return current_user_can('manage_woocommerce');
        }
    ));

    register_rest_route('wc-advanced-stock-manager/v1', '/sales-trend', array(
        'methods' => 'GET',
        'callback' => 'wasm_api_get_sales_trend',
        'permission_callback' => function() {
            return current_user_can('manage_woocommerce');
        }
    ));

    register_rest_route('wc-advanced-stock-manager/v1', '/categories', array(
        'methods' => 'GET',
        'callback' => 'wasm_api_get_categories',
        'permission_callback' => function() {
            return current_user_can('manage_woocommerce');
        }
    ));

    register_rest_route('wc-advanced-stock-manager/v1', '/summary', array(
        'methods' => 'GET',
        'callback' => 'wasm_api_get_summary',
        'permission_callback' => function() {
            return current_user_can('manage_woocommerce');
        }
    ));
}
add_action('rest_api_init', 'wasm_register_rest_routes');

/**
 * API: Ürünleri getir
 */
function wasm_api_get_products($request) {
    global $wpdb;
    
    $start_date = $request->get_param('start_date');
    $end_date = $request->get_param('end_date');
    $category = $request->get_param('category');
    $stock_status = $request->get_param('stock_status');
    $search = $request->get_param('search');
    
    // Varsayılan tarih aralığı: son 3 ay
    if (empty($start_date)) {
        $start_date = date('Y-m-d', strtotime('-3 months'));
    }
    
    if (empty($end_date)) {
        $end_date = date('Y-m-d');
    }
    
    // Ayarlardan yeniden sipariş eşiğini al
    $settings = get_option('wasm_settings', array(
        'reorder_threshold' => 1.5,
        'stock_period' => 2
    ));
    
    // Tüm ürünleri al
    $products_query = "
        SELECT 
            p.ID as id,
            p.post_title as name,
            p.post_date as created_date,
            pm_sku.meta_value as sku,
            pm_price.meta_value as price,
            pm_stock.meta_value as stock,
            pm_threshold.meta_value as reorder_point,
            GROUP_CONCAT(DISTINCT terms.name SEPARATOR ', ') as category_names
        FROM {$wpdb->posts} p
        LEFT JOIN {$wpdb->postmeta} pm_sku ON p.ID = pm_sku.post_id AND pm_sku.meta_key = '_sku'
        LEFT JOIN {$wpdb->postmeta} pm_price ON p.ID = pm_price.post_id AND pm_price.meta_key = '_price'
        LEFT JOIN {$wpdb->postmeta} pm_stock ON p.ID = pm_stock.post_id AND pm_stock.meta_key = '_stock'
        LEFT JOIN {$wpdb->postmeta} pm_threshold ON p.ID = pm_threshold.post_id AND pm_threshold.meta_key = '_wc_notify_low_stock_amount'
        LEFT JOIN {$wpdb->term_relationships} term_rel ON p.ID = term_rel.object_id
        LEFT JOIN {$wpdb->term_taxonomy} tax ON term_rel.term_taxonomy_id = tax.term_taxonomy_id AND tax.taxonomy = 'product_cat'
        LEFT JOIN {$wpdb->terms} terms ON tax.term_id = terms.term_id
        WHERE p.post_type = 'product' AND p.post_status = 'publish'
        GROUP BY p.ID
    ";
    
    $products = $wpdb->get_results($products_query, ARRAY_A);
    
    // Debug için
    error_log('WASM Products Query: ' . $wpdb->last_query);
    error_log('WASM Products Count: ' . count($products));
    
    // Satış verilerini al
    $sales_data = array();
    $order_query = "
        SELECT 
            oi.order_id,
            DATE(o.post_date) as order_date,
            oi_meta.meta_value as product_id,
            oi_qty.meta_value as quantity
        FROM {$wpdb->prefix}woocommerce_order_items oi
        JOIN {$wpdb->posts} o ON oi.order_id = o.ID
        JOIN {$wpdb->prefix}woocommerce_order_itemmeta oi_meta ON oi.order_item_id = oi_meta.order_item_id AND oi_meta.meta_key = '_product_id'
        JOIN {$wpdb->prefix}woocommerce_order_itemmeta oi_qty ON oi.order_item_id = oi_qty.order_item_id AND oi_qty.meta_key = '_qty'
        WHERE o.post_type = 'shop_order'
        AND o.post_status IN ('wc-completed', 'wc-processing')
        AND o.post_date BETWEEN %s AND %s
    ";
    
    $sales = $wpdb->get_results($wpdb->prepare($order_query, $start_date . ' 00:00:00', $end_date . ' 23:59:59'), ARRAY_A);
    
    // Ürün bazında satış toplamları
    foreach ($sales as $sale) {
        $product_id = $sale['product_id'];
        $qty = $sale['quantity'];
        $date = $sale['order_date'];
        
        if (!isset($sales_data[$product_id])) {
            $sales_data[$product_id] = array(
                'total' => 0,
                'last_month' => 0,
                'last_3_months' => 0
            );
        }
        
        $sales_data[$product_id]['total'] += $qty;
        
        // Son ay satış
        if (strtotime($date) >= strtotime('-1 month')) {
            $sales_data[$product_id]['last_month'] += $qty;
        }
        
        // Son 3 ay satış
        if (strtotime($date) >= strtotime('-3 months')) {
            $sales_data[$product_id]['last_3_months'] += $qty;
        }
    }
    
    // Ürünleri ve satış verilerini birleştir
    $result = array();
    
    foreach ($products as $product) {
        $product_id = $product['id'];
        $sales = isset($sales_data[$product_id]) ? $sales_data[$product_id] : array('total' => 0, 'last_month' => 0, 'last_3_months' => 0);
        
        // Yeniden sipariş noktasını hesapla
        $stock = intval($product['stock']);
        
        // Varsayılan yeniden sipariş noktası (ürün ayarından)
        $reorder_point = !empty($product['reorder_point']) ? intval($product['reorder_point']) : 5;
        
        // Önerilen sipariş miktarını hesapla
        $monthly_sales = $sales['last_3_months'] / 3; // Aylık ortalama satış
        $target_stock = ceil($monthly_sales * $settings['reorder_threshold']); // Hedef stok (ör. 2 aylık satış)
        $recommended_order = $stock < $target_stock ? $target_stock - $stock : 0;
        
        // Stok durumunu belirle
        $stock_status_value = 'good';
        if ($stock <= $reorder_point) {
            $stock_status_value = 'low';
        }
        if ($stock <= $reorder_point * 0.5) {
            $stock_status_value = 'critical';
        }
        
        // Kategori adını birincil kategoriye ayarla veya yoksa "Kategorisiz" olarak işaretle
        $category_names = empty($product['category_names']) ? __('Kategorisiz', 'wc-advanced-stock-manager') : $product['category_names'];
        $primary_category = explode(', ', $category_names)[0]; // Birincil kategoriyi al (ilk kategori)
        
        $item = array(
            'id' => $product_id,
            'name' => $product['name'],
            'sku' => $product['sku'],
            'price' => floatval($product['price']),
            'currentStock' => $stock,
            'reorderPoint' => $reorder_point,
            'lastMonthSales' => $sales['last_month'],
            'last3MonthsSales' => $sales['last_3_months'],
            'recommendedOrder' => $recommended_order,
            'stockStatus' => $stock_status_value,
            'category' => $primary_category
        );
        
        // Kategori filtresi
        if (!empty($category) && $category !== 'all' && $primary_category !== $category) {
            continue;
        }
        
        // Stok durumu filtresi
        if (!empty($stock_status) && $stock_status !== 'all' && $stock_status_value !== $stock_status) {
            continue;
        }
        
        // Arama filtresi
        if (!empty($search)) {
            $search_term = strtolower($search);
            if (strpos(strtolower($product['name']), $search_term) === false && 
                strpos(strtolower($product['sku']), $search_term) === false) {
                continue;
            }
        }
        
        $result[] = $item;
    }
    
    return $result;
}

/**
 * API: Kategorileri getir
 */
function wasm_api_get_categories() {
    $categories = get_terms(array(
        'taxonomy' => 'product_cat',
        'hide_empty' => true
    ));
    
    $result = array();
    
    if (!is_wp_error($categories) && !empty($categories)) {
        foreach ($categories as $category) {
            $result[] = array(
                'id' => $category->term_id,
                'name' => $category->name,
                'slug' => $category->slug,
                'count' => $category->count
            );
        }
    }
    
    // Debug için
    error_log('WASM Categories Count: ' . count($result));
    
    return $result;
}

/**
 * API: Satış trendini getir
 */
function wasm_api_get_sales_trend($request) {
    global $wpdb;
    
    $months = $request->get_param('months') ?: 12;
    
    // Son X ay için satış ve stok verilerini topla
    $result = array();
    
    // Son X ay için döngü
    for ($i = 0; $i < $months; $i++) {
        $month_start = date('Y-m-01', strtotime("-{$i} months"));
        $month_end = date('Y-m-t', strtotime("-{$i} months"));
        $month_key = date('M', strtotime("-{$i} months")); // Ay kısaltması (İng)
        
        // O ay için satış verilerini al
        $sales_query = "
            SELECT SUM(meta_qty.meta_value) as total_quantity
            FROM {$wpdb->posts} orders
            JOIN {$wpdb->prefix}woocommerce_order_items items ON orders.ID = items.order_id
            JOIN {$wpdb->prefix}woocommerce_order_itemmeta meta_qty ON items.order_item_id = meta_qty.order_item_id
            WHERE orders.post_type = 'shop_order'
            AND orders.post_status IN ('wc-completed', 'wc-processing')
            AND meta_qty.meta_key = '_qty'
            AND orders.post_date BETWEEN %s AND %s
        ";
        
        $total_sales = $wpdb->get_var($wpdb->prepare($sales_query, $month_start . ' 00:00:00', $month_end . ' 23:59:59'));
        
        // O ay için ortalama stok seviyesini hesapla
        $stock_query = "
            SELECT AVG(meta_stock.meta_value) as avg_stock
            FROM {$wpdb->posts} products
            JOIN {$wpdb->postmeta} meta_stock ON products.ID = meta_stock.post_id
            WHERE products.post_type = 'product'
            AND meta_stock.meta_key = '_stock'
            AND products.post_date <= %s
        ";
        
        $avg_stock = $wpdb->get_var($wpdb->prepare($stock_query, $month_end . ' 23:59:59'));
        
        // Result dizisine ekle
        $result[$months - $i - 1] = array(
            'month' => $month_key,
            'totalSales' => intval($total_sales) ?: 0,
            'averageStock' => intval($avg_stock) ?: 0
        );
    }
    
    // Sonuçları zaman sırasına göre sırala
    ksort($result);
    
    return array_values($result);
}

/**
 * API: Kategorileri getir
 */

/**
 * API: Özet bilgileri getir
 */
function wasm_api_get_summary() {
    global $wpdb;
    
    // Toplam ürün sayısı
    $total_products = $wpdb->get_var("
        SELECT COUNT(ID) 
        FROM {$wpdb->posts} 
        WHERE post_type = 'product' 
        AND post_status = 'publish'
    ");
    
    // Düşük stoklu ürünler
    $low_stock_query = "
        SELECT COUNT(*)
        FROM {$wpdb->posts} p
        JOIN {$wpdb->postmeta} pm_stock ON p.ID = pm_stock.post_id AND pm_stock.meta_key = '_stock'
        LEFT JOIN {$wpdb->postmeta} pm_threshold ON p.ID = pm_threshold.post_id AND pm_threshold.meta_key = '_wc_notify_low_stock_amount'
        WHERE p.post_type = 'product'
        AND p.post_status = 'publish'
        AND pm_stock.meta_value <= COALESCE(pm_threshold.meta_value, 5)
        AND pm_stock.meta_value > 0
    ";
    
    $low_stock_count = $wpdb->get_var($low_stock_query);
    
    // Toplam stok değeri
    $stock_value_query = "
        SELECT SUM(pm_stock.meta_value * pm_price.meta_value) as total_value
        FROM {$wpdb->posts} p
        JOIN {$wpdb->postmeta} pm_stock ON p.ID = pm_stock.post_id AND pm_stock.meta_key = '_stock'
        JOIN {$wpdb->postmeta} pm_price ON p.ID = pm_price.post_id AND pm_price.meta_key = '_price'
        WHERE p.post_type = 'product'
        AND p.post_status = 'publish'
        AND pm_stock.meta_value > 0
    ";
    
    $total_stock_value = $wpdb->get_var($stock_value_query);
    
    // Son 3 ay satış toplamı
    $sales_query = "
        SELECT COUNT(DISTINCT o.ID) as order_count, SUM(oi_qty.meta_value) as item_count
        FROM {$wpdb->posts} o
        JOIN {$wpdb->prefix}woocommerce_order_items oi ON o.ID = oi.order_id
        JOIN {$wpdb->prefix}woocommerce_order_itemmeta oi_qty ON oi.order_item_id = oi_qty.order_item_id AND oi_qty.meta_key = '_qty'
        WHERE o.post_type = 'shop_order'
        AND o.post_status IN ('wc-completed', 'wc-processing')
        AND o.post_date >= %s
    ";
    
    $three_months_ago = date('Y-m-d', strtotime('-3 months'));
    $sales_data = $wpdb->get_row($wpdb->prepare($sales_query, $three_months_ago . ' 00:00:00'), ARRAY_A);
    
    return array(
        'totalProducts' => intval($total_products),
        'lowStockCount' => intval($low_stock_count),
        'totalStockValue' => floatval($total_stock_value),
        'orderCount' => intval($sales_data['order_count']),
        'soldItems' => intval($sales_data['item_count'])
    );
}

/**
 * Dil dosyalarını yükle
 */
function wasm_load_textdomain() {
    load_plugin_textdomain('wc-advanced-stock-manager', false, dirname(WASM_PLUGIN_BASENAME) . '/languages/');
}
add_action('plugins_loaded', 'wasm_load_textdomain');

/**
 * Eklenti aktifleştirildiğinde çalışır
 */
function wasm_activation() {
    // Varsayılan ayarları oluştur
    if (!get_option('wasm_settings')) {
        update_option('wasm_settings', array(
            'reorder_threshold' => 1.5,
            'stock_period' => 2
        ));
    }
}
register_activation_hook(__FILE__, 'wasm_activation');

/**
 * Sınıfları yükle (eklenti başlatılırken)
 */
add_action('plugins_loaded', 'wasm_load_classes');