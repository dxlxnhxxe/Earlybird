import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useSearchParams } from 'react-router-dom';
import '../../styles/kiosk_login.css';

const users = [
  { id: 'leonie-raymonde', name: 'Leonie Raymonde', role: 'admin', status: 'available' },
  { id: 'alisha-walker', name: 'Alisha Walker', role: 'manager', status: 'available' },
  { id: 'frank-garcia', name: 'Frank García', role: 'admin', status: 'available' },
  { id: 'niv-burkhard', name: 'Niv Burkhard', status: 'unavailable' },
  { id: 'erica-zhao', name: 'Erica Zhao', role: 'manager', status: 'away' },
  { id: 'keith-anderson', name: 'Keith Anderson', role: 'manager', status: 'away' },
  { id: 'olivia-johnson', name: 'Olivia Johnson', status: 'unavailable' },
];


const LoginPin = () => {
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  const userId = searchParams.get('userId');
  const user = users.find(u => u.id === userId);
  const [pin, setPin] = useState(['', '', '', '']);

  const handleKeyPress = (key: string) => {
    const firstEmptyIndex = pin.indexOf('');
    if (firstEmptyIndex !== -1) {
      const newPin = [...pin];
      newPin[firstEmptyIndex] = key;
      setPin(newPin);
    }
  };

  const handleDelete = () => {
    const lastFilledIndex = pin.findLastIndex((digit) => digit !== '');
    if (lastFilledIndex >= 0) {
      const newPin = [...pin];
      newPin[lastFilledIndex] = '';
      setPin(newPin);
    }
  };

  // Sidebar content (copied from HomePage)
  const now = new Date();
  const days = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
  const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
  const dayName = days[now.getDay()];
  const day = now.getDate();
  const month = months[now.getMonth()];
  const hours = now.getHours().toString().padStart(2, '0');
  const minutes = now.getMinutes().toString().padStart(2, '0');

  return (
    <div className="app">
      <div className="sidebar">
        <div className="logo"><img src="/logoEarlybird.png" alt="EarlyBird Logo" /></div>
        <div className="date-time">
          <div className="date">{`${dayName}, ${day} ${month}`}</div>
          <div className="time">{`${hours}:${minutes}`}</div>
        </div>
        <div className="location">Epitech Paris, France</div>
      </div>
      <div className="main" style={{ position: 'relative' }}>
        <button
          className="login-pin-keypad-button login-pin-back-button"
          style={{ position: 'absolute', top: 24, left: 0, zIndex: 2 }}
          onClick={() => navigate(-1)}
          aria-label="Back"
        >
          ←
        </button>
        <div className="login-pin-app">
          <div className="login-pin-greeting" style={{ position: 'relative' }}>
            <img
              src={`https://api.dicebear.com/9.x/thumbs/svg?seed=${encodeURIComponent(user?.name || 'User')}&scale=80&backgroundColor=transparent`}
              alt="User Avatar"
              className="login-pin-avatar"
            />
            <h1>Hello, {user?.name || 'User'}!</h1>
          </div>

          <div className="login-pin-pin-input-container">
            <p>Please enter your PIN.</p>
            <div className="login-pin-pin-input">
              {pin.map((digit, index) => (
                <div key={index} className={`login-pin-pin-dot${digit ? ' filled' : ''}`}>
                  {digit && <div className="login-pin-dot-inner" />}
                </div>
              ))}
            </div>
          </div>

          <div className="login-pin-keypad">
            {/* First row */}
            {[1, 2, 3].map((num) => (
              <button key={num} className="login-pin-keypad-button" onClick={() => handleKeyPress(num.toString())}>
                {num}
              </button>
            ))}
            {/* Second row */}
            {[4, 5, 6].map((num) => (
              <button key={num} className="login-pin-keypad-button" onClick={() => handleKeyPress(num.toString())}>
                {num}
              </button>
            ))}
            {/* Third row */}
            {[7, 8, 9].map((num) => (
              <button key={num} className="login-pin-keypad-button" onClick={() => handleKeyPress(num.toString())}>
                {num}
              </button>
            ))}
            {/* Last row: empty, 0, delete */}
            <div />
            <button className="login-pin-keypad-button" onClick={() => handleKeyPress('0')}>
              0
            </button>
            <button className="login-pin-keypad-button login-pin-delete-button" onClick={handleDelete}>
              ✕
            </button>
          </div>

          <div className="login-pin-forgot">
            <a href="#">Forgot your PIN?</a>
          </div>
        </div>
      </div>
    </div>
  );
};

export default LoginPin;
