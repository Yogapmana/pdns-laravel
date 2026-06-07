import { Link, router } from '@inertiajs/react';
import { Plus, Edit, Trash2, Search, X, Loader2, Info } from 'lucide-react';
import { useState } from 'react';
import { Alert } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Modal } from '@/components/ui/modal';
import { PaginationFooter } from '@/components/ui/pagination';
import { Select } from '@/components/ui/select';
import { Container, DataTable, PageHeader, TableEmpty } from '@/components/ui/shared';
import { useFlashToast } from '@/hooks/use-flash-toast';
import { useInertiaSearch } from '@/hooks/use-inertia-search';

type Siswa = {
    nis: string;
    nama_siswa: string;
    kelas: string;
    user: { username: string } | null;
};

type Paginated<T> = {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    from: number;
    to: number;
    total: number;
};

type Props = {
    siswa: Paginated<Siswa>;
    daftar_kelas: string[];
    filters: { search: string | null; kelas: string | null };
};

export default function SiswaIndex({ siswa, daftar_kelas, filters }: Props) {
    useFlashToast();
    const {
        filters: state,
        loading,
        hasFilter,
        setFilter,
        reset,
    } = useInertiaSearch({
        url: '/admin/siswa',
        initialFilters: { search: filters.search, kelas: filters.kelas },
        only: ['siswa', 'filters'],
    });

    const [deleteTarget, setDeleteTarget] = useState<Siswa | null>(null);

    function doDelete() {
        if (!deleteTarget) {
            return;
        }

        router.delete(`/admin/siswa/${deleteTarget.nis}`, {
            onSuccess: () => setDeleteTarget(null),
        });
    }

    return (
        <Container>
            <PageHeader
                title="Manajemen Siswa"
                description={`${siswa.total} siswa`}
                action={
                    <Link href="/admin/siswa/create">
                        <Button>
                            <Plus className="h-4 w-4" />
                            Tambah Siswa
                        </Button>
                    </Link>
                }
            />

            <div className="mb-4 flex flex-col gap-3 rounded-xl border border-border bg-white p-3 shadow-sm md:flex-row">
                <div className="relative flex-1">
                    <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        type="search"
                        placeholder="Cari NIS atau nama..."
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
                    className="md:w-48"
                >
                    <option value="">Semua Kelas</option>
                    {daftar_kelas.map((k) => (
                        <option key={k} value={k}>
                            {k}
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
                                    NIS
                                </th>
                                <th className="px-4 py-3 text-left text-xs font-bold tracking-wide uppercase">
                                    Nama Siswa
                                </th>
                                <th className="px-4 py-3 text-center text-xs font-bold tracking-wide uppercase">
                                    Kelas
                                </th>
                                <th className="px-4 py-3 text-center text-xs font-bold tracking-wide uppercase">
                                    Username
                                </th>
                                <th className="px-4 py-3 text-center text-xs font-bold tracking-wide uppercase">
                                    Aksi
                                </th>
                            </tr>
                        </thead>
                        <tbody
                            className={`divide-y divide-slate-100 transition-opacity ${loading ? 'opacity-50' : ''}`}
                        >
                            {siswa.data.length === 0 ? (
                                <TableEmpty
                                    message="Tidak ada data siswa."
                                    colSpan={5}
                                />
                            ) : (
                                siswa.data.map((s, i) => (
                                    <tr
                                        key={s.nis}
                                        className={`${i % 2 === 0 ? 'bg-white' : 'bg-surface'} transition-colors hover:bg-blue-50`}
                                    >
                                        <td className="px-4 py-3 font-mono text-sm">
                                            {s.nis}
                                        </td>
                                        <td className="px-4 py-3">
                                            {s.nama_siswa}
                                        </td>
                                        <td className="px-4 py-3 text-center">
                                            <Badge variant="info">
                                                {s.kelas}
                                            </Badge>
                                        </td>
                                        <td className="px-4 py-3 text-center font-mono text-xs">
                                            {s.user?.username ?? (
                                                <span className="text-muted-foreground">
                                                    —
                                                </span>
                                            )}
                                        </td>
                                        <td className="px-4 py-3 text-center">
                                            <div className="flex items-center justify-center gap-2">
                                                <Link
                                                    href={`/admin/siswa/${s.nis}/edit`}
                                                    className="rounded p-1.5 text-primary transition hover:bg-blue-100"
                                                    aria-label={`Edit ${s.nama_siswa}`}
                                                >
                                                    <Edit className="h-4 w-4" />
                                                </Link>
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        setDeleteTarget(s)
                                                    }
                                                    className="rounded p-1.5 text-danger transition hover:bg-red-100"
                                                    aria-label={`Hapus ${s.nama_siswa}`}
                                                >
                                                    <Trash2 className="h-4 w-4" />
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
                    from={siswa.from}
                    to={siswa.to}
                    total={siswa.total}
                    links={siswa.links}
                />
            </DataTable>

            <Modal
                open={!!deleteTarget}
                onClose={() => setDeleteTarget(null)}
                title="Konfirmasi Hapus"
                description={`Data siswa ${deleteTarget?.nama_siswa ?? ''} dan seluruh nilai terkait akan dihapus permanen. Tindakan ini tidak dapat dibatalkan.`}
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

SiswaIndex.layout = { title: 'Manajemen Siswa' };
