import { Link, router } from '@inertiajs/react';
import { Plus, Pencil, Trash2, Search, X, Loader2, Users, GraduationCap } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { PaginationFooter } from '@/components/ui/pagination';
import { DataTable, FilterBar, PageHeader, TableEmpty } from '@/components/ui/shared';
import { useFlashToast } from '@/hooks/use-flash-toast';
import { useInertiaSearch } from '@/hooks/use-inertia-search';

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

export default function KelasIndex({ kelas, search }: Props) {
    useFlashToast();
    const { filters: state, loading, hasFilter, setFilter, reset } = useInertiaSearch({
        url: '/admin/kelas',
        initialFilters: { q: search },
        only: ['kelas', 'search'],
    });

    function destroy(id: number, nama: string) {
        if (!confirm(`Hapus kelas "${nama}"? Tindakan ini tidak dapat dibatalkan.`)) {
return;
}

        router.delete(`/admin/kelas/${id}`);
    }

    return (
        <div>
            <PageHeader
                title="Manajemen Kelas"
                description={`Total: ${kelas.total} kelas. Kelas digunakan untuk mengelompokkan siswa dan mengajar guru.`}
                action={
                    <Link href="/admin/kelas/create">
                        <Button>
                            <Plus className="h-4 w-4" />
                            Tambah Kelas
                        </Button>
                    </Link>
                }
            />

            <FilterBar>
                <div className="relative flex-1">
                    <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                    <Input
                        type="search"
                        placeholder="Cari nama kelas..."
                        value={state.q}
                        onChange={(e) => setFilter('q', e.target.value)}
                        className="pl-9 pr-9"
                    />
                    {loading ? (
                        <Loader2 className="absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground animate-spin" />
                    ) : state.q ? (
                        <button
                            type="button"
                            onClick={() => setFilter('q', '')}
                            className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
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
            </FilterBar>

            <DataTable>

                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="bg-primary text-white">
                                <th className="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide w-12">No</th>
                                <th className="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide">Nama Kelas</th>
                                <th className="px-4 py-3 text-center text-xs font-bold uppercase tracking-wide">Jumlah Siswa</th>
                                <th className="px-4 py-3 text-center text-xs font-bold uppercase tracking-wide">Jumlah Mengajar</th>
                                <th className="px-4 py-3 text-center text-xs font-bold uppercase tracking-wide w-32">Aksi</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {kelas.data.length === 0 ? (
                                <TableEmpty message={search ? 'Tidak ada kelas yang cocok dengan pencarian.' : 'Belum ada kelas. Klik "Tambah Kelas" untuk membuat.'} colSpan={5} />
                            ) : (
                                kelas.data.map((k, i) => (
                                    <tr key={k.id} className={`${i % 2 === 0 ? 'bg-white' : 'bg-surface'} hover:bg-blue-50 transition-colors`}>
                                        <td className="px-4 py-3 text-muted-foreground">{kelas.from + i}</td>
                                        <td className="px-4 py-3 font-mono font-medium">{k.nama}</td>
                                        <td className="px-4 py-3 text-center">
                                            <Badge variant={k.siswa_count > 0 ? 'info' : 'neutral'}>
                                                <Users className="h-3 w-3 mr-1" />
                                                {k.siswa_count}
                                            </Badge>
                                        </td>
                                        <td className="px-4 py-3 text-center">
                                            <Badge variant={k.guru_mengajar_count > 0 ? 'success' : 'neutral'}>
                                                <GraduationCap className="h-3 w-3 mr-1" />
                                                {k.guru_mengajar_count}
                                            </Badge>
                                        </td>
                                        <td className="px-4 py-3 text-center">
                                            <div className="flex items-center justify-center gap-1">
                                                <Link href={`/admin/kelas/${k.id}/edit`}>
                                                    <button
                                                        type="button"
                                                        className="p-1.5 text-primary hover:bg-blue-100 rounded transition"
                                                        aria-label={`Edit ${k.nama}`}
                                                        title="Edit"
                                                    >
                                                        <Pencil className="h-4 w-4" />
                                                    </button>
                                                </Link>
                                                <button
                                                    type="button"
                                                    onClick={() => destroy(k.id, k.nama)}
                                                    className="p-1.5 text-danger hover:bg-red-100 rounded transition"
                                                    aria-label={`Hapus ${k.nama}`}
                                                    title={k.siswa_count > 0 || k.guru_mengajar_count > 0 ? 'Tidak dapat dihapus (masih digunakan)' : 'Hapus'}
                                                    disabled={k.siswa_count > 0 || k.guru_mengajar_count > 0}
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

                <PaginationFooter from={kelas.from} to={kelas.to} total={kelas.total} links={kelas.links} />
            </DataTable>
        </div>
    );
}

KelasIndex.layout = { title: 'Manajemen Kelas' };
