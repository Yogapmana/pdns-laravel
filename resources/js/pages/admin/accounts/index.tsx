import { Link, router } from '@inertiajs/react';
import { Plus, Power, KeyRound, Search, X, Loader2 } from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Modal } from '@/components/ui/modal';
import { PaginationFooter } from '@/components/ui/pagination';
import { Select } from '@/components/ui/select';
import { PageHeader, TableEmpty } from '@/components/ui/shared';
import { useFlashToast } from '@/hooks/use-flash-toast';
import { useInertiaSearch } from '@/hooks/use-inertia-search';

type Account = {
    id: number;
    username: string;
    name: string | null;
    role: 'admin' | 'guru' | 'siswa';
    is_active: boolean;
    siswa: { nis: string; nama_siswa: string } | null;
    guru: { id: number; nama_guru: string } | null;
};

type Paginated<T> = {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    from: number;
    to: number;
    total: number;
};

type Props = {
    accounts: Paginated<Account>;
    filters: { search: string | null; role: string | null };
};

const ROLE_LABELS: Record<string, string> = {
    admin: 'Admin',
    guru: 'Guru',
    siswa: 'Siswa',
};

const ROLE_VARIANTS: Record<string, 'default' | 'success' | 'info'> = {
    admin: 'default',
    guru: 'success',
    siswa: 'info',
};

