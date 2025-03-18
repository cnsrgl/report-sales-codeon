import React, { useState } from 'react';
import { FileText, Download, AlertCircle } from '../utils/lucide-polyfill';

const ReportPanel = ({ filters, onGenerateReport }) => {
  const [reportType, setReportType] = useState('summary');
  const [language, setLanguage] = useState('tr_TR'); // Varsayılan dil
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  
  const { translations } = window.wasmSettings || {
    translations: {
      reports: 'Raporlar',
      generateReport: 'Rapor Oluştur',
      reportType: 'Rapor Türü',
      summaryReport: 'Özet Rapor',
      productReport: 'Ürün Raporu',
      stockReport: 'Stok Raporu',
      salesReport: 'Satış Raporu',
      reportDesc: 'PDF formatında rapor oluşturmak için bir rapor türü seçin ve "Rapor Oluştur" düğmesine tıklayın.',
      generating: 'Rapor oluşturuluyor...',
      errorGenerating: 'Rapor oluşturulurken bir hata oluştu:',
      reportLanguage: 'Rapor Dili',
      turkish: 'Türkçe',
      french: 'Français'
    }
  };
  
  const handleGenerateReport = async () => {
    setLoading(true);
    setError(null);
    
    try {
      await onGenerateReport(reportType, language);
    } catch (err) {
      setError(err.message || 'Bilinmeyen bir hata oluştu.');
    } finally {
      setLoading(false);
    }
  };
  
  return (
    <div className="bg-white shadow rounded-lg p-6">
      <div className="flex items-center justify-between mb-4">
        <h2 className="text-lg font-semibold text-gray-800 flex items-center">
          <FileText className="h-5 w-5 mr-2 text-gray-500" />
          {translations.reports || 'Raporlar'}
        </h2>
      </div>
      
      <p className="text-gray-600 mb-4">
        {translations.reportDesc || 'PDF formatında rapor oluşturmak için bir rapor türü seçin ve "Rapor Oluştur" düğmesine tıklayın.'}
      </p>
      
      <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">
            {translations.reportType || 'Rapor Türü'}
          </label>
          <select
            value={reportType}
            onChange={(e) => setReportType(e.target.value)}
            className="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md"
            disabled={loading}
          >
            <option value="summary">{translations.summaryReport || 'Özet Rapor'}</option>
            <option value="products">{translations.productReport || 'Ürün Raporu'}</option>
            <option value="stock">{translations.stockReport || 'Stok Raporu'}</option>
            <option value="sales">{translations.salesReport || 'Satış Raporu'}</option>
          </select>
        </div>
        
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">
            {translations.reportLanguage || 'Rapor Dili'}
          </label>
          <select
            value={language}
            onChange={(e) => setLanguage(e.target.value)}
            className="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md"
            disabled={loading}
          >
            <option value="tr_TR">{translations.turkish || 'Türkçe'}</option>
            <option value="fr_FR">{translations.french || 'Français'}</option>
          </select>
        </div>
      </div>
      
      {error && (
        <div className="bg-red-50 border-l-4 border-red-400 p-4 mb-4">
          <div className="flex">
            <AlertCircle className="h-5 w-5 text-red-400 mr-2" />
            <p className="text-sm text-red-700">
              {translations.errorGenerating || 'Rapor oluşturulurken bir hata oluştu:'} {error}
            </p>
          </div>
        </div>
      )}
      
      <button
        onClick={handleGenerateReport}
        disabled={loading}
        className={`w-full flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white ${
          loading ? 'bg-blue-300' : 'bg-blue-600 hover:bg-blue-700'
        } focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500`}
      >
        {loading ? (
          <>
            <svg className="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
              <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            {translations.generating || 'Rapor oluşturuluyor...'}
          </>
        ) : (
          <>
            <Download className="h-5 w-5 mr-2" />
            {translations.generateReport || 'Rapor Oluştur'}
          </>
        )}
      </button>
    </div>
  );
};

export default ReportPanel;