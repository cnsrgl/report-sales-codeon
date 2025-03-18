<?php
/**
 * FPDF Rapor sınıfı
 * 
 * Eklentinin PDF rapor oluşturma işlevlerini FPDF kütüphanesi ile yönetir
 * 
 * @package WooCommerce Advanced Stock Manager
 */

// Doğrudan erişimi engelle
if (!defined('ABSPATH')) {
    exit;
}

// FPDF kütüphanesini yükle
if (!class_exists('FPDF')) {
    require_once WASM_PLUGIN_DIR . 'vendor/fpdf/fpdf.php';
}

/**
 * PDF Rapor Sınıfı (FPDF tabanlı)
 */
class WASM_FPDF extends FPDF {
    /**
     * Rapor tipi
     */
    private $report_type;
    
    /**
     * Rapor verileri
     */
    private $data;
    
    /**
     * Filtreler
     */
    private $filters;
    
    /**
     * Rapor başlığı
     */
    private $report_title;
    
    /**
     * Header fonksiyonu
     */
    public function Header() {
        // Logo veya site ismi
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 10, get_bloginfo('name'), 0, 0, 'L');
        
        // Rapor başlığı
        $this->SetX(-60);
        $this->Cell(60, 10, $this->report_title, 0, 0, 'R');
        
