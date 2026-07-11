import { Link, Outlet } from 'react-router-dom';
import { useAuthUser, useLogout } from '../../features/auth/useAuth';

export default function AppLayout() {
    const { data: user } = useAuthUser();
    const logout = useLogout();

    return (
        <div className="min-h-screen bg-gray-50">
            <nav className="border-b bg-white">
                <div className="mx-auto flex max-w-5xl items-center justify-between px-4 py-3">
                    <Link to="/dashboard" className="font-semibold text-gray-900">
                        Online Learning Platform
                    </Link>
                    <div className="flex items-center gap-4 text-sm text-gray-600">
                        {user && <span>{user.name}</span>}
                        <button
                            type="button"
                            onClick={() => logout.mutate()}
                            className="text-red-600 hover:underline"
                        >
                            Log out
                        </button>
                    </div>
                </div>
            </nav>
            <main className="mx-auto max-w-5xl px-4 py-8">
                <Outlet />
            </main>
        </div>
    );
}
