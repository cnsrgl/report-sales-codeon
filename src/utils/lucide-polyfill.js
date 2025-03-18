import React, { forwardRef, createElement } from 'react';

/**
 * Basit bir Lucide ikon polyfilll'i
 * Bu, Lucide ikonlarını kullanım için hazırlar
 */

// Temel SVG özellikleri
const defaultAttributes = {
  xmlns: 'http://www.w3.org/2000/svg',
  width: 24,
  height: 24,
  viewBox: '0 0 24 24',
  fill: 'none',
  stroke: 'currentColor',
  strokeWidth: 2,
  strokeLinecap: 'round',
  strokeLinejoin: 'round',
};

// Basit bir Lucide icon oluşturucu
export const createLucideIcon = (iconName, iconNode) => {
  const Component = forwardRef(({ color = 'currentColor', size = 24, strokeWidth = 2, children, ...rest }, ref) => {
    return createElement(
      'svg',
      {
        ...defaultAttributes,
        width: size,
        height: size,
        stroke: color,
        strokeWidth,
        ref,
        ...rest,
      },
      [
        ...iconNode,
        ...(children ? [children] : []),
      ]
    );
  });
  
  Component.displayName = `${iconName}`;
  
  return Component;
};

// İhtiyaç duyulan ikonları oluştur
export const Package = createLucideIcon('Package', [
  createElement('path', { d: 'M16.5 9.4l-9-5.19M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z', key: 'svg-0' }),
  createElement('path', { d: 'M3.27 6.96L12 12.01l8.73-5.05', key: 'svg-1' }),
  createElement('path', { d: 'M12 22.08V12', key: 'svg-2' }),
]);

export const Calendar = createLucideIcon('Calendar', [
  createElement('rect', { x: '3', y: '4', width: '18', height: '18', rx: '2', ry: '2', key: 'svg-0' }),
  createElement('line', { x1: '16', y1: '2', x2: '16', y2: '6', key: 'svg-1' }),
  createElement('line', { x1: '8', y1: '2', x2: '8', y2: '6', key: 'svg-2' }),
  createElement('line', { x1: '3', y1: '10', x2: '21', y2: '10', key: 'svg-3' }),
]);

export const AlertTriangle = createLucideIcon('AlertTriangle', [
  createElement('path', { d: 'M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z', key: 'svg-0' }),
  createElement('line', { x1: '12', y1: '9', x2: '12', y2: '13', key: 'svg-1' }),
  createElement('line', { x1: '12', y1: '17', x2: '12.01', y2: '17', key: 'svg-2' }),
]);

export const RefreshCw = createLucideIcon('RefreshCw', [
  createElement('path', { d: 'M23 4v6h-6', key: 'svg-0' }),
  createElement('path', { d: 'M1 20v-6h6', key: 'svg-1' }),
  createElement('path', { d: 'M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15', key: 'svg-2' }),
]);

export const TrendingUp = createLucideIcon('TrendingUp', [
  createElement('path', { d: 'M22 7l-10 10-4-4-6 6', key: 'svg-0' }),
  createElement('path', { d: 'M16 7h6v6', key: 'svg-1' }),
]);

export const Search = createLucideIcon('Search', [
  createElement('circle', { cx: '11', cy: '11', r: '8', key: 'svg-0' }),
  createElement('line', { x1: '21', y1: '21', x2: '16.65', y2: '16.65', key: 'svg-1' }),
]);

export const FileText = createLucideIcon('FileText', [
  createElement('path', { d: 'M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z', key: 'svg-0' }),
  createElement('polyline', { points: '14 2 14 8 20 8', key: 'svg-1' }),
  createElement('line', { x1: '16', y1: '13', x2: '8', y2: '13', key: 'svg-2' }),
  createElement('line', { x1: '16', y1: '17', x2: '8', y2: '17', key: 'svg-3' }),
  createElement('polyline', { points: '10 9 9 9 8 9', key: 'svg-4' }),
]);

export const Download = createLucideIcon('Download', [
  createElement('path', { d: 'M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4', key: 'svg-0' }),
  createElement('polyline', { points: '7 10 12 15 17 10', key: 'svg-1' }),
  createElement('line', { x1: '12', y1: '15', x2: '12', y2: '3', key: 'svg-2' }),
]);

export const AlertCircle = createLucideIcon('AlertCircle', [
  createElement('circle', { cx: '12', cy: '12', r: '10', key: 'svg-0' }),
  createElement('line', { x1: '12', y1: '8', x2: '12', y2: '12', key: 'svg-1' }),
  createElement('line', { x1: '12', y1: '16', x2: '12.01', y2: '16', key: 'svg-2' }),
]);

export const ChevronDown = createLucideIcon('ChevronDown', [
  createElement('polyline', { points: '6 9 12 15 18 9', key: 'svg-0' }),
]);

export const ChevronRight = createLucideIcon('ChevronRight', [
  createElement('polyline', { points: '9 18 15 12 9 6', key: 'svg-0' }),
]);

export const Globe = createLucideIcon('Globe', [
  createElement('circle', { cx: '12', cy: '12', r: '10', key: 'svg-0' }),
  createElement('line', { x1: '2', y1: '12', x2: '22', y2: '12', key: 'svg-1' }),
  createElement('path', { d: 'M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z', key: 'svg-2' }),
]);

// Bütün ikonları dışa aktar
export const lucideIcons = {
  Package,
  Calendar,
  AlertTriangle,
  RefreshCw,
  TrendingUp,
  Search,
  FileText,
  Download,
  AlertCircle,
  ChevronDown,
  ChevronRight,
  Globe
};