        // Çizgi
        $this->Ln(15);
        $this->Line($this->GetX(), $this->GetY(), $this->GetX() + 190, $this->GetY());
        $this->Ln(5);
    }
    
    /**
     * Footer fonksiyonu
     */
    public function Footer() {
        // Sayfa numarası
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        
        // Sol tarafa tarih
        $this->Cell(100, 10, date_i18n(get_option('date_format'), current_time('timestamp')), 0, 0, 'L');
        
        // Sağa sayfa numarası
        $this->Cell(90, 10, __('Sayfa', 'wc-advanced-stock-manager') . ' ' . $this->PageNo() . '/{nb}', 0, 0, 'R');
    }
    
    /**
     * PDF'i oluştur
     *
     * @param string $report_type Rapor tipi ('summary', 'products', 'sales', 'stock')
     * @param array $data Rapor verileri
     * @param array $filters Rapor filtreleri
     * @return string PDF dosyasının içeriği
     */
    public function generate_pdf($report_type, $data, $filters = []) {
        $this->report_type = $report_type;
        $this->data = $data;
        $this->filters = $filters;
        $this->report_title = $this->get_report_title();
        
        // Yeni PDF belgesi oluştur (A4, mm, Portrait)
        $this->AddPage();
        $this->AliasNbPages();
        
        // Başlık ve tarih bilgisi
        $this->SetFont('Arial', 'B', 16);
        $this->Cell(0, 10, $this->report_title, 0, 1, 'C');
        
        $this->SetFont('Arial', 'I', 10);
        $this->Cell(0, 10, __('Oluşturulma Tarihi:', 'wc-advanced-stock-manager') . ' ' . date_i18n(get_option('date_format'), current_time('timestamp')), 0, 1, 'C');
        
        // Filtre bilgilerini ekle
        if (!empty($this->filters)) {
            $this->add_filters_section();
        }
        
        // Rapora özgü içeriği oluştur
        switch ($this->report_type) {
            case 'summary':
                $this->generate_summary_report();
                break;
            case 'products':
                $this->generate_products_report();
                break;
            case 'stock':
                $this->generate_stock_report();
                break;
            case 'sales':
                $this->generate_sales_report();
                break;
            default:
                $this->SetFont('Arial', '', 12);
                $this->Cell(0, 10, __('Geçersiz rapor tipi.', 'wc-advanced-stock-manager'), 0, 1);
        }
        
        // PDF içeriğini döndür
        return $this->Output('S');
    }
    
    /**
     * Rapor türüne göre başlık döndür
     *
     * @return string Rapor başlığı
     */
    private function get_report_title() {
        switch ($this->report_type) {
            case 'summary':
                return __('Stok Yönetimi Özet Raporu', 'wc-advanced-stock-manager');
            case 'products':
                return __('Ürün Stok Raporu', 'wc-advanced-stock-manager');
            case 'stock':
                return __('Düşük Stok ve Sipariş Raporu', 'wc-advanced-stock-manager');
            case 'sales':
                return __('Satış Trendi Raporu', 'wc-advanced-stock-manager');
            default:
                return __('WooCommerce Gelişmiş Stok Yönetimi Raporu', 'wc-advanced-stock-manager');
        }
    }
    
    /**
     * Filtre bilgilerini ekle
     */
    private function add_filters_section() {
        $this->Ln(5);
        $this->SetFillColor(240, 240, 240);
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 10, __('Filtreler:', 'wc-advanced-stock-manager'), 0, 1, 'L', true);
        
        $this->SetFont('Arial', '', 10);
        
        if (!empty($this->filters['dateRange'])) {
            $start_date = isset($this->filters['dateRange']['start']) ? $this->filters['dateRange']['start'] : '';
            $end_date = isset($this->filters['dateRange']['end']) ? $this->filters['dateRange']['end'] : '';
            
            if ($start_date && $end_date) {
                $this->Cell(0, 8, __('Tarih Aralığı:', 'wc-advanced-stock-manager') . ' ' . 
                    $start_date . ' - ' . $end_date, 0, 1, 'L', true);
            }
        }
        
        if (!empty($this->filters['category']) && $this->filters['category'] !== 'all') {
            $this->Cell(0, 8, __('Kategori:', 'wc-advanced-stock-manager') . ' ' . 
                $this->filters['category'], 0, 1, 'L', true);
        }
        
        if (!empty($this->filters['stockStatus']) && $this->filters['stockStatus'] !== 'all') {
            $stock_status = $this->filters['stockStatus'];
            $status_text = '';
            
            switch ($stock_status) {
                case 'critical':
                    $status_text = __('Kritik', 'wc-advanced-stock-manager');
                    break;
                case 'low':
                    $status_text = __('Düşük', 'wc-advanced-stock-manager');
                    break;
                default:
                    $status_text = __('İyi', 'wc-advanced-stock-manager');
            }
            
            $this->Cell(0, 8, __('Stok Durumu:', 'wc-advanced-stock-manager') . ' ' . 
                $status_text, 0, 1, 'L', true);
        }
        
        if (!empty($this->filters['search'])) {
            $this->Cell(0, 8, __('Arama:', 'wc-advanced-stock-manager') . ' ' . 
                $this->filters['search'], 0, 1, 'L', true);
        }
        
        $this->Ln(5);
    }
    
    /**
     * Özet raporu oluştur
     */
    private function generate_summary_report() {
        $summary = isset($this->data['summary']) ? $this->data['summary'] : [];
        $trend_data = isset($this->data['salesTrend']) ? $this->data['salesTrend'] : [];
        $categories = isset($this->data['categories']) ? $this->data['categories'] : [];
        
        // Özet bilgiler başlık
        $this->Ln(10);
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 10, __('Özet Bilgiler', 'wc-advanced-stock-manager'), 0, 1);
        $this->Ln(2);
        
        // Özet kartları
        $this->SetFillColor(240, 248, 255); // Açık mavi
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(45, 10, __('Toplam Ürün', 'wc-advanced-stock-manager'), 0, 0, 'L', true);
        $this->Cell(45, 10, isset($summary['totalProducts']) ? $summary['totalProducts'] : 0, 0, 0, 'C', true);
        
        $this->SetFillColor(255, 248, 220); // Açık sarı
        $this->Cell(45, 10, __('Düşük Stok', 'wc-advanced-stock-manager'), 0, 0, 'L', true);
        $this->Cell(45, 10, isset($summary['lowStockCount']) ? $summary['lowStockCount'] : 0, 0, 1, 'C', true);
        
        $this->Ln(2);
        
        $this->SetFillColor(240, 255, 240); // Açık yeşil
        $this->Cell(45, 10, __('Sipariş Edilecek', 'wc-advanced-stock-manager'), 0, 0, 'L', true);
        $this->Cell(45, 10, isset($this->data['reorderCount']) ? $this->data['reorderCount'] : 0, 0, 0, 'C', true);
        
        $this->SetFillColor(248, 240, 255); // Açık mor
        $this->Cell(45, 10, __('Stok Değeri', 'wc-advanced-stock-manager'), 0, 0, 'L', true);
        
        // WC Price fonksiyonu yoksa basit formatla
        if (function_exists('wc_price')) {
            $price = wc_price(isset($summary['totalStockValue']) ? $summary['totalStockValue'] : 0);
        } else {
            $price = isset($summary['totalStockValue']) ? number_format($summary['totalStockValue'], 2) . ' ' . get_woocommerce_currency() : '0';
        }
        
        $this->Cell(45, 10, $price, 0, 1, 'C', true);
        
        // Satış Trendi
        if (!empty($trend_data)) {
            $this->Ln(10);
            $this->SetFont('Arial', 'B', 14);
            $this->Cell(0, 10, __('Satış Trendi', 'wc-advanced-stock-manager'), 0, 1);
            
            // Tablo başlıkları
            $this->SetFillColor(230, 230, 230);
            $this->SetFont('Arial', 'B', 10);
            $this->Cell(60, 8, __('Ay', 'wc-advanced-stock-manager'), 1, 0, 'C', true);
            $this->Cell(60, 8, __('Toplam Satış', 'wc-advanced-stock-manager'), 1, 0, 'C', true);
            $this->Cell(60, 8, __('Ortalama Stok', 'wc-advanced-stock-manager'), 1, 1, 'C', true);
            
            // Tablo verileri
            $this->SetFont('Arial', '', 10);
            $fill = false;
            
            foreach ($trend_data as $month_data) {
                $this->Cell(60, 8, $month_data['month'], 1, 0, 'L', $fill);
                $this->Cell(60, 8, $month_data['totalSales'], 1, 0, 'R', $fill);
                $this->Cell(60, 8, $month_data['averageStock'], 1, 1, 'R', $fill);
                $fill = !$fill;
            }
        }
        
        // Kategori Dağılımı
        if (!empty($categories)) {
            $this->Ln(10);
            $this->SetFont('Arial', 'B', 14);
            $this->Cell(0, 10, __('Kategori Dağılımı', 'wc-advanced-stock-manager'), 0, 1);
            
            // Kategorileri ürün sayısına göre azalan şekilde sırala
            usort($categories, function($a, $b) {
                return $b['count'] - $a['count'];
            });
            
            // İlk 10 kategoriyi göster
            $categories = array_slice($categories, 0, 10);
            
            // Tablo başlıkları
            $this->SetFillColor(230, 230, 230);
            $this->SetFont('Arial', 'B', 10);
            $this->Cell(120, 8, __('Kategori', 'wc-advanced-stock-manager'), 1, 0, 'C', true);
            $this->Cell(60, 8, __('Ürün Sayısı', 'wc-advanced-stock-manager'), 1, 1, 'C', true);
            
            // Tablo verileri
            $this->SetFont('Arial', '', 10);
            $fill = false;
            
            foreach ($categories as $category) {
                $this->Cell(120, 8, $category['name'], 1, 0, 'L', $fill);
                $this->Cell(60, 8, $category['count'], 1, 1, 'R', $fill);
                $fill = !$fill;
            }
        }
    }
    
