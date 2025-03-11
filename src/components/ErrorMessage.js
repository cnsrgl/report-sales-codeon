import React from 'react';
import { AlertTriangle } from '../utils/lucide-polyfill';

const ErrorMessage = ({ message, onRetry, retryLabel }) => {
  return (
    <div className="flex flex-col items-center justify-center bg-red-50 rounded-lg p-8 shadow-sm">
      <AlertTriangle className="h-12 w-12 text-red-500 mb-4" />
      <h3 className="text-lg font-medium text-red-800 mb-2">Hata Oluştu</h3>
      <p className="text-red-600 mb-4">{message}</p>
      {onRetry && (
        <button 
          onClick={onRetry}
          className="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
        >
          {retryLabel || 'Tekrar Dene'}
        </button>
      )}
    </div>
  );
};

export default ErrorMessage;