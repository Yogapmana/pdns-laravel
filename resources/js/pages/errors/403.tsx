import { Head, Link } from '@inertiajs/react';
import { ShieldAlert } from 'lucide-react';

type Props = { status: number; message?: string };

export default function Error403({ status, message }: Props) {
    return (
        <>
            <Head title="Akses Ditolak" />
            <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-navy to-primary p-4">
                <div className="bg-white rounded-2xl shadow-2xl p-8 max-w-md text-center">
                    <div className="inline-flex items-center justify-center w-16 h-16 bg-red-100 text-danger rounded-full mb-4">
                        <ShieldAlert className="h-8 w-8" />
                    </div>
                    <h1 className="text-3xl font-bold text-navy mb-2">Akses Ditolak</h1>
                    <p className="text-secondary mb-2">Error {status}: Anda tidak memiliki akses ke halaman ini.</p>
                    {message && <p className="text-sm text-muted-foreground mb-6">{message}</p>}
                    <Link
                        href="/redirect-by-role"
                        className="inline-flex items-center gap-2 px-6 py-3 bg-primary text-white rounded-xl font-semibold hover:bg-primary-700 transition"
                    >
                        Kembali ke Dashboard
                    </Link>
                </div>
            </div>
        </>
    );
}

Error403.layout = undefined;
