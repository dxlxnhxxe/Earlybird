import React, { useEffect, useState } from 'react'
import DateTime from '../../components/DateTime'
import { useNavigate, useParams } from 'react-router-dom'
import Spinner from '../../components/Spinner'
import '../../styles/kiosk_index.sass'

const IndexPage: React.FC = () => {
    const navigate = useNavigate()
    const { teamId } = useParams<{ teamId: string }>()
    const [users, setUsers] = useState([])
    const [team, setTeam] = useState<any>(null)
    const [loading, setLoading] = useState(true)

    useEffect(() => {
        const fetchData = async () => {
            try {
                // If teamId is provided, fetch team details and its members
                if (teamId) {
                    const teamResponse = await fetch(`http://earlybird-api/teams/${teamId}`)
                    if (teamResponse.ok) {
                        const teamData = await teamResponse.json()
                        setTeam(teamData)
                        setUsers(teamData.members || [])
                    } else {
                        console.error('Failed to fetch team:', teamResponse.statusText)
                        // Fallback to all users if team not found
                        const usersResponse = await fetch('http://earlybird-api/users')
                        const usersData = await usersResponse.json()
                        setUsers(usersData)
                    }
                } else {
                    // If no teamId, fetch all users
                    const usersResponse = await fetch('http://earlybird-api/users')
                    const usersData = await usersResponse.json()
                    setUsers(usersData)
                }
            } catch (err) {
                console.error('Failed to fetch data:', err)
            } finally {
                setLoading(false)
            }
        }

        fetchData()
    }, [teamId])

    return (
        <div className="app">
            <div className="sidebar">
                <div className="logo">
                    <img src="/logoEarlybird.png" alt="EarlyBird Logo" />
                </div>
                <DateTime />
                {team && <div className="team-name">{team.name}</div>}
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
                            <div key={user.id} className="user-item" style={{ cursor: 'pointer' }} onClick={() => navigate(`/kiosk/login?userId=${user.id}${teamId ? `&teamId=${teamId}` : ''}`)}>
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
