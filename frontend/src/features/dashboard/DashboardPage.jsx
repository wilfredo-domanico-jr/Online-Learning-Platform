import { useAuthUser } from '../auth/useAuth';

export default function DashboardPage() {
    const { data: user } = useAuthUser();

    return (
        <div>
            <h1 className="text-xl font-semibold text-gray-900">
                Welcome{user ? `, ${user.name}` : ''}
            </h1>
            <p className="mt-2 text-gray-600">
                This is a placeholder dashboard — course catalog, enrollments, and the
                instructor/admin panels land in later phases.
            </p>
        </div>
    );
}
