import { useState } from 'react';
import {
    CheckCircle,
    XCircle,
    BarChart3,
    Filter,
    RotateCcw,
} from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardHeader, CardContent } from '@/components/ui/card';
import { Container, PageHeader } from '@/components/ui/shared';
import { useFlashToast } from '@/hooks/use-flash-toast';
import { cn } from '@/lib/utils';

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

type KomponenKey = 'tugas' | 'uts' | 'uas' | 'akhir';
type KomponenSet = Record<KomponenKey, boolean>;
type StatusFilter = 'semua' | 'lulus' | 'tidak_lulus';

const COMPONENT_META: Record<
    KomponenKey,
    { label: string; full: string; color: string }
> = {
    tugas: { label: 'Tgs', full: 'Tugas', color: '#3b82f6' }, // blue-500
    uts: { label: 'UTS', full: 'UTS', color: '#f59e0b' }, // amber-500
    uas: { label: 'UAS', full: 'UAS', color: '#8b5cf6' }, // violet-500
    akhir: { label: 'Akhir', full: 'Nilai Akhir', color: '#10b981' }, // emerald-500
};

const Y_TICKS = [0, 25, 50, 75, 100] as const;

function nilaiTextColor(v: number | null, kkm: number): string {
    if (v === null) {
        return 'text-muted-foreground';
    }

    return v >= kkm ? 'text-emerald-700' : 'text-rose-700';
}

function describeDataSelection(komponen: KomponenSet): string {
    const selected = (Object.keys(komponen) as KomponenKey[]).filter(
        (k) => komponen[k],
    );

    if (selected.length === 0) {
        return '—';
    }

    return selected.map((k) => COMPONENT_META[k].full).join(', ');
}

function BarChart({
    data,
    components,
    kkm,
}: {
    data: PerMapel[];
    components: KomponenKey[];
    kkm: number;
}) {
    if (data.length === 0) {
        return (
            <p className="py-4 text-center text-sm text-muted-foreground">
                Belum ada data untuk divisualisasikan.
            </p>
        );
    }

    const groupCount = data.length;
    const W = Math.max(800, groupCount * 90);
    const H = 320;
    const padL = 40;
    const padR = 14;
    const padT = 28;
    const padB = 48;
    const chartW = W - padL - padR;
    const chartH = H - padT - padB;

    const valueToY = (v: number) => padT + chartH - (v / 100) * chartH;

    const groupWidth = chartW / Math.max(1, groupCount);
    const barCount = components.length;
    const maxBarWidth = 40;
    const barWidth = Math.max(
        2.5,
        Math.min(maxBarWidth, (groupWidth * 0.7) / Math.max(1, barCount)),
    );
    const groupInnerWidth = barWidth * barCount;
    const groupCenterX = (i: number) => padL + groupWidth * i + groupWidth / 2;

    return (
        <div className="w-full">
            <div className="w-full scrollbar-thin scrollbar-thumb-slate-200 scrollbar-track-transparent overflow-x-auto pb-2">
                <svg
                    viewBox={`0 0 ${W} ${H}`}
                    className="w-full"
                    style={{ minWidth: `${W}px`, maxHeight: 360 }}
                    preserveAspectRatio="xMidYMid meet"
                >
                    <defs>
                        <linearGradient
                            id="grad-pass"
                            x1="0"
                            y1="0"
                            x2="0"
                            y2="1"
                        >
                            <stop offset="0%" stopColor="#6ee7b7" />
                            <stop offset="100%" stopColor="#10b981" />
                        </linearGradient>
                    </defs>

                    {Y_TICKS.map((v) => (
                        <g key={v}>
                            <line
                                x1={padL}
                                x2={W - padR}
                                y1={valueToY(v)}
                                y2={valueToY(v)}
                                stroke="#e2e8f0"
                                strokeDasharray="2,3"
                            />
                            <text
                                x={padL - 6}
                                y={valueToY(v) + 3}
                                fontSize="10"
                                fill="#94a3b8"
                                textAnchor="end"
                            >
                                {v}
                            </text>
                        </g>
                    ))}

                    {data.map((d, gi) => {
                        const cx = groupCenterX(gi);

                        return (
                            <g key={`${d.kelas}|${d.mapel}`}>
                                {components.map((comp, bi) => {
                                    const v =
                                        comp === 'akhir' ? d.akhir : d[comp];
                                    if (v === null) {
                                        return null;
                                    }
                                    const pass = v >= kkm;
                                    const barX =
                                        cx -
                                        groupInnerWidth / 2 +
                                        bi * barWidth;
                                    const barH = (v / 100) * chartH;
                                    const barY = valueToY(v);

                                    return (
                                        <g key={comp}>
                                            <rect
                                                x={barX}
                                                y={barY}
                                                width={Math.max(
                                                    1,
                                                    barWidth - 1.5,
                                                )}
                                                height={barH}
                                                fill={
                                                    COMPONENT_META[comp].color
                                                }
                                                opacity={pass ? 1 : 0.4}
                                                rx="2"
                                                className="transition-all duration-300 hover:opacity-80"
                                            />
                                            <text
                                                x={barX + barWidth / 2 - 0.75}
                                                y={barY - 6}
                                                fontSize="9"
                                                fontWeight="600"
                                                fill={
                                                    pass
                                                        ? COMPONENT_META[comp]
                                                              .color
                                                        : '#94a3b8'
                                                }
                                                textAnchor="middle"
                                            >
                                                {Number(v).toFixed(0)}
                                            </text>
                                            <title>{`${COMPONENT_META[comp].full}: ${Number(v).toFixed(2)}`}</title>
                                        </g>
                                    );
                                })}

                                <text
                                    x={cx}
                                    y={H - padB + 18}
                                    fontSize="10"
                                    fill="#475569"
                                    textAnchor="middle"
                                >
                                    {d.mapel}
                                </text>
                            </g>
                        );
                    })}

                    <line
                        x1={padL}
                        x2={W - padR}
                        y1={valueToY(kkm)}
                        y2={valueToY(kkm)}
                        stroke="#f43f5e"
                        strokeDasharray="4,3"
                        strokeWidth={1.5}
                    />
                    <rect
                        x={W - padR - 42}
                        y={valueToY(kkm) - 9}
                        width="40"
                        height="14"
                        rx="3"
                        fill="#f43f5e"
                    />
                    <text
                        x={W - padR - 22}
                        y={valueToY(kkm) + 1}
                        fontSize="9"
                        fontWeight="bold"
                        fill="white"
                        textAnchor="middle"
                    >
                        KKM {kkm}
                    </text>
                </svg>
            </div>

            <div className="mt-4 flex flex-wrap items-center justify-center gap-x-6 gap-y-2 text-[11px] font-medium text-slate-600">
                {components.map((comp) => (
                    <span key={comp} className="flex items-center gap-1.5">
                        <span
                            className="h-3 w-3 rounded-[3px]"
                            style={{
                                backgroundColor: COMPONENT_META[comp].color,
                            }}
                        />
                        {COMPONENT_META[comp].full}
                    </span>
                ))}
                <span className="ml-2 flex items-center gap-1.5 border-l border-slate-200 pl-4">
                    <span className="flex h-3 w-3 items-center justify-center rounded-[3px] bg-slate-300 opacity-40" />
                    &lt; KKM
                </span>
                <span className="flex items-center gap-1.5">
                    <span className="h-0.5 w-5 border-t-2 border-dashed border-rose-500" />
                    Garis KKM
                </span>
            </div>
        </div>
    );
}

