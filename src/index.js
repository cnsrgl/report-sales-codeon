import React from 'react';
import ReactDOM from 'react-dom';
import App from './App';
import './styles.css';

// Global olarak wasmSettings'in varlığından emin olalım
window.wasmSettings = window.wasmSettings || {
  translations: {},
  apiUrl: '/wp-json/wc-advanced-stock-manager/v1',
  nonce: '',
  currencySymbol: 'CHF',
  currencyCode: 'CHF',
  locale: 'tr',
  siteUrl: '/',
  settings: {
    reorder_threshold: 1.5,
    stock_period: 2
  }
};

document.addEventListener('DOMContentLoaded', function() {
  const appContainer = document.getElementById('wasm-app');
  if (appContainer) {
    ReactDOM.render(<App />, appContainer);
  }
});