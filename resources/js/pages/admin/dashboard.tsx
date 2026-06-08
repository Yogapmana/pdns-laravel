import { Link } from '@inertiajs/react';
import { index as reportsIndex } from '@/routes/admin/reports';
import {
    Users,
    GraduationCap,
    BookOpen,
    CheckCircle,
    AlertTriangle,
    BarChart3,
    Trophy,
    ChevronDown,
    ChevronUp,
    ChevronRight,
} from 'lucide-react';
import { useState } from 'react';
import { ActionChecklist } from '@/components/dashboard/action-checklist';
import {
    KelasBarChart

} from '@/components/dashboard/kelas-bar-chart';
import type { RekapKelas } from '@/components/dashboard/kelas-bar-chart';
import { SiswaList } from '@/components/dashboard/siswa-list';
import type { SiswaEntry } from '@/components/dashboard/siswa-list';
import {
    Card,
    CardHeader,
    CardContent,
    StatCard,
    PageHeader,
    Container,
} from '@/components/ui/shared';
import { useFlashToast } from '@/hooks/use-flash-toast';

type Stats = {
    total_siswa: number;
    total_guru: number;
    total_nilai: number;
    total_mapel: number;
    lulus: number;
    tidak_lulus: number;
    persentase_lulus: number;
};

export type TindakanItem = {
    id: string;
    title: string;
    description: string;
    priority: 'high' | 'medium' | 'low';
    href: string;
};

type Props = {
    stats: Stats;
    rekap_per_kelas: RekapKelas[];
    top_siswa: SiswaEntry[];
    siswa_perhatian: SiswaEntry[];
    tindakan_penting: TindakanItem[];
};

export default function AdminDashboard({
    stats,
    rekap_per_kelas,
    top_siswa,
    siswa_perhatian,
    tindakan_penting,
}: Props) {
    useFlashToast();

    const [isKelasExpanded, setIsKelasExpanded] = useState(false);

    return (
        <Container>
            <PageHeader
                title="Dashboard Admin"
                description="Ringkasan data akademik sekolah"
            />

            <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <StatCard
                    label="Total Siswa Aktif"
                    value={stats.total_siswa}
                    icon={<Users className="h-4 w-4" />}
                    color="primary"
                    variant="colored"
                    description="Seluruh siswa aktif"
                />
                <StatCard
                    label="Guru Aktif"
                    value={stats.total_guru}
                    icon={<GraduationCap className="h-4 w-4" />}
                    color="accent"
                    variant="colored"
                    description="Termasuk non-aktif"
                />
                <StatCard
                    label="Persentase Lulus"
                    value={`${stats.persentase_lulus}%`}
                    icon={<CheckCircle className="h-4 w-4" />}
                    color="success"
                    variant="colored"
                    description="Siswa dengan nilai ≥ KKM"
                />
                <StatCard
                    label="Nilai Belum Lulus"
                    value={stats.tidak_lulus}
                    icon={<BookOpen className="h-4 w-4" />}
                    color="danger"
                    variant="colored"
                    description="Total mapel tidak lulus"
                />
            </div>

            <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <Card>
                    <CardHeader
                        action={
                            <button
                                onClick={() =>
                                    setIsKelasExpanded(!isKelasExpanded)
                                }
                                className="flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-600 transition-colors hover:bg-slate-200 hover:text-slate-900"
                            >
                                {isKelasExpanded ? (
                                    <>
                                        Tutup <ChevronUp className="h-3 w-3" />
                                    </>
                                ) : (
                                    <>
                                        Lihat semua{' '}
                                        <ChevronDown className="h-3 w-3" />
                                    </>
                                )}
                            </button>
                        }
                    >
                        <div className="flex items-center gap-2">
                            <BarChart3 className="h-4 w-4 text-muted-foreground" />
                            <span className="font-semibold">
                                Kelulusan per kelas
                            </span>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <KelasBarChart
                            rekap={rekap_per_kelas}
                            isExpanded={isKelasExpanded}
                        />
                        <div className="mt-6 flex justify-between border-t border-border pt-4 text-xs font-medium text-muted-foreground">
                            <span className="flex items-center gap-2">
                                <span className="h-1.5 w-1.5 rounded-full bg-success"></span>{' '}
                                ≥ 70% - perlu perhatian rendah
                            </span>
                            <span className="flex items-center gap-2">
                                <span className="h-1.5 w-1.5 rounded-full bg-danger"></span>{' '}
                                &lt; 70% - perlu intervensi
                            </span>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="pt-6">
                        <ActionChecklist items={tindakan_penting} />
                    </CardContent>
                </Card>
            </div>

            <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <Card>
                    <CardHeader
                        action={
                            <Link
                                href={reportsIndex.url({ query: { kelas: 'all', sort: 'ranking', sort_type: 'paralel' } })}
                                className="flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-600 transition-colors hover:bg-slate-200 hover:text-slate-900"
                            >
                                Selengkapnya <ChevronRight className="h-3 w-3" />
                            </Link>
                        }
                    >
                        <div className="flex items-center gap-2">
                            <Trophy className="h-4 w-4 text-muted-foreground" />
                            <span className="font-semibold">
                                Siswa berprestasi
                            </span>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <SiswaList
                            data={top_siswa}
                            emptyMessage="Belum ada data siswa."
                        />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader
                        action={
                            <Link
                                href={reportsIndex.url({ query: { kelas: 'all', sort: 'perhatian', sort_type: 'paralel' } })}
                                className="flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-600 transition-colors hover:bg-slate-200 hover:text-slate-900"
                            >
                                Selengkapnya <ChevronRight className="h-3 w-3" />
                            </Link>
                        }
                    >
                        <div className="flex items-center gap-2">
                            <AlertTriangle className="h-4 w-4 text-danger" />
                            <span className="font-semibold text-danger">
                                Siswa perlu perhatian
                            </span>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <SiswaList
                            data={siswa_perhatian}
                            emptyMessage="Tidak ada siswa dengan rasio kelulusan di bawah 70%."
                            isDanger
                            showRatio
                        />
                    </CardContent>
                </Card>
            </div>
        </Container>
    );
}

AdminDashboard.layout = { title: 'Dashboard' };