function FilterCheckboxRow({
    label,
    checked,
    onToggle,
    onSelectAll,
    isAll,
}: {
    label: string;
    checked: boolean;
    onToggle: () => void;
    onSelectAll?: () => void;
    isAll?: boolean;
}) {
    return (
        <label
            className={cn(
                'inline-flex cursor-pointer items-center gap-1.5 rounded-md border px-2.5 py-1 text-xs font-medium transition select-none',
                checked
                    ? 'border-navy bg-navy text-white'
                    : 'border-border bg-white text-secondary hover:bg-surface',
            )}
        >
            <input
                type="checkbox"
                checked={checked}
                onChange={isAll ? onSelectAll : onToggle}
                className="h-3.5 w-3.5 cursor-pointer accent-navy"
            />
            {label}
        </label>
    );
}

function SegmentedControl<T extends string>({
    value,
    onChange,
    options,
}: {
    value: T;
    onChange: (v: T) => void;
    options: { value: T; label: string }[];
}) {
    return (
        <div className="inline-flex overflow-hidden rounded-md border border-border">
            {options.map((opt) => {
                const active = opt.value === value;
                return (
                    <button
                        key={opt.value}
                        type="button"
                        onClick={() => onChange(opt.value)}
                        className={cn(
                            'border-r border-border px-3 py-1 text-xs font-medium transition last:border-r-0',
                            active
                                ? 'bg-navy text-white'
                                : 'bg-white text-secondary hover:bg-surface',
                        )}
                    >
                        {opt.label}
                    </button>
                );
            })}
        </div>
    );
}

