import { Outlet } from 'react-router-dom';

export default function GuestLayout() {
    return (
        <div className="flex min-h-screen flex-col items-center justify-center bg-gray-50 px-4 py-12">
            <div className="w-full max-w-sm">
                <h1 className="mb-8 text-center text-2xl font-semibold text-gray-900">
                    Online Learning Platform
                </h1>
                <div className="rounded-lg bg-white p-6 shadow">
                    <Outlet />
                </div>
            </div>
        </div>
    );
}
