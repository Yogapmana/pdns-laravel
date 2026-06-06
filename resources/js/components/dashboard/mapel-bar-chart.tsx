import { Link } from '@inertiajs/react';

export type RataRataMapel = {
    mata_pelajaran: string;
    rata_rata: number;
    total_nilai: number;
    lulus: number;
    tidak_lulus: number;
    persentase_lulus: number;
};

export function formatAvg(v: number | null | undefined): string {
    if (v === null || v === undefined) {
        return '—';
    }
    return Number(v).toFixed(2);
}

export function MapelBarChart({ data, kkm }: { data: RataRataMapel[]; kkm: number }) {
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
