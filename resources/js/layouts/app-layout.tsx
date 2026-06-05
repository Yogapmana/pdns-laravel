import { Head, Link, router, usePage } from '@inertiajs/react';
import { LayoutDashboard, Users, GraduationCap, UserCog, FileText, LogOut, BookOpen, ClipboardList, BarChart3, BookOpenCheck, PanelLeftClose, PanelLeftOpen, School, Library } from 'lucide-react';
import {  useEffect, useState } from 'react';
import type {ReactNode} from 'react';
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
    { label: 'Manajemen Kelas', href: '/admin/kelas', icon: <School className="h-4 w-4" /> },
    { label: 'Mata Pelajaran', href: '/admin/mata-pelajaran', icon: <Library className="h-4 w-4" /> },
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

        if (path.includes('/admin/kelas')) {
return 'Manajemen Kelas';
}

        if (path.includes('/admin/mata-pelajaran')) {
return 'Mata Pelajaran';
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

const SIDEBAR_KEY = 'pdns-sidebar-collapsed';
const SIDEBAR_WIDTH = 'w-64';
const SIDEBAR_WIDTH_COLLAPSED = 'w-16';
const SIDEBAR_TRANSITION = 'transition-[width] duration-300 ease-in-out';

export default function AppLayout({ children, title }: { children: ReactNode; title?: string }) {
    const { props, url } = usePage<PageProps>();
    const user = props.auth?.user;
    const [collapsed, setCollapsed] = useState<boolean>(() => {
        if (typeof window === 'undefined') {
return false;
}

        return window.localStorage.getItem(SIDEBAR_KEY) === 'true';
    });

    useEffect(() => {
        window.localStorage.setItem(SIDEBAR_KEY, String(collapsed));
    }, [collapsed]);

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
    const userInitial = (user.name ?? user.username).charAt(0).toUpperCase();

    function logout() {
        router.post('/logout');
    }

    return (
        <>
            <Head title={pageTitle} />
            <div className="flex h-screen bg-surface font-sans">
                <aside
                    className={cn(
                        SIDEBAR_TRANSITION,
                        'bg-navy text-white flex-shrink-0 fixed h-full flex flex-col z-30 overflow-hidden',
                        collapsed ? SIDEBAR_WIDTH_COLLAPSED : SIDEBAR_WIDTH,
                    )}
                    aria-expanded={!collapsed}
                >
                    <div className={cn('flex items-center border-b border-navy-light flex-shrink-0', collapsed ? 'px-3 py-5 justify-center' : 'px-6 py-5 justify-between')}>
                        {collapsed ? (
                            <button
                                type="button"
                                onClick={() => setCollapsed(false)}
                                className="text-navy-300 hover:text-white transition p-1.5 rounded hover:bg-navy-light"
                                aria-label="Buka sidebar"
                                title="Buka sidebar"
                            >
                                <PanelLeftOpen className="h-4 w-4" />
                            </button>
                        ) : (
                            <>
                                <div className="min-w-0">
                                    <p className="text-lg font-bold tracking-tight flex items-center gap-2 truncate">
                                        <BookOpen className="h-5 w-5 shrink-0" />
                                        <span className="truncate">SMAN 7 Solo</span>
                                    </p>
                                    <p className="text-xs text-navy-300 mt-0.5 truncate">Sistem Akademik</p>
                                </div>
                                <button
                                    type="button"
                                    onClick={() => setCollapsed(true)}
                                    className="text-navy-300 hover:text-white transition p-1 rounded hover:bg-navy-light shrink-0"
                                    aria-label="Tutup sidebar"
                                    title="Tutup sidebar"
                                >
                                    <PanelLeftClose className="h-4 w-4" />
                                </button>
                            </>
                        )}
                    </div>

                    <nav className="flex-1 p-3 space-y-1 overflow-y-auto overflow-x-hidden">
                        {nav.map((item) => (
                            <Link
                                key={item.href}
                                href={item.href}
                                title={collapsed ? item.label : undefined}
                                className={cn(
                                    'flex items-center px-3 py-2.5 rounded-lg text-sm font-medium transition',
                                    collapsed ? 'justify-center gap-0 px-2' : 'gap-3',
                                    item.active
                                        ? 'bg-primary text-white'
                                        : 'text-navy-300 hover:bg-navy-light hover:text-white',
                                )}
                            >
                                <span className="shrink-0">{item.icon}</span>
                                <span
                                    className={cn(
                                        'whitespace-nowrap transition-all duration-300 ease-in-out overflow-hidden',
                                        collapsed ? 'max-w-0 opacity-0' : 'max-w-[200px] opacity-100',
                                    )}
                                >
                                    {item.label}
                                </span>
                            </Link>
                        ))}
                    </nav>

                    <div className={cn('border-t border-navy-light flex-shrink-0', collapsed ? 'p-3' : 'p-4')}>
                        {collapsed ? (
                            <div className="flex flex-col items-center gap-3">
                                <div
                                    className="h-9 w-9 rounded-full bg-primary text-white text-sm font-bold flex items-center justify-center shrink-0"
                                    title={user.name ?? user.username}
                                >
                                    {userInitial}
                                </div>
                                <button
                                    type="button"
                                    onClick={logout}
                                    title="Keluar"
                                    className="text-navy-300 hover:text-white transition p-1.5 rounded hover:bg-navy-light"
                                    aria-label="Keluar"
                                >
                                    <LogOut className="h-4 w-4" />
                                </button>
                            </div>
                        ) : (
                            <>
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
                            </>
                        )}
                    </div>
                </aside>

                <main
                    className={cn(
                        'flex-1 flex flex-col min-h-screen overflow-hidden transition-[margin] duration-300 ease-in-out',
                        collapsed ? 'ml-16' : 'ml-64',
                    )}
                >
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
