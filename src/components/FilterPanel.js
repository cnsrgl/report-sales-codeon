import React from 'react';
import { Calendar, Search } from '../utils/lucide-polyfill';
// Recharts bileşenlerini doğrudan import et
import {
  BarChart,
  Bar,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  ResponsiveContainer
} from 'recharts';

const FilterPanel = ({ filters, applyFilters, resetFilters, categories }) => {
  const { translations } = window.wasmSettings || {
    translations: {
      filters: 'Filtreler',
      clearFilters: 'Filtreleri Temizle',
      dateRange: 'Tarih Aralığı',
      category: 'Kategori',
      allCategories: 'Tüm Kategoriler',
      stockStatus: 'Stok Durumu',
      all: 'Tümü',
      low: 'Düşük',
      criticalStock: 'Kritik',
      search: 'Arama',
      searchPlaceholder: 'Ürün adı veya SKU ile ara...',
      categoryDistribution: 'Kategori Dağılımı'
    }
  };
  
  // Kategori chart verisi oluştur - kategorileri isme göre sırala
  const categoryChartData = categories
    .map(category => ({
      name: category.name,
      count: category.count
    }))
    .sort((a, b) => b.count - a.count); // Sayıya göre azalan sıralama
  
  // Arama özelliği - arama inputunu değiştirince
  const handleSearchChange = (e) => {
    const value = e.target.value;
    applyFilters({ search: value });
  };
  
  // Enter tuşuna basınca filtreyi uygula
  const handleSearchKeyDown = (e) => {
    if (e.key === 'Enter') {
      applyFilters({ search: e.target.value });
    }
  };
  
  return (
    <div className="bg-white shadow rounded-lg p-6">
      <div className="flex justify-between items-center mb-4">
        <h2 className="text-lg font-semibold text-gray-800">
          {translations.filters || 'Filtreler'}
        </h2>
        <button 
          className="text-sm text-blue-600 hover:text-blue-800"
          onClick={resetFilters}
        >
          {translations.clearFilters || 'Filtreleri Temizle'}
        </button>
      </div>
      
      {/* Tarih Aralığı */}
      <div className="mb-4">
        <label className="block text-sm font-medium text-gray-700 mb-1">
          {translations.dateRange || 'Tarih Aralığı'}
        </label>
        <div className="grid grid-cols-2 gap-4">
          <div className="relative">
            <Calendar className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-5 w-5" />
            <input
              type="date"
              value={filters.dateRange.start}
              onChange={(e) => applyFilters({ dateRange: { ...filters.dateRange, start: e.target.value } })}
              className="pl-10 pr-4 py-2 border border-gray-300 rounded-md w-full focus:ring-blue-500 focus:border-blue-500"
            />
          </div>
          <div className="relative">
            <Calendar className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-5 w-5" />
            <input
              type="date"
              value={filters.dateRange.end}
              onChange={(e) => applyFilters({ dateRange: { ...filters.dateRange, end: e.target.value } })}
              className="pl-10 pr-4 py-2 border border-gray-300 rounded-md w-full focus:ring-blue-500 focus:border-blue-500"
            />
          </div>
        </div>
      </div>
      
      {/* Kategori Filtresi */}
      <div className="mb-4">
        <label className="block text-sm font-medium text-gray-700 mb-1">
          {translations.category || 'Kategori'}
        </label>
        <select
          value={filters.category}
          onChange={(e) => applyFilters({ category: e.target.value })}
          className="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md"
        >
          <option value="all">{translations.allCategories || 'Tüm Kategoriler'}</option>
          {categories.map((category) => (
            <option key={category.id} value={category.name}>{category.name}</option>
          ))}
        </select>
      </div>
      
      {/* Stok Durumu Filtresi */}
      <div className="mb-4">
        <label className="block text-sm font-medium text-gray-700 mb-1">
          {translations.stockStatus || 'Stok Durumu'}
        </label>
        <select
          value={filters.stockStatus}
          onChange={(e) => applyFilters({ stockStatus: e.target.value })}
          className="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md"
        >
          <option value="all">{translations.all || 'Tümü'}</option>
          <option value="low">{translations.low || 'Düşük'}</option>
          <option value="critical">{translations.criticalStock || 'Kritik'}</option>
        </select>
      </div>
      
      {/* Arama */}
      <div className="mb-4">
        <label className="block text-sm font-medium text-gray-700 mb-1">
          {translations.search || 'Arama'}
        </label>
        <div className="relative">
          <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-5 w-5" />
          <input
            type="text"
            placeholder={translations.searchPlaceholder || 'Ürün adı veya SKU ile ara...'}
            value={filters.search}
            onChange={handleSearchChange}
            onKeyDown={handleSearchKeyDown}
            className="pl-10 pr-4 py-2 border border-gray-300 rounded-md w-full focus:ring-blue-500 focus:border-blue-500"
          />
        </div>
      </div>
      
      {/* Kategori Dağılımı */}
      {categories.length > 0 && (
        <div>
          <h3 className="text-sm font-medium text-gray-700 mb-2">
            {translations.categoryDistribution || 'Kategori Dağılımı'}
          </h3>
          <div className="h-40">
            <ResponsiveContainer width="100%" height="100%">
              <BarChart
                data={categoryChartData.slice(0, 5)} // Sadece ilk 5 kategoriyi göster
                margin={{ top: 5, right: 0, left: 0, bottom: 20 }}
              >
                <CartesianGrid strokeDasharray="3 3" stroke="#e5e7eb" />
                <XAxis 
                  dataKey="name" 
                  tick={{ fontSize: 10 }}
                  angle={-45}
                  textAnchor="end"
                  height={50}
                />
                <YAxis tick={{ fontSize: 10 }} />
                <Tooltip 
                  formatter={(value) => [value, 'Ürün Sayısı']}
                  labelFormatter={(value) => `Kategori: ${value}`}
                />
                <Bar dataKey="count" fill="#4f46e5" radius={[4, 4, 0, 0]} />
              </BarChart>
            </ResponsiveContainer>
          </div>
        </div>
      )}
    </div>
  );
};

export default FilterPanel;