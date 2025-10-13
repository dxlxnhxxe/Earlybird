import React, { useEffect, useRef, useState } from 'react';
import '../styles/notfound_bounce.css';

interface AnimatedPopupProps {
  open: boolean;
  onClose?: () => void;
  icon?: React.ReactNode;
  title: string;
  subtitle?: string;
  countdown?: number; // seconds
  countdownText?: string;
  background?: string;
}

const AnimatedPopup: React.FC<AnimatedPopupProps> = ({
  open,
  onClose,
  icon,
  title,
  subtitle,
  countdown = 5,
  countdownText = 'This screen will close in',
  background = 'rgba(100,120,130,0.95)',
}) => {
  const [seconds, setSeconds] = useState(countdown);
  const [animateOut, setAnimateOut] = useState(false);
  const timeoutRef = useRef<NodeJS.Timeout | null>(null);

  useEffect(() => {
    if (!open) return;
    setSeconds(countdown);
    setAnimateOut(false);
  }, [open, countdown]);

  useEffect(() => {
    if (!open) return;
    if (seconds <= 0) {
      setAnimateOut(true);
      timeoutRef.current = setTimeout(() => {
        if (onClose) onClose();
      }, 500);
      return () => {
        if (timeoutRef.current) clearTimeout(timeoutRef.current);
      };
    }
    const timer = setTimeout(() => setSeconds(s => s - 1), 1000);
    return () => {
      clearTimeout(timer);
    };
  }, [seconds, open, onClose]);

  if (!open) return null;

  return (
    <div
      className={animateOut ? 'notfound-bounce-out' : 'notfound-bounce-in'}
      style={{
        position: 'fixed',
        inset: 0,
        zIndex: 1000,
        background,
        display: 'flex',
        flexDirection: 'column',
        alignItems: 'center',
        justifyContent: 'center',
        color: '#fff',
        transition: 'background 0.3s',
      }}
      role="dialog"
      aria-modal="true"
    >
      {icon && <div style={{ fontSize: '3.5em', marginBottom: 16 }}>{icon}</div>}
      <h1 style={{ fontSize: '2.5em', fontWeight: 500, margin: 0 }}>{title}</h1>
      {subtitle && <div style={{ fontSize: '1.2em', margin: '12px 0 24px 0' }}>{subtitle}</div>}
      <div style={{ fontSize: '1.1em', marginTop: 24 }}>
        {countdownText} {seconds}
      </div>
    </div>
  );
};

export default AnimatedPopup;
