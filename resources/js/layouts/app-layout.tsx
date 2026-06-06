import { Head, Link, router, usePage } from '@inertiajs/react';
import { LayoutDashboard, Users, GraduationCap, UserCog, FileText, LogOut, BookOpen, ClipboardList, BarChart3, BookOpenCheck, PanelLeftClose, PanelLeftOpen, School, Library, ClipboardCheck, Menu, X, Search, Calendar } from 'lucide-react';
import { useState, useSyncExternalStore } from 'react';
import type {ReactNode} from 'react';
import { Toaster } from '@/components/ui/sonner';
import { cn } from '@/lib/utils';
import { NotificationBell } from '@/components/notification-bell';

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
    { label: 'Manajemen Nilai', href: '/admin/nilai', icon: <ClipboardCheck className="h-4 w-4" /> },
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
    { label: 'Statistik', href: '/siswa/statistik', icon: <BarChart3 className="h-4 w-4" /> },
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

        if (path.includes('/admin/nilai')) {
return 'Manajemen Nilai';
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
const SIDEBAR_EVENT = 'pdns-sidebar-change';
const SIDEBAR_TRANSITION = 'transition-[width] duration-300 ease-in-out';

const sidebarSubscribers = new Set<() => void>();

function notifySidebarSubscribers(): void {
    sidebarSubscribers.forEach((cb) => cb());
}

function subscribeSidebar(callback: () => void): () => void {
    if (typeof window === 'undefined') {
        return () => {};
    }

    sidebarSubscribers.add(callback);
    window.addEventListener('storage', callback);
    window.addEventListener(SIDEBAR_EVENT, callback);

    return () => {
        sidebarSubscribers.delete(callback);
        window.removeEventListener('storage', callback);
        window.removeEventListener(SIDEBAR_EVENT, callback);
    };
}

function getServerSidebarSnapshot(): boolean {
    return false;
}

function getClientSidebarSnapshot(): boolean {
    return window.localStorage.getItem(SIDEBAR_KEY) === 'true';
}

const DATE_FORMATTER = new Intl.DateTimeFormat('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });

const noopSubscribe = (): (() => void) => () => {};

const getServerToday = (): string => '';

const getClientToday = (): string => DATE_FORMATTER.format(new Date());

function logout() {
    router.post('/logout');
}

export default function AppLayout({ children, title }: { children: ReactNode; title?: string }) {
    const { props, url } = usePage<PageProps>();
    const user = props.auth?.user;
    const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
    const collapsed = useSyncExternalStore(
        subscribeSidebar,
        getClientSidebarSnapshot,
        getServerSidebarSnapshot,
    );
    const today = useSyncExternalStore(noopSubscribe, getClientToday, getServerToday);

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

    function toggleCollapsed() {
        const next = !collapsed;
        window.localStorage.setItem(SIDEBAR_KEY, String(next));
        notifySidebarSubscribers();
    }

    function closeMobileMenu() {
        setMobileMenuOpen(false);
    }

    return (
        <>
            <Head title={pageTitle} />
            <div className="flex h-screen bg-surface font-sans">
                {mobileMenuOpen && (
                    <div
                        className="fixed inset-0 bg-black/50 z-30 md:hidden"
                        onClick={closeMobileMenu}
                        aria-hidden="true"
                    />
                )}

                <aside
                    className={cn(
                        SIDEBAR_TRANSITION,
                        'bg-navy text-white flex-shrink-0 fixed h-full flex flex-col z-40 overflow-hidden',
                        mobileMenuOpen ? 'translate-x-0 w-64' : '-translate-x-full w-64',
                        'md:translate-x-0',
                        collapsed ? 'md:w-16' : 'md:w-64',
                    )}
                >
                    <div className={cn('flex items-center border-b border-navy-light flex-shrink-0 h-[69px]', collapsed ? 'px-3 justify-center' : 'px-6 justify-between')}>
                        {collapsed ? (
                            <button
                                type="button"
                                onClick={toggleCollapsed}
                                className="text-navy-300 hover:text-white transition p-1.5 rounded hover:bg-navy-light hidden md:block"
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
                                </div>
                                <button
                                    type="button"
                                    onClick={toggleCollapsed}
                                    className="text-navy-300 hover:text-white transition p-1 rounded hover:bg-navy-light shrink-0 hidden md:block"
                                    aria-label="Tutup sidebar"
                                    title="Tutup sidebar"
                                >
                                    <PanelLeftClose className="h-4 w-4" />
                                </button>
                                <button
                                    type="button"
                                    onClick={closeMobileMenu}
                                    className="text-navy-300 hover:text-white transition p-1 rounded hover:bg-navy-light shrink-0 md:hidden"
                                    aria-label="Tutup menu"
                                    title="Tutup menu"
                                >
                                    <X className="h-5 w-5" />
                                </button>
                            </>
                        )}
                    </div>

                    <nav className="flex-1 p-3 space-y-1 overflow-y-auto overflow-x-hidden">
                        {nav.map((item) => (
                            <Link
                                key={item.href}
                                href={item.href}
                                onClick={closeMobileMenu}
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
                        'ml-0',
                        collapsed ? 'md:ml-16' : 'md:ml-64',
                    )}
                >
                    <header className="bg-white border-b border-border px-4 md:px-6 h-[69px] flex items-center justify-between flex-shrink-0 gap-4">
                        <div className="flex items-center gap-4">
                            <button
                                type="button"
                                onClick={() => setMobileMenuOpen(true)}
                                className="md:hidden p-1.5 -ml-1.5 text-navy hover:bg-slate-100 rounded-md"
                                aria-label="Buka menu"
                            >
                                <Menu className="h-5 w-5" />
                            </button>
                            <div className="hidden md:flex items-center">
                                <span className="text-sm font-semibold text-navy/70 uppercase tracking-widest">Sistem Akademik</span>
                            </div>
                            <p className="text-sm font-semibold text-navy md:hidden truncate">
                                {pageTitle}
                            </p>
                        </div>
                        
                        <div className="flex items-center gap-1 md:gap-3">
                            <button type="button" className="md:hidden text-muted-foreground hover:text-navy transition p-2 rounded-full hover:bg-slate-100" aria-label="Cari" title="Cari">
                                <Search className="h-4 w-4" />
                            </button>

                            <NotificationBell />

                            <div className="w-px h-6 bg-border hidden md:block mx-1"></div>
                            
                            <div className="hidden md:flex items-center gap-2 text-sm text-navy font-medium bg-slate-50 px-3 py-1.5 rounded-lg border border-slate-100">
                                <Calendar className="h-4 w-4 text-primary" />
                                {today}
                            </div>
                        </div>
                    </header>

                    <div className="flex-1 overflow-y-auto p-6">{children}</div>
                </main>
            </div>
            <Toaster />
        </>
    );
}
