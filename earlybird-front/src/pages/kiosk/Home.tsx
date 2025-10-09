import React from 'react';
import DateTime from '../../components/DateTime';
import '../../styles/kiosk_index.sass';

const users = [
  { id: 'leonie-raymonde', name: 'Leonie Raymonde', role: 'admin', status: 'available' },
  { id: 'alisha-walker', name: 'Alisha Walker', role: 'manager', status: 'available' },
  { id: 'frank-garcia', name: 'Frank García', role: 'admin', status: 'available' },
  { id: 'niv-burkhard', name: 'Niv Burkhard', status: 'unavailable' },
  { id: 'erica-zhao', name: 'Erica Zhao', role: 'manager', status: 'away' },
  { id: 'keith-anderson', name: 'Keith Anderson', role: 'manager', status: 'away' },
  { id: 'olivia-johnson', name: 'Olivia Johnson', status: 'unavailable' },
];


const HomePage: React.FC = () => {

  return (
    <div className="app">
      <div className="sidebar">
        <div className="logo"><img src="/logoEarlybird.png" alt="EarlyBird Logo" /></div>
        <DateTime />
        <div className="location">Epitech Paris, France</div>
      </div>
      <div className="main">
        <h1>Welcome to EarlyBird Kiosk</h1>
      </div>
    </div>
  );
};

export { users };
export default HomePage;
