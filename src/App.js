import React, { useState, useEffect } from 'react';
import FilterPanel from './components/FilterPanel';
import ProductTable from './components/ProductTable';
import SummaryCards from './components/SummaryCards';
import SalesTrendChart from './components/SalesTrendChart';
import LoadingSpinner from './components/LoadingSpinner';
import ErrorMessage from './components/ErrorMessage';
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

  // Filtreleme mantığı - ayrı fonksiyon
  const filterProducts = (productsToFilter, currentFilters) => {
    let result = [...productsToFilter];
    
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
        (product.name && product.name.toLowerCase().includes(searchTerm)) || 
        (product.sku && product.sku.toLowerCase().includes(searchTerm))
      );
    }
    
    // Sıralama
    result.sort((a, b) => {
      const aValue = a[currentFilters.sortBy] || '';
      const bValue = b[currentFilters.sortBy] || '';
      
      // String değerleri için
      if (typeof aValue === 'string' && typeof bValue === 'string') {
        return currentFilters.sortOrder === 'asc' 
          ? aValue.localeCompare(bValue)
          : bValue.localeCompare(aValue);
      }
      
      // Sayısal değerler için
      if (aValue < bValue) {
        return currentFilters.sortOrder === 'asc' ? -1 : 1;
      }
      if (aValue > bValue) {
        return currentFilters.sortOrder === 'asc' ? 1 : -1;
      }
      return 0;
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