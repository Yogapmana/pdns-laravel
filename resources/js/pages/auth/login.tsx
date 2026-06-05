import { Form, Head, usePage } from '@inertiajs/react';
import { Eye, EyeOff, GraduationCap } from 'lucide-react';
import { useState } from 'react';
import { Alert } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

type Props = { status?: string };

export default function Login({ status }: Props) {
    const { props } = usePage<{ errors: { username?: string; password?: string; credentials?: string } }>();
    const errors = props.errors ?? {};
    const [showPassword, setShowPassword] = useState(false);

    return (
        <>
            <Head title="Login" />

            <div className="bg-white rounded-2xl shadow-2xl p-8">
                <div className="text-center mb-6">
                    <div className="inline-flex items-center justify-center w-14 h-14 bg-primary rounded-xl mb-3">
                        <GraduationCap className="h-7 w-7 text-white" />
                    </div>
                    <h1 className="text-2xl font-bold text-navy">SMAN 7 Solo</h1>
                    <p className="text-sm text-muted-foreground mt-1">Sistem Manajemen Akademik</p>
                </div>

                {status && <Alert variant="info">{status}</Alert>}
                {errors.credentials && <Alert variant="error">Username atau password salah.</Alert>}

                <Form
                    action="/login"
                    method="post"
                    resetOnSuccess={['password']}
                    className="space-y-4"
                >
                    {({ processing }) => (
                        <>
                            <div>
                                <label htmlFor="username" className="block text-sm font-medium text-secondary mb-2">
                                    Username
                                </label>
                                <Input
                                    id="username"
                                    type="text"
                                    name="username"
                                    required
                                    autoFocus
                                    autoComplete="username"
                                    placeholder="Masukkan username"
                                />
                                {errors.username && (
                                    <p className="mt-1 text-xs text-danger">{errors.username}</p>
                                )}
                            </div>

                            <div>
                                <label htmlFor="password" className="block text-sm font-medium text-secondary mb-2">
                                    Password
                                </label>
                                <div className="relative">
                                    <Input
                                        id="password"
                                        type={showPassword ? 'text' : 'password'}
                                        name="password"
                                        required
                                        autoComplete="current-password"
                                        placeholder="Masukkan password"
                                        className="pr-10"
                                    />
                                    <button
                                        type="button"
                                        onClick={() => setShowPassword((s) => !s)}
                                        className="absolute inset-y-0 right-0 flex items-center px-3 text-muted-foreground hover:text-foreground"
                                        aria-label={showPassword ? 'Sembunyikan password' : 'Tampilkan password'}
                                        tabIndex={-1}
                                    >
                                        {showPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                                    </button>
                                </div>
                                {errors.password && (
                                    <p className="mt-1 text-xs text-danger">{errors.password}</p>
                                )}
                            </div>

                            <Button type="submit" disabled={processing} className="w-full" size="lg">
                                {processing ? 'Memproses...' : 'MASUK'}
                            </Button>
                        </>
                    )}
                </Form>

                <p className="text-center text-xs text-muted-foreground mt-6">
                    &copy; {new Date().getFullYear()} SMAN 7 Solo. Sistem Informasi Akademik.
                </p>
            </div>
        </>
    );
}

Login.layout = undefined;
