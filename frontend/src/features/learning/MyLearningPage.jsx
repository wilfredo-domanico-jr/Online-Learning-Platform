import { useQuery } from '@tanstack/react-query';
import { Link } from 'react-router-dom';
import { fetchMyEnrollments } from '../../api/learning';

export default function MyLearningPage() {
    const { data, isLoading } = useQuery({
        queryKey: ['my-enrollments'],
        queryFn: () => fetchMyEnrollments(),
    });

    return (
        <div>
            <h1 className="mb-6 text-xl font-semibold text-gray-900">My Learning</h1>

            {isLoading && <p className="text-gray-500">Loading…</p>}
            {data && data.data.length === 0 && (
                <p className="text-gray-500">
                    You haven't enrolled in any courses yet. <Link to="/courses" className="underline">Browse courses</Link>.
                </p>
            )}

            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                {data?.data.map((enrollment) => (
                    <Link
                        key={enrollment.id}
                        to={`/learn/${enrollment.course.slug}`}
                        className="block rounded-lg border bg-white p-4 shadow-sm hover:shadow-md"
                    >
                        <h3 className="font-semibold text-gray-900">{enrollment.course.title}</h3>
                        <p className="text-sm text-gray-500">{enrollment.course.instructor?.name}</p>
                        <div className="mt-3 h-2 w-full rounded-full bg-gray-100">
                            <div
                                className="h-2 rounded-full bg-gray-900"
                                style={{ width: `${enrollment.progress_percent}%` }}
                            />
                        </div>
                        <p className="mt-1 text-xs text-gray-500">{enrollment.progress_percent}% complete</p>
                    </Link>
                ))}
            </div>
        </div>
    );
}
