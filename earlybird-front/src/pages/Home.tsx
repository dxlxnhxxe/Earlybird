import React, { useState, useEffect } from 'react'
import { useNavigate } from 'react-router-dom'
import FullCalendar from '@fullcalendar/react'
import dayGridPlugin from '@fullcalendar/daygrid'
import resourceTimelinePlugin from '@fullcalendar/resource-timeline';
import '../styles/kiosk_login.css'
import '../styles/kiosk_home_main.css'

const users = [
    { id: 'leonie-raymonde', name: 'Leonie Raymonde', role: 'admin', status: 'available' },
    { id: 'alisha-walker', name: 'Alisha Walker', role: 'manager', status: 'available' },
    { id: 'frank-garcia', name: 'Frank García', role: 'admin', status: 'available' },
    { id: 'niv-burkhard', name: 'Niv Burkhard', status: 'unavailable' },
    { id: 'erica-zhao', name: 'Erica Zhao', role: 'manager', status: 'away' },
    { id: 'keith-anderson', name: 'Keith Anderson', role: 'manager', status: 'away' },
    { id: 'olivia-johnson', name: 'Olivia Johnson', status: 'unavailable' }
]

const KioskHomePage: React.FC = () => {
    const navigate = useNavigate()

    return (
        <div className="app">
            <div className="main" style={{ position: 'relative', display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'flex-start', minHeight: '100vh', paddingTop: 40 }}>
                <div style={{ width: 280, display: 'flex', flexDirection: 'column', alignItems: 'center', gap: 24 }}>
                    <div style={{ textAlign: 'center', marginBottom: 40 }}>
                        <div className="home-main-title">Home Page</div>
                        <div className="home-main-subtitle">(wip)</div>
                    </div>
                </div>
                <div style={{ width: '100%', maxWidth: 700, margin: '0 auto', background: '#fff', borderRadius: 12, boxShadow: '0 2px 8px rgba(0,0,0,0.06)', padding: 24 }}>
                    <FullCalendar
                        plugins={[resourceTimelinePlugin]}
                        initialView="resourceTimelineDay"
                        resourceAreaHeaderContent='Rooms'
                        resources='https://fullcalendar.io/api/demo-feeds/resources.json?with-nesting&with-colors'
                        events='https://fullcalendar.io/api/demo-feeds/events.json?single-day&for-resource-timeline'
                        editable={true}
                    />
                </div>
            </div>
        </div>
    )
}

export { users }
export default KioskHomePage