import { Head, Link } from '@inertiajs/react';
import { GraduationCap } from 'lucide-react';

export default function Welcome() {
    return (
        <>
            <Head title="Selamat Datang" />
            <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-navy to-primary p-4">
                <div className="text-center text-white max-w-md">
                    <div className="inline-flex items-center justify-center w-16 h-16 bg-white/10 backdrop-blur rounded-2xl mb-4">
                        <GraduationCap className="h-8 w-8" />
                    </div>
                    <h1 className="text-4xl font-bold mb-2">NilaiSiswa</h1>
                    <p className="text-lg text-blue-100 mb-8">Sistem Manajemen Akademik</p>
                    <Link
                        href="/login"
                        className="inline-flex items-center gap-2 px-6 py-3 bg-white text-primary rounded-xl font-semibold hover:bg-blue-50 transition"
                    >
                        Masuk ke Sistem
                    </Link>
                </div>
            </div>
        </>
    );
}
