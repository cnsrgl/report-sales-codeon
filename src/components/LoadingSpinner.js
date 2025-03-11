import React from 'react';

const LoadingSpinner = ({ message }) => {
  return (
    <div className="flex flex-col items-center justify-center h-64 p-6 bg-white rounded-lg shadow">
      <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mb-4"></div>
      <p className="text-gray-600">{message}</p>
    </div>
  );
};

export default LoadingSpinner;