import React, { useState, useEffect } from 'react';
import DateTime from '../../components/DateTime';
import Spinner from '../../components/Spinner';
import { useNavigate } from 'react-router-dom';
import { useSearchParams } from 'react-router-dom';
import '../../styles/kiosk_login.css';

interface User {
  id: number;
  firstname: string;
  lastname: string;
  email: string;
  phone_number: string;
  role: string;
  code_pin: number;
}

const LoginPin = () => {
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  const userId = searchParams.get('userId');
  const teamId = searchParams.get('teamId');
  const [users, setUsers] = useState<User[]>([]);
  const [loading, setLoading] = useState(true);
  const [pin, setPin] = useState(['', '', '', '']);
  const [error, setError] = useState('');

  useEffect(() => {
    fetch('http://earlybird-api/users')
      .then(res => res.json())
      .then(data => {
        setUsers(data);
        setLoading(false);
      })
      .catch(() => setLoading(false));
  }, []);

  const user = users.find(u => String(u.id) === String(userId));

  const handleKeyPress = (key: string) => {
    if (error) setError('');
    const firstEmptyIndex = pin.indexOf('');
    if (firstEmptyIndex !== -1) {
      const newPin = [...pin];
      newPin[firstEmptyIndex] = key;
      setPin(newPin);

      if(firstEmptyIndex === 3) {
        // Check PIN
        const enteredPin = newPin.join('');
        if (user && String(user.code_pin).padStart(4, '0') === enteredPin) {
          // Store user and team info for the home page
          localStorage.setItem('kioskUserId', userId || '');
          localStorage.setItem('kioskTeamId', teamId || '');
          navigate('/kiosk/home');
        } else {
          setError('Incorrect PIN');
          setTimeout(() => setError(''), 1500);
          setPin(['', '', '', '']);
        }
      }
    }
  };

  const handleDelete = () => {
    if (error) setError('');
    const lastFilledIndex = pin.findLastIndex((digit) => digit !== '');
    if (lastFilledIndex >= 0) {
      const newPin = [...pin];
      newPin[lastFilledIndex] = '';
      setPin(newPin);
    }
  };

  if (loading) {
    return (
      <div className="app">
        <div className="main" style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', minHeight: '100vh' }}>
          <Spinner size={48} />
        </div>
      </div>
    );
  }

  return (
    <div className="app">
      <div className="sidebar">
        <div className="logo"><img src="/logoEarlybird.png" alt="EarlyBird Logo" /></div>
        <DateTime />
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
              src={`https://api.dicebear.com/9.x/thumbs/svg?seed=${encodeURIComponent(user ? (user.firstname + ' ' + user.lastname) : 'User')}&scale=80&backgroundColor=transparent`}
              alt="User Avatar"
              className="login-pin-avatar"
            />
            <h1>Hello, {user ? (user.firstname + ' ' + user.lastname) : 'User'}!</h1>
          </div>

          <div className="login-pin-pin-input-container">
            <p>Please enter your PIN.</p>
            <div className="login-pin-pin-input">
              {pin.map((digit, index) => (
                <div key={index} className={`login-pin-pin-dot${digit ? ' filled' : ''} ${error ? ' error' : ''}`}>
                  {digit && <div className="login-pin-dot-inner" />}
                </div>
              ))}
            </div>
            {error && <div style={{ color: 'red', marginTop: 8 }}>{error}</div>}
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
