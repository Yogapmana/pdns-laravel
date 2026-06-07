import { Link, router } from '@inertiajs/react';
import {
    Plus,
    Edit,
    Trash2,
    Search,
    X,
    Loader2,
} from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Modal } from '@/components/ui/modal';
import { PaginationFooter } from '@/components/ui/pagination';
import { Select } from '@/components/ui/select';
import { Container, DataTable, PageHeader, TableEmpty } from '@/components/ui/shared';
import { useFlashToast } from '@/hooks/use-flash-toast';
import { useInertiaSearch } from '@/hooks/use-inertia-search';

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
    filters: {
        search: string | null;
        kelas: string | null;
        mapel: string | null;
    };
};

export default function GuruIndex({
    guru,
    daftar_kelas,
    daftar_mapel,
    filters,
}: Props) {
    useFlashToast();
    const {
        filters: state,
        loading,
        hasFilter,
        setFilter,
        reset,
    } = useInertiaSearch({
        url: '/admin/guru',
        initialFilters: {
            search: filters.search,
            kelas: filters.kelas,
            mapel: filters.mapel,
        },
        only: ['guru', 'filters'],
    });

    const [deleteTarget, setDeleteTarget] = useState<Guru | null>(null);

    function doDelete() {
        if (!deleteTarget) {
            return;
        }

        router.delete(`/admin/guru/${deleteTarget.id}`, {
            onSuccess: () => setDeleteTarget(null),
        });
    }

    return (
        <Container>
            <PageHeader
                title="Manajemen Guru"
                description={`${guru.total} guru`}
                action={
                    <Link href="/admin/guru/create">
                        <Button>
                            <Plus className="h-4 w-4" />
                            Tambah Guru
                        </Button>
                    </Link>
                }
            />

            <div className="mb-4 flex flex-col gap-3 rounded-xl border border-border bg-white p-3 shadow-sm md:flex-row">
                <div className="relative flex-1">
                    <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        type="search"
                        placeholder="Cari nama guru..."
                        value={state.search}
                        onChange={(e) => setFilter('search', e.target.value)}
                        className="pr-9 pl-9"
                    />
                    {loading ? (
                        <Loader2 className="absolute top-1/2 right-3 h-4 w-4 -translate-y-1/2 animate-spin text-muted-foreground" />
                    ) : state.search ? (
                        <button
                            type="button"
                            onClick={() => setFilter('search', '')}
                            className="absolute top-1/2 right-3 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                            aria-label="Bersihkan pencarian"
                        >
                            <X className="h-4 w-4" />
                        </button>
                    ) : null}
                </div>
                <Select
                    value={state.kelas}
                    onChange={(e) => setFilter('kelas', e.target.value)}
                    className="md:w-40"
                >
                    <option value="">Semua Kelas</option>
                    {daftar_kelas.map((k) => (
                        <option key={k} value={k}>
                            {k}
                        </option>
                    ))}
                </Select>
                <Select
                    value={state.mapel}
                    onChange={(e) => setFilter('mapel', e.target.value)}
                    className="md:w-56"
                >
                    <option value="">Semua Mata Pelajaran</option>
                    {daftar_mapel.map((m) => (
                        <option key={m} value={m}>
                            {m}
                        </option>
                    ))}
                </Select>
                {hasFilter && (
                    <Button onClick={reset} variant="outline">
                        <X className="h-4 w-4" />
                        Reset
                    </Button>
                )}
            </div>

            <DataTable>
                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="bg-primary text-white">
                                <th className="px-4 py-3 text-left text-xs font-bold tracking-wide uppercase">
                                    Nama Guru
                                </th>
                                <th className="px-4 py-3 text-left text-xs font-bold tracking-wide uppercase">
                                    Mengajar
                                </th>
                                <th className="px-4 py-3 text-center text-xs font-bold tracking-wide uppercase">
                                    Username
                                </th>
                                <th className="px-4 py-3 text-center text-xs font-bold tracking-wide uppercase">
                                    Status
                                </th>
                                <th className="px-4 py-3 text-center text-xs font-bold tracking-wide uppercase">
                                    Aksi
                                </th>
                            </tr>
                        </thead>
                        <tbody
                            className={`divide-y divide-slate-100 transition-opacity ${loading ? 'opacity-50' : ''}`}
                        >
                            {guru.data.length === 0 ? (
                                <TableEmpty
                                    message="Tidak ada data guru."
                                    colSpan={5}
                                />
                            ) : (
                                guru.data.map((g, i) => (
                                    <tr
                                        key={g.id}
                                        className={`${i % 2 === 0 ? 'bg-white' : 'bg-surface'} transition-colors hover:bg-blue-50`}
                                    >
                                        <td className="px-4 py-3">
                                            <div className="font-medium">
                                                {g.nama_guru}
                                            </div>
                                        </td>
                                        <td className="px-4 py-3">
                                            <div className="flex flex-wrap gap-1">
                                                {g.mengajar.length === 0 ? (
                                                    <span className="text-xs text-muted-foreground">
                                                        Belum ada
                                                    </span>
                                                ) : (
                                                    g.mengajar.map((m) => (
                                                        <div
                                                            key={m.id}
                                                            className="flex items-center gap-1.5 rounded-lg border border-blue-100 bg-blue-50 px-2 py-1.5 text-xs font-medium text-blue-700"
                                                        >
                                                            <Badge
                                                                variant="neutral"
                                                                className="py-0 text-[10px]"
                                                            >
                                                                {m.kelas}
                                                            </Badge>
                                                            <span>
                                                                {
                                                                    m.mata_pelajaran
                                                                }
                                                            </span>
                                                        </div>
                                                    ))
                                                )}
                                            </div>
                                        </td>
                                        <td className="px-4 py-3 text-center font-mono text-xs">
                                            {g.user?.username ?? (
                                                <span className="text-muted-foreground">
                                                    —
                                                </span>
                                            )}
                                        </td>
                                        <td className="px-4 py-3 text-center">
                                            {g.user ? (
                                                g.user.is_active ? (
                                                    <Badge variant="success">
                                                        Aktif
                                                    </Badge>
                                                ) : (
                                                    <Badge variant="warning">
                                                        Nonaktif
                                                    </Badge>
                                                )
                                            ) : (
                                                <Badge variant="neutral">
                                                    Tanpa Akun
                                                </Badge>
                                            )}
                                        </td>
                                        <td className="px-4 py-3 text-center">
                                            <div className="flex items-center justify-center gap-1">
                                                <Link
                                                    href={`/admin/guru/${g.id}/edit`}
                                                    className="rounded p-1.5 text-primary transition hover:bg-blue-100"
                                                    aria-label={`Edit ${g.nama_guru}`}
                                                >
                                                    <Edit className="h-4 w-4" />
                                                </Link>
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        setDeleteTarget(g)
                                                    }
                                                    className="rounded p-1.5 text-danger transition hover:bg-red-100"
                                                    aria-label={`Hapus ${g.nama_guru}`}
                                                    title={
                                                        g.nilai_count > 0
                                                            ? 'Tidak dapat dihapus (sudah punya nilai)'
                                                            : 'Hapus'
                                                    }
                                                    disabled={g.nilai_count > 0}
                                                >
                                                    <Trash2
                                                        className={`h-4 w-4 ${g.nilai_count > 0 ? 'opacity-30' : ''}`}
                                                    />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>

                <PaginationFooter
                    from={guru.from}
                    to={guru.to}
                    total={guru.total}
                    links={guru.links}
                />
            </DataTable>

            <Modal
                open={!!deleteTarget}
                onClose={() => setDeleteTarget(null)}
                title="Konfirmasi Hapus"
                description={`Hapus guru ${deleteTarget?.nama_guru ?? ''}?`}
                footer={
                    <>
                        <Button
                            variant="outline"
                            onClick={() => setDeleteTarget(null)}
                        >
                            Batal
                        </Button>
                        <Button variant="danger" onClick={doDelete}>
                            Ya, Hapus
                        </Button>
                    </>
                }
            />
        </Container>
    );
}

GuruIndex.layout = { title: 'Manajemen Guru' };
