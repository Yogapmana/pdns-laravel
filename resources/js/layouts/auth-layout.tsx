import type { ReactNode } from 'react';

export default function AuthLayout({ children }: { children: ReactNode }) {
    return (
        <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-navy to-primary p-4 font-sans">
            <div className="w-full max-w-md">{children}</div>
        </div>
    );
}
