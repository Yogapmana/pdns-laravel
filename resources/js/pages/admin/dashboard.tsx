import { Link } from '@inertiajs/react';
import {
    Users,
    GraduationCap,
    BookOpen,
    CheckCircle,
    FileText,
    UserPlus,
    AlertTriangle,
    Filter,
    BarChart3,
    Trophy,
} from 'lucide-react';
import { useState, useMemo } from 'react';
import { Alert } from '@/components/ui/alert';
import { Card, CardHeader, CardContent, StatCard, PageHeader, Container, MenuLink } from '@/components/ui/shared';
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

type RekapKelas = {
    kelas: string;
    jumlah_siswa: number;
    lulus: number;
    tidak_lulus: number;
    total_nilai: number;
    persentase_lulus: number;
};

type RataRataMapel = {
    mata_pelajaran: string;
    rata_rata: number;
    total_nilai: number;
    lulus: number;
    tidak_lulus: number;
    persentase_lulus: number;
};

type SiswaEntry = {
    nis: string;
    nama_siswa: string;
    kelas: string;
    rata_rata: number;
    total_mapel: number;
    lulus: number;
    tidak_lulus: number;
    rasio_tidak_lulus?: number;
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

function formatAvg(v: number | null | undefined): string {
    if (v === null || v === undefined) {
        return '—';
    }

    return Number(v).toFixed(2);
}

function DonutChart({ lulus, tidakLulus }: { lulus: number; tidakLulus: number }) {
    const total = lulus + tidakLulus;
    const lulusPct = total > 0 ? (lulus / total) * 100 : 0;
    const tdkPct = total > 0 ? (tidakLulus / total) * 100 : 0;

    const background = total === 0
        ? '#E2E8F0'
        : `conic-gradient(#10B981 0deg ${lulusPct * 3.6}deg, #EF4444 ${lulusPct * 3.6}deg ${(lulusPct + tdkPct) * 3.6}deg, #E2E8F0 ${(lulusPct + tdkPct) * 3.6}deg 360deg)`;

    return (
        <div className="flex flex-col items-center gap-3">
            <div className="relative w-40 h-40">
                <div
                    className="absolute inset-0 rounded-full transition-[background] duration-700"
                    style={{ background }}
                    role="img"
                    aria-label={`Donut chart kelulusan: ${lulus} lulus, ${tidakLulus} tidak lulus`}
                />
                <div className="absolute inset-6 bg-white rounded-full flex items-center justify-center flex-col">
                    <p className="text-3xl font-bold text-navy">{total}</p>
                    <p className="text-[10px] text-muted-foreground uppercase tracking-wide">Total Nilai</p>
                </div>
            </div>
            <div className="grid grid-cols-2 gap-3 w-full text-xs">
                <div className="flex items-center gap-2">
                    <span className="w-3 h-3 rounded-sm bg-success flex-shrink-0" />
                    <span className="font-semibold text-success">Lulus</span>
                    <span className="ml-auto font-mono font-bold">{lulus}</span>
                </div>
                <div className="flex items-center gap-2">
                    <span className="w-3 h-3 rounded-sm bg-danger flex-shrink-0" />
                    <span className="font-semibold text-danger">Tidak</span>
                    <span className="ml-auto font-mono font-bold">{tidakLulus}</span>
                </div>
            </div>
        </div>
    );
}

function KelasBarChart({ rekap }: { rekap: RekapKelas[] }) {
    if (rekap.length === 0) {
        return <p className="text-sm text-muted-foreground text-center py-6">Belum ada data.</p>;
    }

    const maxTotal = Math.max(...rekap.map((r) => r.lulus + r.tidak_lulus), 1);

    return (
        <div className="space-y-3">
            {rekap.map((r) => {
                const total = r.lulus + r.tidak_lulus;
                const lulusPct = total > 0 ? (r.lulus / maxTotal) * 100 : 0;
                const tdkPct = total > 0 ? (r.tidak_lulus / maxTotal) * 100 : 0;

                return (
                    <Link
                        key={r.kelas}
                        href={`/admin/siswa?kelas=${encodeURIComponent(r.kelas)}`}
                        prefetch
                        className="block group"
                        title={`Klik untuk lihat siswa di ${r.kelas}`}
                    >
                        <div className="flex items-center justify-between mb-1 text-xs">
                            <span className="font-bold text-navy group-hover:text-primary transition">{r.kelas}</span>
                            <span className="text-muted-foreground font-mono">
                                {r.lulus}/{total} <span className="text-success font-semibold">({r.persentase_lulus}%)</span>
                            </span>
                        </div>
                        <div className="relative h-5 w-full bg-slate-100 rounded overflow-hidden flex">
                            <div
                                className="h-full bg-success group-hover:bg-emerald-600 transition-all"
                                style={{ width: `${lulusPct}%` }}
                                title={`Lulus: ${r.lulus}`}
                            />
                            <div
                                className="h-full bg-danger group-hover:bg-rose-600 transition-all"
                                style={{ width: `${tdkPct}%` }}
                                title={`Tidak Lulus: ${r.tidak_lulus}`}
                            />
                        </div>
                    </Link>
                );
            })}
            <div className="flex items-center gap-4 pt-2 text-[10px] text-muted-foreground">
                <span className="flex items-center gap-1">
                    <span className="w-3 h-2 bg-success rounded-sm" /> Lulus
                </span>
                <span className="flex items-center gap-1">
                    <span className="w-3 h-2 bg-danger rounded-sm" /> Tidak Lulus
                </span>
            </div>
        </div>
    );
}

function MapelBarChart({ data, kkm }: { data: RataRataMapel[]; kkm: number }) {
    if (data.length === 0) {
        return <p className="text-sm text-muted-foreground text-center py-6">Belum ada data.</p>;
    }

    const maxVal = 100;

    return (
        <div className="space-y-3">
            {data.map((m) => {
                const pct = (m.rata_rata / maxVal) * 100;
                const kkmPct = (kkm / maxVal) * 100;
                const barColor = m.rata_rata >= kkm ? 'bg-success' : 'bg-warning';

                return (
                    <Link
                        key={m.mata_pelajaran}
                        href={`/admin/laporan?mapel=${encodeURIComponent(m.mata_pelajaran)}`}
                        prefetch
                        className="block group"
                        title={`Klik untuk lihat laporan ${m.mata_pelajaran}`}
                    >
                        <div className="flex items-center justify-between mb-1 text-xs">
                            <span className="font-semibold text-navy truncate group-hover:text-primary transition flex-1 mr-2">
                                {m.mata_pelajaran}
                            </span>
                            <span className={`font-mono font-bold flex-shrink-0 ${m.rata_rata >= kkm ? 'text-success' : 'text-warning'}`}>
                                {formatAvg(m.rata_rata)}
                            </span>
                        </div>
                        <div className="relative h-4 w-full bg-slate-100 rounded overflow-hidden">
                            <div
                                className={`absolute inset-y-0 left-0 ${barColor} group-hover:opacity-80 transition-all`}
                                style={{ width: `${pct}%` }}
                            />
                            <div
                                className="absolute inset-y-0 w-0.5 bg-navy"
                                style={{ left: `${kkmPct}%` }}
                                title={`KKM ${kkm}`}
                            />
                        </div>
                        <div className="flex items-center justify-between mt-0.5 text-[10px] text-muted-foreground">
                            <span>{m.total_nilai} nilai</span>
                            <span>
                                Lulus <span className="text-success font-semibold">{m.lulus}</span> • Tidak{' '}
                                <span className="text-danger font-semibold">{m.tidak_lulus}</span>
                            </span>
                        </div>
                    </Link>
                );
            })}
            <div className="flex items-center gap-3 pt-2 text-[10px] text-muted-foreground">
                <span className="flex items-center gap-1">
                    <span className="w-3 h-0.5 bg-navy" /> KKM {kkm}
                </span>
                <span className="flex items-center gap-1">
                    <span className="w-3 h-2 bg-success rounded-sm" /> ≥ KKM
                </span>
                <span className="flex items-center gap-1">
                    <span className="w-3 h-2 bg-warning rounded-sm" /> &lt; KKM
                </span>
            </div>
        </div>
    );
}

type SortKey = 'rank' | 'alpha';

function SiswaList({
    title,
    icon,
    data,
    sortKey,
    onSortChange,
    emptyMessage,
    colorScheme,
    showRatio = false,
}: {
    title: string;
    icon: React.ReactNode;
    data: SiswaEntry[];
    sortKey: SortKey;
    onSortChange: (key: SortKey) => void;
    emptyMessage: string;
    colorScheme: 'success' | 'danger';
    showRatio?: boolean;
}) {
    const sorted = useMemo(() => {
        if (sortKey === 'alpha') {
            return [...data].sort((a, b) => a.nama_siswa.localeCompare(b.nama_siswa));
        }

        return data;
    }, [data, sortKey]);

    const accentColor = colorScheme === 'success' ? 'text-success' : 'text-danger';
    const barColor = colorScheme === 'success' ? 'bg-emerald-500' : 'bg-rose-500';

    return (
        <div>
            <div className="flex items-center justify-between mb-3">
                <div className="flex items-center gap-2">
                    {icon}
                    <span className="font-semibold text-navy">{title}</span>
                </div>
                <button
                    type="button"
                    onClick={() => onSortChange(sortKey === 'rank' ? 'alpha' : 'rank')}
                    className="inline-flex items-center gap-1 px-2 py-1 text-[10px] font-semibold text-secondary border border-border rounded hover:bg-surface transition"
                    title={sortKey === 'rank' ? 'Urutkan A-Z' : 'Urutkan berdasarkan ranking'}
                >
                    <ArrowUpDown className="h-3 w-3" />
                    {sortKey === 'rank' ? 'Ranking' : 'A-Z'}
                </button>
            </div>
            {sorted.length === 0 ? (
                <p className="text-sm text-muted-foreground text-center py-6">{emptyMessage}</p>
            ) : (
                <div className="space-y-2">
                    {sorted.map((s, i) => {
                        const ratio = showRatio && s.rasio_tidak_lulus !== undefined ? s.rasio_tidak_lulus : null;
                        const barPct = colorScheme === 'success' ? s.rata_rata : (ratio ?? 0);

                        return (
                            <Link
                                key={s.nis}
                                href={`/admin/siswa/${s.nis}/edit`}
                                prefetch
                                className="flex items-center gap-3 p-2 rounded-lg border border-border hover:border-primary hover:bg-blue-50/50 transition group"
                            >
                                <span
                                    className={`flex-shrink-0 w-6 h-6 rounded-full ${colorScheme === 'success' ? 'bg-emerald-100' : 'bg-rose-100'} ${accentColor} text-xs font-bold flex items-center justify-center`}
                                >
                                    {sortKey === 'rank' ? i + 1 : s.nama_siswa.charAt(0).toUpperCase()}
                                </span>
                                <div className="flex-1 min-w-0">
                                    <div className="flex items-baseline justify-between gap-2">
                                        <p className="text-sm font-semibold text-navy truncate group-hover:text-primary transition">
                                            {s.nama_siswa}
                                        </p>
                                        <span className={`text-sm font-mono font-bold flex-shrink-0 ${accentColor}`}>
                                            {colorScheme === 'success' ? formatAvg(s.rata_rata) : `${ratio}%`}
                                        </span>
                                    </div>
                                    <div className="flex items-center gap-2 mt-0.5">
                                        <span className="text-[10px] text-muted-foreground">{s.kelas}</span>
                                        <span className="text-[10px] text-muted-foreground">•</span>
                                        <span className="text-[10px] text-muted-foreground">
                                            {colorScheme === 'success'
                                                ? `${s.total_mapel} mapel • Lulus ${s.lulus}/${s.total_mapel}`
                                                : `${s.tidak_lulus}/${s.total_mapel} mapel tidak lulus`}
                                        </span>
                                    </div>
                                    <div className="relative h-1.5 w-full bg-slate-100 rounded-full overflow-hidden mt-1">
                                        <div
                                            className={`absolute inset-y-0 left-0 ${barColor}`}
                                            style={{ width: `${Math.min(100, barPct)}%` }}
                                        />
                                    </div>
                                </div>
                            </Link>
                        );
                    })}
                </div>
            )}
        </div>
    );
}

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

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <Card className="lg:col-span-2">
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

                <Card>
                    <CardHeader>Aksi Cepat</CardHeader>
                    <CardContent className="space-y-3">
                        <MenuLink
                            href="/admin/siswa/create"
                            icon={<UserPlus />}
                            title="Tambah Siswa"
                            description="Daftarkan siswa baru"
                            color="primary"
                        />
                        <MenuLink
                            href="/admin/guru/create"
                            icon={<GraduationCap />}
                            title="Tambah Guru"
                            description="Daftarkan guru baru"
                            color="accent"
                        />
                        <MenuLink
                            href="/admin/laporan"
                            icon={<FileText />}
                            title="Cetak Laporan"
                            description="Generate laporan kelas"
                            color="success"
                        />
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
                    <p className="font-semibold mb-1">Tingkat kelulusan rendah</p>
                    <p className="text-xs font-normal">
                        Jumlah siswa tidak lulus ({stats.tidak_lulus}) melebihi jumlah siswa lulus ({stats.lulus}). Pertimbangkan untuk melakukan evaluasi pembelajaran atau remedial.
                    </p>
                </Alert>
            )}

            {stats.lulus > 0 && stats.lulus > stats.tidak_lulus && (
                <Alert variant="success">
                    <p className="font-semibold mb-1">Performa akademik baik</p>
                    <p className="text-xs font-normal">
                        {stats.persentase_lulus}% siswa lulus dari total {stats.total_nilai} nilai yang diinput. Pertahankan kualitas pembelajaran.
                    </p>
                </Alert>
            )}
        </Container>
    );
}

AdminDashboard.layout = { title: 'Dashboard' };
