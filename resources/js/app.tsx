import { createInertiaApp } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import AuthLayout from '@/layouts/auth-layout';

const appName = import.meta.env.VITE_APP_NAME || 'SMAN 7 SOLO';

const GUEST_PAGES = ['welcome', 'errors/403'];

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        if (GUEST_PAGES.includes(name) || name.startsWith('auth/')) {
            return AuthLayout;
        }

        return AppLayout;
    },
    strictMode: true,
    progress: {
        color: '#1A56DB',
    },
});
