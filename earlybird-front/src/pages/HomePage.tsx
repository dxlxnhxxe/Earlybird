import React from 'react';
import DateTime from '../components/DateTime';
import { useNavigate } from 'react-router-dom';
import '../styles/kiosk_home.sass';

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

  return (
    <div className="app">
      <div className="sidebar">
        <div className="logo"><img src="/logoEarlybird.png" alt="EarlyBird Logo" /></div>
        <DateTime />
        <div className="location">Epitech Paris, France</div>
      </div>
      <div className="main">
        <div className="search-bar">
          <input type="text" placeholder="Search" />
          <span className="search-icon" aria-hidden="true">
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
              <circle cx="9" cy="9" r="7" stroke="#b0b3b8" strokeWidth="2" />
              <line x1="14.4142" y1="14" x2="18" y2="17.5858" stroke="#b0b3b8" strokeWidth="2" strokeLinecap="round" />
            </svg>
          </span>
        </div>
        <div className="user-list">
          {users.map((user) => (
            <div key={user.id} className="user-item" style={{ cursor: 'pointer' }} onClick={() => navigate(`/kiosk/login?userId=${user.id}`)}>
                <img
                  src={`https://api.dicebear.com/9.x/thumbs/svg?seed=${encodeURIComponent(user.name)}&scale=80&backgroundColor=transparent`}
                  alt={user.name}
                  className="avatar"
                />
              <div className="user-info">
                <div className="user-name">
                  {user.name} {user.role && <span className="user-role">({user.role})</span>}
                </div>
              </div>
              <div className={`status-indicator ${user.status}`}></div>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
};

export { users };
export default HomePage;
