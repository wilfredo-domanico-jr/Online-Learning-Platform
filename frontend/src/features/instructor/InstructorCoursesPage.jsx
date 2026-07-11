import { useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Link, useNavigate } from 'react-router-dom';
import { createCourse, fetchMyCourses } from '../../api/instructor';

const STATUS_STYLES = {
    draft: 'bg-gray-100 text-gray-700',
    pending_review: 'bg-amber-100 text-amber-800',
    published: 'bg-green-100 text-green-800',
    rejected: 'bg-red-100 text-red-800',
};

export default function InstructorCoursesPage() {
    const [title, setTitle] = useState('');
    const navigate = useNavigate();
    const queryClient = useQueryClient();

    const { data, isLoading } = useQuery({
        queryKey: ['instructor', 'courses'],
        queryFn: () => fetchMyCourses(),
    });

    const create = useMutation({
        mutationFn: createCourse,
        onSuccess: (course) => {
            queryClient.invalidateQueries({ queryKey: ['instructor', 'courses'] });
            navigate(`/instructor/courses/${course.id}`);
        },
    });

    return (
        <div>
            <div className="mb-6 flex items-center justify-between">
                <h1 className="text-xl font-semibold text-gray-900">My Courses</h1>
            </div>

            <form
                onSubmit={(e) => {
                    e.preventDefault();
                    if (title.trim()) create.mutate({ title });
                }}
                className="mb-6 flex gap-2"
            >
                <input
                    type="text"
                    placeholder="New course title…"
                    value={title}
                    onChange={(e) => setTitle(e.target.value)}
                    className="flex-1 rounded border-gray-300 shadow-sm"
                />
                <button
                    type="submit"
                    disabled={create.isPending}
                    className="rounded bg-gray-900 px-4 py-2 text-white disabled:opacity-50"
                >
                    Create course
                </button>
            </form>

            {isLoading && <p className="text-gray-500">Loading…</p>}

            <ul className="divide-y rounded border bg-white">
                {data?.data.map((course) => (
                    <li key={course.id} className="flex items-center justify-between px-4 py-3">
                        <Link to={`/instructor/courses/${course.id}`} className="font-medium text-gray-900 hover:underline">
                            {course.title}
                        </Link>
                        <span className={`rounded px-2 py-1 text-xs font-medium ${STATUS_STYLES[course.status]}`}>
                            {course.status.replace('_', ' ')}
                        </span>
                    </li>
                ))}
                {data && data.data.length === 0 && (
                    <li className="px-4 py-3 text-gray-500">No courses yet — create your first one above.</li>
                )}
            </ul>
        </div>
    );
}
