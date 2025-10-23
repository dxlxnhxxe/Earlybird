import React, { useEffect, useState } from 'react'
import DateTime from '../../components/DateTime'
import { useNavigate } from 'react-router-dom'
import Spinner from '../../components/Spinner'
import '../../styles/kiosk_index.sass'

const IndexPage: React.FC = () => {
    const navigate = useNavigate()
    const [users, setUsers] = useState([])
    const [loading, setLoading] = useState(true)

    useEffect(() => {
        fetch('http://earlybird-api/users')
            .then((res) => res.json())
            .then((data) => setUsers(data))
            .catch((err) => console.error('Failed to fetch users:', err))
            .finally(() => setLoading(false))
    }, [])

    return (
        <div className="app">
            <div className="sidebar">
                <div className="logo">
                    <img src="/logoEarlybird.png" alt="EarlyBird Logo" />
                </div>
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
                {loading ? (
                    <div style={{ minHeight: 200, display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                        <Spinner size={48} />
                    </div>
                ) : (
                    <div className="user-list">
                        {users.map((user: any) => (
                            <div key={user.id} className="user-item" style={{ cursor: 'pointer' }} onClick={() => navigate(`/kiosk/login?userId=${user.id}`)}>
                                <img
                                    src={`https://api.dicebear.com/9.x/thumbs/svg?seed=${encodeURIComponent(user.firstname + ' ' + user.lastname)}&scale=80&backgroundColor=transparent`}
                                    alt={user.firstname + ' ' + user.lastname}
                                    className="avatar"
                                />
                                <div className="user-info">
                                    <div className="user-name">
                                        {user.firstname} {user.lastname} {user.role && <span className="user-role">({user.role})</span>}
                                    </div>
                                </div>
                                <div className={`status-indicator available`}></div>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </div>
    )
}

export default IndexPage