export default function AccountsIndex({ accounts, filters }: Props) {
    useFlashToast();
    const { filters: state, loading, hasFilter, setFilter, reset } = useInertiaSearch({
        url: '/admin/akun',
        initialFilters: { search: filters.search, role: filters.role },
        only: ['accounts', 'filters'],
    });

    const [resetTarget, setResetTarget] = useState<Account | null>(null);
    const [newPassword, setNewPassword] = useState('');

    function toggleActive(a: Account) {
        router.patch(`/admin/akun/${a.id}/toggle-active`);
    }

    function doResetPassword() {
        if (!resetTarget || !newPassword) {
return;
}

        router.post(`/admin/akun/${resetTarget.id}/reset-password`, { password: newPassword }, {
            onSuccess: () => {
                setResetTarget(null);
                setNewPassword('');
            },
        });
    }

    return (
        <div>
            <PageHeader
                title="Manajemen Akun"
                description={`Total: ${accounts.total} akun`}
                action={
                    <Link href="/admin/akun/create">
                        <Button>
                            <Plus className="h-4 w-4" />
                            Buat Akun
                        </Button>
                    </Link>
                }
            />

            <Card className="p-0">
                <div className="p-4 border-b border-border flex flex-col md:flex-row gap-3">
                    <div className="relative flex-1">
                        <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                        <Input
                            type="search"
                            placeholder="Cari username atau nama..."
                            value={state.search}
                            onChange={(e) => setFilter('search', e.target.value)}
                            className="pl-9 pr-9"
                        />
                        {loading ? (
                            <Loader2 className="absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground animate-spin" />
                        ) : state.search ? (
                            <button
                                type="button"
                                onClick={() => setFilter('search', '')}
                                className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                                aria-label="Bersihkan pencarian"
                            >
                                <X className="h-4 w-4" />
                            </button>
                        ) : null}
                    </div>
                    <Select value={state.role} onChange={(e) => setFilter('role', e.target.value)} className="md:w-48">
                        <option value="">Semua Role</option>
                        <option value="admin">Admin</option>
                        <option value="guru">Guru</option>
                        <option value="siswa">Siswa</option>
                    </Select>
                    {hasFilter && (
                        <Button onClick={reset} variant="outline">
                            <X className="h-4 w-4" />
                            Reset
                        </Button>
                    )}
                </div>

                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="bg-primary text-white">
                                <th className="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide">Username</th>
                                <th className="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide">Nama</th>
                                <th className="px-4 py-3 text-center text-xs font-bold uppercase tracking-wide">Role</th>
                                <th className="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide">Profil Terkait</th>
                                <th className="px-4 py-3 text-center text-xs font-bold uppercase tracking-wide">Status</th>
                                <th className="px-4 py-3 text-center text-xs font-bold uppercase tracking-wide">Aksi</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {accounts.data.length === 0 ? (
                                <TableEmpty message="Tidak ada akun." colSpan={6} />
                            ) : (
                                accounts.data.map((a, i) => (
                                    <tr key={a.id} className={`${i % 2 === 0 ? 'bg-white' : 'bg-surface'} hover:bg-blue-50 transition-colors`}>
                                        <td className="px-4 py-3 font-mono text-sm">{a.username}</td>
                                        <td className="px-4 py-3">{a.name ?? '—'}</td>
                                        <td className="px-4 py-3 text-center">
                                            <Badge variant={ROLE_VARIANTS[a.role]}>{ROLE_LABELS[a.role]}</Badge>
                                        </td>
                                        <td className="px-4 py-3 text-xs">
                                            {a.role === 'siswa' && a.siswa && (
                                                <span>Siswa: <strong>{a.siswa.nama_siswa}</strong> ({a.siswa.nis})</span>
                                            )}
                                            {a.role === 'guru' && a.guru && (
                                                <span>Guru: <strong>{a.guru.nama_guru}</strong></span>
                                            )}
                                            {a.role === 'admin' && <span className="text-muted-foreground">—</span>}
                                        </td>
                                        <td className="px-4 py-3 text-center">
                                            {a.is_active ? <Badge variant="success">Aktif</Badge> : <Badge variant="warning">Nonaktif</Badge>}
                                        </td>
                                        <td className="px-4 py-3 text-center">
                                            <div className="flex items-center justify-center gap-1">
                                                <button
                                                    type="button"
                                                    onClick={() => setResetTarget(a)}
                                                    className="p-1.5 text-primary hover:bg-blue-100 rounded transition"
                                                    aria-label={`Reset password ${a.username}`}
                                                    title="Reset Password"
                                                >
                                                    <KeyRound className="h-4 w-4" />
                                                </button>
                                                <button
                                                    type="button"
                                                    onClick={() => toggleActive(a)}
                                                    className={`p-1.5 rounded transition ${a.is_active ? 'text-warning hover:bg-yellow-100' : 'text-success hover:bg-green-100'}`}
                                                    aria-label={a.is_active ? `Nonaktifkan ${a.username}` : `Aktifkan ${a.username}`}
                                                    title={a.is_active ? 'Nonaktifkan' : 'Aktifkan'}
                                                >
                                                    <Power className="h-4 w-4" />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>

                <PaginationFooter from={accounts.from} to={accounts.to} total={accounts.total} links={accounts.links} />
            </Card>

            <Modal
                open={!!resetTarget}
                onClose={() => {
 setResetTarget(null); setNewPassword(''); 
}}
                title="Reset Password"
                description={`Reset password untuk akun ${resetTarget?.username ?? ''}.`}
                footer={
                    <>
                        <Button variant="outline" onClick={() => {
 setResetTarget(null); setNewPassword(''); 
}}>Batal</Button>
                        <Button variant="primary" onClick={doResetPassword} disabled={!newPassword || newPassword.length < 6}>
                            Reset
                        </Button>
                    </>
                }
            >
                <div>
                    <label htmlFor="new_password" className="block text-sm font-medium text-secondary mb-2">
                        Password Baru <span className="text-danger">*</span>
                    </label>
                    <Input
                        id="new_password"
                        type="text"
                        value={newPassword}
                        onChange={(e) => setNewPassword(e.target.value)}
                        placeholder="Minimal 6 karakter"
                    />
                    <p className="text-xs text-muted-foreground mt-1">Minimal 6 karakter.</p>
                </div>
            </Modal>
        </div>
    );
}

AccountsIndex.layout = { title: 'Manajemen Akun' };
