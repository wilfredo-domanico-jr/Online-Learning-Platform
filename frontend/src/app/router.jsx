import { createBrowserRouter, Navigate } from 'react-router-dom';
import GuestLayout from './layouts/GuestLayout';
import AppLayout from './layouts/AppLayout';
import RequireAuth from './RequireAuth';
import NotFoundPage from './NotFoundPage';
import ForbiddenPage from './ForbiddenPage';
import LoginPage from '../features/auth/LoginPage';
import RegisterPage from '../features/auth/RegisterPage';
import OAuthCallbackPage from '../features/auth/OAuthCallbackPage';
import DashboardPage from '../features/dashboard/DashboardPage';

export const router = createBrowserRouter([
    {
        element: <GuestLayout />,
        children: [
            { path: '/login', element: <LoginPage /> },
            { path: '/register', element: <RegisterPage /> },
        ],
    },
    { path: '/auth/callback', element: <OAuthCallbackPage /> },
    { path: '/403', element: <ForbiddenPage /> },
    {
        element: <AppLayout />,
        children: [
            {
                path: '/dashboard',
                element: (
                    <RequireAuth>
                        <DashboardPage />
                    </RequireAuth>
                ),
            },
        ],
    },
    { path: '/', element: <Navigate to="/dashboard" replace /> },
    { path: '*', element: <NotFoundPage /> },
]);
