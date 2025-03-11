import React from 'react';
// Lucide ikonlarını polyfill'den import et
import { Package, AlertTriangle, RefreshCw, TrendingUp } from '../utils/lucide-polyfill';
import { formatCurrency } from '../utils/formatters';

const SummaryCards = ({ summary, reorderCount }) => {
  const { translations } = window.wasmSettings || {
    translations: {
      totalProducts: 'Toplam Ürün',
      lowStock: 'Düşük Stok',
      reorderNeeded: 'Sipariş Edilecek',
      stockValue: 'Stok Değeri'
    }
  };
  
  return (
    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
      <div className="bg-white shadow rounded-lg p-6">
        <div className="flex items-center">
          <div className="p-3 rounded-full bg-blue-100 text-blue-600">
            <Package className="h-6 w-6" />
          </div>
          <div className="ml-4">
            <p className="text-sm font-medium text-gray-500">
              {translations.totalProducts || 'Toplam Ürün'}
            </p>
            <p className="text-2xl font-semibold text-gray-900">
              {summary.totalProducts}
            </p>
          </div>
        </div>
      </div>
      
      <div className="bg-white shadow rounded-lg p-6">
        <div className="flex items-center">
          <div className="p-3 rounded-full bg-yellow-100 text-yellow-600">
            <AlertTriangle className="h-6 w-6" />
          </div>
          <div className="ml-4">
            <p className="text-sm font-medium text-gray-500">
              {translations.lowStock || 'Düşük Stok'}
            </p>
            <p className="text-2xl font-semibold text-gray-900">
              {summary.lowStockCount}
            </p>
          </div>
        </div>
      </div>
      
      <div className="bg-white shadow rounded-lg p-6">
        <div className="flex items-center">
          <div className="p-3 rounded-full bg-green-100 text-green-600">
            <RefreshCw className="h-6 w-6" />
          </div>
          <div className="ml-4">
            <p className="text-sm font-medium text-gray-500">
              {translations.reorderNeeded || 'Sipariş Edilecek'}
            </p>
            <p className="text-2xl font-semibold text-gray-900">
              {reorderCount}
            </p>
          </div>
        </div>
      </div>
      
      <div className="bg-white shadow rounded-lg p-6">
        <div className="flex items-center">
          <div className="p-3 rounded-full bg-purple-100 text-purple-600">
            <TrendingUp className="h-6 w-6" />
          </div>
          <div className="ml-4">
            <p className="text-sm font-medium text-gray-500">
              {translations.stockValue || 'Stok Değeri'}
            </p>
            <p className="text-2xl font-semibold text-gray-900">
              {formatCurrency(summary.totalStockValue)}
            </p>
          </div>
        </div>
      </div>
    </div>
  );
};

export default SummaryCards;