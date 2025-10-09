import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import DateTime from '../../components/DateTime';
import AnimatedPopup from '../../components/AnimatedPopup';
import '../../styles/kiosk_login.css';
import '../../styles/kiosk_home_main.css';

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
  const navigate = useNavigate();
  const [showPopup, setShowPopup] = useState(false);

  return (
    <div className="app">
      <div className="sidebar">
        <div className="logo"><img src="/logoEarlybird.png" alt="EarlyBird Logo" /></div>
        <DateTime />
        <div className="location">Epitech Paris, France</div>
      </div>
      <div className="main" style={{ position: 'relative', display: 'flex', alignItems: 'center', justifyContent: 'center', minHeight: '100vh' }}>
        <button
          className="login-pin-keypad-button login-pin-back-button"
          style={{ position: 'absolute', top: 24, left: 0, zIndex: 2 }}
          onClick={() => navigate(-1)}
          aria-label="Back"
        >
          ←
        </button>
        <div style={{ width: 280, display: 'flex', flexDirection: 'column', alignItems: 'center', gap: 24 }}>
          <div style={{ textAlign: 'center', marginBottom: 40 }}>
            <div className="home-main-title">Clock in to</div>
            <div className="home-main-subtitle">Westside Branch</div>
          </div>
          <button className="home-action-btn" onClick={() => setShowPopup(true)}>
            <span style={{ fontSize: '2rem', display: 'flex', alignItems: 'center' }}>🕒</span>
            Clock in
          </button>
          <button className="home-action-btn orange">
            <span style={{ fontSize: '2rem', display: 'flex', alignItems: 'center' }}>☕</span>
            Start break
          </button>
        </div>
        <AnimatedPopup
          open={showPopup}
          onClose={() => setShowPopup(false)}
          icon={<span style={{ fontSize: '2.5em' }}>✔️</span>}
          title="Clocked in!"
          subtitle="Epitech Paris, France"
          countdown={3}
          countdownText="This screen will close in"
          background="#8fd16a"
        />
      </div>
    </div>
  );
};

export { users };
export default HomePage;
