import { Link, router } from '@inertiajs/react';
import { Plus, Edit, Trash2, Search, X, Power, UserPlus, Loader2 } from 'lucide-react';
import { useRef, useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Modal } from '@/components/ui/modal';
import { PaginationFooter } from '@/components/ui/pagination';
import { Select } from '@/components/ui/select';
import { PageHeader, TableEmpty } from '@/components/ui/shared';
import { useFlashToast } from '@/hooks/use-flash-toast';

type Mengajar = {
    id: number;
    kelas: string;
    mata_pelajaran: string;
};

type Guru = {
    id: number;
    nama_guru: string;
    mengajar: Mengajar[];
    nilai_count: number;
    user: { username: string; is_active: boolean } | null;
};

type Paginated<T> = {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    from: number;
    to: number;
    total: number;
};

type Props = {
    guru: Paginated<Guru>;
    daftar_kelas: string[];
    daftar_mapel: string[];
    filters: { search: string | null; kelas: string | null; mapel: string | null };
};

export default function GuruIndex({ guru, daftar_kelas, daftar_mapel, filters }: Props) {
    useFlashToast();
    const [search, setSearch] = useState(filters.search ?? '');
    const [kelas, setKelas] = useState(filters.kelas ?? '');
    const [mapel, setMapel] = useState(filters.mapel ?? '');
    const [deleteTarget, setDeleteTarget] = useState<Guru | null>(null);
    const [loading, setLoading] = useState(false);
    const debounceRef = useRef<ReturnType<typeof setTimeout> | null>(null);

    function triggerSearch(nextSearch: string, nextKelas: string, nextMapel: string) {
        if (debounceRef.current) {
clearTimeout(debounceRef.current);
}

        debounceRef.current = setTimeout(() => {
            const params: Record<string, string> = {};

            if (nextSearch) {
params.search = nextSearch;
}

            if (nextKelas) {
params.kelas = nextKelas;
}

            if (nextMapel) {
params.mapel = nextMapel;
}

            setLoading(true);
            router.get('/admin/guru', params, {
                preserveState: true,
                replace: true,
                only: ['guru', 'filters'],
                preserveScroll: true,
                onFinish: () => setLoading(false),
            });
        }, 300);
    }

    function onSearchChange(value: string) {
        setSearch(value);
        triggerSearch(value, kelas, mapel);
    }

    function onKelasChange(value: string) {
        setKelas(value);
        triggerSearch(search, value, mapel);
    }

    function onMapelChange(value: string) {
        setMapel(value);
        triggerSearch(search, kelas, value);
    }

    function clearFilters() {
        if (debounceRef.current) {
clearTimeout(debounceRef.current);
}

        setSearch('');
        setKelas('');
        setMapel('');
        setLoading(true);
        router.get('/admin/guru', {}, {
            preserveState: true,
            replace: true,
            only: ['guru', 'filters'],
            preserveScroll: true,
            onFinish: () => setLoading(false),
        });
    }

    function doDelete() {
        if (!deleteTarget) {
return;
}

        router.delete(`/admin/guru/${deleteTarget.id}`, {
            onSuccess: () => setDeleteTarget(null),
        });
    }

    function toggleActive(g: Guru) {
        router.patch(`/admin/guru/${g.id}/toggle-active`);
    }

    const hasFilter = !!search || !!kelas || !!mapel;

    return (
        <div>
            <PageHeader
                title="Manajemen Guru"
                description={`Total: ${guru.total} guru`}
                action={
                    <Link href="/admin/guru/create">
                        <Button>
                            <Plus className="h-4 w-4" />
                            Tambah Guru
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
                            placeholder="Cari nama guru..."
                            value={search}
                            onChange={(e) => onSearchChange(e.target.value)}
                            className="pl-9 pr-9"
                        />
                        {loading ? (
                            <Loader2 className="absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground animate-spin" />
                        ) : hasFilter ? (
                            <button
                                type="button"
                                onClick={() => onSearchChange('')}
                                className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                                aria-label="Bersihkan pencarian"
                            >
                                <X className="h-4 w-4" />
                            </button>
                        ) : null}
                    </div>
                    <Select value={kelas} onChange={(e) => onKelasChange(e.target.value)} className="md:w-40">
                        <option value="">Semua Kelas</option>
                        {daftar_kelas.map((k) => (
                            <option key={k} value={k}>{k}</option>
                        ))}
                    </Select>
                    <Select value={mapel} onChange={(e) => onMapelChange(e.target.value)} className="md:w-56">
                        <option value="">Semua Mata Pelajaran</option>
                        {daftar_mapel.map((m) => (
                            <option key={m} value={m}>{m}</option>
                        ))}
                    </Select>
                    {hasFilter && (
                        <Button onClick={clearFilters} variant="outline">
                            <X className="h-4 w-4" />
                            Reset
                        </Button>
                    )}
                </div>

                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="bg-primary text-white">
                                <th className="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide">Nama Guru</th>
                                <th className="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide">Mengajar</th>
                                <th className="px-4 py-3 text-center text-xs font-bold uppercase tracking-wide">Username</th>
                                <th className="px-4 py-3 text-center text-xs font-bold uppercase tracking-wide">Status</th>
                                <th className="px-4 py-3 text-center text-xs font-bold uppercase tracking-wide">Aksi</th>
                            </tr>
                        </thead>
                        <tbody className={`divide-y divide-slate-100 transition-opacity ${loading ? 'opacity-50' : ''}`}>
                            {guru.data.length === 0 ? (
                                <TableEmpty message="Tidak ada data guru." colSpan={5} />
                            ) : (
                                guru.data.map((g, i) => (
                                    <tr key={g.id} className={`${i % 2 === 0 ? 'bg-white' : 'bg-surface'} hover:bg-blue-50 transition-colors`}>
                                        <td className="px-4 py-3">
                                            <div className="font-medium">{g.nama_guru}</div>
                                            <div className="text-xs text-muted-foreground">{g.nilai_count} nilai diinput</div>
                                        </td>
                                        <td className="px-4 py-3">
                                            <div className="flex flex-wrap gap-1">
                                                {g.mengajar.length === 0 ? (
                                                    <span className="text-xs text-muted-foreground">Belum ada</span>
                                                ) : (
                                                    g.mengajar.map((m) => (
                                                        <Badge key={m.id} variant="info">
                                                            {m.kelas} • {m.mata_pelajaran}
                                                        </Badge>
                                                    ))
                                                )}
                                            </div>
                                        </td>
                                        <td className="px-4 py-3 text-center font-mono text-xs">
                                            {g.user?.username ?? <span className="text-muted-foreground">—</span>}
                                        </td>
                                        <td className="px-4 py-3 text-center">
                                            {g.user ? (
                                                g.user.is_active ? (
                                                    <Badge variant="success">Aktif</Badge>
                                                ) : (
                                                    <Badge variant="warning">Nonaktif</Badge>
                                                )
                                            ) : (
                                                <Badge variant="neutral">Tanpa Akun</Badge>
                                            )}
                                        </td>
                                        <td className="px-4 py-3 text-center">
                                            <div className="flex items-center justify-center gap-1">
                                                <Link href={`/admin/guru/${g.id}/edit`} className="p-1.5 text-primary hover:bg-blue-100 rounded transition" aria-label={`Edit ${g.nama_guru}`}>
                                                    <Edit className="h-4 w-4" />
                                                </Link>
                                                {!g.user && (
                                                    <Link
                                                        href={`/admin/guru/${g.id}/create-account`}
                                                        className="p-1.5 text-success hover:bg-green-100 rounded transition"
                                                        aria-label={`Buat akun untuk ${g.nama_guru}`}
                                                        title="Buat Akun Login"
                                                    >
                                                        <UserPlus className="h-4 w-4" />
                                                    </Link>
                                                )}
                                                {g.user && (
                                                    <button
                                                        type="button"
                                                        onClick={() => toggleActive(g)}
                                                        className={`p-1.5 rounded transition ${g.user.is_active ? 'text-warning hover:bg-yellow-100' : 'text-success hover:bg-green-100'}`}
                                                        aria-label={g.user.is_active ? `Nonaktifkan ${g.nama_guru}` : `Aktifkan ${g.nama_guru}`}
                                                        title={g.user.is_active ? 'Nonaktifkan Akun' : 'Aktifkan Akun'}
                                                    >
                                                        <Power className="h-4 w-4" />
                                                    </button>
                                                )}
                                                <button
                                                    type="button"
                                                    onClick={() => setDeleteTarget(g)}
                                                    className="p-1.5 text-danger hover:bg-red-100 rounded transition"
                                                    aria-label={`Hapus ${g.nama_guru}`}
                                                    title={g.nilai_count > 0 ? 'Tidak dapat dihapus (sudah punya nilai)' : 'Hapus'}
                                                    disabled={g.nilai_count > 0}
                                                >
                                                    <Trash2 className={`h-4 w-4 ${g.nilai_count > 0 ? 'opacity-30' : ''}`} />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>

                <PaginationFooter from={guru.from} to={guru.to} total={guru.total} links={guru.links} />
            </Card>

            <Modal
                open={!!deleteTarget}
                onClose={() => setDeleteTarget(null)}
                title="Konfirmasi Hapus"
                description={`Hapus guru ${deleteTarget?.nama_guru ?? ''}?`}
                footer={
                    <>
                        <Button variant="outline" onClick={() => setDeleteTarget(null)}>Batal</Button>
                        <Button variant="danger" onClick={doDelete}>Ya, Hapus</Button>
                    </>
                }
            />
        </div>
    );
}

GuruIndex.layout = { title: 'Manajemen Guru' };
