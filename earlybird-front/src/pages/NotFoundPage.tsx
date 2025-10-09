import React, { useEffect, useRef, useState } from 'react'
import '../styles/notfound_bounce.css';
import { useNavigate, useLocation } from 'react-router'

const NotFoundPage: React.FC = () => {
    const navigate = useNavigate()
    const location = useLocation();

    const [seconds, setSeconds] = useState(5);
    const [animateOut, setAnimateOut] = useState(false);
    const timeoutRef = useRef<NodeJS.Timeout | null>(null);

    useEffect(() => {
        if (seconds <= 0) {
            if (location.pathname !== '/kiosk/') {
                setAnimateOut(true);
                timeoutRef.current = setTimeout(() => {
                    navigate('/kiosk/', { replace: true });
                }, 500);
            }
            return;
        }
        const timer = setTimeout(() => setSeconds(s => s - 1), 1000);
        return () => {
            clearTimeout(timer);
            if (timeoutRef.current) clearTimeout(timeoutRef.current);
        };
    }, [seconds, navigate, location.pathname]);

    return (
        <div
            className={animateOut ? 'notfound-bounce-out' : 'notfound-bounce-in'}
            style={{ position: 'relative', width: '100%', display: 'flex', justifyContent: 'center', alignItems: 'center', flexDirection: 'column' }}
        >
            <img
                src="https://api.dicebear.com/9.x/thumbs/svg?seed=Jade?scale=80&backgroundColor=transparent"
                alt="avatar" />
            <h1 style={{ fontSize: '4em' }}>Oops 404!</h1>
            <span style={{ color: '#888', fontSize: '1.1em' }}>
                You'll be redirected to the home page in {seconds} second
                <span style={{ display: 'inline-block', width: '0.65em', overflow: 'hidden', verticalAlign: 'bottom' }}>
                    {seconds !== 1 ? 's' : <span style={{ opacity: 0 }}>s</span>}
                </span>
            </span>
        </div>
    )
}

export default NotFoundPage
