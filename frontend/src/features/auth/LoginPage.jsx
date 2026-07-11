import { useForm } from 'react-hook-form';
import { Link, useLocation, useNavigate } from 'react-router-dom';
import FormError from '../../components/FormError';
import { fieldErrors, generalError } from '../../lib/apiErrors';
import { socialRedirectUrl } from '../../api/auth';
import { useLogin } from './useAuth';

export default function LoginPage() {
    const {
        register,
        handleSubmit,
        setError,
        formState: { errors, isSubmitting },
    } = useForm();
    const login = useLogin();
    const navigate = useNavigate();
    const location = useLocation();

    const onSubmit = async (values) => {
        try {
            await login.mutateAsync(values);
            navigate(location.state?.from?.pathname ?? '/dashboard', { replace: true });
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
                <FormError message={errors.root?.message} />
                <button
                    type="submit"
                    disabled={isSubmitting}
                    className="w-full rounded bg-gray-900 py-2 text-white disabled:opacity-50"
                >
                    Log in
                </button>
            </form>

            <div className="space-y-2">
                <a
                    href={socialRedirectUrl('google')}
                    className="block w-full rounded border py-2 text-center text-sm hover:bg-gray-50"
                >
                    Continue with Google
                </a>
                <a
                    href={socialRedirectUrl('github')}
                    className="block w-full rounded border py-2 text-center text-sm hover:bg-gray-50"
                >
                    Continue with GitHub
                </a>
            </div>

            <p className="text-center text-sm text-gray-600">
                Don't have an account?{' '}
                <Link to="/register" className="text-gray-900 underline">
                    Register
                </Link>
            </p>
        </div>
    );
}
