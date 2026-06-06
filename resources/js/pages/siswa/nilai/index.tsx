import {
    Lock,
    CheckCircle,
    XCircle,
    BookOpen,
    Printer,
    TrendingUp,
    AlertTriangle,
    BarChart3,
    Inbox,
    FileSpreadsheet,
} from 'lucide-react';
import { Alert } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Card, CardHeader, CardContent } from '@/components/ui/card';
import { Container, DataTable, PageHeader } from '@/components/ui/shared';
import { useFlashToast } from '@/hooks/use-flash-toast';

type Siswa = { nis: string; nama_siswa: string; kelas: string };
type Guru = { id: number; nama_guru: string };

type Nilai = {
    id: number;
    kelas: string;
    mata_pelajaran: string;
    nilai_tugas: number | null;
    nilai_uts: number | null;
    nilai_uas: number | null;
    nilai_akhir: number | null;
    status_lulus: string | null;
    status_validasi: string;
    id_guru: number;
};

type PerMapel = {
    mapel: string;
    kelas: string;
    tugas: number | null;
    uts: number | null;
    uas: number | null;
    akhir: number | null;
    status: string | null;
    kkm: number;
};

type ChartData = {
    overall: {
        tugas: number | null;
        uts: number | null;
        uas: number | null;
        akhir: number | null;
        count: number;
    };
    per_mapel: PerMapel[];
    kkm: number;
    stats: { total_mapel: number; lulus: number; tidak_lulus: number };
};

type Props = {
    siswa: Siswa;
    nilai: Record<string, Nilai[]>;
    mapel_list: string[];
    guru_map: Record<string, Guru>;
    chart_data: ChartData;
};

function formatNumber(v: number | null): string {
    if (v === null || v === undefined) {
        return '—';
    }

    return Number(v).toFixed(2);
}

function nilaiColor(v: number | null, kkm: number): string {
    if (v === null) {
        return 'bg-slate-200';
    }

    return v >= kkm ? 'bg-emerald-500' : 'bg-rose-500';
}

function nilaiTextColor(v: number | null, kkm: number): string {
    if (v === null) {
        return 'text-muted-foreground';
    }

    return v >= kkm ? 'text-emerald-700' : 'text-rose-700';
}

function ComponentBar({
    value,
    kkm,
    label,
    weight,
}: {
    value: number | null;
    kkm: number;
    label: string;
    weight: string;
}) {
    const pct = value === null ? 0 : Math.max(0, Math.min(100, value));
    const barColor = nilaiColor(value, kkm);
    const textColor = nilaiTextColor(value, kkm);
    const isBelowKkm = value !== null && value < kkm;

    return (
        <div>
            <div className="mb-1 flex items-baseline justify-between">
                <span className="text-xs font-semibold text-secondary">
                    {label}{' '}
                    <span className="font-normal text-muted-foreground">
                        ({weight})
                    </span>
                </span>
                <span className={`font-mono text-sm font-bold ${textColor}`}>
                    {formatNumber(value)}
                </span>
            </div>
            <div className="relative h-6 w-full overflow-hidden rounded-md bg-slate-100">
                <div
                    className={`absolute inset-y-0 left-0 ${barColor} transition-all`}
                    style={{ width: `${pct}%` }}
                />
                <div
                    className="absolute inset-y-0 w-0.5 bg-navy"
                    style={{ left: `${kkm}%` }}
                    title={`KKM ${kkm}`}
                />
            </div>
            {isBelowKkm && (
                <p className="mt-1 flex items-center gap-1 text-[10px] text-rose-600">
                    <AlertTriangle className="h-3 w-3" />
                    Di bawah KKM
                </p>
            )}
        </div>
    );
}

