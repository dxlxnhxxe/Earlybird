import React, { useEffect, useState } from 'react'
import { useNavigate } from 'react-router'
import { ROUTES } from '../resources/routes-constants'

const NotFoundPage: React.FC = () => {
    const navigate = useNavigate()

    const [seconds, setSeconds] = useState(5);
    useEffect(() => {
        if (seconds <= 0) {
            navigate(ROUTES.HOMEPAGE_ROUTE);
            return;
        }
        const timer = setTimeout(() => setSeconds(s => s - 1), 1000);
        return () => clearTimeout(timer);
    }, [seconds, navigate]);

    return (
        <div style={{ position: 'relative', width: '100%', display: 'flex', justifyContent: 'center', alignItems: 'center', flexDirection: 'column' }}>
            <img
                src="https://api.dicebear.com/9.x/thumbs/svg?seed=Jade?scale=80&backgroundColor=transparent"
                alt="avatar" />
            <h1 style={{ fontSize: '4em' }}>Oops 404!</h1>
            <span style={{ color: '#888', fontSize: '1.1em' }}>
                You'll be redirected to the home page in {seconds} second{seconds !== 1 ? 's' : ''}.
            </span>
        </div>
    )
}

export default NotFoundPage
