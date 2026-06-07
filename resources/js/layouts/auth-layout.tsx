import type { ReactNode } from 'react';

export default function AuthLayout({ children }: { children: ReactNode }) {
    return (
        <div className="min-h-screen flex flex-col md:flex-row font-sans bg-white">
            <aside className="relative overflow-hidden bg-gradient-to-br from-navy via-navy-light to-primary text-white md:w-[42%] md:min-h-screen flex flex-col items-center justify-center px-8 py-16">
                <div className="absolute -top-24 -right-24 w-80 h-80 rounded-full bg-primary/25 blur-3xl pointer-events-none" />
                <div className="absolute -bottom-24 -left-24 w-80 h-80 rounded-full bg-navy/40 blur-3xl pointer-events-none" />

                <div className="relative z-10 flex flex-col items-center text-center">
                    <img
                        src="/brand/logo-sman7.png"
                        alt="Logo SMAN 7 Solo"
                        className="h-32 w-auto mb-6 drop-shadow-2xl"
                    />
                    <h1 className="text-3xl md:text-4xl font-extrabold tracking-wider mb-2">SMAN 7 SOLO</h1>
                    <p className="text-sm md:text-base text-blue-100 tracking-wide">Sekolah Menengah Atas</p>
                </div>

                <p className="relative z-10 mt-12 text-xs text-blue-200/60">© 2026 SMAN 7 Solo</p>
            </aside>

            <main className="flex-1 flex items-center justify-center bg-surface p-6 md:p-12">
                <div className="w-full max-w-md">{children}</div>
            </main>
        </div>
    );
}
