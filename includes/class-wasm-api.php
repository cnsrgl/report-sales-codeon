<?php
/**
 * API sınıfı
 * 
 * Eklentinin API endpoint'lerini yönetir
 * 
 * @package WooCommerce Advanced Stock Manager
 */

// Doğrudan erişimi engelle
if (!defined('ABSPATH')) {
    exit;
}

/**
 * API Sınıfı
 */
class WASM_API {
    /**
     * Sınıfı başlat
     */
    public function __construct() {
        // REST API routelarını kaydet
        add_action('rest_api_init', array($this, 'register_routes'));
    }
    
    /**
     * REST API route'larını kaydet
     */
    public function register_routes() {
        // Debug için
        error_log('WASM API: Registering REST routes');

        // Standart endpoint'ler
        register_rest_route('wc-advanced-stock-manager/v1', '/products', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_products'),
            'permission_callback' => function() {
                return current_user_can('manage_woocommerce');
            }
        ));

        register_rest_route('wc-advanced-stock-manager/v1', '/sales-trend', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_sales_trend'),
            'permission_callback' => function() {
                return current_user_can('manage_woocommerce');
            }
        ));

        register_rest_route('wc-advanced-stock-manager/v1', '/categories', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_categories'),
            'permission_callback' => function() {
                return current_user_can('manage_woocommerce');
            }
        ));

        register_rest_route('wc-advanced-stock-manager/v1', '/summary', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_summary'),
            'permission_callback' => function() {
                return current_user_can('manage_woocommerce');
            }
        ));
        
        // Test endpoint - Herkes erişebilir
        register_rest_route('wc-advanced-stock-manager/v1', '/test', array(
            'methods' => 'GET',
            'callback' => function() {
                return array(
                    'success' => true,
                    'message' => 'API endpoint çalışıyor!',
                    'time' => current_time('mysql')
                );
            },
            'permission_callback' => '__return_true'
        ));
        
        // PDF Rapor endpoint'i
        register_rest_route('wc-advanced-stock-manager/v1', '/generate-pdf', array(
            'methods' => 'POST',
            'callback' => array($this, 'generate_pdf_report'),
            'permission_callback' => function() {
                return current_user_can('manage_woocommerce');
            }
        ));
        
        // Hangi routeların kayıtlı olduğunu logla
        $server = rest_get_server();
        $routes = $server->get_routes();
        $our_routes = array_filter(array_keys($routes), function($route) {
            return strpos($route, 'wc-advanced-stock-manager') !== false;
        });
        error_log('WASM API: Registered routes - ' . json_encode($our_routes));
    }
    
    /**
     * API: Ürünleri getir
     */
    public function get_products($request) {
        // Bu fonksiyon ana dosyada tanımlanmış
        return wasm_api_get_products($request);
    }
    
    /**
     * API: Satış trendini getir
     */
    public function get_sales_trend($request) {
        // Bu fonksiyon ana dosyada tanımlanmış
        return wasm_api_get_sales_trend($request);
    }
    
    /**
     * API: Kategorileri getir
     */
    public function get_categories() {
        // Bu fonksiyon ana dosyada tanımlanmış
        return wasm_api_get_categories();
    }
    
    /**
     * API: Özet bilgileri getir
     */
    public function get_summary() {
        // Bu fonksiyon ana dosyada tanımlanmış
        return wasm_api_get_summary();
    }
    
    /**
     * API: PDF raporu oluştur
     * Optimize edilmiş ve daha güvenilir PDF oluşturma fonksiyonu
     */
    public function generate_pdf_report($request) {
        // Log başlangıcı
        error_log('WASM API: PDF raporu oluşturuluyor - ' . json_encode($request->get_params()));
        
        // FPDF sınıfını içe aktar
        require_once WASM_PLUGIN_DIR . 'includes/class-wasm-fpdf.php';
        
        // İstek parametrelerini al
        $params = $request->get_params();
        $report_type = isset($params['reportType']) ? sanitize_text_field($params['reportType']) : 'summary';
        $filters = isset($params['filters']) ? $params['filters'] : [];
        
        // Geçerli rapor tiplerini kontrol et
        $valid_report_types = array('summary', 'products', 'stock', 'sales');
        if (!in_array($report_type, $valid_report_types)) {
            error_log('WASM API: Geçersiz rapor türü: ' . $report_type);
            return new WP_Error('invalid_report_type', __('Geçersiz rapor türü.', 'wc-advanced-stock-manager'), ['status' => 400]);
        }
        
        // Raporlama için verileri topla
        $data = [];
        
        try {
            // Rapor türüne göre veri toplama işlemi
            switch ($report_type) {
                case 'summary':
                    // Özet raporu için tüm verileri topla
                    error_log('WASM API: Özet raporu verileri toplanıyor');
                    
                    $summary_request = new WP_REST_Request('GET', '/wc-advanced-stock-manager/v1/summary');
                    $data['summary'] = $this->get_summary($summary_request);
                    
                    $trend_request = new WP_REST_Request('GET', '/wc-advanced-stock-manager/v1/sales-trend');
                    $trend_request->set_param('months', 12);
                    $data['salesTrend'] = $this->get_sales_trend($trend_request);
                    
                    $data['categories'] = $this->get_categories();
                    
                    // Sipariş edilecek ürün sayısını hesapla
                    $products_request = new WP_REST_Request('GET', '/wc-advanced-stock-manager/v1/products');
                    if (!empty($filters)) {
                        foreach ($filters as $key => $value) {
                            if ($key === 'dateRange' && is_array($value)) {
                                if (isset($value['start'])) $products_request->set_param('start_date', sanitize_text_field($value['start']));
                                if (isset($value['end'])) $products_request->set_param('end_date', sanitize_text_field($value['end']));
                            } elseif ($key === 'category' && $value !== 'all') {
                                $products_request->set_param('category', sanitize_text_field($value));
                            } elseif ($key === 'stockStatus' && $value !== 'all') {
                                $products_request->set_param('stock_status', sanitize_text_field($value));
                            } elseif ($key === 'search' && !empty($value)) {
                                $products_request->set_param('search', sanitize_text_field($value));
                            }
                        }
                    }
                    
                    $products = $this->get_products($products_request);
                    $data['reorderCount'] = count(array_filter($products, function($product) {
                        return isset($product['recommendedOrder']) && $product['recommendedOrder'] > 0;
                    }));
                    break;
                    
                case 'products':
                case 'stock':
                    // Ürün raporu için ürün verilerini topla
                    error_log('WASM API: Ürün raporu verileri toplanıyor');
                    
                    $products_request = new WP_REST_Request('GET', '/wc-advanced-stock-manager/v1/products');
                    if (!empty($filters)) {
                        foreach ($filters as $key => $value) {
                            if ($key === 'dateRange' && is_array($value)) {
                                if (isset($value['start'])) $products_request->set_param('start_date', sanitize_text_field($value['start']));
                                if (isset($value['end'])) $products_request->set_param('end_date', sanitize_text_field($value['end']));
                            } elseif ($key === 'category' && $value !== 'all') {
                                $products_request->set_param('category', sanitize_text_field($value));
                            } elseif ($key === 'stockStatus' && $value !== 'all') {
                                $products_request->set_param('stock_status', sanitize_text_field($value));
                            } elseif ($key === 'search' && !empty($value)) {
                                $products_request->set_param('search', sanitize_text_field($value));
                            }
                        }
                    }
                    
                    $data['products'] = $this->get_products($products_request);
                    break;
                    
                case 'sales':
                    // Satış raporu için trend verilerini topla
                    error_log('WASM API: Satış raporu verileri toplanıyor');
                    
                    $trend_request = new WP_REST_Request('GET', '/wc-advanced-stock-manager/v1/sales-trend');
                    $trend_request->set_param('months', 12);
                    $data['salesTrend'] = $this->get_sales_trend($trend_request);
                    break;
            }
            
            // Verilerin hazır olduğunu logla
            error_log('WASM API: Rapor verileri toplandı, PDF oluşturuluyor');
            
            // PDF oluşturucu sınıfını başlat
            $pdf_generator = new WASM_FPDF();
            
            // PDF'i oluştur
            $pdf_content = $pdf_generator->generate_pdf($report_type, $data, $filters);
            
            if (!$pdf_content) {
                error_log('WASM API: PDF oluşturma başarısız oldu - FPDF bir içerik döndürmedi');
                return new WP_Error('pdf_generation_failed', __('PDF oluşturma başarısız oldu.', 'wc-advanced-stock-manager'), ['status' => 500]);
            }
            
            // Dosya adını oluştur
            $file_name = 'wasm-' . $report_type . '-report-' . date('Y-m-d') . '.pdf';
            
            // Base64 formatında PDF içeriğini döndür
            $encoded_content = base64_encode($pdf_content);
            
            // İçeriğin geçerli olup olmadığını kontrol et
            if (!$encoded_content) {
                error_log('WASM API: PDF içeriği base64 olarak kodlanamadı');
                return new WP_Error('pdf_encoding_failed', __('PDF içeriği kodlanamadı.', 'wc-advanced-stock-manager'), ['status' => 500]);
            }
            
            // Yanıt boyutunu logla
            error_log('WASM API: PDF raporu başarıyla oluşturuldu - ' . strlen($encoded_content) . ' bayt');
            
            $response = [
                'success' => true,
                'fileName' => $file_name,
                'content' => $encoded_content
            ];
            
            return $response;
            
        } catch (Exception $e) {
            error_log('WASM API: PDF oluşturma hatası - ' . $e->getMessage());
            error_log('WASM API: Hata Detayları - ' . print_r($e->getTrace(), true));
            return new WP_Error('pdf_exception', $e->getMessage(), ['status' => 500]);
        }
    }
} // Sınıf tanımı burada bitiyor

// API sınıfını oluştur ve başlat - Sınıf DIŞINDA
global $wasm_api;
$wasm_api = new WASM_API();