function OverallChart({
    overall,
    kkm,
}: {
    overall: ChartData['overall'];
    kkm: number;
}) {
    return (
        <div className="space-y-3">
            <ComponentBar
                value={overall.tugas}
                kkm={kkm}
                label="Tugas"
                weight="30%"
            />
            <ComponentBar
                value={overall.uts}
                kkm={kkm}
                label="UTS"
                weight="30%"
            />
            <ComponentBar
                value={overall.uas}
                kkm={kkm}
                label="UAS"
                weight="40%"
            />
        </div>
    );
}

function PerMapelChart({ perMapel }: { perMapel: PerMapel[] }) {
    if (perMapel.length === 0) {
        return (
            <p className="py-4 text-center text-sm text-muted-foreground">
                Belum ada data untuk divisualisasikan.
            </p>
        );
    }

    return (
        <div className="space-y-4">
            {perMapel.map((m) => {
                const kkm = m.kkm;
                const weakest = [
                    { key: 'T', value: m.tugas },
                    { key: 'U', value: m.uts },
                    { key: 'A', value: m.uas },
                ]
                    .filter((c) => c.value !== null)
                    .sort((a, b) => (a.value ?? 0) - (b.value ?? 0))[0];

                return (
                    <div
                        key={`${m.kelas}|${m.mapel}`}
                        className="rounded-lg border border-slate-200 p-3"
                    >
                        <div className="mb-2 flex items-center justify-between">
                            <div className="flex min-w-0 items-center gap-2">
                                <span className="truncate text-sm font-semibold text-navy">
                                    {m.mapel}
                                </span>
                                <Badge variant="neutral">{m.kelas}</Badge>
                            </div>
                            <div className="flex flex-shrink-0 items-center gap-2">
                                <span className="text-xs text-muted-foreground">
                                    Akhir:
                                </span>
                                <span
                                    className={`font-mono text-sm font-bold ${nilaiTextColor(m.akhir, kkm)}`}
                                >
                                    {formatNumber(m.akhir)}
                                </span>
                                {m.status === 'Lulus' && (
                                    <Badge variant="success">
                                        <CheckCircle className="mr-1 h-3 w-3" />
                                        Lulus
                                    </Badge>
                                )}
                                {m.status === 'Tidak Lulus' && (
                                    <Badge variant="danger">
                                        <XCircle className="mr-1 h-3 w-3" />
                                        Tidak Lulus
                                    </Badge>
                                )}
                            </div>
                        </div>

                        <div className="grid grid-cols-3 gap-2">
                            <MiniBar label="Tgs" value={m.tugas} kkm={kkm} />
                            <MiniBar label="UTS" value={m.uts} kkm={kkm} />
                            <MiniBar label="UAS" value={m.uas} kkm={kkm} />
                        </div>

                        {weakest &&
                            weakest.value !== null &&
                            weakest.value < kkm && (
                                <p className="mt-2 flex items-center gap-1 text-[10px] text-rose-600">
                                    <AlertTriangle className="h-3 w-3" />
                                    Komponen terlemah:{' '}
                                    <strong>
                                        {weakest.key === 'T'
                                            ? 'Tugas'
                                            : weakest.key === 'U'
                                              ? 'UTS'
                                              : 'UAS'}
                                    </strong>{' '}
                                    ({formatNumber(weakest.value)})
                                </p>
                            )}
                    </div>
                );
            })}
        </div>
    );
}

function MiniBar({
    label,
    value,
    kkm,
}: {
    label: string;
    value: number | null;
    kkm: number;
}) {
    const pct = value === null ? 0 : Math.max(0, Math.min(100, value));
    const barColor = nilaiColor(value, kkm);
    const textColor = nilaiTextColor(value, kkm);

    return (
        <div>
            <div className="mb-0.5 flex items-baseline justify-between">
                <span className="text-[10px] font-medium text-muted-foreground">
                    {label}
                </span>
                <span
                    className={`font-mono text-[10px] font-bold ${textColor}`}
                >
                    {formatNumber(value)}
                </span>
            </div>
            <div className="relative h-3 w-full overflow-hidden rounded bg-slate-100">
                <div
                    className={`absolute inset-y-0 left-0 ${barColor}`}
                    style={{ width: `${pct}%` }}
                />
                <div
                    className="absolute inset-y-0 w-px bg-navy"
                    style={{ left: `${kkm}%` }}
                />
            </div>
        </div>
    );
}

