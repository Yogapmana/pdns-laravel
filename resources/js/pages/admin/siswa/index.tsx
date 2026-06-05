import { Link, router } from '@inertiajs/react';
import { Plus, Edit, Trash2, Search, X } from 'lucide-react';
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
    const [search, setSearch] = useState(filters.search ?? '');
    const [kelas, setKelas] = useState(filters.kelas ?? '');
    const [deleteTarget, setDeleteTarget] = useState<Siswa | null>(null);

    function applyFilters() {
        router.get('/admin/siswa', { search, kelas }, { preserveState: true });
    }

    function clearFilters() {
        setSearch('');
        setKelas('');
        router.get('/admin/siswa', {}, { preserveState: true });
    }

    function doDelete() {
        if (!deleteTarget) {
return;
}

        router.delete(`/admin/siswa/${deleteTarget.nis}`, {
            onSuccess: () => setDeleteTarget(null),
        });
    }

    return (
        <div>
            <PageHeader
                title="Manajemen Siswa"
                description={`Total: ${siswa.total} siswa`}
                action={
                    <Link href="/admin/siswa/create">
                        <Button>
                            <Plus className="h-4 w-4" />
                            Tambah Siswa
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
                            placeholder="Cari NIS atau nama..."
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            onKeyDown={(e) => e.key === 'Enter' && applyFilters()}
                            className="pl-9"
                        />
                    </div>
                    <Select value={kelas} onChange={(e) => {
 setKelas(e.target.value); 
}} className="md:w-48">
                        <option value="">Semua Kelas</option>
                        {daftar_kelas.map((k) => (
                            <option key={k} value={k}>{k}</option>
                        ))}
                    </Select>
                    <Button onClick={applyFilters} variant="primary">Cari</Button>
                    {(search || kelas) && (
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
                                <th className="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide">NIS</th>
                                <th className="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide">Nama Siswa</th>
                                <th className="px-4 py-3 text-center text-xs font-bold uppercase tracking-wide">Kelas</th>
                                <th className="px-4 py-3 text-center text-xs font-bold uppercase tracking-wide">Username</th>
                                <th className="px-4 py-3 text-center text-xs font-bold uppercase tracking-wide">Aksi</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {siswa.data.length === 0 ? (
                                <TableEmpty message="Tidak ada data siswa." colSpan={5} />
                            ) : (
                                siswa.data.map((s, i) => (
                                    <tr key={s.nis} className={`${i % 2 === 0 ? 'bg-white' : 'bg-surface'} hover:bg-blue-50 transition-colors`}>
                                        <td className="px-4 py-3 font-mono text-sm">{s.nis}</td>
                                        <td className="px-4 py-3">{s.nama_siswa}</td>
                                        <td className="px-4 py-3 text-center">
                                            <Badge variant="info">{s.kelas}</Badge>
                                        </td>
                                        <td className="px-4 py-3 text-center font-mono text-xs">
                                            {s.user?.username ?? <span className="text-muted-foreground">—</span>}
                                        </td>
                                        <td className="px-4 py-3 text-center">
                                            <div className="flex items-center justify-center gap-2">
                                                <Link href={`/admin/siswa/${s.nis}/edit`} className="p-1.5 text-primary hover:bg-blue-100 rounded transition" aria-label={`Edit ${s.nama_siswa}`}>
                                                    <Edit className="h-4 w-4" />
                                                </Link>
                                                <button
                                                    type="button"
                                                    onClick={() => setDeleteTarget(s)}
                                                    className="p-1.5 text-danger hover:bg-red-100 rounded transition"
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

                <PaginationFooter from={siswa.from} to={siswa.to} total={siswa.total} links={siswa.links} />
            </Card>

            <Modal
                open={!!deleteTarget}
                onClose={() => setDeleteTarget(null)}
                title="Konfirmasi Hapus"
                description={`Data siswa ${deleteTarget?.nama_siswa ?? ''} dan seluruh nilai terkait akan dihapus permanen. Tindakan ini tidak dapat dibatalkan.`}
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

SiswaIndex.layout = { title: 'Manajemen Siswa' };
