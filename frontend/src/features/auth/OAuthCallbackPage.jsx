import { useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { useQueryClient } from '@tanstack/react-query';
import { fetchMe } from '../../api/auth';
import { AUTH_QUERY_KEY } from './useAuth';

/**
 * Landed on after SocialController::callback() redirects the full browser
 * away from the API back into the SPA. The session cookie is already set at
 * this point, so this just needs to hydrate the auth query and move on.
 */
export default function OAuthCallbackPage() {
    const navigate = useNavigate();
    const queryClient = useQueryClient();

    useEffect(() => {
        fetchMe()
            .then((user) => {
                queryClient.setQueryData(AUTH_QUERY_KEY, user);
                navigate('/dashboard', { replace: true });
            })
            .catch(() => navigate('/login?error=oauth_failed', { replace: true }));
    }, [navigate, queryClient]);

    return (
        <div className="flex min-h-screen items-center justify-center text-gray-600">
            Signing you in…
        </div>
    );
}
