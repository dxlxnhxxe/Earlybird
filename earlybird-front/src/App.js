
import React from 'react';
import { Routes, Route, useParams, useNavigate } from 'react-router-dom';
import MainPage, { users } from './pages/MainPage';
import LoginPin from './pages/LoginPin';
import './App.css';

const LoginPinWrapper = () => {
  const { id } = useParams();
  const user = users.find(u => u.id === id);
  return <LoginPin user={user} />;
};

const App = () => {
  const navigate = useNavigate();
  return (
    <Routes>
      <Route path="/" element={<MainPage navigate={navigate} />} />
      <Route path="/login/:id" element={<LoginPinWrapper />} />
    </Routes>
  );
};

export default App;
