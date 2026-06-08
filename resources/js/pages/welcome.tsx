import { Head, Link } from '@inertiajs/react';
import { store as loginStore } from '@/routes/login';

export default function Welcome() {
    return (
        <>
            <Head title="Selamat Datang" />
            <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-navy to-primary p-4">
                <div className="text-center text-white max-w-md">
                    <img
                        src="/brand/logo-sman7.png"
                        alt="Logo SMAN 7 Solo"
                        className="h-28 w-28 mb-4 mx-auto object-contain drop-shadow-md"
                    />
                    <h1 className="text-4xl font-bold mb-2 tracking-wide">SMAN 7 SOLO</h1>
                    <p className="text-lg text-blue-100 mb-8">Sistem Manajemen Akademik</p>
                    <Link
                        href={loginStore.url()}
                        className="inline-flex items-center gap-2 px-6 py-3 bg-white text-primary rounded-xl font-semibold hover:bg-blue-50 transition"
                    >
                        Masuk ke Sistem
                    </Link>
                </div>
            </div>
        </>
    );
}
