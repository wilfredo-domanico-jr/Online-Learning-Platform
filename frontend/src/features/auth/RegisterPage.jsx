import { useForm } from 'react-hook-form';
import { Link, useNavigate } from 'react-router-dom';
import FormError from '../../components/FormError';
import { fieldErrors, generalError } from '../../lib/apiErrors';
import { useRegister } from './useAuth';

export default function RegisterPage() {
    const {
        register,
        handleSubmit,
        setError,
        formState: { errors, isSubmitting },
    } = useForm();
    const registerUser = useRegister();
    const navigate = useNavigate();

    const onSubmit = async (values) => {
        try {
            await registerUser.mutateAsync(values);
            navigate('/dashboard', { replace: true });
        } catch (error) {
            const fieldErrs = fieldErrors(error);
            if (Object.keys(fieldErrs).length) {
                Object.entries(fieldErrs).forEach(([field, message]) =>
                    setError(field, { message })
                );
            } else {
                setError('root', { message: generalError(error) });
            }
        }
    };

    return (
        <div className="space-y-6">
            <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
                <div>
                    <label className="block text-sm font-medium text-gray-700">Name</label>
                    <input
                        type="text"
                        className="mt-1 w-full rounded border-gray-300 shadow-sm"
                        {...register('name', { required: true })}
                    />
                    <FormError message={errors.name?.message} />
                </div>
                <div>
                    <label className="block text-sm font-medium text-gray-700">Email</label>
                    <input
                        type="email"
                        className="mt-1 w-full rounded border-gray-300 shadow-sm"
                        {...register('email', { required: true })}
                    />
                    <FormError message={errors.email?.message} />
                </div>
                <div>
                    <label className="block text-sm font-medium text-gray-700">Password</label>
                    <input
                        type="password"
                        className="mt-1 w-full rounded border-gray-300 shadow-sm"
                        {...register('password', { required: true })}
                    />
                    <FormError message={errors.password?.message} />
                </div>
                <div>
                    <label className="block text-sm font-medium text-gray-700">
                        Confirm password
                    </label>
                    <input
                        type="password"
                        className="mt-1 w-full rounded border-gray-300 shadow-sm"
                        {...register('password_confirmation', { required: true })}
                    />
                    <FormError message={errors.password_confirmation?.message} />
                </div>
                <FormError message={errors.root?.message} />
                <button
                    type="submit"
                    disabled={isSubmitting}
                    className="w-full rounded bg-gray-900 py-2 text-white disabled:opacity-50"
                >
                    Register
                </button>
            </form>

            <p className="text-center text-sm text-gray-600">
                Already have an account?{' '}
                <Link to="/login" className="text-gray-900 underline">
                    Log in
                </Link>
            </p>
        </div>
    );
}
