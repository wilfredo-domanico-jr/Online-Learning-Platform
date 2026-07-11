import { useForm } from 'react-hook-form';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import FormError from '../../components/FormError';
import { fieldErrors, generalError } from '../../lib/apiErrors';
import { applyToTeach, fetchMyApplication } from '../../api/instructor';
import { AUTH_QUERY_KEY, useAuthUser } from '../auth/useAuth';

export default function ApplyPage() {
    const { data: user } = useAuthUser();
    const queryClient = useQueryClient();
    const { data: application, isLoading } = useQuery({
        queryKey: ['instructor-application', 'me'],
        queryFn: fetchMyApplication,
    });

    const {
        register,
        handleSubmit,
        setError,
        formState: { errors, isSubmitting },
    } = useForm();

    const apply = useMutation({
        mutationFn: applyToTeach,
        onSuccess: () => queryClient.invalidateQueries({ queryKey: ['instructor-application', 'me'] }),
    });

    const onSubmit = async (values) => {
        try {
            await apply.mutateAsync({
                bio: values.bio,
                portfolio_url: values.portfolio_url || undefined,
                expertise: values.expertise
                    ? values.expertise.split(',').map((s) => s.trim()).filter(Boolean)
                    : undefined,
            });
            queryClient.invalidateQueries({ queryKey: AUTH_QUERY_KEY });
        } catch (error) {
            const fieldErrs = fieldErrors(error);
            if (Object.keys(fieldErrs).length) {
                Object.entries(fieldErrs).forEach(([field, message]) => setError(field, { message }));
            } else {
                setError('root', { message: generalError(error) });
            }
        }
    };

    if (user?.roles?.some((r) => r.name === 'instructor')) {
        return <p className="text-gray-700">You're already an approved instructor.</p>;
    }

    if (isLoading) return <p className="text-gray-500">Loading…</p>;

    if (application && application.status === 'pending') {
        return (
            <div className="max-w-lg">
                <h1 className="mb-2 text-xl font-semibold text-gray-900">Application pending</h1>
                <p className="text-gray-600">
                    Your instructor application is awaiting review. We'll notify you once it's been decided.
                </p>
            </div>
        );
    }

    return (
        <div className="max-w-lg">
            <h1 className="mb-4 text-xl font-semibold text-gray-900">Apply to become an instructor</h1>

            {application?.status === 'rejected' && (
                <p className="mb-4 rounded bg-red-50 p-3 text-sm text-red-700">
                    Your previous application was rejected
                    {application.rejection_reason && <>: {application.rejection_reason}</>}. You can apply again below.
                </p>
            )}

            <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
                <div>
                    <label className="block text-sm font-medium text-gray-700">
                        Tell us about your teaching/professional background
                    </label>
                    <textarea
                        rows={5}
                        className="mt-1 w-full rounded border-gray-300 shadow-sm"
                        {...register('bio', { required: true, minLength: 20 })}
                    />
                    <FormError message={errors.bio?.message} />
                </div>
                <div>
                    <label className="block text-sm font-medium text-gray-700">
                        Areas of expertise (comma-separated)
                    </label>
                    <input
                        type="text"
                        placeholder="React, Laravel, Data Science"
                        className="mt-1 w-full rounded border-gray-300 shadow-sm"
                        {...register('expertise')}
                    />
                </div>
                <div>
                    <label className="block text-sm font-medium text-gray-700">Portfolio URL (optional)</label>
                    <input
                        type="url"
                        className="mt-1 w-full rounded border-gray-300 shadow-sm"
                        {...register('portfolio_url')}
                    />
                    <FormError message={errors.portfolio_url?.message} />
                </div>
                <FormError message={errors.root?.message} />
                <button
                    type="submit"
                    disabled={isSubmitting}
                    className="rounded bg-gray-900 px-4 py-2 text-white disabled:opacity-50"
                >
                    Submit application
                </button>
            </form>
        </div>
    );
}
