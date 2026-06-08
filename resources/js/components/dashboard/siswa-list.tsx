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

export function SiswaList({
    data,
    emptyMessage,
    isDanger = false,
    showRatio = false,
}: {
    data: SiswaEntry[];
    emptyMessage: string;
    isDanger?: boolean;
    showRatio?: boolean;
}) {
    const accentColor = isDanger ? 'text-rose-600' : 'text-emerald-600';
    const barColor = isDanger 
        ? 'bg-gradient-to-r from-rose-400 to-rose-500 shadow-sm shadow-rose-200' 
        : 'bg-gradient-to-r from-emerald-400 to-emerald-500 shadow-sm shadow-emerald-200';

    return (
        <div>
            {data.length === 0 ? (
                <p className="text-sm text-muted-foreground text-center py-6">{emptyMessage}</p>
            ) : (
                <div className="pt-2 space-y-2">
                    {data.map((s, i) => {
                        const ratio = showRatio && s.rasio_tidak_lulus !== undefined ? s.rasio_tidak_lulus : null;
                        const barPct = isDanger ? (ratio ?? 0) : s.rata_rata;
                        
                        // Rank colors
                        let circleBg = 'bg-slate-100 text-slate-500';
                        if (i === 0) circleBg = 'bg-amber-100 text-amber-700 font-extrabold shadow-sm'; // Gold
                        else if (i === 1) circleBg = 'bg-slate-200 text-slate-700 font-extrabold shadow-sm'; // Silver
                        else if (i === 2) circleBg = 'bg-orange-100 text-orange-800 font-extrabold shadow-sm'; // Bronze
                        else if (isDanger) circleBg = 'bg-rose-50 text-rose-600';
                        else circleBg = 'bg-emerald-50 text-emerald-600';

                        return (
                            <Link
                                key={s.nis}
                                href={`/admin/siswa/${s.nis}/edit`}
                                prefetch
                                className="flex items-center gap-4 py-3 px-3 rounded-2xl hover:bg-white hover:shadow-sm hover:ring-1 hover:ring-slate-100 transition-all group"
                            >
                                <div
                                    className={`flex-shrink-0 w-8 h-8 rounded-full ${circleBg} text-sm font-bold flex items-center justify-center`}
                                >
                                    {i + 1}
                                </div>
                                <div className="flex-1 min-w-0">
                                    <p className="text-[15px] font-bold text-navy truncate group-hover:text-primary transition">
                                        {s.nama_siswa}
                                    </p>
                                    <p className="text-xs text-muted-foreground mt-0.5">
                                        <span className="font-semibold text-navy">{s.kelas}</span>
                                        <span className="mx-1.5 text-[10px] text-slate-300">•</span>
                                        {!isDanger 
                                            ? <span className="text-emerald-600 font-medium">Lulus {s.lulus}/{s.total_mapel}</span>
                                            : <span className="text-rose-600 font-medium">{s.tidak_lulus}/{s.total_mapel} mapel gagal</span>
                                        }
                                    </p>
                                    <div className="relative h-1.5 w-full bg-slate-100 rounded-full overflow-hidden mt-2.5">
                                        <div
                                            className={`absolute inset-y-0 left-0 rounded-full ${barColor}`}
                                            style={{ width: `${Math.min(100, barPct)}%` }}
                                        />
                                    </div>
                                </div>
                                <div className={`text-base font-mono font-bold flex-shrink-0 text-right ${accentColor}`}>
                                    <div>{!isDanger ? formatAvg(s.rata_rata) : s.tidak_lulus}</div>
                                    {isDanger && <div className="text-[10px] font-sans font-medium uppercase mt-0.5 opacity-80">Mapel</div>}
                                </div>
                            </Link>
                        );
                    })}
                </div>
            )}
        </div>
    );
}