/**
 * Ürün raporu oluştur
 */
private function generate_products_report() {
    $products = $this->data['products'] ?? [];
    
    if (empty($products)) {
        $this->SetFont('Arial', '', 12);
        $this->Cell(0, 10, __('Ürün bulunamadı.', 'wc-advanced-stock-manager'), 0, 1);
        return;
    }
    
    $this->Ln(5);
    $this->SetFont('Arial', 'B', 14);
    $this->Cell(0, 10, __('Ürün Stok Listesi', 'wc-advanced-stock-manager'), 0, 1);
    
    // Tablo başlıkları
    $this->SetFillColor(230, 230, 230);
    $this->SetFont('Arial', 'B', 8);
    $this->Cell(70, 8, __('Ürün Adı', 'wc-advanced-stock-manager'), 1, 0, 'L', true);
    $this->Cell(25, 8, __('Stok', 'wc-advanced-stock-manager'), 1, 0, 'C', true);
    $this->Cell(25, 8, __('Son 3 Ay', 'wc-advanced-stock-manager'), 1, 0, 'C', true);
    $this->Cell(30, 8, __('Durum', 'wc-advanced-stock-manager'), 1, 0, 'C', true);
    $this->Cell(20, 8, __('Önerilen', 'wc-advanced-stock-manager'), 1, 0, 'C', true);
    $this->Cell(40, 8, __('Kategori', 'wc-advanced-stock-manager'), 1, 1, 'L', true);
    
    // Tablo verileri
    $this->SetFont('Arial', '', 8);
    $fill = false;
    
    // Ürün sıralama anahtarları
    $sortedProducts = [];
    
    // Ürünleri sırala
    foreach ($products as $product) {
        // Ana ürün adına göre grupla
        $sortKey = isset($product['productType']) && $product['productType'] === 'variable' ? 
            $product['name'] . '_0' :     // Ana ürün
            ($product['parentProductName'] ?? $product['name']) . '_1_' . $product['name']; // Varyasyon
        
        $sortedProducts[$sortKey] = $product;
    }
    
    // Anahtara göre sırala
    ksort($sortedProducts);
    
    foreach ($sortedProducts as $product) {
        // Ürün adı için kontrol (uzunsa kısalt)
        $product_name = $product['name'];
        if (mb_strlen($product_name) > 35) {
            $product_name = mb_substr($product_name, 0, 32) . '...';
        }
        
        // Ürün tipini kontrol et
        $isVariable = isset($product['productType']) && $product['productType'] === 'variable';
        $isVariation = isset($product['isVariation']) && $product['isVariation'] === true;
        
        // Varyasyonlar için girintili gösterim
        $padding = $isVariation ? 5 : 0;
        
        $this->Cell(70, 8, str_repeat(' ', $padding) . $product_name, 1, 0, 'L', $fill);
        
        // Stok durumuna göre renk ayarla
        $stock_status = $product['stockStatus'];
        $status_text = __('İyi', 'wc-advanced-stock-manager');
        
        switch ($stock_status) {
            case 'critical':
                $status_text = __('Kritik', 'wc-advanced-stock-manager');
                $this->SetTextColor(255, 0, 0); // Kırmızı
                break;
            case 'low':
                $status_text = __('Düşük', 'wc-advanced-stock-manager');
                $this->SetTextColor(255, 128, 0); // Turuncu
                break;
            default:
                $this->SetTextColor(0, 128, 0); // Yeşil
        }
        
        // Stok miktarı gösterilirken, varyasyonlu ürünlerde (Toplam) etiketi göster
        $stock_text = $product['currentStock'];
        if ($isVariable) {
            $stock_text .= ' ('.__('Toplam', 'wc-advanced-stock-manager').')';
        }
        
        $this->Cell(25, 8, $stock_text, 1, 0, 'C', $fill);
        $this->SetTextColor(0, 0, 0); // Normal renk
        
        $this->Cell(25, 8, $product['last3MonthsSales'], 1, 0, 'C', $fill);
        
        // Stok durumu
        switch ($stock_status) {
            case 'critical':
                $this->SetTextColor(255, 0, 0); // Kırmızı
                break;
            case 'low':
                $this->SetTextColor(255, 128, 0); // Turuncu
                break;
            default:
                $this->SetTextColor(0, 128, 0); // Yeşil
        }
        
        $this->Cell(30, 8, $status_text, 1, 0, 'C', $fill);
        $this->SetTextColor(0, 0, 0); // Normal renk
        
        // Önerilen sipariş
        if (isset($product['recommendedOrder']) && $product['recommendedOrder'] > 0) {
            $this->SetTextColor(0, 0, 255); // Mavi
            $this->Cell(20, 8, $product['recommendedOrder'], 1, 0, 'C', $fill);
            $this->SetTextColor(0, 0, 0); // Normal renk
        } else {
            $this->Cell(20, 8, '-', 1, 0, 'C', $fill);
        }
        
        $this->Cell(40, 8, $product['category'] ?? __('Kategorisiz', 'wc-advanced-stock-manager'), 1, 1, 'L', $fill);
        
        $fill = !$fill;
    }
}

