import { Link, router } from '@inertiajs/react';
import {
    Plus,
    Pencil,
    Trash2,
    Search,
    X,
    Loader2,
    GraduationCap,
    FileText,
} from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { PaginationFooter } from '@/components/ui/pagination';
import { Container, DataTable, PageHeader, TableEmpty } from '@/components/ui/shared';
import { useFlashToast } from '@/hooks/use-flash-toast';
import { useInertiaSearch } from '@/hooks/use-inertia-search';

type MapelItem = {
    id: number;
    nama: string;
    guru_mengajar_count: number;
    nilai_count: number;
};

type Paginated<T> = {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    from: number;
    to: number;
    total: number;
};

type Props = {
    mataPelajaran: Paginated<MapelItem>;
    search: string | null;
};

function destroy(id: number, nama: string) {
    if (
        !confirm(
            `Hapus mata pelajaran "${nama}"? Tindakan ini tidak dapat dibatalkan.`,
        )
    ) {
        return;
    }

    router.delete(`/admin/mata-pelajaran/${id}`);
}

export default function MataPelajaranIndex({ mataPelajaran, search }: Props) {
    useFlashToast();
    const {
        filters: state,
        loading,
        hasFilter,
        setFilter,
        reset,
    } = useInertiaSearch({
        url: '/admin/mata-pelajaran',
        initialFilters: { q: search },
        only: ['mataPelajaran', 'search'],
    });

    return (
        <Container>
            <PageHeader
                title="Manajemen Mata Pelajaran"
                description={`${mataPelajaran.total} Mata Pelajaran`}
                action={
                    <Link href="/admin/mata-pelajaran/create">
                        <Button>
                            <Plus className="h-4 w-4" />
                            Tambah Mata Pelajaran
                        </Button>
                    </Link>
                }
            />

            <div className="mb-4 flex flex-col gap-3 rounded-xl border border-border bg-white p-3 shadow-sm md:flex-row">
                <div className="relative flex-1">
                    <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        type="search"
                        placeholder="Cari nama mata pelajaran..."
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
                                    Nama Mata Pelajaran
                                </th>
                                <th className="px-4 py-3 text-center text-xs font-bold tracking-wide uppercase">
                                    Jumlah Guru Mengajar
                                </th>
                                <th className="px-4 py-3 text-center text-xs font-bold tracking-wide uppercase">
                                    Jumlah Nilai
                                </th>
                                <th className="w-32 px-4 py-3 text-center text-xs font-bold tracking-wide uppercase">
                                    Aksi
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {mataPelajaran.data.length === 0 ? (
                                <TableEmpty
                                    message={
                                        search
                                            ? 'Tidak ada mata pelajaran yang cocok dengan pencarian.'
                                            : 'Belum ada mata pelajaran. Klik "Tambah Mata Pelajaran" untuk membuat.'
                                    }
                                    colSpan={5}
                                />
                            ) : (
                                mataPelajaran.data.map((m, i) => (
                                    <tr
                                        key={m.id}
                                        className={`${i % 2 === 0 ? 'bg-white' : 'bg-surface'} transition-colors hover:bg-blue-50`}
                                    >
                                        <td className="px-4 py-3 text-muted-foreground">
                                            {mataPelajaran.from + i}
                                        </td>
                                        <td className="px-4 py-3 font-medium">
                                            {m.nama}
                                        </td>
                                        <td className="px-4 py-3 text-center">
                                            <Badge
                                                variant={
                                                    m.guru_mengajar_count > 0
                                                        ? 'success'
                                                        : 'neutral'
                                                }
                                            >
                                                <GraduationCap className="mr-1 h-3 w-3" />
                                                {m.guru_mengajar_count}
                                            </Badge>
                                        </td>
                                        <td className="px-4 py-3 text-center">
                                            <Badge
                                                variant={
                                                    m.nilai_count > 0
                                                        ? 'info'
                                                        : 'neutral'
                                                }
                                            >
                                                <FileText className="mr-1 h-3 w-3" />
                                                {m.nilai_count}
                                            </Badge>
                                        </td>
                                        <td className="px-4 py-3 text-center">
                                            <div className="flex items-center justify-center gap-1">
                                                <Link
                                                    href={`/admin/mata-pelajaran/${m.id}/edit`}
                                                >
                                                    <button
                                                        type="button"
                                                        className="rounded p-1.5 text-primary transition hover:bg-blue-100"
                                                        aria-label={`Edit ${m.nama}`}
                                                        title="Edit"
                                                    >
                                                        <Pencil className="h-4 w-4" />
                                                    </button>
                                                </Link>
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        destroy(m.id, m.nama)
                                                    }
                                                    className="rounded p-1.5 text-danger transition hover:bg-red-100"
                                                    aria-label={`Hapus ${m.nama}`}
                                                    title={
                                                        m.guru_mengajar_count >
                                                            0 ||
                                                        m.nilai_count > 0
                                                            ? 'Tidak dapat dihapus (masih digunakan)'
                                                            : 'Hapus'
                                                    }
                                                    disabled={
                                                        m.guru_mengajar_count >
                                                            0 ||
                                                        m.nilai_count > 0
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
                    from={mataPelajaran.from}
                    to={mataPelajaran.to}
                    total={mataPelajaran.total}
                    links={mataPelajaran.links}
                />
            </DataTable>
        </Container>
    );
}

MataPelajaranIndex.layout = { title: 'Manajemen Mata Pelajaran' };
