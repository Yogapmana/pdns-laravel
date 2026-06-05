import { Link } from '@inertiajs/react';
import { Users, GraduationCap, CheckCircle, FileText, UserPlus } from 'lucide-react';
import { Card, CardHeader, CardContent } from '@/components/ui/card';
import { StatCard, PageHeader, Container } from '@/components/ui/shared';
import { useFlashToast } from '@/hooks/use-flash-toast';

type Stats = {
    total_siswa: number;
    total_guru: number;
    total_nilai: number;
    lulus: number;
    tidak_lulus: number;
    persentase_lulus: number;
};

type RekapKelas = {
    kelas: string;
    jumlah_siswa: number;
    lulus: number;
    tidak_lulus: number;
};

type Props = {
    stats: Stats;
    rekap_per_kelas: RekapKelas[];
    daftar_kelas: string[];
};

export default function AdminDashboard({ stats, rekap_per_kelas }: Props) {
    useFlashToast();

    return (
        <Container>
            <PageHeader title="Dashboard Admin" description="Ringkasan data akademik sekolah" />

            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <StatCard label="Total Siswa" value={stats.total_siswa} icon={<Users className="h-6 w-6" />} color="primary" />
                <StatCard label="Total Guru" value={stats.total_guru} icon={<GraduationCap className="h-6 w-6" />} color="accent" />
                <StatCard label="Persentase Lulus" value={`${stats.persentase_lulus}%`} icon={<CheckCircle className="h-6 w-6" />} color="success" />
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <Card className="lg:col-span-2 p-0">
                    <CardHeader>Rekap Kelulusan per Kelas</CardHeader>
                    <CardContent className="p-0">
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="bg-primary text-white">
                                        <th className="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide">Kelas</th>
                                        <th className="px-4 py-3 text-center text-xs font-bold uppercase tracking-wide">Jumlah Siswa</th>
                                        <th className="px-4 py-3 text-center text-xs font-bold uppercase tracking-wide">Lulus</th>
                                        <th className="px-4 py-3 text-center text-xs font-bold uppercase tracking-wide">Tidak Lulus</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {rekap_per_kelas.length === 0 ? (
                                        <tr>
                                            <td colSpan={4} className="px-4 py-12 text-center text-muted-foreground">
                                                Belum ada data.
                                            </td>
                                        </tr>
                                    ) : (
                                        rekap_per_kelas.map((r) => (
                                            <tr key={r.kelas} className="hover:bg-surface">
                                                <td className="px-4 py-3 font-semibold">{r.kelas}</td>
                                                <td className="px-4 py-3 text-center">{r.jumlah_siswa}</td>
                                                <td className="px-4 py-3 text-center font-semibold text-success">{r.lulus}</td>
                                                <td className="px-4 py-3 text-center font-semibold text-danger">{r.tidak_lulus}</td>
                                            </tr>
                                        ))
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>Aksi Cepat</CardHeader>
                    <CardContent className="space-y-3">
                        <Link
                            href="/admin/siswa/create"
                            className="flex items-center gap-3 p-3 rounded-lg border border-border hover:bg-surface transition"
                        >
                            <div className="p-2 bg-blue-100 text-primary rounded-lg">
                                <UserPlus className="h-4 w-4" />
                            </div>
                            <div>
                                <p className="text-sm font-semibold">Tambah Siswa</p>
                                <p className="text-xs text-muted-foreground">Daftarkan siswa baru</p>
                            </div>
                        </Link>
                        <Link
                            href="/admin/guru/create"
                            className="flex items-center gap-3 p-3 rounded-lg border border-border hover:bg-surface transition"
                        >
                            <div className="p-2 bg-sky-100 text-accent rounded-lg">
                                <GraduationCap className="h-4 w-4" />
                            </div>
                            <div>
                                <p className="text-sm font-semibold">Tambah Guru</p>
                                <p className="text-xs text-muted-foreground">Daftarkan guru baru</p>
                            </div>
                        </Link>
                        <Link
                            href="/admin/laporan"
                            className="flex items-center gap-3 p-3 rounded-lg border border-border hover:bg-surface transition"
                        >
                            <div className="p-2 bg-green-100 text-success rounded-lg">
                                <FileText className="h-4 w-4" />
                            </div>
                            <div>
                                <p className="text-sm font-semibold">Cetak Laporan</p>
                                <p className="text-xs text-muted-foreground">Generate laporan kelas</p>
                            </div>
                        </Link>
                    </CardContent>
                </Card>
            </div>
        </Container>
    );
}

AdminDashboard.layout = { title: 'Dashboard' };
