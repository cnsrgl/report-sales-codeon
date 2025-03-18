import React, { useState, useEffect } from 'react';
import FilterPanel from './components/FilterPanel';
import ProductTable from './components/ProductTable';
import SummaryCards from './components/SummaryCards';
import SalesTrendChart from './components/SalesTrendChart';
import LoadingSpinner from './components/LoadingSpinner';
import ErrorMessage from './components/ErrorMessage';
import ReportPanel from './components/ReportPanel';
import { formatCurrency } from './utils/formatters';

const App = () => {
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [products, setProducts] = useState([]);
  const [filteredProducts, setFilteredProducts] = useState([]);
  const [salesTrendData, setSalesTrendData] = useState([]);
  const [categories, setCategories] = useState([]);
  const [summary, setSummary] = useState({
    totalProducts: 0,
    lowStockCount: 0,
    totalStockValue: 0,
    orderCount: 0,
    soldItems: 0
  });

  // Filtreler
  const [filters, setFilters] = useState({
    dateRange: {
      start: new Date(new Date().setMonth(new Date().getMonth() - 3)).toISOString().split('T')[0],
      end: new Date().toISOString().split('T')[0]
    },
    category: 'all',
    stockStatus: 'all',
    search: '',
    sortBy: 'name',
    sortOrder: 'asc'
  });

  // İlk yükleme
  useEffect(() => {
    fetchData();
  }, []);

  // Verileri getir
  const fetchData = async () => {
    setLoading(true);
    setError(null);

    try {
      // API için temel URL ve headers
      const baseUrl = window.wasmSettings.apiUrl || '/wp-json/wc-advanced-stock-manager/v1';
      const headers = {
        'X-WP-Nonce': window.wasmSettings.nonce || ''
      };

      // Ürünleri getir
      const productsUrl = new URL(`${baseUrl}/products`, window.location.origin);
      productsUrl.search = new URLSearchParams({
        start_date: filters.dateRange.start,
        end_date: filters.dateRange.end,
        category: filters.category !== 'all' ? filters.category : '',
        stock_status: filters.stockStatus !== 'all' ? filters.stockStatus : '',
        search: filters.search
      }).toString();

      const productsResponse = await fetch(productsUrl, { headers });

      if (!productsResponse.ok) {
        throw new Error('Ürün verileri yüklenemedi.');
      }

      const productsData = await productsResponse.json();
      
      // API yanıtını debug et
      if (productsData && productsData.length > 0) {
        const variableProducts = productsData.filter(p => p.productType === 'variable');
        console.log(`Toplam ürün: ${productsData.length}, Değişkenli ürün: ${variableProducts.length}`);
        
        if (variableProducts.length > 0) {
          const sampleProduct = variableProducts[0];
          console.log('Örnek değişkenli ürün:', sampleProduct);
          console.log('Stok miktarı:', sampleProduct.currentStock);
          
          if (sampleProduct.variations && sampleProduct.variations.length > 0) {
            console.log('Örnek varyasyon:', sampleProduct.variations[0]);
            console.log('Varyasyon stoğu:', sampleProduct.variations[0].stock);
          }
        }
      }
      
      setProducts(productsData);
      
      // Filtreleri uygula
      filterProducts(productsData, filters);

      // Satış trendlerini getir
      const trendUrl = new URL(`${baseUrl}/sales-trend`, window.location.origin);
      trendUrl.search = new URLSearchParams({ months: '12' }).toString();

      const trendResponse = await fetch(trendUrl, { headers });

      if (!trendResponse.ok) {
        throw new Error('Satış trend verileri yüklenemedi.');
      }

      const trendData = await trendResponse.json();
      setSalesTrendData(trendData);

      // Kategorileri getir
      const categoriesResponse = await fetch(`${baseUrl}/categories`, { headers });

      if (!categoriesResponse.ok) {
        throw new Error('Kategoriler yüklenemedi.');
      }

      const categoriesData = await categoriesResponse.json();
      setCategories(categoriesData);

      // Özet verileri getir
      const summaryResponse = await fetch(`${baseUrl}/summary`, { headers });

      if (!summaryResponse.ok) {
        throw new Error('Özet veriler yüklenemedi.');
      }

      const summaryData = await summaryResponse.json();
      setSummary(summaryData);
    } catch (err) {
      console.error('API Hatası:', err);
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  // PDF raporu oluştur ve indir
  const generatePdfReport = async (reportType) => {
    try {
      // API için temel URL ve headers
      const baseUrl = window.wasmSettings.apiUrl || '/wp-json/wc-advanced-stock-manager/v1';
      const headers = {
        'X-WP-Nonce': window.wasmSettings.nonce || '',
        'Content-Type': 'application/json'
      };
      
      // Debug için API bilgilerini konsola yazdır
      console.log('API Bilgileri:', {
        baseUrl: baseUrl,
        endpoint: `${baseUrl}/generate-pdf`,
        nonce: headers['X-WP-Nonce'] ? 'Var (gizlendi)' : 'Yok',
        filtreler: filters
      });

      // İstek gövdesi
      const requestBody = {
        reportType: reportType,
        filters: filters
      };
      
      console.log('İstek gövdesi:', JSON.stringify(requestBody));

      // Rapor oluşturma isteği gönder
      const response = await fetch(`${baseUrl}/generate-pdf`, {
        method: 'POST',
        headers: headers,
        body: JSON.stringify(requestBody),
        credentials: 'same-origin' // Cookie ve kimlik bilgilerini gönder
      });

      // Hata yanıtını ele al
      if (!response.ok) {
        let errorMessage = `HTTP Hata: ${response.status} ${response.statusText}`;
        let errorDetails = '';
        
        try {
          // Hata yanıtını JSON olarak parse etmeyi dene
          const errorData = await response.json();
          errorDetails = JSON.stringify(errorData);
        } catch (parseError) {
          // JSON olarak parse edilemezse metin olarak al
          errorDetails = await response.text();
        }
        
        console.error('API Hata Detayları:', {
          status: response.status,
          statusText: response.statusText,
          details: errorDetails
        });
        
        throw new Error(`${errorMessage}. Detaylar: ${errorDetails}`);
      }

      // Başarılı yanıtı ele al
      let data;
      try {
        data = await response.json();
      } catch (jsonError) {
        console.error('API yanıtı JSON olarak parse edilemedi:', jsonError);
        throw new Error('API yanıtı geçersiz format.');
      }

      // Yanıt verilerini doğrula
      if (!data.success || !data.content) {
        console.error('Geçersiz API yanıtı:', data);
        throw new Error('API geçersiz yanıt döndürdü.');
      }

      // Base64 formatındaki PDF içeriğini çöz
      const binaryString = window.atob(data.content);
      const len = binaryString.length;
      const bytes = new Uint8Array(len);
      
      for (let i = 0; i < len; i++) {
        bytes[i] = binaryString.charCodeAt(i);
      }
      
      // PDF dosyasını oluştur
      const blob = new Blob([bytes], { type: 'application/pdf' });
      const url = window.URL.createObjectURL(blob);
      
      // Dosyayı indir
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', data.fileName || 'report.pdf');
      document.body.appendChild(link);
      link.click();
      
      // Temizleme işlemleri
      document.body.removeChild(link);
      window.URL.revokeObjectURL(url);
      
      return true;
    } catch (err) {
      console.error('PDF Rapor Hatası:', err);
      throw err;
    }
  };

  // Filtreleme mantığı - ayrı fonksiyon
  const filterProducts = (productsToFilter, currentFilters) => {
    let result = [];
    
    // Debug amaçlı sayaçlar
    let countVariableProducts = 0;
    let countVariations = 0;
    
    // Her ürünü dolaş ve varyasyonları ayrı ürünler olarak ekle
    productsToFilter.forEach(product => {
      // Ürünün türünü kontrol et
      const isVariable = product.productType === 'variable';
      
      if (isVariable) {
        countVariableProducts++;
      }
      
      // Ana ürünü ekle
      const productCopy = {...product};
      
      // Ana ürün için benzersiz sıralama kimliği
      productCopy.groupId = `${product.id}`;
      productCopy.sortOrder = 0; // Ana ürün her zaman ilk
      
      result.push(productCopy);
      
      // Varyasyonları kontrol et
      const variations = product.variations || [];
      
      // Eğer ürün varyasyonlu ve varyasyonlar varsa, her bir varyasyonu ekle
      if (isVariable && variations.length > 0) {
        variations.forEach((variation, index) => {
          countVariations++;
          
          // Varyasyonlar için yeni bir ürün nesnesi oluştur
          const variationProduct = {
            ...variation,
            id: `${product.id}-var-${index}`, // Benzersiz ID oluştur
            name: variation.title ? `${product.name} - ${variation.title}` : `${product.name} - Varyasyon ${index + 1}`,
            category: product.category,
            productType: 'variation', // Bu bir varyasyon
            isVariation: true, // Bu bir varyasyon olduğunu işaretle
            parentProductId: product.id, // Ana ürün ID'sini sakla
            parentProductName: product.name, // Ana ürün adını sakla
            stockStatus: variation.stockStatus || 'good',
            currentStock: variation.stock || 0,
            last3MonthsSales: variation.last3MonthsSales || 0,
            recommendedOrder: variation.recommendedOrder || 0,
            // Sıralama değerleri
            groupId: `${product.id}`,  // Ana ürünle aynı grup
            sortOrder: index + 1       // Ana üründen sonra
          };
          
          result.push(variationProduct);
        });
      }
    });
    
    // Debug amaçlı loglama
    console.log('Filtreleme İstatistikleri:', {
      toplamÜrün: productsToFilter.length,
      varyasyonluÜrün: countVariableProducts,
      toplamVaryasyon: countVariations,
      sonuçListeÖğesi: result.length
    });
    
    // Kategori filtreleme
    if (currentFilters.category && currentFilters.category !== 'all') {
      result = result.filter(product => product.category === currentFilters.category);
    }
    
    // Stok durumu filtreleme
    if (currentFilters.stockStatus && currentFilters.stockStatus !== 'all') {
      result = result.filter(product => product.stockStatus === currentFilters.stockStatus);
    }
    
    // Arama filtreleme
    if (currentFilters.search && currentFilters.search.trim() !== '') {
      const searchTerm = currentFilters.search.toLowerCase().trim();
      result = result.filter(product => 
        (product.name && product.name.toLowerCase().includes(searchTerm))
      );
    }
    
    // BASİTLEŞTİRİLMİŞ SIRALAMA MANTİĞİ
    result.sort((a, b) => {
      // 1. Farklı ürünleri sırala (ilk normal sıralama)
      if (a.groupId !== b.groupId) {
        // Farklı ürünlerse normal sıralama kriterlerine göre sırala
        const aValue = a[currentFilters.sortBy] || '';
        const bValue = b[currentFilters.sortBy] || '';
        
        // String değerleri için
        if (typeof aValue === 'string' && typeof bValue === 'string') {
          return currentFilters.sortOrder === 'asc' 
            ? aValue.localeCompare(bValue)
            : bValue.localeCompare(aValue);
        }
        
        // Sayısal değerler için
        return currentFilters.sortOrder === 'asc' 
          ? (aValue - bValue) 
          : (bValue - aValue);
      }
      
      // 2. Aynı ürünün ana ürün ve varyasyonlarını sırala
      return a.sortOrder - b.sortOrder; // Daima ana ürün önce, sonra varyasyonlar
    });
    
    setFilteredProducts(result);
  };

  // Filtreleri uygula
  const applyFilters = (newFilters) => {
    const updatedFilters = { ...filters, ...newFilters };
    setFilters(updatedFilters);

    // Eğer tarih aralığı değiştiyse, API'dan yeni veriler çek
    if (
      newFilters.dateRange &&
      (newFilters.dateRange.start !== filters.dateRange.start || 
      newFilters.dateRange.end !== filters.dateRange.end)
    ) {
      fetchData();
      return;
    }

    // Yerel filtreleme
    filterProducts(products, updatedFilters);
  };

  // Sıralama değiştir
  const handleSort = (field) => {
    const newSortOrder = field === filters.sortBy && filters.sortOrder === 'asc' ? 'desc' : 'asc';
    const newFilters = { ...filters, sortBy: field, sortOrder: newSortOrder };
    setFilters(newFilters);
    filterProducts(products, newFilters);
  };

  // Filtreleri temizle
  const resetFilters = () => {
    const defaultFilters = {
      dateRange: {
        start: new Date(new Date().setMonth(new Date().getMonth() - 3)).toISOString().split('T')[0],
        end: new Date().toISOString().split('T')[0]
      },
      category: 'all',
      stockStatus: 'all',
      search: '',
      sortBy: 'name',
      sortOrder: 'asc'
    };
    
    setFilters(defaultFilters);
    fetchData();
  };

  return (
    <div className="bg-gray-50 min-h-screen">
      {/* Üst Bar */}
      <div className="bg-white shadow-sm border-b border-gray-200">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
          <h1 className="text-2xl font-bold text-gray-800">
            {window.wasmSettings.translations.title || 'WooCommerce Gelişmiş Stok Yönetimi'}
          </h1>
          <p className="text-gray-600">
            {window.wasmSettings.translations.subtitle || 'Stok durumunuzu izleyin, satış trendlerini analiz edin ve sipariş planlaması yapın.'}
          </p>
        </div>
      </div>
      
      {/* Ana İçerik */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {loading ? (
          <LoadingSpinner message={window.wasmSettings.translations.loading || 'Yükleniyor...'} />
        ) : error ? (
          <ErrorMessage 
            message={error} 
            onRetry={fetchData} 
            retryLabel={window.wasmSettings.translations.tryAgain || 'Tekrar Dene'} 
          />
        ) : (
          <>
            {/* Özet Kartları */}
            <SummaryCards 
              summary={summary} 
              reorderCount={products.filter(p => p.recommendedOrder > 0).length} 
            />
            
            {/* Grafik ve Filtreler */}
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
              {/* Satış Trend Grafiği */}
              <div className="lg:col-span-2 bg-white shadow rounded-lg p-6">
                <SalesTrendChart data={salesTrendData} />
              </div>
              
              {/* Filtreler */}
              <FilterPanel 
                filters={filters} 
                applyFilters={applyFilters} 
                resetFilters={resetFilters} 
                categories={categories} 
              />
            </div>
            
            {/* Rapor Paneli */}
            <div className="mb-8">
              <ReportPanel 
                filters={filters}
                onGenerateReport={generatePdfReport}
              />
            </div>
            
            {/* Ürünler Tablosu */}
            <ProductTable 
              products={filteredProducts}
              filters={filters}
              onSort={handleSort}
            />
          </>
        )}
      </div>
    </div>
  );
};

export default App;