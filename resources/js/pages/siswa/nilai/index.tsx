import { Lock, CheckCircle, XCircle, BookOpen, Printer, TrendingUp, AlertTriangle, BarChart3 } from 'lucide-react';
import { Alert } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Card, CardHeader, CardContent } from '@/components/ui/card';
import { Container, PageHeader } from '@/components/ui/shared';
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
    overall: { tugas: number | null; uts: number | null; uas: number | null; akhir: number | null; count: number };
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

function ComponentBar({ value, kkm, label, weight }: { value: number | null; kkm: number; label: string; weight: string }) {
    const pct = value === null ? 0 : Math.max(0, Math.min(100, value));
    const barColor = nilaiColor(value, kkm);
    const textColor = nilaiTextColor(value, kkm);
    const isBelowKkm = value !== null && value < kkm;

    return (
        <div>
            <div className="flex items-baseline justify-between mb-1">
                <span className="text-xs font-semibold text-secondary">
                    {label} <span className="text-muted-foreground font-normal">({weight})</span>
                </span>
                <span className={`text-sm font-bold font-mono ${textColor}`}>{formatNumber(value)}</span>
            </div>
            <div className="relative h-6 w-full bg-slate-100 rounded-md overflow-hidden">
                <div className={`absolute inset-y-0 left-0 ${barColor} transition-all`} style={{ width: `${pct}%` }} />
                <div
                    className="absolute inset-y-0 w-0.5 bg-navy"
                    style={{ left: `${kkm}%` }}
                    title={`KKM ${kkm}`}
                />
            </div>
            {isBelowKkm && (
                <p className="text-[10px] text-rose-600 mt-1 flex items-center gap-1">
                    <AlertTriangle className="h-3 w-3" />
                    Di bawah KKM
                </p>
            )}
        </div>
    );
}

function OverallChart({ overall, kkm }: { overall: ChartData['overall']; kkm: number }) {
    return (
        <div className="space-y-3">
            <ComponentBar value={overall.tugas} kkm={kkm} label="Tugas" weight="30%" />
            <ComponentBar value={overall.uts} kkm={kkm} label="UTS" weight="30%" />
            <ComponentBar value={overall.uas} kkm={kkm} label="UAS" weight="40%" />
        </div>
    );
}

function PerMapelChart({ perMapel }: { perMapel: PerMapel[] }) {
    if (perMapel.length === 0) {
        return <p className="text-sm text-muted-foreground text-center py-4">Belum ada data untuk divisualisasikan.</p>;
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
                    <div key={`${m.kelas}|${m.mapel}`} className="rounded-lg border border-slate-200 p-3">
                        <div className="flex items-center justify-between mb-2">
                            <div className="flex items-center gap-2 min-w-0">
                                <span className="text-sm font-semibold text-navy truncate">{m.mapel}</span>
                                <Badge variant="neutral">{m.kelas}</Badge>
                            </div>
                            <div className="flex items-center gap-2 flex-shrink-0">
                                <span className="text-xs text-muted-foreground">Akhir:</span>
                                <span className={`text-sm font-bold font-mono ${nilaiTextColor(m.akhir, kkm)}`}>
                                    {formatNumber(m.akhir)}
                                </span>
                                {m.status === 'Lulus' && (
                                    <Badge variant="success">
                                        <CheckCircle className="h-3 w-3 mr-1" />
                                        Lulus
                                    </Badge>
                                )}
                                {m.status === 'Tidak Lulus' && (
                                    <Badge variant="danger">
                                        <XCircle className="h-3 w-3 mr-1" />
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

                        {weakest && weakest.value !== null && weakest.value < kkm && (
                            <p className="text-[10px] text-rose-600 mt-2 flex items-center gap-1">
                                <AlertTriangle className="h-3 w-3" />
                                Komponen terlemah: <strong>{weakest.key === 'T' ? 'Tugas' : weakest.key === 'U' ? 'UTS' : 'UAS'}</strong>
                                {' '}({formatNumber(weakest.value)})
                            </p>
                        )}
                    </div>
                );
            })}
        </div>
    );
}

