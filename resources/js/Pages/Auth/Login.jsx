import { Head, useForm } from '@inertiajs/react';
import { Lock } from 'lucide-react';
import BrandLogo from '@/Components/BrandLogo';
import Button from '@/Components/Button';
import FlashMessages from '@/Components/FlashMessages';
import ThemeToggle from '@/Components/ThemeToggle';

const inputClass =
    'block min-h-touch w-full rounded-xl border border-line bg-surface px-4 text-base text-ink placeholder:text-ink-muted/70 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30';

export default function Login() {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const submit = (event) => {
        event.preventDefault();
        post('/admin/login', { onFinish: () => reset('password') });
    };

    return (
        <div className="flex min-h-screen items-center justify-center bg-surface-sunken px-4 py-10">
            <Head title="Acceso al panel" />

            <div className="absolute right-4 top-4">
                <ThemeToggle />
            </div>

            <div className="w-full max-w-md">
                <div className="mb-8 flex justify-center">
                    <BrandLogo className="h-14 w-auto max-w-[14rem]" textClassName="text-2xl" />
                </div>

                <div className="rounded-2xl border border-line bg-surface-raised p-6 shadow-sm sm:p-8">
                    <div className="mb-6 flex items-center gap-3">
                        <span className="inline-flex h-10 w-10 items-center justify-center rounded-full bg-primary/10 text-primary">
                            <Lock className="h-5 w-5" aria-hidden="true" />
                        </span>
                        <div>
                            <h1 className="text-lg font-semibold">Acceso al panel</h1>
                            <p className="text-sm text-ink-muted">Ingresa con tu cuenta de administrador.</p>
                        </div>
                    </div>

                    <div className="mb-4">
                        <FlashMessages />
                    </div>

                    <form onSubmit={submit} className="space-y-5" noValidate>
                        <div>
                            <label htmlFor="email" className="mb-1.5 block text-sm font-medium">
                                Correo electrónico
                            </label>
                            <input
                                id="email"
                                type="email"
                                autoComplete="username"
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                                className={inputClass}
                                aria-invalid={!!errors.email}
                                aria-describedby={errors.email ? 'email-error' : undefined}
                                autoFocus
                            />
                            {errors.email && (
                                <p id="email-error" className="mt-1.5 text-sm text-red-600 dark:text-red-400">
                                    {errors.email}
                                </p>
                            )}
                        </div>

                        <div>
                            <label htmlFor="password" className="mb-1.5 block text-sm font-medium">
                                Contraseña
                            </label>
                            <input
                                id="password"
                                type="password"
                                autoComplete="current-password"
                                value={data.password}
                                onChange={(e) => setData('password', e.target.value)}
                                className={inputClass}
                                aria-invalid={!!errors.password}
                                aria-describedby={errors.password ? 'password-error' : undefined}
                            />
                            {errors.password && (
                                <p id="password-error" className="mt-1.5 text-sm text-red-600 dark:text-red-400">
                                    {errors.password}
                                </p>
                            )}
                        </div>

                        <label className="flex min-h-touch cursor-pointer items-center gap-3 text-sm">
                            <input
                                type="checkbox"
                                checked={data.remember}
                                onChange={(e) => setData('remember', e.target.checked)}
                                className="h-5 w-5 rounded border-line text-primary focus:ring-primary"
                            />
                            Mantener sesión iniciada
                        </label>

                        <Button type="submit" size="lg" className="w-full" disabled={processing}>
                            {processing ? 'Ingresando…' : 'Ingresar'}
                        </Button>
                    </form>
                </div>
            </div>
        </div>
    );
}
