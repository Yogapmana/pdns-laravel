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

const COMPONENT_META: Record<KomponenKey, { label: string; full: string }> = {
    tugas: { label: 'Tgs', full: 'Tugas' },
    uts: { label: 'UTS', full: 'UTS' },
    uas: { label: 'UAS', full: 'UAS' },
    akhir: { label: 'Akhir', full: 'Nilai Akhir' },
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

    const W = 800;
    const H = 320;
    const padL = 40;
    const padR = 14;
    const padT = 14;
    const padB = 48;
    const chartW = W - padL - padR;
    const chartH = H - padT - padB;

    const valueToY = (v: number) => padT + chartH - (v / 100) * chartH;

    const groupCount = data.length;
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
            <svg
                viewBox={`0 0 ${W} ${H}`}
                className="w-full"
                style={{ maxHeight: 360 }}
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
                    <linearGradient
                        id="grad-fail"
                        x1="0"
                        y1="0"
                        x2="0"
                        y2="1"
                    >
                        <stop offset="0%" stopColor="#fda4af" />
                        <stop offset="100%" stopColor="#f43f5e" />
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
                                            width={Math.max(1, barWidth - 1.5)}
                                            height={barH}
                                            fill={
                                                pass
                                                    ? 'url(#grad-pass)'
                                                    : 'url(#grad-fail)'
                                            }
                                            rx="2"
                                        />
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

            <div className="mt-2 flex flex-wrap items-center justify-center gap-x-4 gap-y-1 text-[10px] text-muted-foreground">
                <span className="flex items-center gap-1.5">
                    <span className="h-2.5 w-2.5 rounded-sm bg-gradient-to-b from-emerald-300 to-emerald-500" />
                    ≥ KKM (Lulus)
                </span>
                <span className="flex items-center gap-1.5">
                    <span className="h-2.5 w-2.5 rounded-sm bg-gradient-to-b from-rose-300 to-rose-500" />
                    &lt; KKM (Tidak Lulus)
                </span>
                <span className="flex items-center gap-1.5">
                    <span className="h-0.5 w-4 border-t-2 border-dashed border-rose-500" />
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
                'inline-flex cursor-pointer select-none items-center gap-1.5 rounded-md border px-2.5 py-1 text-xs font-medium transition',
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
        komponen.tugas &&
        komponen.uts &&
        komponen.uas &&
        komponen.akhir;
    const isMapelAll = mapelSet.size === 0;
    const hasFilter =
        !isKomponenAll || statusFilter !== 'semua' || !isMapelAll;

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
                            <span className="text-xs text-muted-foreground">
                                Menampilkan {filteredMapel.length} dari{' '}
                                {chart_data.per_mapel.length} mata pelajaran
                            </span>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div className="mb-4 space-y-3 rounded-lg border border-border bg-surface/40 p-3">
                            <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:flex-wrap sm:gap-x-6 sm:gap-y-2">
                                <div className="flex items-center gap-2">
                                    <Filter className="h-3.5 w-3.5 text-muted-foreground" />
                                    <span className="text-xs font-semibold text-secondary">
                                        Komponen:
                                    </span>
                                </div>
                                <div className="flex flex-wrap items-center gap-1.5">
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
                                        onToggle={() => toggleKomponen('uts')}
                                    />
                                    <FilterCheckboxRow
                                        label="UAS"
                                        checked={komponen.uas}
                                        onToggle={() => toggleKomponen('uas')}
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

                            <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:flex-wrap sm:gap-x-6 sm:gap-y-2">
                                <span className="text-xs font-semibold text-secondary">
                                    Status:
                                </span>
                                <SegmentedControl<StatusFilter>
                                    value={statusFilter}
                                    onChange={setStatusFilter}
                                    options={[
                                        { value: 'semua', label: 'Semua' },
                                        { value: 'lulus', label: 'Lulus' },
                                        {
                                            value: 'tidak_lulus',
                                            label: 'Tidak Lulus',
                                        },
                                    ]}
                                />
                            </div>

                            <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:flex-wrap sm:gap-x-2 sm:gap-y-2">
                                <span className="text-xs font-semibold text-secondary">
                                    Mapel:
                                </span>
                                <button
                                    type="button"
                                    onClick={() => setMapelSet(new Set())}
                                    className={cn(
                                        'inline-flex cursor-pointer items-center rounded-full border px-3 py-1 text-xs font-medium transition',
                                        isMapelAll
                                            ? 'border-navy bg-navy text-white'
                                            : 'border-border bg-white text-secondary hover:bg-surface',
                                    )}
                                >
                                    Semua
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
                                                    : 'border-border bg-white text-secondary hover:bg-surface',
                                            )}
                                        >
                                            {m}
                                        </button>
                                    );
                                })}
                                {hasFilter && (
                                    <Button
                                        variant="outline"
                                        onClick={resetAll}
                                        className="ml-auto h-7 px-2.5 text-xs"
                                    >
                                        <RotateCcw className="h-3.5 w-3.5" />
                                        Reset
                                    </Button>
                                )}
                            </div>
                        </div>

                        {activeKomponen.length === 0 ? (
                            <div className="flex flex-col items-center justify-center py-8 text-center">
                                <Filter className="mb-2 h-6 w-6 text-muted-foreground" />
                                <p className="text-sm font-medium text-secondary">
                                    Pilih minimal 1 komponen untuk
                                    ditampilkan.
                                </p>
                            </div>
                        ) : filteredMapel.length === 0 ? (
                            <div className="flex flex-col items-center justify-center py-8 text-center">
                                <Filter className="mb-2 h-6 w-6 text-muted-foreground" />
                                <p className="text-sm font-medium text-secondary">
                                    Tidak ada mata pelajaran yang sesuai
                                    filter.
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