function MiniBar({ label, value, kkm }: { label: string; value: number | null; kkm: number }) {
    const pct = value === null ? 0 : Math.max(0, Math.min(100, value));
    const barColor = nilaiColor(value, kkm);
    const textColor = nilaiTextColor(value, kkm);

    return (
        <div>
            <div className="flex items-baseline justify-between mb-0.5">
                <span className="text-[10px] font-medium text-muted-foreground">{label}</span>
                <span className={`text-[10px] font-bold font-mono ${textColor}`}>{formatNumber(value)}</span>
            </div>
            <div className="relative h-3 w-full bg-slate-100 rounded overflow-hidden">
                <div className={`absolute inset-y-0 left-0 ${barColor}`} style={{ width: `${pct}%` }} />
                <div className="absolute inset-y-0 w-px bg-navy" style={{ left: `${kkm}%` }} />
            </div>
        </div>
    );
}

export default function SiswaNilai({ siswa, nilai, mapel_list, guru_map, chart_data }: Props) {
    useFlashToast();

    const hasData = mapel_list.length > 0;
    const overallAkhir = chart_data.overall.akhir;
    const isPassing = overallAkhir !== null && overallAkhir >= chart_data.kkm;

    return (
        <Container>
            <div className="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 mb-4">
                <PageHeader
                    title="Nilai Saya"
                    description={`${siswa.nama_siswa} — NIS: ${siswa.nis} — Kelas: ${siswa.kelas}`}
                />
                {hasData && (
                    <a
                        href="/siswa/rapor/pdf"
                        target="_blank"
                        rel="noopener noreferrer"
                        className="inline-flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg text-sm font-semibold hover:bg-primary-700 transition shadow-sm flex-shrink-0"
                    >
                        <Printer className="h-4 w-4" />
                        Cetak Rapor (PDF)
                    </a>
                )}
            </div>

            <Alert variant="info">
                <span className="flex items-center gap-2">
                    <Lock className="h-4 w-4" />
                    Halaman ini hanya dapat dilihat, tidak dapat diubah.
                </span>
            </Alert>

            {hasData && (
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-4">
                    <Card>
                        <CardHeader>
                            <div className="flex items-center gap-2">
                                <TrendingUp className="h-4 w-4 text-primary" />
                                <span className="font-semibold">Rata-rata Keseluruhan</span>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <OverallChart overall={chart_data.overall} kkm={chart_data.kkm} />
                            <div className="mt-4 pt-4 border-t border-border flex items-center justify-between">
                                <span className="text-xs text-muted-foreground">Nilai Akhir Rata-rata</span>
                                <div className="flex items-center gap-2">
                                    <span className={`text-2xl font-bold font-mono ${nilaiTextColor(overallAkhir, chart_data.kkm)}`}>
                                        {formatNumber(overallAkhir)}
                                    </span>
                                    {overallAkhir !== null && (
                                        <Badge variant={isPassing ? 'success' : 'danger'}>{isPassing ? 'Lulus' : 'Tidak Lulus'}</Badge>
                                    )}
                                </div>
                            </div>
                            <div className="mt-3 flex items-center gap-3 text-[10px] text-muted-foreground">
                                <span className="flex items-center gap-1">
                                    <span className="w-3 h-0.5 bg-navy" />
                                    KKM {chart_data.kkm}
                                </span>
                                <span className="flex items-center gap-1">
                                    <span className="w-3 h-2 bg-emerald-500 rounded-sm" />
                                    ≥ KKM
                                </span>
                                <span className="flex items-center gap-1">
                                    <span className="w-3 h-2 bg-rose-500 rounded-sm" />
                                    &lt; KKM
                                </span>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <div className="flex items-center gap-2">
                                <BarChart3 className="h-4 w-4 text-primary" />
                                <span className="font-semibold">Ringkasan Akademik</span>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-3">
                                <div className="flex items-center justify-between p-3 bg-blue-50 rounded-lg">
                                    <span className="text-sm text-secondary">Total Mata Pelajaran</span>
                                    <span className="text-2xl font-bold text-primary">{chart_data.stats.total_mapel}</span>
                                </div>
                                <div className="flex items-center justify-between p-3 bg-emerald-50 rounded-lg">
                                    <span className="text-sm text-secondary flex items-center gap-1.5">
                                        <CheckCircle className="h-4 w-4 text-emerald-600" />
                                        Lulus
                                    </span>
                                    <span className="text-2xl font-bold text-emerald-600">{chart_data.stats.lulus}</span>
                                </div>
                                <div className="flex items-center justify-between p-3 bg-rose-50 rounded-lg">
                                    <span className="text-sm text-secondary flex items-center gap-1.5">
                                        <XCircle className="h-4 w-4 text-rose-600" />
                                        Tidak Lulus
                                    </span>
                                    <span className="text-2xl font-bold text-rose-600">{chart_data.stats.tidak_lulus}</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <div className="flex items-center gap-2">
                                <AlertTriangle className="h-4 w-4 text-warning" />
                                <span className="font-semibold">Komponen Perlu Perhatian</span>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <WeakComponents overall={chart_data.overall} kkm={chart_data.kkm} />
                        </CardContent>
                    </Card>
                </div>
            )}

            {hasData && (
                <Card>
                    <CardHeader>
                        <div className="flex items-center gap-2">
                            <BarChart3 className="h-4 w-4 text-primary" />
                            <span className="font-semibold">Performa per Mata Pelajaran</span>
                        </div>
                        <p className="text-xs text-muted-foreground mt-1">
                            Setiap baris menunjukkan perbandingan nilai Tugas, UTS, dan UAS untuk satu mata pelajaran.
                        </p>
                    </CardHeader>
                    <CardContent>
                        <PerMapelChart perMapel={chart_data.per_mapel} />
                    </CardContent>
                </Card>
            )}

            {!hasData ? (
                <Card>
                    <CardContent className="text-center text-muted-foreground py-12">
                        Belum ada nilai yang diinput.
                    </CardContent>
                </Card>
            ) : (
                mapel_list.map((mapel) => {
                    const entries = Object.entries(nilai).filter(([key]) => key.endsWith(`|${mapel}`));

                    return entries.map(([key, list]) => {
                        const kelas = list[0]?.kelas ?? '';
                        const namaGuru = guru_map[String(list[0]?.id_guru)]?.nama_guru ?? '—';

                        return (
                            <Card key={key} className="p-0 overflow-hidden">
                                <CardHeader>
                                    <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 w-full">
                                        <span className="flex items-center gap-2">
                                            <BookOpen className="h-4 w-4 text-primary" />
                                            {mapel}
                                        </span>
                                        <div className="flex items-center gap-2">
                                            <Badge variant="info">{kelas}</Badge>
                                            <span className="text-sm font-normal text-muted-foreground">
                                                Guru: <strong>{namaGuru}</strong>
                                            </span>
                                        </div>
                                    </div>
                                </CardHeader>
                                <div className="overflow-x-auto">
                                    <table className="w-full text-sm">
                                        <thead>
                                            <tr className="bg-surface text-secondary">
                                                <th className="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide">Komponen</th>
                                                <th className="px-4 py-3 text-center text-xs font-bold uppercase tracking-wide">Nilai</th>
                                                <th className="px-4 py-3 text-center text-xs font-bold uppercase tracking-wide">Bobot</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-slate-100">
                                            <tr>
                                                <td className="px-4 py-3">Tugas</td>
                                                <td className="px-4 py-3 text-center font-mono">{list[0]?.nilai_tugas ?? '—'}</td>
                                                <td className="px-4 py-3 text-center text-muted-foreground">30%</td>
                                            </tr>
                                            <tr>
                                                <td className="px-4 py-3">UTS</td>
                                                <td className="px-4 py-3 text-center font-mono">{list[0]?.nilai_uts ?? '—'}</td>
                                                <td className="px-4 py-3 text-center text-muted-foreground">30%</td>
                                            </tr>
                                            <tr>
                                                <td className="px-4 py-3">UAS</td>
                                                <td className="px-4 py-3 text-center font-mono">{list[0]?.nilai_uas ?? '—'}</td>
                                                <td className="px-4 py-3 text-center text-muted-foreground">40%</td>
                                            </tr>
                                            <tr className="bg-blue-50">
                                                <td className="px-4 py-3 font-semibold text-navy">Nilai Akhir</td>
                                                <td colSpan={2} className="px-4 py-3 text-center">
                                                    <div className="flex items-center justify-center gap-3">
                                                        <span className="text-2xl font-bold text-navy">
                                                            {list[0]?.nilai_akhir ?? '—'}
                                                        </span>
                                                        {list[0]?.status_lulus === 'Lulus' && (
                                                            <Badge variant="success">
                                                                <CheckCircle className="h-3 w-3 mr-1" />
                                                                Lulus
                                                            </Badge>
                                                        )}
                                                        {list[0]?.status_lulus === 'Tidak Lulus' && (
                                                            <Badge variant="danger">
                                                                <XCircle className="h-3 w-3 mr-1" />
                                                                Tidak Lulus
                                                            </Badge>
                                                        )}
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </Card>
                        );
                    });
                })
            )}

            {hasData && (
                <div className="flex justify-center pt-2">
                    <a
                        href="/siswa/rapor/pdf"
                        target="_blank"
                        rel="noopener noreferrer"
                        className="inline-flex items-center gap-2 px-5 py-2.5 bg-white border border-primary text-primary rounded-lg text-sm font-semibold hover:bg-primary hover:text-white transition"
                    >
                        <Printer className="h-4 w-4" />
                        Cetak Rapor Digital (PDF)
                    </a>
                </div>
            )}
        </Container>
    );
}