export default function SiswaNilai({
    siswa,
    nilai,
    mapel_list,
    guru_map,
    chart_data,
}: Props) {
    useFlashToast();

    const hasData = mapel_list.length > 0;
    const overallAkhir = chart_data.overall.akhir;
    const isPassing = overallAkhir !== null && overallAkhir >= chart_data.kkm;

    return (
        <Container>
            <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <PageHeader
                    title="Nilai Saya"
                    description={
                        <div className="flex flex-wrap items-center gap-2">
                            <span>{siswa.nama_siswa}</span>
                            <Badge variant="neutral">NIS: {siswa.nis}</Badge>
                            <Badge variant="info">Kelas: {siswa.kelas}</Badge>
                        </div>
                    }
                />
                {hasData && (
                    <a
                        href="/siswa/rapor/pdf"
                        target="_blank"
                        rel="noopener noreferrer"
                        className="inline-flex flex-shrink-0 items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700"
                    >
                        <Printer className="h-4 w-4" />
                        Cetak Rapor (PDF)
                    </a>
                )}
            </div>

            {!hasData ? (
                <div className="mt-8 flex flex-col items-center justify-center rounded-xl border border-dashed border-border bg-slate-50/50 py-16 text-center">
                    <div className="rounded-full bg-slate-100 p-4">
                        <FileSpreadsheet className="h-8 w-8 text-slate-400" />
                    </div>
                    <h3 className="mt-4 text-lg font-semibold text-secondary">
                        Belum Ada Nilai
                    </h3>
                    <p className="mx-auto mt-2 max-w-sm text-sm text-muted-foreground">
                        Saat ini belum ada nilai mata pelajaran yang diinput
                        oleh guru untuk Anda. Silakan periksa kembali nanti.
                    </p>
                </div>
            ) : (
                mapel_list.map((mapel) => {
                    const entries = Object.entries(nilai).filter(([key]) =>
                        key.endsWith(`|${mapel}`),
                    );

                    return entries.map(([key, list]) => {
                        const kelas = list[0]?.kelas ?? '';
                        const namaGuru =
                            guru_map[String(list[0]?.id_guru)]?.nama_guru ??
                            '—';

                        return (
                            <DataTable key={key}>
                                <CardHeader>
                                    <div className="flex w-full flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                        <span className="flex items-center gap-2">
                                            <BookOpen className="h-4 w-4 text-primary" />
                                            {mapel}
                                        </span>
                                        <div className="flex items-center gap-2">
                                            <Badge variant="info">
                                                {kelas}
                                            </Badge>
                                            <span className="text-sm font-normal text-muted-foreground">
                                                Guru:{' '}
                                                <strong>{namaGuru}</strong>
                                            </span>
                                        </div>
                                    </div>
                                </CardHeader>
                                <div className="overflow-x-auto">
                                    <table className="w-full text-sm">
                                        <thead>
                                            <tr className="bg-surface text-secondary">
                                                <th className="px-4 py-3 text-left text-xs font-bold tracking-wide uppercase">
                                                    Komponen
                                                </th>
                                                <th className="px-4 py-3 text-center text-xs font-bold tracking-wide uppercase">
                                                    Nilai
                                                </th>
                                                <th className="px-4 py-3 text-center text-xs font-bold tracking-wide uppercase">
                                                    Bobot
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-slate-100">
                                            <tr>
                                                <td className="px-4 py-3">
                                                    Tugas
                                                </td>
                                                <td className="px-4 py-3 text-center font-mono">
                                                    {list[0]?.nilai_tugas ??
                                                        '—'}
                                                </td>
                                                <td className="px-4 py-3 text-center text-muted-foreground">
                                                    30%
                                                </td>
                                            </tr>
                                            <tr>
                                                <td className="px-4 py-3">
                                                    UTS
                                                </td>
                                                <td className="px-4 py-3 text-center font-mono">
                                                    {list[0]?.nilai_uts ?? '—'}
                                                </td>
                                                <td className="px-4 py-3 text-center text-muted-foreground">
                                                    30%
                                                </td>
                                            </tr>
                                            <tr>
                                                <td className="px-4 py-3">
                                                    UAS
                                                </td>
                                                <td className="px-4 py-3 text-center font-mono">
                                                    {list[0]?.nilai_uas ?? '—'}
                                                </td>
                                                <td className="px-4 py-3 text-center text-muted-foreground">
                                                    40%
                                                </td>
                                            </tr>
                                            <tr className="bg-blue-50">
                                                <td className="px-4 py-3 font-semibold text-navy">
                                                    Nilai Akhir
                                                </td>
                                                <td
                                                    colSpan={2}
                                                    className="px-4 py-3 text-center"
                                                >
                                                    <div className="flex items-center justify-center gap-3">
                                                        <span className="text-2xl font-bold text-navy">
                                                            {list[0]
                                                                ?.nilai_akhir ??
                                                                '—'}
                                                        </span>
                                                        {list[0]
                                                            ?.status_lulus ===
                                                            'Lulus' && (
                                                            <Badge variant="success">
                                                                <CheckCircle className="mr-1 h-3 w-3" />
                                                                Lulus
                                                            </Badge>
                                                        )}
                                                        {list[0]
                                                            ?.status_lulus ===
                                                            'Tidak Lulus' && (
                                                            <Badge variant="danger">
                                                                <XCircle className="mr-1 h-3 w-3" />
                                                                Tidak Lulus
                                                            </Badge>
                                                        )}
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </DataTable>
                        );
                    });
                })
            )}
        </Container>
    );
}

