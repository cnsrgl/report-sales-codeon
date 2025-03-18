import React, { useState } from 'react';
import { FileText, Download, AlertCircle, Check, ChevronDown } from '../utils/lucide-polyfill';

const ReportPanel = ({ filters, onGenerateReport }) => {
  const [reportType, setReportType] = useState('summary');
  const [isGenerating, setIsGenerating] = useState(false);
  const [error, setError] = useState(null);
  const [success, setSuccess] = useState(false);
  const [isOpen, setIsOpen] = useState(false);
  
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
      reportSuccess: 'Rapor başarıyla oluşturuldu',
      downloadAgain: 'Tekrar indir'
    }
  };
  
  const reportTypes = [
    { id: 'summary', name: translations.summaryReport || 'Özet Rapor', 
      description: 'Tüm stok verilerinin özeti, satış trendi ve kategori analizini içerir.' },
    { id: 'products', name: translations.productReport || 'Ürün Raporu', 
      description: 'Tüm ürünlerin detaylı stok ve satış verilerini listeler.' },
    { id: 'stock', name: translations.stockReport || 'Stok Raporu', 
      description: 'Düşük stoklu ve sipariş edilmesi gereken ürünleri listeler.' },
    { id: 'sales', name: translations.salesReport || 'Satış Raporu', 
      description: 'Aylık satış trendi ve stok devir hızı analizini gösterir.' }
  ];
  
  const handleGenerateReport = async () => {
    setIsGenerating(true);
    setError(null);
    setSuccess(false);
    
    try {
      await onGenerateReport(reportType);
      setSuccess(true);
    } catch (err) {
      setError(err.message || 'Bilinmeyen bir hata oluştu.');
    } finally {
      setIsGenerating(false);
    }
  };
  
  return (
    <div className="bg-white shadow rounded-lg overflow-hidden">
      {/* Panel başlığı */}
      <div 
        className="px-6 py-4 border-b border-gray-200 flex items-center justify-between cursor-pointer"
        onClick={() => setIsOpen(!isOpen)}
      >
        <div className="flex items-center">
          <FileText className="h-5 w-5 text-indigo-600 mr-2" />
          <h2 className="text-lg font-semibold text-gray-800">
            {translations.reports || 'Raporlar'}
          </h2>
        </div>
        <ChevronDown 
          className={`h-5 w-5 text-gray-500 transform transition-transform ${isOpen ? 'rotate-180' : ''}`} 
        />
      </div>
      
      {/* Panel içeriği */}
      {isOpen && (
        <div className="p-6">
          <p className="text-gray-600 mb-6">
            {translations.reportDesc || 'PDF formatında rapor oluşturmak için bir rapor türü seçin ve "Rapor Oluştur" düğmesine tıklayın.'}
          </p>
          
          {/* Rapor tipleri */}
          <div className="space-y-3 mb-6">
            {reportTypes.map(type => (
              <div 
                key={type.id}
                className={`border rounded-lg p-4 cursor-pointer transition-colors ${
                  reportType === type.id 
                    ? 'border-indigo-500 bg-indigo-50' 
                    : 'border-gray-200 hover:border-indigo-300 hover:bg-indigo-50/50'
                }`}
                onClick={() => setReportType(type.id)}
              >
                <div className="flex items-center">
                  <div className={`w-5 h-5 rounded-full border flex items-center justify-center mr-3 ${
                    reportType === type.id 
                      ? 'border-indigo-500 bg-indigo-500' 
                      : 'border-gray-300'
                  }`}>
                    {reportType === type.id && <Check className="h-3 w-3 text-white" />}
                  </div>
                  <div>
                    <h3 className={`font-medium ${reportType === type.id ? 'text-indigo-700' : 'text-gray-700'}`}>
                      {type.name}
                    </h3>
                    <p className="text-sm text-gray-500 mt-1">{type.description}</p>
                  </div>
                </div>
              </div>
            ))}
          </div>
          
          {/* Hata mesajı */}
          {error && (
            <div className="bg-red-50 border-l-4 border-red-400 p-4 mb-6">
              <div className="flex">
                <AlertCircle className="h-5 w-5 text-red-400 mr-2" />
                <p className="text-sm text-red-700">
                  {translations.errorGenerating || 'Rapor oluşturulurken bir hata oluştu:'} {error}
                </p>
              </div>
            </div>
          )}
          
          {/* Başarı mesajı */}
          {success && !error && (
            <div className="bg-green-50 border-l-4 border-green-400 p-4 mb-6">
              <div className="flex items-center">
                <Check className="h-5 w-5 text-green-500 mr-2" />
                <p className="text-sm text-green-700">
                  {translations.reportSuccess || 'Rapor başarıyla oluşturuldu'}
                </p>
              </div>
            </div>
          )}
          
          {/* Rapor oluşturma butonu */}
          <button
            onClick={handleGenerateReport}
            disabled={isGenerating}
            className={`w-full flex justify-center items-center px-4 py-3 border border-transparent text-sm font-medium rounded-md text-white ${
              isGenerating 
                ? 'bg-indigo-400 cursor-not-allowed' 
                : 'bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500'
            }`}
          >
            {isGenerating ? (
              <>
                <svg className="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                  <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                {translations.generating || 'Rapor oluşturuluyor...'}
              </>
            ) : success ? (
              <>
                <Download className="h-5 w-5 mr-2" />
                {translations.downloadAgain || 'Tekrar indir'}
              </>
            ) : (
              <>
                <Download className="h-5 w-5 mr-2" />
                {translations.generateReport || 'Rapor Oluştur'}
              </>
            )}
          </button>
        </div>
      )}
    </div>
  );
};

export default ReportPanel;