SiswaNilai.layout = { title: 'Nilai Saya' };

function WeakComponents({ overall, kkm }: { overall: ChartData['overall']; kkm: number }) {
    const components = [
        { name: 'Tugas', value: overall.tugas, weight: 'Bobot 30%' },
        { name: 'UTS', value: overall.uts, weight: 'Bobot 30%' },
        { name: 'UAS', value: overall.uas, weight: 'Bobot 40%' },
    ];

    const below = components.filter((c) => c.value !== null && c.value < kkm);
    const above = components.filter((c) => c.value !== null && c.value >= kkm);

    if (overall.count === 0) {
        return <p className="text-sm text-muted-foreground text-center py-4">Belum ada data.</p>;
    }

    return (
        <div className="space-y-3">
            {below.length > 0 && (
                <div className="rounded-lg border border-rose-200 bg-rose-50 p-3">
                    <p className="text-xs font-bold text-rose-700 mb-2">⚠️ Perlu Ditingkatkan</p>
                    <ul className="space-y-1">
                        {below.map((c) => (
                            <li key={c.name} className="text-sm text-rose-700 flex items-center justify-between">
                                <span>{c.name}</span>
                                <span className="font-mono font-bold">{formatNumber(c.value)}</span>
                            </li>
                        ))}
                    </ul>
                </div>
            )}
            {above.length > 0 && (
                <div className="rounded-lg border border-emerald-200 bg-emerald-50 p-3">
                    <p className="text-xs font-bold text-emerald-700 mb-2">✓ Sudah Di Atas KKM</p>
                    <ul className="space-y-1">
                        {above.map((c) => (
                            <li key={c.name} className="text-sm text-emerald-700 flex items-center justify-between">
                                <span>{c.name}</span>
                                <span className="font-mono font-bold">{formatNumber(c.value)}</span>
                            </li>
                        ))}
                    </ul>
                </div>
            )}
            {below.length === 0 && (
                <div className="rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-center">
                    <p className="text-sm text-emerald-700 font-semibold">🎉 Semua komponen di atas KKM!</p>
                </div>
            )}
        </div>
    );
}
