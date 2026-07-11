import { useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import {
    approveInstructorApplication,
    fetchInstructorApplications,
    rejectInstructorApplication,
} from '../../api/admin';

export default function ApplicationsQueuePage() {
    const queryClient = useQueryClient();
    const { data, isLoading } = useQuery({
        queryKey: ['admin', 'instructor-applications'],
        queryFn: () => fetchInstructorApplications({ status: 'pending' }),
    });

    const invalidate = () => queryClient.invalidateQueries({ queryKey: ['admin', 'instructor-applications'] });
    const approve = useMutation({ mutationFn: approveInstructorApplication, onSuccess: invalidate });
    const reject = useMutation({
        mutationFn: ({ id, reason }) => rejectInstructorApplication(id, reason),
        onSuccess: invalidate,
    });

    const [rejectingId, setRejectingId] = useState(null);
    const [reason, setReason] = useState('');

    return (
        <div>
            <h1 className="mb-6 text-xl font-semibold text-gray-900">Instructor Applications</h1>

            {isLoading && <p className="text-gray-500">Loading…</p>}
            {data && data.data.length === 0 && <p className="text-gray-500">No pending applications.</p>}

            <div className="space-y-4">
                {data?.data.map((app) => (
                    <div key={app.id} className="rounded border bg-white p-4">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="font-medium text-gray-900">{app.user?.name}</p>
                                <p className="text-sm text-gray-500">{app.user?.email}</p>
                            </div>
                            <div className="flex gap-2">
                                <button
                                    type="button"
                                    onClick={() => approve.mutate(app.id)}
                                    className="rounded bg-green-600 px-3 py-1.5 text-sm text-white"
                                >
                                    Approve
                                </button>
                                <button
                                    type="button"
                                    onClick={() => setRejectingId(rejectingId === app.id ? null : app.id)}
                                    className="rounded bg-red-600 px-3 py-1.5 text-sm text-white"
                                >
                                    Reject
                                </button>
                            </div>
                        </div>
                        <p className="mt-2 text-sm text-gray-700">{app.bio}</p>
                        {app.expertise?.length > 0 && (
                            <p className="mt-1 text-xs text-gray-500">Expertise: {app.expertise.join(', ')}</p>
                        )}
                        {app.portfolio_url && (
                            <a
                                href={app.portfolio_url}
                                target="_blank"
                                rel="noreferrer"
                                className="mt-1 block text-xs text-blue-600 hover:underline"
                            >
                                {app.portfolio_url}
                            </a>
                        )}

                        {rejectingId === app.id && (
                            <form
                                onSubmit={(e) => {
                                    e.preventDefault();
                                    reject.mutate({ id: app.id, reason });
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
