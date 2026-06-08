import { Link, router } from '@inertiajs/react';
import {
    Plus,
    Pencil,
    Trash2,
    Search,
    X,
    Loader2,
    Users,
    GraduationCap,
} from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { PaginationFooter } from '@/components/ui/pagination';
import { Container, DataTable, PageHeader, TableEmpty } from '@/components/ui/shared';
import { useFlashToast } from '@/hooks/use-flash-toast';
import { useInertiaSearch } from '@/hooks/use-inertia-search';
import { index, create, edit, destroy as destroyRoute } from '@/routes/admin/kelas';

type KelasItem = {
    id: number;
    nama: string;
    siswa_count: number;
    guru_mengajar_count: number;
};

type Paginated<T> = {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    from: number;
    to: number;
    total: number;
};

type Props = {
    kelas: Paginated<KelasItem>;
    search: string | null;
};

function destroy(id: number, nama: string) {
    if (
        !confirm(`Hapus kelas "${nama}"? Tindakan ini tidak dapat dibatalkan.`)
    ) {
        return;
    }

    router.delete(destroyRoute.url(id));
}

export default function KelasIndex({ kelas, search }: Props) {
    useFlashToast();
    const {
        filters: state,
        loading,
        hasFilter,
        setFilter,
        reset,
    } = useInertiaSearch({
        url: index.url(),
        initialFilters: { q: search },
        only: ['kelas', 'search'],
    });

    return (
        <Container>
            <PageHeader
                title="Manajemen Kelas"
                description={`${kelas.total} kelas`}
                action={
                    <Link href={create.url()}>
                        <Button>
                            <Plus className="h-4 w-4" />
                            Tambah Kelas
                        </Button>
                    </Link>
                }
            />

            <div className="mb-4 flex flex-col gap-3 rounded-xl border border-border bg-white p-3 shadow-sm md:flex-row">
                <div className="relative flex-1">
                    <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        type="search"
                        placeholder="Cari nama kelas..."
                        value={state.q}
                        onChange={(e) => setFilter('q', e.target.value)}
                        className="pr-9 pl-9"
                    />
                    {loading ? (
                        <Loader2 className="absolute top-1/2 right-3 h-4 w-4 -translate-y-1/2 animate-spin text-muted-foreground" />
                    ) : state.q ? (
                        <button
                            type="button"
                            onClick={() => setFilter('q', '')}
                            className="absolute top-1/2 right-3 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                            aria-label="Bersihkan pencarian"
                        >
                            <X className="h-4 w-4" />
                        </button>
                    ) : null}
                </div>
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
                                <th className="w-12 px-4 py-3 text-left text-xs font-bold tracking-wide uppercase">
                                    No
                                </th>
                                <th className="px-4 py-3 text-left text-xs font-bold tracking-wide uppercase">
                                    Nama Kelas
                                </th>
                                <th className="px-4 py-3 text-center text-xs font-bold tracking-wide uppercase">
                                    Jumlah Siswa
                                </th>
                                <th className="px-4 py-3 text-center text-xs font-bold tracking-wide uppercase">
                                    Jumlah Mengajar
                                </th>
                                <th className="w-32 px-4 py-3 text-center text-xs font-bold tracking-wide uppercase">
                                    Aksi
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {kelas.data.length === 0 ? (
                                <TableEmpty
                                    message={
                                        search
                                            ? 'Tidak ada kelas yang cocok dengan pencarian.'
                                            : 'Belum ada kelas. Klik "Tambah Kelas" untuk membuat.'
                                    }
                                    colSpan={5}
                                />
                            ) : (
                                kelas.data.map((k, i) => (
                                    <tr
                                        key={k.id}
                                        className={`${i % 2 === 0 ? 'bg-white' : 'bg-surface'} transition-colors hover:bg-blue-50`}
                                    >
                                        <td className="px-4 py-3 text-muted-foreground">
                                            {kelas.from + i}
                                        </td>
                                        <td className="px-4 py-3 font-mono font-medium">
                                            {k.nama}
                                        </td>
                                        <td className="px-4 py-3 text-center">
                                            <Badge
                                                variant={
                                                    k.siswa_count > 0
                                                        ? 'info'
                                                        : 'neutral'
                                                }
                                            >
                                                <Users className="mr-1 h-3 w-3" />
                                                {k.siswa_count}
                                            </Badge>
                                        </td>
                                        <td className="px-4 py-3 text-center">
                                            <Badge
                                                variant={
                                                    k.guru_mengajar_count > 0
                                                        ? 'success'
                                                        : 'neutral'
                                                }
                                            >
                                                <GraduationCap className="mr-1 h-3 w-3" />
                                                {k.guru_mengajar_count}
                                            </Badge>
                                        </td>
                                        <td className="px-4 py-3 text-center">
                                            <div className="flex items-center justify-center gap-1">
                                                <Link
                                                    href={edit.url({ id: k.id })}
                                                >
                                                    <button
                                                        type="button"
                                                        className="rounded p-1.5 text-primary transition hover:bg-blue-100"
                                                        aria-label={`Edit ${k.nama}`}
                                                        title="Edit"
                                                    >
                                                        <Pencil className="h-4 w-4" />
                                                    </button>
                                                </Link>
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        destroy(k.id, k.nama)
                                                    }
                                                    className="rounded p-1.5 text-danger transition hover:bg-red-100"
                                                    aria-label={`Hapus ${k.nama}`}
                                                    title={
                                                        k.siswa_count > 0 ||
                                                        k.guru_mengajar_count >
                                                            0
                                                            ? 'Tidak dapat dihapus (masih digunakan)'
                                                            : 'Hapus'
                                                    }
                                                    disabled={
                                                        k.siswa_count > 0 ||
                                                        k.guru_mengajar_count >
                                                            0
                                                    }
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
                    from={kelas.from}
                    to={kelas.to}
                    total={kelas.total}
                    links={kelas.links}
                />
            </DataTable>
        </Container>
    );
}

KelasIndex.layout = { title: 'Manajemen Kelas' };
