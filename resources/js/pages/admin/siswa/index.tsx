import { Link, router } from '@inertiajs/react';
import { Plus, Edit, Trash2, Search, X, Loader2 } from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Drawer } from '@/components/ui/drawer';
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
    kelas: { id: number; nama: string } | null;
    nilai_count: number;
    user: { username: string; is_active: boolean; created_at: string | null } | null;
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
    daftar_kelas: { id: number; nama: string }[];
    filters: { search: string | null; kelas: string | null };
};

const DATE_FORMATTER = new Intl.DateTimeFormat('id-ID', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
});

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

    const [selected, setSelected] = useState<Siswa | null>(null);
    const [deleteTarget, setDeleteTarget] = useState<Siswa | null>(null);

    function doDelete() {
        if (!deleteTarget) {
            return;
        }

        router.delete(`/admin/siswa/${deleteTarget.nis}`, {
            onSuccess: () => setDeleteTarget(null),
        });
    }

    function closeDrawer() {
        setSelected(null);
    }

    function formatCreatedAt(iso: string | null | undefined): string {
        if (!iso) {
            return '—';
        }
        const d = new Date(iso);
        if (Number.isNaN(d.getTime())) {
            return '—';
        }
        return DATE_FORMATTER.format(d);
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
                        <option key={k.id} value={k.nama}>
                            {k.nama}
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
                            <tr className="bg-navy text-white">
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
                                        onClick={() => setSelected(s)}
                                        className={`${i % 2 === 0 ? 'bg-white' : 'bg-surface'} cursor-pointer transition-colors hover:bg-blue-50`}
                                    >
                                        <td className="px-4 py-3 font-mono text-sm">
                                            {s.nis}
                                        </td>
                                        <td className="px-4 py-3">
                                            {s.nama_siswa}
                                        </td>
                                        <td className="px-4 py-3 text-center">
                                            <Badge variant="info">
                                                {s.kelas?.nama ?? '—'}
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
                                            <div
                                                className="flex items-center justify-center gap-2"
                                                onClick={(e) => e.stopPropagation()}
                                            >
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

            <Drawer
                open={!!selected}
                onClose={closeDrawer}
                title="Detail Siswa"
                description={selected?.nama_siswa}
                footer={
                    selected && (
                        <>
                            <Button
                                variant="outline"
                                onClick={closeDrawer}
                            >
                                Tutup
                            </Button>
                            <Link href={`/admin/siswa/${selected.nis}/edit`}>
                                <Button>
                                    <Edit className="h-4 w-4" />
                                    Edit
                                </Button>
                            </Link>
                            <Button
                                variant="danger"
                                onClick={() => {
                                    setDeleteTarget(selected);
                                    closeDrawer();
                                }}
                            >
                                <Trash2 className="h-4 w-4" />
                                Hapus
                            </Button>
                        </>
                    )
                }
            >
                {selected && (
                    <div className="space-y-6">
                        <section>
                            <h4 className="mb-3 text-xs font-bold tracking-wide text-muted-foreground uppercase">
                                Profil
                            </h4>
                            <dl className="space-y-2.5 text-sm">
                                <div className="flex items-center justify-between gap-3">
                                    <dt className="text-muted-foreground">NIS</dt>
                                    <dd className="font-mono text-navy">{selected.nis}</dd>
                                </div>
                                <div className="flex items-center justify-between gap-3">
                                    <dt className="text-muted-foreground">Kelas</dt>
                                    <dd>
                                        <Badge variant="info">{selected.kelas?.nama ?? '—'}</Badge>
                                    </dd>
                                </div>
                            </dl>
                        </section>

                        <section>
                            <h4 className="mb-3 text-xs font-bold tracking-wide text-muted-foreground uppercase">
                                Informasi Akun
                            </h4>
                            {selected.user ? (
                                <dl className="space-y-2.5 text-sm">
                                    <div className="flex items-center justify-between gap-3">
                                        <dt className="text-muted-foreground">Username</dt>
                                        <dd className="font-mono text-xs">
                                            {selected.user.username}
                                        </dd>
                                    </div>
                                    <div className="flex items-center justify-between gap-3">
                                        <dt className="text-muted-foreground">Status</dt>
                                        <dd>
                                            {selected.user.is_active ? (
                                                <Badge variant="success">Aktif</Badge>
                                            ) : (
                                                <Badge variant="warning">Nonaktif</Badge>
                                            )}
                                        </dd>
                                    </div>
                                    <div className="flex items-center justify-between gap-3">
                                        <dt className="text-muted-foreground">Akun dibuat</dt>
                                        <dd className="text-navy">
                                            {formatCreatedAt(selected.user.created_at)}
                                        </dd>
                                    </div>
                                </dl>
                            ) : (
                                <p className="text-sm text-muted-foreground">
                                    Siswa ini belum memiliki akun login.
                                </p>
                            )}
                        </section>

                        <section>
                            <h4 className="mb-3 text-xs font-bold tracking-wide text-muted-foreground uppercase">
                                Statistik
                            </h4>
                            <div className="flex items-center justify-between gap-3 text-sm">
                                <span className="text-muted-foreground">Total nilai</span>
                                <span className="font-medium text-navy">
                                    {selected.nilai_count} baris
                                </span>
                            </div>
                        </section>
                    </div>
                )}
            </Drawer>

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
