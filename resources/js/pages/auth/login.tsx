import { Form, Head, usePage } from '@inertiajs/react';
import { Eye, EyeOff, User, Lock, ArrowRight } from 'lucide-react';
import { useState } from 'react';
import { Alert } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { InputError } from '@/components/ui/shared';

const CURRENT_YEAR = new Date().getFullYear();

type Props = { status?: string };

export default function Login({ status }: Props) {
    const { props } = usePage<{
        errors: { username?: string; password?: string; credentials?: string };
    }>();
    const errors = props.errors ?? {};
    const [showPassword, setShowPassword] = useState(false);

    return (
        <>
            <Head title="Masuk · SMAN 7 Solo" />

            <div className="w-full">
                <div className="flex flex-col items-center mb-8">
                    <h2 className="text-2xl font-bold text-navy tracking-wide">Masuk ke Sistem</h2>
                    <p className="text-sm text-muted-foreground mt-1">Sistem Informasi Akademik</p>
                </div>

                {status && <Alert variant="info" className="mb-5">{status}</Alert>}
                {errors.credentials && <Alert variant="error" className="mb-5">Username atau password salah.</Alert>}

                <Form action="/login" method="post" resetOnSuccess={['password']} className="space-y-4">
                    {({ processing }) => (
                        <>
                            <div>
                                <Label htmlFor="username">Username</Label>
                                <div className="relative">
                                    <User className="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-muted-foreground pointer-events-none" />
                                    <Input
                                        id="username"
                                        type="text"
                                        name="username"
                                        required
                                        autoFocus
                                        autoComplete="username"
                                        placeholder="Masukkan username"
                                        className="pl-12 pr-4 py-3 text-base"
                                    />
                                </div>
                                <InputError message={errors.username} />
                            </div>

                            <div>
                                <Label htmlFor="password">Password</Label>
                                <div className="relative">
                                    <Lock className="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-muted-foreground pointer-events-none" />
                                    <Input
                                        id="password"
                                        type={showPassword ? 'text' : 'password'}
                                        name="password"
                                        required
                                        autoComplete="current-password"
                                        placeholder="Masukkan password"
                                        className="pl-12 pr-12 py-3 text-base"
                                    />
                                    <button
                                        type="button"
                                        onClick={() => setShowPassword((s) => !s)}
                                        className="absolute inset-y-0 right-0 flex items-center px-4 text-muted-foreground hover:text-foreground"
                                        aria-label={showPassword ? 'Sembunyikan password' : 'Tampilkan password'}
                                        tabIndex={-1}
                                    >
                                        {showPassword ? <EyeOff className="h-5 w-5" /> : <Eye className="h-5 w-5" />}
                                    </button>
                                </div>
                                <InputError message={errors.password} />
                            </div>

                            <div className="flex items-center text-sm pt-1">
                                <label className="flex items-center gap-2 text-secondary cursor-pointer select-none">
                                    <input
                                        type="checkbox"
                                        name="remember"
                                        value="true"
                                        className="h-4 w-4 rounded border-border text-primary focus:ring-2 focus:ring-primary"
                                    />
                                    Ingat saya
                                </label>
                            </div>

                            <Button type="submit" disabled={processing} className="w-full" size="lg">
                                {processing ? 'Memproses...' : (
                                    <>
                                        MASUK
                                        <ArrowRight className="h-4 w-4" />
                                    </>
                                )}
                            </Button>
                        </>
                    )}
                </Form>

                <p className="text-center text-xs text-muted-foreground mt-8">
                    © {CURRENT_YEAR} SMAN 7 Solo · Sistem Informasi Akademik
                </p>
            </div>
        </>
    );
}

Login.layout = undefined;
