import React from 'react';

const users = [
  { id: 'leonie-raymonde', name: 'Leonie Raymonde', role: 'admin', status: 'available' },
  { id: 'alisha-walker', name: 'Alisha Walker', role: 'manager', status: 'available' },
  { id: 'frank-garcia', name: 'Frank García', role: 'admin', status: 'available' },
  { id: 'niv-burkhard', name: 'Niv Burkhard', status: 'unavailable' },
  { id: 'erica-zhao', name: 'Erica Zhao', role: 'manager', status: 'away' },
  { id: 'keith-anderson', name: 'Keith Anderson', role: 'manager', status: 'away' },
  { id: 'olivia-johnson', name: 'Olivia Johnson', status: 'unavailable' },
];

const days = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];

const MainPage = ({ navigate }) => {
  const now = new Date();
  const dayName = days[now.getDay()];
  const day = now.getDate();
  const month = months[now.getMonth()];
  const hours = now.getHours().toString().padStart(2, '0');
  const minutes = now.getMinutes().toString().padStart(2, '0');

  return (
    <div className="app">
      <div className="sidebar">
        <div className="logo"><img src="./logoEarlybird.png" alt="EarlyBird Logo" /></div>
        <div className="date-time">
          <div className="date">{`${dayName}, ${day} ${month}`}</div>
          <div className="time">{`${hours}:${minutes}`}</div>
        </div>
        <div className="location">Epitech Paris, France</div>
      </div>
      <div className="main">
        <div className="search-bar">
          <input type="text" placeholder="Search" />
        </div>
        <div className="user-list">
          {users.map((user) => (
            <div key={user.id} className="user-item" style={{ cursor: 'pointer' }} onClick={() => navigate(`/login/${user.id}`)}>
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
export default MainPage;
