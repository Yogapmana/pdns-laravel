import { Link } from '@inertiajs/react';

export type RekapKelas = {
    kelas: string;
    jumlah_siswa: number;
    lulus: number;
    tidak_lulus: number;
    total_nilai: number;
    persentase_lulus: number;
};

export function KelasBarChart({ rekap, isExpanded = true }: { rekap: RekapKelas[], isExpanded?: boolean }) {
    if (rekap.length === 0) {
        return <p className="text-sm text-muted-foreground text-center py-6">Belum ada data.</p>;
    }

    const initialItems = rekap.slice(0, 5);
    const hiddenItems = rekap.slice(5);

    const renderItem = (r: RekapKelas) => {
        const isWarning = r.persentase_lulus < 70;
        const barColor = isWarning 
            ? 'bg-gradient-to-r from-rose-400 to-rose-500 shadow-sm shadow-rose-200' 
            : 'bg-gradient-to-r from-emerald-400 to-emerald-500 shadow-sm shadow-emerald-200';

        return (
            <Link
                key={r.kelas}
                href={`/admin/siswa?kelas=${encodeURIComponent(r.kelas)}`}
                prefetch
                className="flex items-center gap-4 group hover:bg-white hover:shadow-sm hover:ring-1 hover:ring-slate-100 py-3 px-3 rounded-2xl transition-all"
                title={`Klik untuk lihat siswa di ${r.kelas}`}
            >
                <div className="w-20 font-bold text-[15px] text-navy group-hover:text-primary transition truncate">
                    {r.kelas}
                </div>
                <div className="flex-1 relative h-1.5 bg-slate-100 rounded-full overflow-hidden">
                    <div
                        className={`absolute inset-y-0 left-0 h-full rounded-full ${barColor}`}
                        style={{ width: `${r.persentase_lulus}%` }}
                    />
                </div>
                <div className={`w-14 text-right font-mono font-bold text-base ${isWarning ? 'text-danger' : 'text-navy'}`}>
                    {r.persentase_lulus}%
                </div>
            </Link>
        );
    };

    return (
        <div className="pt-2">
            <div className="space-y-1">
                {initialItems.map(renderItem)}
            </div>
            
            {hiddenItems.length > 0 && (
                <div 
                    className={`grid transition-all duration-300 ease-in-out ${isExpanded ? 'grid-rows-[1fr] opacity-100' : 'grid-rows-[0fr] opacity-0'}`}
                >
                    <div className="overflow-hidden space-y-1 pt-1">
                        {hiddenItems.map(renderItem)}
                    </div>
                </div>
            )}
        </div>
    );
}
