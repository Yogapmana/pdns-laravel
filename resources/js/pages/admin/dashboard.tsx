import { Link } from '@inertiajs/react';
import {
    Users,
    GraduationCap,
    BookOpen,
    CheckCircle,
    AlertTriangle,
    Filter,
    BarChart3,
    Trophy,
} from 'lucide-react';
import { useState } from 'react';
import { Alert } from '@/components/ui/alert';
import { Card, CardHeader, CardContent, StatCard, PageHeader, Container } from '@/components/ui/shared';
import { useFlashToast } from '@/hooks/use-flash-toast';
import { DonutChart } from '@/components/dashboard/donut-chart';
import { KelasBarChart, type RekapKelas } from '@/components/dashboard/kelas-bar-chart';
import { MapelBarChart, type RataRataMapel } from '@/components/dashboard/mapel-bar-chart';
import { SiswaList, type SiswaEntry, type SortKey } from '@/components/dashboard/siswa-list';

type Stats = {
    total_siswa: number;
    total_guru: number;
    total_nilai: number;
    total_mapel: number;
    lulus: number;
    tidak_lulus: number;
    persentase_lulus: number;
};


type Props = {
    stats: Stats;
    rekap_per_kelas: RekapKelas[];
    rata_rata_per_mapel: RataRataMapel[];
    top_siswa: SiswaEntry[];
    siswa_perhatian: SiswaEntry[];
    daftar_kelas: string[];
    kkm: number;
};



export default function AdminDashboard({
    stats,
    rekap_per_kelas,
    rata_rata_per_mapel,
    top_siswa,
    siswa_perhatian,
    kkm,
}: Props) {
    useFlashToast();

    const [topSort, setTopSort] = useState<SortKey>('rank');
    const [perhatianSort, setPerhatianSort] = useState<SortKey>('rank');

    return (
        <Container>
            <PageHeader
                title="Dashboard Admin"
                description="Ringkasan data akademik sekolah"
            />

            <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <StatCard label="Total Siswa" value={stats.total_siswa} icon={<Users className="h-4 w-4" />} color="primary" variant="colored" description="Seluruh siswa aktif" />
                <StatCard label="Total Guru" value={stats.total_guru} icon={<GraduationCap className="h-4 w-4" />} color="accent" variant="colored" description="Termasuk non-aktif" />
                <StatCard label="Mata Pelajaran" value={stats.total_mapel} icon={<BookOpen className="h-4 w-4" />} color="warning" variant="colored" description="Total mapel terdaftar" />
                <StatCard label="Persentase Lulus" value={`${stats.persentase_lulus}%`} icon={<CheckCircle className="h-4 w-4" />} color="success" variant="colored" description="Siswa dengan nilai ≥ KKM" />
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <Card>
                    <CardHeader>
                        <div className="flex items-center gap-2">
                            <BarChart3 className="h-4 w-4 text-primary" />
                            <span className="font-semibold">Komposisi Kelulusan</span>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <DonutChart lulus={stats.lulus} tidakLulus={stats.tidak_lulus} />
                    </CardContent>
                </Card>

                <Card className="lg:col-span-2">
                    <CardHeader
                        action={
                            <span className="text-xs text-muted-foreground flex items-center gap-1">
                                <Filter className="h-3 w-3" /> Klik bar untuk detail
                            </span>
                        }
                    >
                        <div className="flex items-center gap-2">
                            <Users className="h-4 w-4 text-primary" />
                            <span className="font-semibold">Kelulusan per Kelas</span>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <KelasBarChart rekap={rekap_per_kelas} />
                    </CardContent>
                </Card>
            </div>

            <div className="grid grid-cols-1 gap-6">
                <Card>
                    <CardHeader
                        action={
                            <span className="text-xs text-muted-foreground flex items-center gap-1">
                                <Filter className="h-3 w-3" /> Klik bar untuk laporan
                            </span>
                        }
                    >
                        <div className="flex items-center gap-2">
                            <BarChart3 className="h-4 w-4 text-primary" />
                            <span className="font-semibold">Rata-rata Nilai per Mata Pelajaran</span>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <MapelBarChart data={rata_rata_per_mapel} kkm={kkm} />
                    </CardContent>
                </Card>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <Card>
                    <CardContent className="pt-6">
                        <SiswaList
                            title="Siswa Berprestasi"
                            icon={<Trophy className="h-4 w-4 text-success" />}
                            data={top_siswa}
                            sortKey={topSort}
                            onSortChange={setTopSort}
                            emptyMessage="Belum ada siswa dengan nilai lengkap."
                            colorScheme="success"
                        />
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="pt-6">
                        <SiswaList
                            title="Siswa Perlu Perhatian"
                            icon={<AlertTriangle className="h-4 w-4 text-danger" />}
                            data={siswa_perhatian}
                            sortKey={perhatianSort}
                            onSortChange={setPerhatianSort}
                            emptyMessage="Tidak ada siswa yang memerlukan perhatian khusus. 🎉"
                            colorScheme="danger"
                            showRatio
                        />
                    </CardContent>
                </Card>
            </div>

            {stats.tidak_lulus > stats.lulus && (
                <Alert variant="error">
                    Siswa tidak lulus ({stats.tidak_lulus}) lebih banyak dari lulus ({stats.lulus}). Pertimbangkan remedial.
                </Alert>
            )}

            {stats.lulus > 0 && stats.lulus > stats.tidak_lulus && (
                <Alert variant="success">
                    {stats.persentase_lulus}% lulus dari {stats.total_nilai} nilai. Pertahankan!
                </Alert>
            )}
        </Container>
    );
}

AdminDashboard.layout = { title: 'Dashboard' };
