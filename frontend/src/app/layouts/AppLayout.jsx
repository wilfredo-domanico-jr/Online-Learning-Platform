import { Link, Outlet } from 'react-router-dom';
import { useAuthUser, useLogout } from '../../features/auth/useAuth';

export default function AppLayout() {
    const { data: user, isLoading } = useAuthUser();
    const logout = useLogout();

    const isInstructor = user?.roles?.some((r) => r.name === 'instructor');
    const isAdmin = user?.roles?.some((r) => r.name === 'admin');

    return (
        <div className="min-h-screen bg-gray-50">
            <nav className="border-b bg-white">
                <div className="mx-auto flex max-w-5xl items-center justify-between px-4 py-3">
                    <div className="flex items-center gap-6">
                        <Link to="/courses" className="font-semibold text-gray-900">
                            Online Learning Platform
                        </Link>
                        <Link to="/courses" className="text-sm text-gray-600 hover:text-gray-900">
                            Browse
                        </Link>
                        {user && (
                            <Link to="/my-learning" className="text-sm text-gray-600 hover:text-gray-900">
                                My Learning
                            </Link>
                        )}
                        {user && !isInstructor && (
                            <Link to="/instructor/apply" className="text-sm text-gray-600 hover:text-gray-900">
                                Teach
                            </Link>
                        )}
                        {isInstructor && (
                            <Link to="/instructor/courses" className="text-sm text-gray-600 hover:text-gray-900">
                                My Courses
                            </Link>
                        )}
                        {isAdmin && (
                            <>
                                <Link
                                    to="/admin/instructor-applications"
                                    className="text-sm text-gray-600 hover:text-gray-900"
                                >
                                    Applications
                                </Link>
                                <Link to="/admin/courses" className="text-sm text-gray-600 hover:text-gray-900">
                                    Moderation
                                </Link>
                            </>
                        )}
                    </div>
                    <div className="flex items-center gap-4 text-sm text-gray-600">
                        {!isLoading && user && (
                            <>
                                <span>{user.name}</span>
                                <button
                                    type="button"
                                    onClick={() => logout.mutate()}
                                    className="text-red-600 hover:underline"
                                >
                                    Log out
                                </button>
                            </>
                        )}
                        {!isLoading && !user && (
                            <Link to="/login" className="text-gray-900 hover:underline">
                                Log in
                            </Link>
                        )}
                    </div>
                </div>
            </nav>
            <main className="mx-auto max-w-5xl px-4 py-8">
                <Outlet />
            </main>
        </div>
    );
}
