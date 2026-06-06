import { Link } from '@inertiajs/react';
import { useMemo } from 'react';
import { ArrowUpDown } from 'lucide-react';
import { formatAvg } from './mapel-bar-chart';

export type SiswaEntry = {
    nis: string;
    nama_siswa: string;
    kelas: string;
    rata_rata: number;
    total_mapel: number;
    lulus: number;
    tidak_lulus: number;
    rasio_tidak_lulus?: number;
};

export type SortKey = 'rank' | 'alpha';

export function SiswaList({
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
                    className="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold text-secondary border border-border rounded-md hover:bg-surface transition"
                    title={sortKey === 'rank' ? 'Urutkan A-Z' : 'Urutkan berdasarkan ranking'}
                >
                    <ArrowUpDown className="h-3.5 w-3.5" />
                    {sortKey === 'rank' ? 'Ranking' : 'A-Z'}
                </button>
            </div>
            {sorted.length === 0 ? (
                <p className="text-sm text-muted-foreground text-center py-6">{emptyMessage}</p>
            ) : (
                <div className="divide-y divide-border border-t border-border mt-2">
                    {sorted.map((s, i) => {
                        const ratio = showRatio && s.rasio_tidak_lulus !== undefined ? s.rasio_tidak_lulus : null;
                        const barPct = colorScheme === 'success' ? s.rata_rata : (ratio ?? 0);

                        return (
                            <Link
                                key={s.nis}
                                href={`/admin/siswa/${s.nis}/edit`}
                                prefetch
                                className="flex items-center gap-4 py-4 hover:bg-slate-50/50 transition group px-2 -mx-2 rounded-lg"
                            >
                                <span
                                    className={`flex-shrink-0 w-8 h-8 rounded-full ${colorScheme === 'success' ? 'bg-emerald-100' : 'bg-rose-100'} ${accentColor} text-sm font-bold flex items-center justify-center`}
                                >
                                    {sortKey === 'rank' ? i + 1 : s.nama_siswa.charAt(0).toUpperCase()}
                                </span>
                                <div className="flex-1 min-w-0">
                                    <div className="flex items-baseline justify-between gap-2">
                                        <p className="text-sm font-bold text-navy truncate group-hover:text-primary transition">
                                            {s.nama_siswa}
                                        </p>
                                        <span className={`text-base font-mono font-bold flex-shrink-0 ${accentColor}`}>
                                            {colorScheme === 'success' ? formatAvg(s.rata_rata) : `${ratio}%`}
                                        </span>
                                    </div>
                                    <div className="flex items-center gap-2 mt-0.5">
                                        <span className="text-xs text-muted-foreground font-medium">{s.kelas}</span>
                                        <span className="text-[10px] text-slate-300">•</span>
                                        <span className="text-xs text-muted-foreground">
                                            {colorScheme === 'success'
                                                ? `${s.total_mapel} mapel • Lulus ${s.lulus}/${s.total_mapel}`
                                                : `${s.tidak_lulus}/${s.total_mapel} mapel tidak lulus`}
                                        </span>
                                    </div>
                                    <div className="relative h-2 w-full bg-slate-100 rounded-full overflow-hidden mt-2.5">
                                        <div
                                            className={`absolute inset-y-0 left-0 rounded-full ${barColor}`}
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