/**
 * Stok raporu oluştur
 */
private function generate_stock_report() {
    $products = $this->data['products'] ?? [];
    
    // Ürünleri ürün tipine ve varyasyon ilişkilerine göre sırala
    $sortedProducts = [];
    
    // Ürünleri sırala
    foreach ($products as $product) {
        // Ana ürün adına göre grupla
        $sortKey = isset($product['productType']) && $product['productType'] === 'variable' ? 
            $product['name'] . '_0' :     // Ana ürün
            ($product['parentProductName'] ?? $product['name']) . '_1_' . $product['name']; // Varyasyon
        
        $sortedProducts[$sortKey] = $product;
    }
    
    // Anahtara göre sırala
    ksort($sortedProducts);
    
    // Düşük stoklu ürünleri filtrele
    $low_stock_products = array_filter($sortedProducts, function($product) {
        return $product['stockStatus'] === 'critical' || $product['stockStatus'] === 'low';
    });
    
    // Sipariş edilecek ürünleri filtrele
    $reorder_products = array_filter($sortedProducts, function($product) {
        return isset($product['recommendedOrder']) && $product['recommendedOrder'] > 0;
    });
    
    // Düşük stoklu ürünler
    $this->Ln(5);
    $this->SetFont('Arial', 'B', 14);
    $this->Cell(0, 10, __('Düşük Stoklu Ürünler', 'wc-advanced-stock-manager'), 0, 1);
    
    if (empty($low_stock_products)) {
        $this->SetFont('Arial', '', 10);
        $this->Cell(0, 10, __('Düşük stoklu ürün bulunmamaktadır.', 'wc-advanced-stock-manager'), 0, 1);
    } else {
        // Tablo başlıkları
        $this->SetFillColor(230, 230, 230);
        $this->SetFont('Arial', 'B', 8);
        $this->Cell(70, 8, __('Ürün Adı', 'wc-advanced-stock-manager'), 1, 0, 'L', true);
        $this->Cell(25, 8, __('Stok', 'wc-advanced-stock-manager'), 1, 0, 'C', true);
        $this->Cell(35, 8, __('Durum', 'wc-advanced-stock-manager'), 1, 0, 'C', true);
        $this->Cell(40, 8, __('Kategori', 'wc-advanced-stock-manager'), 1, 1, 'L', true);
        
        // Tablo verileri
        $this->SetFont('Arial', '', 8);
        $fill = false;
        
        foreach ($low_stock_products as $product) {
            // Ürün adı için kontrol
            $product_name = $product['name'];
            if (mb_strlen($product_name) > 40) {
                $product_name = mb_substr($product_name, 0, 37) . '...';
            }
            
            // Ürün tipini kontrol et
            $isVariable = isset($product['productType']) && $product['productType'] === 'variable';
            $isVariation = isset($product['isVariation']) && $product['isVariation'] === true;
            
            // Varyasyonlar için girintili gösterim
            $padding = $isVariation ? 5 : 0;
            
            $this->Cell(70, 8, str_repeat(' ', $padding) . $product_name, 1, 0, 'L', $fill);
            
            // Stok durumuna göre renk ayarla
            $stock_status = $product['stockStatus'];
            $status_text = $stock_status === 'critical' ? __('Kritik', 'wc-advanced-stock-manager') : __('Düşük', 'wc-advanced-stock-manager');
            
            // Stok miktarı gösterilirken, varyasyonlu ürünlerde (Toplam) etiketi göster
            $stock_text = $product['currentStock'];
            if ($isVariable) {
                $stock_text .= ' ('.__('Toplam', 'wc-advanced-stock-manager').')';
            }
            
            $this->SetTextColor($stock_status === 'critical' ? 255 : 255, $stock_status === 'critical' ? 0 : 128, 0);
            $this->Cell(25, 8, $stock_text, 1, 0, 'C', $fill);
            
            $this->Cell(35, 8, $status_text, 1, 0, 'C', $fill);
            $this->SetTextColor(0, 0, 0); // Normal renk
            
            $this->Cell(40, 8, $product['category'] ?? __('Kategorisiz', 'wc-advanced-stock-manager'), 1, 1, 'L', $fill);
            
            $fill = !$fill;
        }
    }
    
    // Sipariş edilecek ürünler
    $this->Ln(10);
    $this->SetFont('Arial', 'B', 14);
    $this->Cell(0, 10, __('Sipariş Edilecek Ürünler', 'wc-advanced-stock-manager'), 0, 1);
    
    if (empty($reorder_products)) {
        $this->SetFont('Arial', '', 10);
        $this->Cell(0, 10, __('Sipariş edilecek ürün bulunmamaktadır.', 'wc-advanced-stock-manager'), 0, 1);
    } else {
        // Tablo başlıkları
        $this->SetFillColor(230, 230, 230);
        $this->SetFont('Arial', 'B', 8);
        $this->Cell(60, 8, __('Ürün Adı', 'wc-advanced-stock-manager'), 1, 0, 'L', true);
        $this->Cell(20, 8, __('Stok', 'wc-advanced-stock-manager'), 1, 0, 'C', true);
        $this->Cell(25, 8, __('Son 3 Ay', 'wc-advanced-stock-manager'), 1, 0, 'C', true);
        $this->Cell(25, 8, __('Önerilen', 'wc-advanced-stock-manager'), 1, 0, 'C', true);
        $this->Cell(40, 8, __('Kategori', 'wc-advanced-stock-manager'), 1, 1, 'L', true);
        
        // Tablo verileri
        $this->SetFont('Arial', '', 8);
        $fill = false;
        
        foreach ($reorder_products as $product) {
            // Ürün adı için kontrol
            $product_name = $product['name'];
            if (mb_strlen($product_name) > 35) {
                $product_name = mb_substr($product_name, 0, 32) . '...';
            }
            
            // Ürün tipini kontrol et
            $isVariable = isset($product['productType']) && $product['productType'] === 'variable';
            $isVariation = isset($product['isVariation']) && $product['isVariation'] === true;
            
            // Varyasyonlar için girintili gösterim
            $padding = $isVariation ? 5 : 0;
            
            $this->Cell(60, 8, str_repeat(' ', $padding) . $product_name, 1, 0, 'L', $fill);
            
            // Stok miktarı gösterilirken, varyasyonlu ürünlerde (Toplam) etiketi göster
            $stock_text = $product['currentStock'];
            if ($isVariable) {
                $stock_text .= ' ('.__('Toplam', 'wc-advanced-stock-manager').')';
            }
            
            $this->Cell(20, 8, $stock_text, 1, 0, 'C', $fill);
            $this->Cell(25, 8, $product['last3MonthsSales'], 1, 0, 'C', $fill);
            
            // Önerilen sipariş
            $this->SetTextColor(0, 0, 255); // Mavi
            $this->Cell(25, 8, $product['recommendedOrder'], 1, 0, 'C', $fill);
            $this->SetTextColor(0, 0, 0); // Normal renk
            
            $this->Cell(40, 8, $product['category'] ?? __('Kategorisiz', 'wc-advanced-stock-manager'), 1, 1, 'L', $fill);
            
            $fill = !$fill;
        }
    }
}
    
    /**
     * Satış trendi raporu oluştur
     */
    private function generate_sales_report() {
        $trend_data = $this->data['salesTrend'] ?? [];
        
        if (empty($trend_data)) {
            $this->SetFont('Arial', '', 12);
            $this->Cell(0, 10, __('Satış trend verisi bulunamadı.', 'wc-advanced-stock-manager'), 0, 1);
            return;
        }
        
        $this->Ln(5);
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 10, __('Satış Trendi', 'wc-advanced-stock-manager'), 0, 1);
        
        // Tablo başlıkları
        $this->SetFillColor(230, 230, 230);
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(40, 8, __('Ay', 'wc-advanced-stock-manager'), 1, 0, 'C', true);
        $this->Cell(40, 8, __('Toplam Satış', 'wc-advanced-stock-manager'), 1, 0, 'C', true);
        $this->Cell(40, 8, __('Ortalama Stok', 'wc-advanced-stock-manager'), 1, 0, 'C', true);
        $this->Cell(40, 8, __('Stok/Satış Oranı', 'wc-advanced-stock-manager'), 1, 1, 'C', true);
        
        // Tablo verileri
        $this->SetFont('Arial', '', 10);
        $fill = false;
        
        foreach ($trend_data as $month_data) {
            $ratio = 0;
            if ($month_data['totalSales'] > 0) {
                $ratio = round($month_data['averageStock'] / $month_data['totalSales'], 2);
            }
            
            $this->Cell(40, 8, $month_data['month'], 1, 0, 'L', $fill);
            $this->Cell(40, 8, $month_data['totalSales'], 1, 0, 'R', $fill);
            $this->Cell(40, 8, $month_data['averageStock'], 1, 0, 'R', $fill);
            $this->Cell(40, 8, $ratio, 1, 1, 'R', $fill);
            
            $fill = !$fill;
        }
        
        // Satış ve stok analizi
        $this->Ln(10);
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 10, __('Satış ve Stok Analizi', 'wc-advanced-stock-manager'), 0, 1);
        
        $total_sales = array_sum(array_column($trend_data, 'totalSales'));
        $avg_stock = array_sum(array_column($trend_data, 'averageStock')) / count($trend_data);
        
        $this->SetFont('Arial', '', 10);
        $this->Cell(0, 8, __('Dönem Toplam Satış:', 'wc-advanced-stock-manager') . ' ' . $total_sales, 0, 1);
        $this->Cell(0, 8, __('Dönem Ortalama Stok:', 'wc-advanced-stock-manager') . ' ' . round($avg_stock), 0, 1);
        
        if ($total_sales > 0) {
            $stock_turnover = round(($total_sales / $avg_stock) * (12 / count($trend_data)), 2);
            $this->Cell(0, 8, __('Tahmini Yıllık Stok Devir Hızı:', 'wc-advanced-stock-manager') . ' ' . $stock_turnover, 0, 1);
        }
    }
}