import React, { useState, useEffect } from 'react'
import { useNavigate } from 'react-router-dom'
import DateTime from '../../components/DateTime'
import AnimatedPopup from '../../components/AnimatedPopup'
import '../../styles/kiosk_login.css'
import '../../styles/kiosk_home_main.css'

const users = [
    { id: 'leonie-raymonde', name: 'Leonie Raymonde', role: 'admin', status: 'available' },
    { id: 'alisha-walker', name: 'Alisha Walker', role: 'manager', status: 'available' },
    { id: 'frank-garcia', name: 'Frank García', role: 'admin', status: 'available' },
    { id: 'niv-burkhard', name: 'Niv Burkhard', status: 'unavailable' },
    { id: 'erica-zhao', name: 'Erica Zhao', role: 'manager', status: 'away' },
    { id: 'keith-anderson', name: 'Keith Anderson', role: 'manager', status: 'away' },
    { id: 'olivia-johnson', name: 'Olivia Johnson', status: 'unavailable' }
]

const HomePage: React.FC = () => {
    const navigate = useNavigate()
    const [showPopup, setShowPopup] = useState(false)
    const [popupType, setPopupType] = useState<'in' | 'out' | null>(null)
    const [timer, setTimer] = useState<number | null>(null)
    const [timerInterval, setTimerInterval] = useState<NodeJS.Timeout | null>(null)
    const [loading, setLoading] = useState(false)
    const [error, setError] = useState('')

    // On mount, check if already clocked in (persist timer in localStorage)
    useEffect(() => {
        const clockInStart = localStorage.getItem('clockInStart')
        if (clockInStart) {
            const start = parseInt(clockInStart, 10)
            const elapsed = Math.floor((Date.now() - start) / 1000)
            setTimer(elapsed >= 0 ? elapsed : 0)
            if (!timerInterval) {
                const interval = setInterval(() => {
                    setTimer((prev) => (prev !== null ? prev + 1 : 1))
                }, 1000)
                setTimerInterval(interval)
            }
        }
        // Cleanup on unmount
        return () => {
            if (timerInterval) clearInterval(timerInterval)
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [])

    const handleClockIn = async () => {
        setLoading(true)
        setError('')
        try {
            const res = await fetch('http://localhost:8080/clocks', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_id: 1, type: 'arrival' })
            })
            if (!res.ok) throw new Error('Failed to clock in')
            setPopupType('in')
            setShowPopup(true)
            // Persist clock-in start time
            const now = Date.now()
            localStorage.setItem('clockInStart', now.toString())
            setTimer(0)
            if (timerInterval) clearInterval(timerInterval)
            const interval = setInterval(() => {
                setTimer((prev) => (prev !== null ? prev + 1 : 1))
            }, 1000)
            setTimerInterval(interval)
            // No manual timeout, let AnimatedPopup handle closing
        } catch (e) {
            setError(e instanceof Error ? e.message : 'Error')
        } finally {
            setLoading(false)
        }
    }

    // Cleanup timer if user leaves or refreshes
    useEffect(() => {
        return () => {
            if (timerInterval) clearInterval(timerInterval)
        }
    }, [timerInterval])

    // Optionally, add a "Clock out" button to clear timer/localStorage
    const handleClockOut = async () => {
        setLoading(true)
        setError('')
        try {
            const res = await fetch('http://localhost:8080/clocks', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_id: 1, type: 'departure' })
            })
            if (!res.ok) throw new Error('Failed to clock out')
            if (timerInterval) clearInterval(timerInterval)
            setTimerInterval(null)
            setTimer(null)
            localStorage.removeItem('clockInStart')
            setPopupType('out')
            setShowPopup(true)
        } catch (e) {
            setError(e instanceof Error ? e.message : 'Error')
        } finally {
            setLoading(false)
        }
    }

    return (
        <div className="app">
            <div className="sidebar">
                <div className="logo">
                    <img src="/logoEarlybird.png" alt="EarlyBird Logo" />
                </div>
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
                    {timer === null && (
                        <button className="home-action-btn" onClick={handleClockIn} disabled={loading}>
                            <span style={{ fontSize: '2rem', display: 'flex', alignItems: 'center' }}>🕒</span>
                            {loading ? 'Clocking in...' : 'Clock in'}
                        </button>
                    )}
                    {timer !== null && (
                        <button className="home-action-btn" style={{ backgroundColor: 'red' }} onClick={handleClockOut} disabled={loading || timer === null}>
                            <span style={{ fontSize: '2rem', display: 'flex', alignItems: 'center' }}>🕒</span>
                            {loading ? 'Clocking out...' : timer !== null ? 'Clock out' : 'Clocked out'}
                        </button>
                    )}
                    <button className="home-action-btn orange" disabled={timer !== null}>
                        <span style={{ fontSize: '2rem', display: 'flex', alignItems: 'center' }}>☕</span>
                        Start break
                    </button>
                    {error && <div style={{ color: 'red', marginTop: 8 }}>{error}</div>}
                    {timer !== null && (
                        <div style={{ marginTop: 16, fontSize: '1.5rem', fontWeight: 600 }}>
                            Clocked in for: {Math.floor(timer / 60)}:{(timer % 60).toString().padStart(2, '0')}
                        </div>
                    )}
                </div>
                <AnimatedPopup
                    key={popupType + String(showPopup)}
                    open={showPopup}
                    onClose={() => setShowPopup(false)}
                    icon={<img src="/checkmark-round.svg" alt="Checkmark" style={{ width: '2.5em', height: '2.5em' }} />}
                    title={popupType === 'in' ? 'Clocked in!' : popupType === 'out' ? 'Clocked out!' : ''}
                    subtitle="Epitech Paris, France"
                    countdown={3}
                    countdownText="This screen will close in"
                    background={popupType === 'in' ? '#8fd16a' : popupType === 'out' ? '#e41212ff' : '#8fd16a'}
                />
            </div>
        </div>
    )
}

export { users }
export default HomePage
