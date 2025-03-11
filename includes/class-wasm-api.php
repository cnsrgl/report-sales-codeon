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
        // Burada API özelliklerini tanımlayabilirsiniz
    }
    
    /**
     * REST API route'larını kaydet
     */
    public function register_routes() {
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
}