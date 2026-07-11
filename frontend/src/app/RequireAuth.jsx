import { Navigate, useLocation } from 'react-router-dom';
import { useAuthUser } from '../features/auth/useAuth';

/**
 * Gate a route behind authentication, and optionally behind one of a set of
 * Spatie roles returned on the user's `roles` relation by /auth/me.
 */
export default function RequireAuth({ roles, children }) {
    const location = useLocation();
    const { data: user, isLoading, isError } = useAuthUser();

    if (isLoading) {
        return null;
    }

    if (isError || !user) {
        return <Navigate to="/login" state={{ from: location }} replace />;
    }

    if (roles && !user.roles?.some((role) => roles.includes(role.name))) {
        return <Navigate to="/403" replace />;
    }

    return children;
}
