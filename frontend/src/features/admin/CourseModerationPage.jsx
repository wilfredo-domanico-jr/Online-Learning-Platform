import { useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Link } from 'react-router-dom';
import { approveCourse, fetchCoursesForModeration, rejectCourse } from '../../api/admin';

export default function CourseModerationPage() {
    const queryClient = useQueryClient();
    const { data, isLoading } = useQuery({
        queryKey: ['admin', 'courses', 'pending_review'],
        queryFn: () => fetchCoursesForModeration({ status: 'pending_review' }),
    });

    const invalidate = () => queryClient.invalidateQueries({ queryKey: ['admin', 'courses'] });
    const approve = useMutation({ mutationFn: approveCourse, onSuccess: invalidate });
    const reject = useMutation({
        mutationFn: ({ id, reason }) => rejectCourse(id, reason),
        onSuccess: invalidate,
    });

    const [rejectingId, setRejectingId] = useState(null);
    const [reason, setReason] = useState('');

    return (
        <div>
            <h1 className="mb-6 text-xl font-semibold text-gray-900">Course Moderation Queue</h1>

            {isLoading && <p className="text-gray-500">Loading…</p>}
            {data && data.data.length === 0 && <p className="text-gray-500">Nothing awaiting review.</p>}

            <div className="space-y-4">
                {data?.data.map((course) => (
                    <div key={course.id} className="rounded border bg-white p-4">
                        <div className="flex items-center justify-between">
                            <div>
                                <Link to={`/courses/${course.slug}`} className="font-medium text-gray-900 hover:underline">
                                    {course.title}
                                </Link>
                                <p className="text-sm text-gray-500">by {course.instructor?.name}</p>
                            </div>
                            <div className="flex gap-2">
                                <button
                                    type="button"
                                    onClick={() => approve.mutate(course.id)}
                                    className="rounded bg-green-600 px-3 py-1.5 text-sm text-white"
                                >
                                    Approve
                                </button>
                                <button
                                    type="button"
                                    onClick={() => setRejectingId(rejectingId === course.id ? null : course.id)}
                                    className="rounded bg-red-600 px-3 py-1.5 text-sm text-white"
                                >
                                    Reject
                                </button>
                            </div>
                        </div>
                        {course.subtitle && <p className="mt-2 text-sm text-gray-700">{course.subtitle}</p>}

                        {rejectingId === course.id && (
                            <form
                                onSubmit={(e) => {
                                    e.preventDefault();
                                    reject.mutate({ id: course.id, reason });
                                    setRejectingId(null);
                                    setReason('');
                                }}
                                className="mt-3 flex gap-2"
                            >
                                <input
                                    required
                                    value={reason}
                                    onChange={(e) => setReason(e.target.value)}
                                    placeholder="Rejection reason…"
                                    className="flex-1 rounded border-gray-300 text-sm shadow-sm"
                                />
                                <button type="submit" className="rounded border px-3 py-1.5 text-sm hover:bg-gray-50">
                                    Confirm reject
                                </button>
                            </form>
                        )}
                    </div>
                ))}
            </div>
        </div>
    );
}
