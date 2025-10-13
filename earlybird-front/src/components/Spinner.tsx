import React from 'react';

const Spinner: React.FC<{ size?: number }> = ({ size = 48 }) => (
  <div style={{ width: size + 16, height: size + 16, display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
    <div style={{
      border: `${size / 6}px solid #f3f3f3`,
      borderTop: `${size / 6}px solid #3498db`,
      borderRadius: '50%',
      width: size,
      height: size,
      animation: 'spin 1s linear infinite'
    }} />
    <style>{`
      @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
      }
    `}</style>
  </div>
);

export default Spinner;
