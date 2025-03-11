/**
 * Para birimini formatla
 * CHF ve fr-CH (İsviçre Frangı ve İsviçre Fransızcası) için özel destek içerir
 * 
 * @param {number} amount Formatlanacak para miktarı
 * @returns {string} Formatlanmış para miktarı
 */
export const formatCurrency = (amount) => {
    const currencyCode = wasmSettings.currencyCode || 'CHF';
    const currencySymbol = wasmSettings.currencySymbol || 'CHF';
    const locale = wasmSettings.locale || 'fr-CH';
    
    // Ondalık ayırıcıyı doğru belirle (İsviçre Fransızcası için virgül, diğerleri için nokta)
    const decimalSeparator = locale.startsWith('fr') ? ',' : '.';
    
    // Binlik ayırıcıyı doğru belirle (İsviçre için apostrof)
    const thousandSeparator = locale.includes('CH') ? "'" : ',';
    
    // Para miktarını formatlama
    const value = parseFloat(amount || 0).toFixed(2);
    
    // Sayıyı parçalara ayır
    const parts = value.split('.');
    
    // Binlik ayırıcıları ekle
    parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, thousandSeparator);
    
    // Ondalık kısmı ayırıcıyla birleştir
    const formattedValue = parts.join(decimalSeparator);
    
    // Para birimi sembolünü ekle
    if (locale.startsWith('fr') || locale.includes('CH')) {
      // İsviçre formatı para birimi sembolü sonda
      return `${currencySymbol} ${formattedValue}`;
    }
    
    // Diğer formatlar para birimi sembolü önde
    return `${currencySymbol}${formattedValue}`;
  };
  
  /**
   * Yüzdeyi formatla
   * 
   * @param {number} value Formatlanacak yüzde değeri
   * @param {number} decimals Gösterilecek ondalık basamak sayısı
   * @returns {string} Formatlanmış yüzde
   */
  export const formatPercent = (value, decimals = 1) => {
    return `${parseFloat(value || 0).toFixed(decimals)}%`;
  };
  
  /**
   * Tarihi formatla
   * 
   * @param {string} dateString Formatlanacak tarih
   * @returns {string} Formatlanmış tarih
   */
  export const formatDate = (dateString) => {
    const locale = wasmSettings.locale || 'tr-TR';
    
    try {
      const date = new Date(dateString);
      return date.toLocaleDateString(locale, {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      });
    } catch (e) {
      // Hata durumunda tarihi olduğu gibi döndür
      return dateString;
    }
  };