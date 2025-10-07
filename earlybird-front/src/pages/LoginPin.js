import React, { useState } from 'react';
import './LoginPin.css';

const LoginPin = ({ user }) => {
  const [pin, setPin] = useState(['', '', '', '']);

  const handleKeyPress = (key) => {
    // Find the first empty slot in the PIN array
    const firstEmptyIndex = pin.indexOf('');
    if (firstEmptyIndex !== -1) {
      const newPin = [...pin];
      newPin[firstEmptyIndex] = key;
      setPin(newPin);
    }
  };

  const handleDelete = () => {
    // Find the last filled slot in the PIN array
    const lastFilledIndex = pin.findLastIndex((digit) => digit !== '');
    if (lastFilledIndex >= 0) {
      const newPin = [...pin];
      newPin[lastFilledIndex] = '';
      setPin(newPin);
    }
  };

  return (
    <div className="login-pin-app">
      <div className="login-pin-greeting">
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
  );
};

export default LoginPin;
