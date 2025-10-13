import React from 'react'
import { BrowserRouter as Router, Route, Routes } from 'react-router'
import { ROUTES } from './resources/routes-constants'
import './styles/main.sass'

// Pages
import Index from './pages/kiosk/Index'
import NotFoundPage from './pages/NotFoundPage'
import LoginPin from './pages/kiosk/Login'
import HomePage from './pages/kiosk/Home'

const RootComponent: React.FC = () => {
    return (
        <Router>
            <Routes>
                <Route path="*" element={<NotFoundPage />} />
                <Route path={ROUTES.KIOSK_INDEX_ROUTE} element={<Index />} />
                <Route path={ROUTES.KIOSK_LOGIN_ROUTE} element={<LoginPin />} />
                <Route path={ROUTES.KIOSK_HOME_ROUTE} element={<HomePage />} />
            </Routes>
        </Router>
    )
}

export default RootComponent
