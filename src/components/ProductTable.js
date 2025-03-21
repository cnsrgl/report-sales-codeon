import React from 'react';

const ProductTable = ({ products, filters, onSort }) => {
  const { translations } = window.wasmSettings || {
    translations: {
      productStockList: 'Ürün Stok Listesi',
      showingProducts: 'ürün gösteriliyor',
      productName: 'Ürün Adı',
      currentStock: 'Mevcut Stok',
      sales3Months: 'Son 3 Ay Satış',
      stockStatus: 'Stok Durumu',
      recommendedOrder: 'Önerilen Sipariş',
      category: 'Kategori',
      noProductsFound: 'Filtrelere uygun ürün bulunamadı.',
      critical: 'Kritik',
      low: 'Düşük',
      good: 'İyi',
      variations: 'Varyasyonlar',
      variable: 'Değişkenli',
      simple: 'Basit',
      variation: 'Varyasyon',
      total: 'Toplam'
    }
  };
  
  // Stok durumu renklerini belirle
  const getStockStatusColor = (status) => {
    switch (status) {
      case 'critical':
        return {
          bg: 'bg-red-100',
          text: 'text-red-800',
          textColor: 'text-red-600'
        };
      case 'low':
        return {
          bg: 'bg-yellow-100',
          text: 'text-yellow-800',
          textColor: 'text-yellow-600'
        };
      default:
        return {
          bg: 'bg-green-100',
          text: 'text-green-800',
          textColor: 'text-gray-900'
        };
    }
  };
  
  // Stok durumu ekran adını belirle
  const getStockStatusLabel = (status) => {
    switch (status) {
      case 'critical':
        return translations.critical || 'Kritik';
      case 'low':
        return translations.low || 'Düşük';
      default:
        return translations.good || 'İyi';
    }
  };
  
  // Ürün türü etiketini belirle
  const getProductTypeLabel = (type) => {
    switch (type) {
      case 'variable':
        return translations.variable || 'Değişkenli';
      default:
        return translations.simple || 'Basit';
    }
  };
  
  return (
    <div className="bg-white shadow rounded-lg overflow-hidden">
      <div className="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
        <h2 className="text-lg font-semibold text-gray-800">
          {translations.productStockList || 'Ürün Stok Listesi'}
        </h2>
        <p className="text-sm text-gray-600">
          {products.length} {translations.showingProducts || 'ürün gösteriliyor'}
        </p>
      </div>
      
      <div className="overflow-x-auto">
        <table className="min-w-full divide-y divide-gray-200">
          <thead className="bg-gray-50">
            <tr>
              <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer w-72" onClick={() => onSort('name')}>
                <div className="flex items-center">
                  {translations.productName || 'Ürün Adı'}
                  {filters.sortBy === 'name' && (
                    <span className="ml-1">{filters.sortOrder === 'asc' ? '↑' : '↓'}</span>
                  )}
                </div>
              </th>
              <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer w-28" onClick={() => onSort('currentStock')}>
                <div className="flex items-center">
                  {translations.currentStock || 'Mevcut Stok'}
                  {filters.sortBy === 'currentStock' && (
                    <span className="ml-1">{filters.sortOrder === 'asc' ? '↑' : '↓'}</span>
                  )}
                </div>
              </th>
              <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer w-28" onClick={() => onSort('last3MonthsSales')}>
                <div className="flex items-center">
                  {translations.sales3Months || 'Son 3 Ay Satış'}
                  {filters.sortBy === 'last3MonthsSales' && (
                    <span className="ml-1">{filters.sortOrder === 'asc' ? '↑' : '↓'}</span>
                  )}
                </div>
              </th>
              <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-28">
                {translations.stockStatus || 'Stok Durumu'}
              </th>
              <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer w-28" onClick={() => onSort('recommendedOrder')}>
                <div className="flex items-center">
                  {translations.recommendedOrder || 'Önerilen Sipariş'}
                  {filters.sortBy === 'recommendedOrder' && (
                    <span className="ml-1">{filters.sortOrder === 'asc' ? '↑' : '↓'}</span>
                  )}
                </div>
              </th>
              <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-28">
                {translations.category || 'Kategori'}
              </th>
            </tr>
          </thead>
          <tbody className="bg-white divide-y divide-gray-200">
            {products.length === 0 ? (
              <tr>
                <td colSpan="6" className="px-6 py-12 text-center text-gray-500">
                  {translations.noProductsFound || 'Filtrelere uygun ürün bulunamadı.'}
                </td>
              </tr>
            ) : (
              products.map((product) => {
                const statusColors = getStockStatusColor(product.stockStatus);
                const isVariable = product.productType === 'variable';
                const isVariation = product.isVariation === true;
                
                return (
                  <tr key={product.id} className={isVariation ? 'bg-gray-50' : ''}>
                    <td className="px-6 py-4 whitespace-normal">
                      <div className="flex items-center">
                        <div className={`text-sm font-medium text-gray-900 truncate max-w-xs ${isVariation ? 'pl-4' : ''}`}>
                          {isVariation ? (
                            // Varyasyon gösterimi
                            <div className="flex items-center">
                              <span className="inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-200 text-gray-800 px-2 mr-2">
                                {translations.variation || 'Varyasyon'}
                              </span>
                              <span title={product.name}>{product.name}</span>
                            </div>
                          ) : (
                            // Ana ürün gösterimi
                            <>
                              <a 
                                href={`${window.wasmSettings.siteUrl}/wp-admin/post.php?post=${product.id}&action=edit`} 
                                className="hover:text-blue-600 hover:underline"
                                title={product.name}
                              >
                                {product.name}
                              </a>
                              
                              {isVariable && (
                                <span className="ml-2 px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 text-purple-800">
                                  {getProductTypeLabel('variable')}
                                </span>
                              )}
                              
                              {!isVariable && (
                                <span className="ml-2 px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                  {getProductTypeLabel('simple')}
                                </span>
                              )}
                            </>
                          )}
                          
                          {/* Varyasyon niteliklerini göster */}
                          {isVariation && product.attributes && product.attributes.length > 0 && (
                            <div className="mt-1 text-xs text-gray-500">
                              {product.attributes.join(', ')}
                            </div>
                          )}
                        </div>
                      </div>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <div className={`text-sm font-medium ${statusColors.textColor}`}>
                        {product.currentStock}
                        
                        {isVariable && !isVariation && (
                          <span className="ml-1 text-xs text-gray-500">
                            ({translations.total || 'Toplam'})
                          </span>
                        )}
                      </div>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <div className="text-sm text-gray-500">{product.last3MonthsSales}</div>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${statusColors.bg} ${statusColors.text}`}>
                        {getStockStatusLabel(product.stockStatus)}
                      </span>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      {product.recommendedOrder > 0 ? (
                        <div className="text-sm font-medium text-blue-600">{product.recommendedOrder}</div>
                      ) : (
                        <div className="text-sm text-gray-500">-</div>
                      )}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                      {product.category || 'Kategorisiz'}
                    </td>
                  </tr>
                );
              })
            )}
          </tbody>
        </table>
      </div>
    </div>
  );
};

export default ProductTable;