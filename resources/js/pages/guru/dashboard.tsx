import { Link } from '@inertiajs/react';
import {
    Users,
    FileEdit,
    CheckCircle,
    XCircle,
    BookOpen,
    ChevronRight,
    TrendingUp,
    AlertTriangle,
    Edit3,
    Lock,
} from 'lucide-react';
import { Alert } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardHeader, CardContent } from '@/components/ui/card';
import {
    PageHeader,
    Container,
    StatCard,
    DataTable,
} from '@/components/ui/shared';
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

type PerCombo = {
    id_mengajar: number;
    kelas: string;
    mata_pelajaran: string;
    jumlah_siswa: number;
    jumlah_input: number;
    jumlah_final: number;
    jumlah_draft: number;
};

type NotifikasiItem = PerCombo & { sisa?: number };

type Notifikasi = {
    belum_diinput: NotifikasiItem[];
    masih_draft: NotifikasiItem[];
};

type Props = {
    guru: Guru;
    stats: Stats;
    mengajar: Mengajar[];
    per_combo_stats: PerCombo[];
    notifikasi: Notifikasi;
};

function formatComboList(items: NotifikasiItem[]): string {
    return items.map((it) => `${it.mata_pelajaran} ${it.kelas}`).join(', ');
}

function comboStateLabel(combo: PerCombo): {
    label: string;
    variant: 'neutral' | 'success' | 'warning' | 'info' | 'danger';
} {
    if (combo.jumlah_siswa === 0) {
        return { label: 'Kelas kosong', variant: 'neutral' };
    }

    if (combo.jumlah_input < combo.jumlah_siswa) {
        const sisa = combo.jumlah_siswa - combo.jumlah_input;

        return { label: `${sisa} belum input`, variant: 'warning' };
    }

    if (combo.jumlah_draft > 0) {
        return { label: `${combo.jumlah_draft} Draft`, variant: 'warning' };
    }

    if (combo.jumlah_final === combo.jumlah_siswa) {
        return { label: 'Semua Final', variant: 'success' };
    }

    return {
        label: `${combo.jumlah_final}/${combo.jumlah_siswa} Final`,
        variant: 'info',
    };
}

