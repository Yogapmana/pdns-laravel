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
        <div>
            <div className="space-y-5 max-h-[400px] overflow-y-auto pr-2 custom-scrollbar">
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
                            <div className="flex items-center justify-between mb-1.5 text-sm">
                                <span className="font-semibold text-navy truncate group-hover:text-primary transition flex-1 mr-2">
                                    {m.mata_pelajaran}
                                </span>
                                <span className={`font-mono text-base font-bold flex-shrink-0 ${m.rata_rata >= kkm ? 'text-success' : 'text-warning'}`}>
                                    {formatAvg(m.rata_rata)}
                                </span>
                            </div>
                            <div className="relative h-2 w-full bg-slate-100 rounded-full overflow-hidden">
                                <div
                                    className={`absolute inset-y-0 left-0 rounded-full ${barColor} group-hover:opacity-80 transition-all`}
                                    style={{ width: `${pct}%` }}
                                />
                                <div
                                    className="absolute inset-y-0 w-0.5 bg-navy"
                                    style={{ left: `${kkmPct}%` }}
                                    title={`KKM ${kkm}`}
                                />
                            </div>
                            <div className="flex items-center justify-between mt-1.5 text-[11px] text-muted-foreground">
                                <span>{m.total_nilai} nilai</span>
                                <div className="flex items-center gap-3">
                                    <span>Lulus <span className="text-success font-semibold">{m.lulus}</span></span>
                                    <span>Tidak <span className="text-danger font-semibold">{m.tidak_lulus}</span></span>
                                </div>
                            </div>
                        </Link>
                    );
                })}
            </div>
            <div className="flex items-center gap-4 pt-3 mt-3 border-t border-border text-xs text-muted-foreground">
                <span className="flex items-center gap-1.5">
                    <span className="w-4 h-0.5 bg-navy" /> KKM {kkm}
                </span>
                <span className="flex items-center gap-1.5">
                    <span className="w-4 h-2 bg-success rounded-sm" /> ≥ KKM
                </span>
                <span className="flex items-center gap-1.5">
                    <span className="w-4 h-2 bg-warning rounded-sm" /> &lt; KKM
                </span>
            </div>
        </div>
    );
}
