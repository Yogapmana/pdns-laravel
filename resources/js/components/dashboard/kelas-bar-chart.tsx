import { Link } from '@inertiajs/react';

export type RekapKelas = {
    kelas: string;
    jumlah_siswa: number;
    lulus: number;
    tidak_lulus: number;
    total_nilai: number;
    persentase_lulus: number;
};

export function KelasBarChart({ rekap }: { rekap: RekapKelas[] }) {
    if (rekap.length === 0) {
        return <p className="text-sm text-muted-foreground text-center py-6">Belum ada data.</p>;
    }

    const maxTotal = Math.max(...rekap.map((r) => r.lulus + r.tidak_lulus), 1);

    return (
        <div className="space-y-3">
            {rekap.map((r) => {
                const total = r.lulus + r.tidak_lulus;
                const lulusPct = total > 0 ? (r.lulus / maxTotal) * 100 : 0;
                const tdkPct = total > 0 ? (r.tidak_lulus / maxTotal) * 100 : 0;

                return (
                    <Link
                        key={r.kelas}
                        href={`/admin/siswa?kelas=${encodeURIComponent(r.kelas)}`}
                        prefetch
                        className="block group"
                        title={`Klik untuk lihat siswa di ${r.kelas}`}
                    >
                        <div className="flex items-center justify-between mb-1 text-xs">
                            <span className="font-bold text-navy group-hover:text-primary transition">{r.kelas}</span>
                            <span className="text-muted-foreground font-mono">
                                {r.lulus}/{total} <span className="text-success font-semibold">({r.persentase_lulus}%)</span>
                            </span>
                        </div>
                        <div className="relative h-5 w-full bg-slate-100 rounded overflow-hidden flex">
                            <div
                                className="h-full bg-success group-hover:bg-emerald-600 transition-all"
                                style={{ width: `${lulusPct}%` }}
                                title={`Lulus: ${r.lulus}`}
                            />
                            <div
                                className="h-full bg-danger group-hover:bg-rose-600 transition-all"
                                style={{ width: `${tdkPct}%` }}
                                title={`Tidak Lulus: ${r.tidak_lulus}`}
                            />
                        </div>
                    </Link>
                );
            })}
            <div className="flex items-center gap-4 pt-2 text-[10px] text-muted-foreground">
                <span className="flex items-center gap-1">
                    <span className="w-3 h-2 bg-success rounded-sm" /> Lulus
                </span>
                <span className="flex items-center gap-1">
                    <span className="w-3 h-2 bg-danger rounded-sm" /> Tidak Lulus
                </span>
            </div>
        </div>
    );
}
