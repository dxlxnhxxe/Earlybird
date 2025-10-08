import React from 'react'
import { BrowserRouter as Router, Route, Routes } from 'react-router'
import { ROUTES } from './resources/routes-constants'
import './styles/main.sass'

// Pages
import HomePage from './pages/HomePage'
import NotFoundPage from './pages/NotFoundPage'
import LoginPin from './pages/kiosk/Login'

const RootComponent: React.FC = () => {
    return (
        <Router>
            <Routes>
                <Route path="*" element={<NotFoundPage />} />
                <Route path={ROUTES.HOMEPAGE_ROUTE} element={<HomePage />} />
                <Route path={ROUTES.KIOSK_LOGIN_ROUTE} element={<LoginPin />} />
            </Routes>
        </Router>
    )
}

export default RootComponent
