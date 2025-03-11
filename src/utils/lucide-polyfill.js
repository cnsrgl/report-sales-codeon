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

// Bütün ikonları dışa aktar
export const lucideIcons = {
  Package,
  Calendar,
  AlertTriangle,
  RefreshCw,
  TrendingUp,
  Search
};