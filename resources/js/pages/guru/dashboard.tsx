import { Link } from '@inertiajs/react';
import { Users, ClipboardList, FileEdit, CheckCircle, XCircle, BarChart3, BookOpen } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Card, CardHeader, CardContent } from '@/components/ui/card';
import { StatCard, PageHeader, Container } from '@/components/ui/shared';
import { useFlashToast } from '@/hooks/use-flash-toast';

type Mengajar = { id: number; kelas: string; mata_pelajaran: string };

type Guru = { id: number; nama_guru: string; mengajar: Mengajar[] };

type Stats = {
    total_siswa: number;
    total_nilai: number;
    draft: number;
    final: number;
    lulus: number;
    tidak_lulus: number;
    rata_rata: number;
};

type Props = { guru: Guru; stats: Stats };

export default function GuruDashboard({ guru, stats }: Props) {
    useFlashToast();

    const mengajar = guru.mengajar ?? [];
    const totalKelas = new Set(mengajar.map((m) => m.kelas)).size;
    const totalMapel = new Set(mengajar.map((m) => m.mata_pelajaran)).size;

    return (
        <Container>
            <PageHeader
                title={`Selamat Datang, ${guru.nama_guru}`}
                description={`${mengajar.length} kombinasi mengajar • ${totalKelas} kelas • ${totalMapel} mata pelajaran`}
                action={
                    <Link href="/guru/input-nilai" className="inline-flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg text-sm font-semibold hover:bg-primary-700 transition">
                        <ClipboardList className="h-4 w-4" />
                        Input Nilai
                    </Link>
                }
            />

            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <StatCard label="Total Siswa" value={stats.total_siswa} icon={<Users className="h-6 w-6" />} color="primary" />
                <StatCard label="Total Nilai" value={stats.total_nilai} icon={<FileEdit className="h-6 w-6" />} color="accent" />
                <StatCard label="Status Draft" value={stats.draft} icon={<BarChart3 className="h-6 w-6" />} color="warning" />
                <StatCard label="Status Final" value={stats.final} icon={<CheckCircle className="h-6 w-6" />} color="success" />
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <Card>
                    <CardHeader>Ringkasan Kelulusan</CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-2 gap-4">
                            <div className="p-4 bg-green-50 rounded-lg text-center">
                                <CheckCircle className="h-6 w-6 text-success mx-auto mb-2" />
                                <p className="text-3xl font-bold text-success">{stats.lulus}</p>
                                <p className="text-sm text-secondary">Lulus</p>
                            </div>
                            <div className="p-4 bg-red-50 rounded-lg text-center">
                                <XCircle className="h-6 w-6 text-danger mx-auto mb-2" />
                                <p className="text-3xl font-bold text-danger">{stats.tidak_lulus}</p>
                                <p className="text-sm text-secondary">Tidak Lulus</p>
                            </div>
                        </div>
                        <div className="mt-4 p-3 bg-blue-50 rounded-lg text-center">
                            <p className="text-xs text-muted-foreground">Rata-rata nilai mata pelajaran</p>
                            <p className="text-2xl font-bold text-primary mt-1">{stats.rata_rata}</p>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>Mengajar</CardHeader>
                    <CardContent>
                        {mengajar.length === 0 ? (
                            <p className="text-sm text-muted-foreground text-center py-4">
                                Belum ada kombinasi mengajar. Hubungi admin.
                            </p>
                        ) : (
                            <div className="space-y-2">
                                {mengajar.map((m) => (
                                    <div key={m.id} className="flex items-center gap-2 p-2 rounded-lg bg-surface">
                                        <BookOpen className="h-4 w-4 text-primary" />
                                        <Badge variant="info">{m.kelas}</Badge>
                                        <span className="text-sm font-medium">{m.mata_pelajaran}</span>
                                    </div>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>

            <Card>
                <CardHeader>Menu Cepat</CardHeader>
                <CardContent className="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <Link href="/guru/input-nilai" className="flex items-center gap-3 p-3 rounded-lg border border-border hover:bg-surface transition">
                        <div className="p-2 bg-blue-100 text-primary rounded-lg">
                            <ClipboardList className="h-5 w-5" />
                        </div>
                        <div>
                            <p className="text-sm font-semibold">Input Nilai</p>
                            <p className="text-xs text-muted-foreground">Input nilai untuk siswa di kelas & mapel yang Anda ajar</p>
                        </div>
                    </Link>
                    <Link href="/guru/rekap" className="flex items-center gap-3 p-3 rounded-lg border border-border hover:bg-surface transition">
                        <div className="p-2 bg-green-100 text-success rounded-lg">
                            <BookOpen className="h-5 w-5" />
                        </div>
                        <div>
                            <p className="text-sm font-semibold">Rekap Nilai</p>
                            <p className="text-xs text-muted-foreground">Lihat rekap nilai yang sudah diinput</p>
                        </div>
                    </Link>
                </CardContent>
            </Card>
        </Container>
    );
}

GuruDashboard.layout = { title: 'Dashboard Guru' };