export default function SiswaStatistik({ mapel_list, chart_data }: Props) {
    useFlashToast();

    const hasData = mapel_list.length > 0;

    const [komponen, setKomponen] = useState<KomponenSet>({
        tugas: true,
        uts: true,
        uas: true,
        akhir: true,
    });
    const [statusFilter, setStatusFilter] = useState<StatusFilter>('semua');
    const [mapelSet, setMapelSet] = useState<Set<string>>(new Set());

    const activeKomponen = (Object.keys(komponen) as KomponenKey[]).filter(
        (k) => komponen[k],
    );
    const isKomponenAll =
        komponen.tugas && komponen.uts && komponen.uas && komponen.akhir;
    const isMapelAll = mapelSet.size === 0;
    const hasFilter = !isKomponenAll || statusFilter !== 'semua' || !isMapelAll;

    const filteredMapel = chart_data.per_mapel.filter((m) => {
        if (statusFilter === 'lulus' && m.status !== 'Lulus') {
            return false;
        }
        if (statusFilter === 'tidak_lulus' && m.status !== 'Tidak Lulus') {
            return false;
        }
        if (!isMapelAll && !mapelSet.has(m.mapel)) {
            return false;
        }
        return true;
    });

    function toggleKomponen(key: KomponenKey) {
        setKomponen((prev) => ({ ...prev, [key]: !prev[key] }));
    }

    function selectAllKomponen() {
        setKomponen({ tugas: true, uts: true, uas: true, akhir: true });
    }

    function toggleMapel(mapel: string) {
        setMapelSet((prev) => {
            const next = new Set(prev);
            if (next.has(mapel)) {
                next.delete(mapel);
            } else {
                next.add(mapel);
            }
            return next;
        });
    }

    function resetAll() {
        setKomponen({ tugas: true, uts: true, uas: true, akhir: true });
        setStatusFilter('semua');
        setMapelSet(new Set());
    }

    return (
        <Container>
            <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <PageHeader title="Statistik Akademik" />
            </div>

            {hasData && (
                <Card>
                    <CardHeader>
                        <div className="flex items-center gap-2">
                            <BarChart3 className="h-4 w-4 text-primary" />
                            <span className="font-semibold">
                                Ringkasan Akademik
                            </span>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-1 gap-3 sm:grid-cols-3">
                            <div className="flex items-center justify-between rounded-lg bg-blue-50 p-3">
                                <span className="text-sm text-secondary">
                                    Total Mata Pelajaran
                                </span>
                                <span className="text-2xl font-bold text-primary">
                                    {chart_data.stats.total_mapel}
                                </span>
                            </div>
                            <div className="flex items-center justify-between rounded-lg bg-emerald-50 p-3">
                                <span className="flex items-center gap-1.5 text-sm text-secondary">
                                    <CheckCircle className="h-4 w-4 text-emerald-600" />
                                    Lulus
                                </span>
                                <span className="text-2xl font-bold text-emerald-600">
                                    {chart_data.stats.lulus}
                                </span>
                            </div>
                            <div className="flex items-center justify-between rounded-lg bg-rose-50 p-3">
                                <span className="flex items-center gap-1.5 text-sm text-secondary">
                                    <XCircle className="h-4 w-4 text-rose-600" />
                                    Tidak Lulus
                                </span>
                                <span className="text-2xl font-bold text-rose-600">
                                    {chart_data.stats.tidak_lulus}
                                </span>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            )}

            {hasData && (
                <Card>
                    <CardHeader>
                        <div className="flex w-full flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div className="flex items-center gap-2">
                                <BarChart3 className="h-4 w-4 text-primary" />
                                <span className="font-semibold">
                                    Performa per Mata Pelajaran
                                </span>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div className="mb-4 space-y-4 rounded-lg border border-border bg-surface/30 p-4">
                            <div className="grid grid-cols-1 gap-4 lg:grid-cols-12">
                                <div className="space-y-2 lg:col-span-3">
                                    <span className="flex items-center gap-1.5 text-xs font-semibold text-secondary">
                                        <Filter className="h-3.5 w-3.5 text-muted-foreground" />
                                        Status Kelulusan
                                    </span>
                                    <div className="flex w-full">
                                        <SegmentedControl<StatusFilter>
                                            value={statusFilter}
                                            onChange={setStatusFilter}
                                            options={[
                                                {
                                                    value: 'semua',
                                                    label: 'Semua',
                                                },
                                                {
                                                    value: 'lulus',
                                                    label: 'Lulus',
                                                },
                                                {
                                                    value: 'tidak_lulus',
                                                    label: 'Tidak Lulus',
                                                },
                                            ]}
                                        />
                                    </div>
                                </div>

                                <div className="space-y-2 lg:col-span-9">
                                    <span className="flex items-center gap-1.5 text-xs font-semibold text-secondary">
                                        <Filter className="h-3.5 w-3.5 text-muted-foreground" />
                                        Komponen Nilai
                                    </span>
                                    <div className="flex flex-wrap items-center gap-2">
                                        <FilterCheckboxRow
                                            label="Semua"
                                            checked={isKomponenAll}
                                            onSelectAll={selectAllKomponen}
                                            isAll
                                        />
                                        <FilterCheckboxRow
                                            label="Tugas"
                                            checked={komponen.tugas}
                                            onToggle={() =>
                                                toggleKomponen('tugas')
                                            }
                                        />
                                        <FilterCheckboxRow
                                            label="UTS"
                                            checked={komponen.uts}
                                            onToggle={() =>
                                                toggleKomponen('uts')
                                            }
                                        />
                                        <FilterCheckboxRow
                                            label="UAS"
                                            checked={komponen.uas}
                                            onToggle={() =>
                                                toggleKomponen('uas')
                                            }
                                        />
                                        <FilterCheckboxRow
                                            label="Nilai Akhir"
                                            checked={komponen.akhir}
                                            onToggle={() =>
                                                toggleKomponen('akhir')
                                            }
                                        />
                                    </div>
                                </div>
                            </div>

                            <div className="space-y-2 border-t border-border/50 pt-2">
                                <div className="flex items-center justify-between">
                                    <span className="flex items-center gap-1.5 text-xs font-semibold text-secondary">
                                        <Filter className="h-3.5 w-3.5 text-muted-foreground" />
                                        Filter Mata Pelajaran
                                    </span>
                                    {hasFilter && (
                                        <Button
                                            variant="ghost"
                                            onClick={resetAll}
                                            className="h-6 px-2 text-[11px] text-rose-600 hover:bg-rose-50 hover:text-rose-700"
                                        >
                                            <RotateCcw className="mr-1 h-3 w-3" />
                                            Reset Filter
                                        </Button>
                                    )}
                                </div>
                                <div className="flex max-h-32 flex-wrap items-center gap-1.5 overflow-y-auto rounded-md border border-border/50 bg-white p-2 shadow-sm">
                                    <button
                                        type="button"
                                        onClick={() => setMapelSet(new Set())}
                                        className={cn(
                                            'inline-flex cursor-pointer items-center rounded-full border px-3 py-1 text-xs font-medium transition',
                                            isMapelAll
                                                ? 'border-navy bg-navy text-white'
                                                : 'border-transparent bg-slate-100 text-secondary hover:bg-slate-200',
                                        )}
                                    >
                                        Semua Mata Pelajaran
                                    </button>
                                    {mapel_list.map((m) => {
                                        const active = mapelSet.has(m);
                                        return (
                                            <button
                                                key={m}
                                                type="button"
                                                onClick={() => toggleMapel(m)}
                                                className={cn(
                                                    'inline-flex cursor-pointer items-center rounded-full border px-3 py-1 text-xs font-medium transition',
                                                    active
                                                        ? 'border-navy bg-navy text-white'
                                                        : 'border-transparent bg-slate-100 text-secondary hover:bg-slate-200',
                                                )}
                                            >
                                                {m}
                                            </button>
                                        );
                                    })}
                                </div>
                            </div>
                        </div>

                        {activeKomponen.length === 0 ? (
                            <div className="flex flex-col items-center justify-center py-8 text-center">
                                <Filter className="mb-2 h-6 w-6 text-muted-foreground" />
                                <p className="text-sm font-medium text-secondary">
                                    Pilih minimal 1 komponen untuk ditampilkan.
                                </p>
                            </div>
                        ) : filteredMapel.length === 0 ? (
                            <div className="flex flex-col items-center justify-center py-8 text-center">
                                <Filter className="mb-2 h-6 w-6 text-muted-foreground" />
                                <p className="text-sm font-medium text-secondary">
                                    Tidak ada mata pelajaran yang sesuai filter.
                                </p>
                                <Button
                                    variant="outline"
                                    onClick={resetAll}
                                    className="mt-3"
                                >
                                    <RotateCcw className="h-4 w-4" />
                                    Reset Filter
                                </Button>
                            </div>
                        ) : (
                            <>
                                <div className="mb-3 text-center text-sm text-muted-foreground">
                                    Menampilkan data:{' '}
                                    <span className="font-semibold text-secondary">
                                        {describeDataSelection(komponen)}
                                    </span>
                                </div>
                                <BarChart
                                    data={filteredMapel}
                                    components={activeKomponen}
                                    kkm={chart_data.kkm}
                                />
                            </>
                        )}
                    </CardContent>
                </Card>
            )}
        </Container>
    );
}

SiswaStatistik.layout = { title: 'Statistik Nilai' };
