import { Head, Link, router, usePage } from '@inertiajs/react';
import { LayoutDashboard, Users, GraduationCap, UserCog, FileText, LogOut, BookOpen, ClipboardList, BarChart3, BookOpenCheck } from 'lucide-react';
import type { ReactNode } from 'react';
import { Toaster } from '@/components/ui/sonner';
import { cn } from '@/lib/utils';

type NavItem = {
    label: string;
    href: string;
    icon: ReactNode;
    active?: boolean;
};

type User = {
    id: number;
    username: string;
    name: string | null;
    role: 'admin' | 'guru' | 'siswa';
};

type PageProps = {
    auth: { user: User };
    flash?: { success?: string; error?: string };
};

const NAV_ADMIN: NavItem[] = [
    { label: 'Dashboard', href: '/admin/dashboard', icon: <LayoutDashboard className="h-4 w-4" /> },
    { label: 'Manajemen Siswa', href: '/admin/siswa', icon: <Users className="h-4 w-4" /> },
    { label: 'Manajemen Guru', href: '/admin/guru', icon: <GraduationCap className="h-4 w-4" /> },
    { label: 'Manajemen Akun', href: '/admin/akun', icon: <UserCog className="h-4 w-4" /> },
    { label: 'Laporan', href: '/admin/laporan', icon: <FileText className="h-4 w-4" /> },
];

const NAV_GURU: NavItem[] = [
    { label: 'Dashboard', href: '/guru/dashboard', icon: <LayoutDashboard className="h-4 w-4" /> },
    { label: 'Input Nilai', href: '/guru/input-nilai', icon: <ClipboardList className="h-4 w-4" /> },
    { label: 'Rekap Nilai', href: '/guru/rekap', icon: <BarChart3 className="h-4 w-4" /> },
];

const NAV_SISWA: NavItem[] = [
    { label: 'Dashboard', href: '/siswa/dashboard', icon: <LayoutDashboard className="h-4 w-4" /> },
    { label: 'Nilai Saya', href: '/siswa/nilai', icon: <BookOpenCheck className="h-4 w-4" /> },
];

function getNav(role: string, currentPath: string): NavItem[] {
    const base = role === 'admin' ? NAV_ADMIN : role === 'guru' ? NAV_GURU : NAV_SISWA;

    return base.map((item) => ({ ...item, active: currentPath.startsWith(item.href) }));
}

function getPageTitle(path: string, role: string): string {
    if (role === 'admin') {
        if (path.includes('/admin/siswa')) {
return 'Manajemen Siswa';
}

        if (path.includes('/admin/guru')) {
return 'Manajemen Guru';
}

        if (path.includes('/admin/akun')) {
return 'Manajemen Akun';
}

        if (path.includes('/admin/laporan')) {
return 'Laporan Nilai';
}

        return 'Dashboard';
    }

    if (role === 'guru') {
        if (path.includes('/guru/input-nilai')) {
return 'Input Nilai';
}

        if (path.includes('/guru/rekap')) {
return 'Rekap Nilai';
}

        return 'Dashboard';
    }

    if (path.includes('/siswa/nilai')) {
return 'Nilai Saya';
}

    return 'Dashboard';
}

export default function AppLayout({ children, title }: { children: ReactNode; title?: string }) {
    const { props, url } = usePage<PageProps>();
    const user = props.auth?.user;

    if (!user) {
        return (
            <>
                <Head title="Memuat..." />
                <div className="flex h-screen items-center justify-center bg-surface">
                    <p className="text-muted-foreground">Memuat...</p>
                </div>
            </>
        );
    }

    const nav = getNav(user.role, url);
    const pageTitle = title ?? getPageTitle(url, user.role);
    const roleLabel = user.role === 'admin' ? 'Admin' : user.role === 'guru' ? 'Guru' : 'Siswa';

    function logout() {
        router.post('/logout');
    }

    return (
        <>
            <Head title={pageTitle} />
            <div className="flex h-screen bg-surface font-sans">
                <aside className="w-64 bg-navy text-white flex-shrink-0 fixed h-full flex flex-col z-30">
                    <div className="px-6 py-5 border-b border-navy-light">
                        <p className="text-lg font-bold tracking-tight flex items-center gap-2">
                            <BookOpen className="h-5 w-5" />
                            SMAN 7 Solo
                        </p>
                        <p className="text-xs text-navy-300 mt-0.5">Sistem Akademik</p>
                    </div>

                    <nav className="flex-1 p-4 space-y-1 overflow-y-auto">
                        {nav.map((item) => (
                            <Link
                                key={item.href}
                                href={item.href}
                                className={cn(
                                    'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition',
                                    item.active
                                        ? 'bg-primary text-white'
                                        : 'text-navy-300 hover:bg-navy-light hover:text-white',
                                )}
                            >
                                {item.icon}
                                {item.label}
                            </Link>
                        ))}
                    </nav>

                    <div className="p-4 border-t border-navy-light">
                        <p className="text-sm font-medium truncate">{user.name ?? user.username}</p>
                        <p className="text-xs text-navy-300 capitalize">{roleLabel}</p>
                        <button
                            type="button"
                            onClick={logout}
                            className="w-full text-left text-xs text-navy-300 hover:text-white transition mt-3 flex items-center gap-1.5"
                        >
                            <LogOut className="h-3 w-3" />
                            Keluar
                        </button>
                    </div>
                </aside>

                <main className="ml-64 flex-1 flex flex-col min-h-screen overflow-hidden">
                    <header className="bg-white border-b border-border px-6 py-4 flex items-center justify-between flex-shrink-0">
                        <p className="text-sm text-muted-foreground">
                            {new Date().toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })}
                        </p>
                    </header>

                    <div className="flex-1 overflow-y-auto p-6">{children}</div>
                </main>
            </div>
            <Toaster />
        </>
    );
}
