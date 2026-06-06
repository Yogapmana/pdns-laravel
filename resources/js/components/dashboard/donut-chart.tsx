export function DonutChart({ lulus, tidakLulus }: { lulus: number; tidakLulus: number }) {
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