SiswaNilai.layout = { title: 'Nilai Saya' };

function WeakComponents({
    overall,
    kkm,
}: {
    overall: ChartData['overall'];
    kkm: number;
}) {
    const components = [
        { name: 'Tugas', value: overall.tugas, weight: 'Bobot 30%' },
        { name: 'UTS', value: overall.uts, weight: 'Bobot 30%' },
        { name: 'UAS', value: overall.uas, weight: 'Bobot 40%' },
    ];

    const below = components.filter((c) => c.value !== null && c.value < kkm);
    const above = components.filter((c) => c.value !== null && c.value >= kkm);

    if (overall.count === 0) {
        return (
            <p className="py-4 text-center text-sm text-muted-foreground">
                Belum ada data.
            </p>
        );
    }

    return (
        <div className="space-y-3">
            {below.length > 0 && (
                <Alert variant="error">
                    <p className="mb-2 text-xs font-bold">
                        ⚠️ Perlu Ditingkatkan
                    </p>
                    <ul className="space-y-1 text-sm font-normal">
                        {below.map((c) => (
                            <li
                                key={c.name}
                                className="flex items-center justify-between"
                            >
                                <span>{c.name}</span>
                                <span className="font-mono font-bold">
                                    {formatNumber(c.value)}
                                </span>
                            </li>
                        ))}
                    </ul>
                </Alert>
            )}
            {above.length > 0 && (
                <Alert variant="success">
                    <p className="mb-2 text-xs font-bold">
                        ✓ Sudah Di Atas KKM
                    </p>
                    <ul className="space-y-1 text-sm font-normal">
                        {above.map((c) => (
                            <li
                                key={c.name}
                                className="flex items-center justify-between"
                            >
                                <span>{c.name}</span>
                                <span className="font-mono font-bold">
                                    {formatNumber(c.value)}
                                </span>
                            </li>
                        ))}
                    </ul>
                </Alert>
            )}
            {below.length === 0 && (
                <Alert variant="success">
                    <p className="text-center font-semibold">
                        🎉 Semua komponen di atas KKM!
                    </p>
                </Alert>
            )}
        </div>
    );
}
