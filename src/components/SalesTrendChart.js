import React from 'react';
// Recharts bileşenlerini doğrudan import ediyoruz
import {
  LineChart,
  Line,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  Legend,
  ResponsiveContainer
} from 'recharts';

const CustomTooltip = ({ active, payload, label }) => {
  if (active && payload && payload.length) {
    return (
      <div className="bg-white p-3 border border-gray-200 shadow-md rounded text-sm">
        <p className="font-medium mb-1">{label}</p>
        {payload.map((entry, index) => (
          <p key={`item-${index}`} style={{ color: entry.color }}>
            {entry.name}: {entry.value.toLocaleString()}
          </p>
        ))}
      </div>
    );
  }

  return null;
};

const SalesTrendChart = ({ data }) => {
  const { translations } = window.wasmSettings || {
    translations: {
      salesAndStockTrend: 'Satış ve Stok Trendi',
      totalSales: 'Toplam Satış',
      averageStock: 'Ortalama Stok'
    }
  };
  
  // Veri olmadığında mesaj göster
  if (!data || data.length === 0) {
    return (
      <div className="flex flex-col items-center justify-center h-80">
        <p className="text-gray-500">Grafik verisi bulunamadı</p>
      </div>
    );
  }
  
  return (
    <>
      <h2 className="text-lg font-semibold text-gray-800 mb-4">
        {translations.salesAndStockTrend || 'Satış ve Stok Trendi'}
      </h2>
      
      <div className="h-80">
        <ResponsiveContainer width="100%" height="100%">
          <LineChart
            data={data}
            margin={{ top: 10, right: 30, left: 20, bottom: 5 }}
          >
            <CartesianGrid strokeDasharray="3 3" stroke="#e5e7eb" />
            <XAxis 
              dataKey="month" 
              stroke="#6b7280"
              tick={{ fill: '#4b5563', fontSize: 12 }}
            />
            <YAxis 
              stroke="#6b7280"
              tick={{ fill: '#4b5563', fontSize: 12 }}
            />
            <Tooltip content={<CustomTooltip />} />
            <Legend 
              wrapperStyle={{ paddingTop: 10 }}
              iconType="circle"
            />
            <Line 
              type="monotone" 
              dataKey="totalSales" 
              name={translations.totalSales || 'Toplam Satış'} 
              stroke="#4f46e5" 
              strokeWidth={2}
              dot={{ r: 4 }}
              activeDot={{ r: 6, stroke: '#4338ca', strokeWidth: 2 }} 
            />
            <Line 
              type="monotone" 
              dataKey="averageStock" 
              name={translations.averageStock || 'Ortalama Stok'} 
              stroke="#ef4444" 
              strokeWidth={2}
              dot={{ r: 4 }}
              activeDot={{ r: 6, stroke: '#b91c1c', strokeWidth: 2 }}
            />
          </LineChart>
        </ResponsiveContainer>
      </div>
    </>
  );
};

export default SalesTrendChart;