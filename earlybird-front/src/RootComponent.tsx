import React from 'react'
import { BrowserRouter as Router, Route, Routes } from 'react-router'
import { ROUTES } from './resources/routes-constants'
import './styles/main.sass'

// Pages
import Index from './pages/kiosk/Index'
import NotFoundPage from './pages/NotFoundPage'
import LoginPin from './pages/kiosk/Login'
import KioskHomePage from './pages/kiosk/Home'
import HomePage from './pages/Home'
import DashboardIndex from './pages/dashboard/Index'

const RootComponent: React.FC = () => {
    return (
        <Router>
            <Routes>
                <Route path="*" element={<NotFoundPage />} />
                <Route path={ROUTES.KIOSK_INDEX_ROUTE} element={<Index />} />
                <Route path={ROUTES.KIOSK_LOGIN_ROUTE} element={<LoginPin />} />
                <Route path={ROUTES.KIOSK_HOME_ROUTE} element={<KioskHomePage />} />
                <Route path={ROUTES.HOMEPAGE_ROUTE} element={<HomePage />} />
                <Route path={ROUTES.DASHBOARD_ROUTE} element={<DashboardIndex />} />
            </Routes>
        </Router>
    )
}

export default RootComponent
