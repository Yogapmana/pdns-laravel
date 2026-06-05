import { Link } from '@inertiajs/react';
import { ArrowLeft, FileDown, Globe, BarChart3, CheckCircle, XCircle } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Container, PageHeader, StatCard, TableEmpty } from '@/components/ui/shared';

type NilaiItem = {
    id: number;
    nilai_tugas: number | null;
    nilai_uts: number | null;
    nilai_uas: number | null;
    nilai_akhir: number | null;
    status_lulus: string | null;
};

type Row = {
    siswa: { nis: string; nama_siswa: string; kelas: string };
    nilai_per_mapel: Record<string, NilaiItem | null>;
    rata_rata: number | null;
};

type Props = {
    kelas: string;
    rows: Row[];
    mapel_list: string[];
    stats: { jumlah_siswa: number; jumlah_lulus: number; jumlah_tidak_lulus: number };
    tanggal_cetak: string;
};

export default function ReportsPreview({ kelas, rows, mapel_list, stats, tanggal_cetak }: Props) {
    return (
        <Container>
            <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                <div className="flex items-center gap-3">
                    <Link href="/admin/laporan" className="text-muted-foreground hover:text-foreground">
                        <ArrowLeft className="h-4 w-4" />
                    </Link>
                    <PageHeader title={`Laporan Kelas ${kelas}`} description={`Tanggal cetak: ${tanggal_cetak}`} />
                </div>
                <div className="flex gap-2">
                    <a href={`/admin/laporan/export/pdf?kelas=${encodeURIComponent(kelas)}`} target="_blank" rel="noopener">
                        <Button variant="success">
                            <FileDown className="h-4 w-4" />
                            PDF
                        </Button>
                    </a>
                    <a href={`/admin/laporan/export/html?kelas=${encodeURIComponent(kelas)}`} target="_blank" rel="noopener">
                        <Button variant="outline">
                            <Globe className="h-4 w-4" />
                            HTML
                        </Button>
                    </a>
                </div>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <StatCard label="Total Siswa" value={stats.jumlah_siswa} icon={<BarChart3 className="h-6 w-6" />} color="primary" />
                <StatCard label="Lulus" value={stats.jumlah_lulus} icon={<CheckCircle className="h-6 w-6" />} color="success" />
                <StatCard label="Tidak Lulus" value={stats.jumlah_tidak_lulus} icon={<XCircle className="h-6 w-6" />} color="danger" />
            </div>

            <Card className="p-0 overflow-hidden">
                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="bg-primary text-white">
                                <th className="px-3 py-3 text-left text-xs font-bold uppercase tracking-wide sticky left-0 bg-primary">No</th>
                                <th className="px-3 py-3 text-left text-xs font-bold uppercase tracking-wide sticky left-12 bg-primary">NIS</th>
                                <th className="px-3 py-3 text-left text-xs font-bold uppercase tracking-wide">Nama Siswa</th>
                                {mapel_list.map((m) => (
                                    <th key={m} className="px-3 py-3 text-center text-xs font-bold uppercase tracking-wide" colSpan={3}>
                                        {m}
                                    </th>
                                ))}
                                <th className="px-3 py-3 text-center text-xs font-bold uppercase tracking-wide">Rata-rata</th>
                            </tr>
                            <tr className="bg-primary-600 text-white">
                                <th colSpan={3} className="px-3 py-1 text-right text-[10px]">Bobot</th>
                                {mapel_list.map((m) => (
                                    <>
                                        <th key={`${m}-t`} className="px-1 py-1 text-center text-[9px]">Tgs</th>
                                        <th key={`${m}-u`} className="px-1 py-1 text-center text-[9px]">UTS</th>
                                        <th key={`${m}-a`} className="px-1 py-1 text-center text-[9px]">UAS</th>
                                    </>
                                ))}
                                <th className="px-3 py-1 text-center text-[9px]">—</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {rows.length === 0 ? (
                                <TableEmpty message="Tidak ada data siswa di kelas ini." colSpan={3 + mapel_list.length * 3 + 1} />
                            ) : (
                                rows.map((r, i) => (
                                    <tr key={r.siswa.nis} className={`${i % 2 === 0 ? 'bg-white' : 'bg-surface'} hover:bg-blue-50`}>
                                        <td className="px-3 py-3 sticky left-0 bg-inherit">{i + 1}</td>
                                        <td className="px-3 py-3 font-mono text-xs sticky left-12 bg-inherit">{r.siswa.nis}</td>
                                        <td className="px-3 py-3 font-medium">{r.siswa.nama_siswa}</td>
                                        {mapel_list.map((m) => {
                                            const n = r.nilai_per_mapel[m];

                                            return (
                                                <>
                                                    <td key={`${r.siswa.nis}-${m}-t`} className="px-2 py-3 text-center text-xs">{n?.nilai_tugas ?? '—'}</td>
                                                    <td key={`${r.siswa.nis}-${m}-u`} className="px-2 py-3 text-center text-xs">{n?.nilai_uts ?? '—'}</td>
                                                    <td key={`${r.siswa.nis}-${m}-a`} className="px-2 py-3 text-center text-xs">
                                                        <div className="flex flex-col items-center">
                                                            <span>{n?.nilai_uas ?? '—'}</span>
                                                            {n?.nilai_akhir !== null && n?.nilai_akhir !== undefined && (
                                                                <Badge variant={n.status_lulus === 'Lulus' ? 'success' : 'danger'} className="mt-0.5 text-[10px]">
                                                                    {n.nilai_akhir}
                                                                </Badge>
                                                            )}
                                                        </div>
                                                    </td>
                                                </>
                                            );
                                        })}
                                        <td className="px-3 py-3 text-center font-bold text-navy">
                                            {r.rata_rata !== null ? r.rata_rata.toFixed(2) : '—'}
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>
            </Card>
        </Container>
    );
}

ReportsPreview.layout = { title: 'Preview Laporan' };