export default function GuruDashboard({
    guru,
    stats,
    per_combo_stats,
    notifikasi,
}: Props) {
    useFlashToast();

    const mengajar = guru.mengajar ?? [];
    const totalKelas = new Set(mengajar.map((m) => m.kelas)).size;
    const totalMapel = new Set(mengajar.map((m) => m.mata_pelajaran)).size;

    const belumDiinput = notifikasi?.belum_diinput ?? [];
    const masihDraft = notifikasi?.masih_draft ?? [];
    const adaNotifikasi = belumDiinput.length > 0 || masihDraft.length > 0;

    const comboFinal = per_combo_stats.filter(
        (c) => c.jumlah_siswa > 0 && c.jumlah_final === c.jumlah_siswa,
    ).length;
    const comboSebagian = per_combo_stats.filter(
        (c) => c.jumlah_siswa > 0 && c.jumlah_draft > 0 && c.jumlah_final > 0,
    ).length;
    const comboBelumInput = per_combo_stats.filter(
        (c) => c.jumlah_siswa > 0 && c.jumlah_input < c.jumlah_siswa,
    ).length;
    const comboKosong = per_combo_stats.filter(
        (c) => c.jumlah_siswa === 0,
    ).length;

    return (
        <Container>
            <PageHeader
                title={`Selamat Datang, ${guru.nama_guru}`}
                description={
                    <div className="flex flex-wrap items-center gap-2"></div>
                }
                action={
                    <Link href="/guru/input-nilai">
                        <Button>
                            <Edit3 className="h-4 w-4" />
                            Input Nilai
                        </Button>
                    </Link>
                }
            />

            {adaNotifikasi && (
                <div className="mb-6 flex flex-col gap-4">
                    {belumDiinput.length > 0 && (
                        <Alert variant="warning">
                            <div className="flex flex-col gap-2">
                                <p>
                                    Nilai {belumDiinput.length} kelas (
                                    {formatComboList(belumDiinput)}){' '}
                                    <strong>belum lengkap diinput</strong>.
                                </p>
                                <div className="flex flex-wrap gap-2">
                                    {belumDiinput.map((it) => (
                                        <Link
                                            key={it.id_mengajar}
                                            href={`/guru/input-nilai?kelas=${encodeURIComponent(it.kelas)}&mata_pelajaran=${encodeURIComponent(it.mata_pelajaran)}`}
                                            className="inline-flex items-center gap-1.5 rounded-md border border-yellow-300 bg-white px-2.5 py-1 text-xs font-medium text-yellow-900 transition hover:bg-yellow-100"
                                        >
                                            <Badge
                                                variant="info"
                                                className="!px-1.5 !py-0 !text-[10px]"
                                            >
                                                {it.kelas}
                                            </Badge>
                                            <span>{it.mata_pelajaran}</span>
                                            <span className="text-yellow-700">
                                                ({it.jumlah_input}/
                                                {it.jumlah_siswa})
                                            </span>
                                            <ChevronRight className="h-3 w-3" />
                                        </Link>
                                    ))}
                                </div>
                            </div>
                        </Alert>
                    )}

                    {masihDraft.length > 0 && (
                        <Alert variant="error">
                            <div className="flex flex-col gap-2">
                                <p>
                                    Nilai {masihDraft.length} kelas (
                                    {formatComboList(masihDraft)}) sudah lengkap
                                    namun <strong>masih Draft</strong>. Silakan
                                    lakukan Validasi Final.
                                </p>
                                <div className="flex flex-wrap gap-2">
                                    {masihDraft.map((it) => (
                                        <Link
                                            key={it.id_mengajar}
                                            href={`/guru/input-nilai?kelas=${encodeURIComponent(it.kelas)}&mata_pelajaran=${encodeURIComponent(it.mata_pelajaran)}`}
                                            className="inline-flex items-center gap-1.5 rounded-md border border-red-300 bg-white px-2.5 py-1 text-xs font-medium text-red-900 transition hover:bg-red-100"
                                        >
                                            <Badge
                                                variant="info"
                                                className="!px-1.5 !py-0 !text-[10px]"
                                            >
                                                {it.kelas}
                                            </Badge>
                                            <span>{it.mata_pelajaran}</span>
                                            <Badge
                                                variant="warning"
                                                className="!px-1.5 !py-0 !text-[10px]"
                                            >
                                                {it.jumlah_draft} Draft
                                            </Badge>
                                            <ChevronRight className="h-3 w-3" />
                                        </Link>
                                    ))}
                                </div>
                            </div>
                        </Alert>
                    )}
                </div>
            )}

            <Card>
                <CardHeader>Ringkasan Nilai</CardHeader>
                <CardContent>
                    <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
                        <StatCard
                            label="Total Siswa"
                            value={stats.total_siswa}
                            icon={<Users className="h-4 w-4" />}
                            color="primary"
                            variant="colored"
                            description="Siswa di kelas yang Anda ajar"
                        />
                        <StatCard
                            label="Total Nilai"
                            value={stats.total_nilai}
                            icon={<FileEdit className="h-4 w-4" />}
                            color="neutral"
                            variant="colored"
                            description="Baris nilai yang tersimpan"
                        />
                        <StatCard
                            label="Status Draft"
                            value={stats.draft}
                            icon={<Edit3 className="h-4 w-4" />}
                            color="warning"
                            variant="colored"
                            description="Nilai yang masih bisa diedit"
                        />
                        <StatCard
                            label="Status Final"
                            value={stats.final}
                            icon={<Lock className="h-4 w-4" />}
                            color="success"
                            variant="colored"
                            description="Nilai yang sudah dikunci"
                        />
                    </div>
                </CardContent>
            </Card>

            <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <Card>
                    <CardHeader>Ringkasan Kelulusan</CardHeader>
                    <CardContent>
                        <div className="flex flex-col divide-y divide-slate-100">
                            <div className="flex items-center justify-between py-3">
                                <div className="flex items-center gap-3">
                                    <div className="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                                        <CheckCircle className="h-4 w-4" />
                                    </div>
                                    <span className="text-sm font-medium text-slate-600">Lulus (≥ KKM 70)</span>
                                </div>
                                <span className="text-xl font-bold text-emerald-600">{stats.lulus}</span>
                            </div>
                            <div className="flex items-center justify-between py-3">
                                <div className="flex items-center gap-3">
                                    <div className="flex h-8 w-8 items-center justify-center rounded-full bg-rose-100 text-rose-600">
                                        <XCircle className="h-4 w-4" />
                                    </div>
                                    <span className="text-sm font-medium text-slate-600">Tidak Lulus (&lt; KKM 70)</span>
                                </div>
                                <span className="text-xl font-bold text-rose-600">{stats.tidak_lulus}</span>
                            </div>
                            <div className="flex items-center justify-between py-3">
                                <div className="flex items-center gap-3">
                                    <div className="flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 text-blue-600">
                                        <TrendingUp className="h-4 w-4" />
                                    </div>
                                    <span className="text-sm font-medium text-slate-600">Rata-rata Nilai</span>
                                </div>
                                <span className="text-xl font-bold text-blue-600">{stats.rata_rata}</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <DataTable className="lg:col-span-2">
                    <CardHeader>Status Mengajar</CardHeader>
                    <CardContent>
                        {per_combo_stats.length === 0 ? (
                            <p className="py-4 text-center text-sm text-muted-foreground">
                                Belum ada kombinasi mengajar. Hubungi admin.
                            </p>
                        ) : (
                            <div className="-mx-2 overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="text-secondary">
                                            <th className="px-2 py-2 text-left text-xs font-bold tracking-wide uppercase">
                                                Kelas
                                            </th>
                                            <th className="px-2 py-2 text-left text-xs font-bold tracking-wide uppercase">
                                                Mata Pelajaran
                                            </th>
                                            <th className="px-2 py-2 text-center text-xs font-bold tracking-wide uppercase">
                                                Siswa
                                            </th>
                                            <th className="px-2 py-2 text-center text-xs font-bold tracking-wide uppercase">
                                                Input
                                            </th>
                                            <th className="px-2 py-2 text-center text-xs font-bold tracking-wide uppercase">
                                                Status
                                            </th>
                                            <th className="w-20 px-2 py-2 text-right text-xs font-bold tracking-wide uppercase">
                                                Aksi
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100">
                                        {per_combo_stats.map((c) => {
                                            const state = comboStateLabel(c);

                                            return (
                                                <tr
                                                    key={c.id_mengajar}
                                                    className="hover:bg-blue-50/50"
                                                >
                                                    <td className="px-2 py-2">
                                                        <Badge variant="info">
                                                            {c.kelas}
                                                        </Badge>
                                                    </td>
                                                    <td className="px-2 py-2 font-medium">
                                                        {c.mata_pelajaran}
                                                    </td>
                                                    <td className="px-2 py-2 text-center font-mono">
                                                        {c.jumlah_siswa}
                                                    </td>
                                                    <td className="px-2 py-2 text-center font-mono">
                                                        {c.jumlah_input}/
                                                        {c.jumlah_siswa}
                                                    </td>
                                                    <td className="px-2 py-2 text-center">
                                                        <Badge
                                                            variant={
                                                                state.variant
                                                            }
                                                        >
                                                            {state.label}
                                                        </Badge>
                                                    </td>
                                                    <td className="px-2 py-2 text-right">
                                                        <Link
                                                            href={`/guru/input-nilai?kelas=${encodeURIComponent(c.kelas)}&mata_pelajaran=${encodeURIComponent(c.mata_pelajaran)}`}
                                                            className="inline-flex items-center gap-1 rounded px-2 py-1 text-xs font-medium text-primary transition hover:bg-blue-100"
                                                            title="Buka Input Nilai"
                                                        >
                                                            Buka{' '}
                                                            <ChevronRight className="h-3 w-3" />
                                                        </Link>
                                                    </td>
                                                </tr>
                                            );
                                        })}
                                    </tbody>
                                </table>
                            </div>
                        )}

                        {per_combo_stats.length > 0 && (
                            <div className="mt-4 flex flex-wrap gap-2 border-t border-border pt-3 text-xs text-muted-foreground">
                                <span className="inline-flex items-center gap-1">
                                    <CheckCircle className="h-3 w-3 text-success" />
                                    {comboFinal} combo Final
                                </span>
                                {comboSebagian > 0 && (
                                    <span className="inline-flex items-center gap-1">
                                        <Edit3 className="h-3 w-3 text-warning" />
                                        {comboSebagian} combo sebagian
                                    </span>
                                )}
                                {comboBelumInput > 0 && (
                                    <span className="inline-flex items-center gap-1">
                                        <AlertTriangle className="h-3 w-3 text-warning" />
                                        {comboBelumInput} combo belum input
                                    </span>
                                )}
                                {comboKosong > 0 && (
                                    <span className="inline-flex items-center gap-1">
                                        <BookOpen className="h-3 w-3 text-muted-foreground" />
                                        {comboKosong} combo kelas kosong
                                    </span>
                                )}
                            </div>
                        )}
                    </CardContent>
                </DataTable>
            </div>
        </Container>
    );
}

GuruDashboard.layout = { title: 'Dashboard Guru' };
