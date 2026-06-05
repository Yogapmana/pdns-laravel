import { Link } from '@inertiajs/react';
import { ArrowLeft, FileDown, Globe, BarChart3, CheckCircle, XCircle, FileSpreadsheet, FileText } from 'lucide-react';
import { Fragment } from 'react';
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

type Section = {
    kelas: string;
    rows: Row[];
    stats: { jumlah_siswa: number; jumlah_lulus: number; jumlah_tidak_lulus: number };
};

type Props = {
    kelas_list: string[];
    sections: Section[];
    mapel_list: string[];
    stats: { jumlah_siswa: number; jumlah_lulus: number; jumlah_tidak_lulus: number };
    tanggal_cetak: string;
};

function buildExportUrl(endpoint: string, params: Record<string, string | string[]>) {
    const search = new URLSearchParams();

    for (const [k, v] of Object.entries(params)) {
        if (Array.isArray(v)) {
            v.forEach((vv) => search.append(`${k}[]`, vv));
        } else if (v) {
            search.set(k, v);
        }
    }

    return `${endpoint}?${search.toString()}`;
}

export default function ReportsPreview({ kelas_list, sections, mapel_list, stats, tanggal_cetak }: Props) {
    const isMulti = kelas_list.length > 1;
    const title = isMulti ? `Laporan Multi-Kelas (${kelas_list.length})` : `Laporan Kelas ${kelas_list[0]}`;

    const exportParams = { kelas: kelas_list };

    return (
        <Container>
            <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                <div className="flex items-center gap-3">
                    <Link href="/admin/laporan" className="text-muted-foreground hover:text-foreground">
                        <ArrowLeft className="h-4 w-4" />
                    </Link>
                    <PageHeader
                        title={title}
                        description={isMulti ? `Kelas: ${kelas_list.join(', ')} — ${tanggal_cetak}` : `Tanggal cetak: ${tanggal_cetak}`}
                    />
                </div>
                <div className="flex flex-wrap gap-2">
                    <a href={buildExportUrl('/admin/laporan/export/pdf', exportParams)} target="_blank" rel="noopener">
                        <Button variant="success">
                            <FileDown className="h-4 w-4" />
                            PDF
                        </Button>
                    </a>
                    <a href={buildExportUrl('/admin/laporan/export/xlsx', exportParams)} target="_blank" rel="noopener">
                        <Button variant="primary">
                            <FileSpreadsheet className="h-4 w-4" />
                            Excel
                        </Button>
                    </a>
                    <a href={buildExportUrl('/admin/laporan/export/csv', exportParams)} target="_blank" rel="noopener">
                        <Button variant="outline">
                            <FileText className="h-4 w-4" />
                            CSV
                        </Button>
                    </a>
                    <a href={buildExportUrl('/admin/laporan/export/html', exportParams)} target="_blank" rel="noopener">
                        <Button variant="outline">
                            <Globe className="h-4 w-4" />
                            HTML
                        </Button>
                    </a>
                </div>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <StatCard label={isMulti ? 'Total Siswa (Semua)' : 'Total Siswa'} value={stats.jumlah_siswa} icon={<BarChart3 className="h-6 w-6" />} color="primary" />
                <StatCard label="Lulus" value={stats.jumlah_lulus} icon={<CheckCircle className="h-6 w-6" />} color="success" />
                <StatCard label="Tidak Lulus" value={stats.jumlah_tidak_lulus} icon={<XCircle className="h-6 w-6" />} color="danger" />
            </div>

            {sections.length === 0 ? (
                <Card>
                    <Card className="text-center text-muted-foreground py-12">
                        Tidak ada data untuk filter yang dipilih.
                    </Card>
                </Card>
            ) : (
                sections.map((section) => (
                    <div key={section.kelas} className="space-y-3">
                        <div className="flex items-center justify-between">
                            <h2 className="text-lg font-bold text-navy">Kelas {section.kelas}</h2>
                            <div className="flex gap-3 text-sm text-muted-foreground">
                                <span>{section.stats.jumlah_siswa} siswa</span>
                                <span className="text-success">{section.stats.jumlah_lulus} lulus</span>
                                <span className="text-danger">{section.stats.jumlah_tidak_lulus} tidak lulus</span>
                            </div>
                        </div>

                        <Card className="p-0 overflow-hidden">
                            <div className="text-[10px] text-muted-foreground bg-slate-50 px-4 py-1.5 border-b border-border flex items-center gap-1.5">
                                <span>← Geser horizontal untuk melihat mata pelajaran lainnya →</span>
                            </div>
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm border-collapse table-fixed">
                                    <colgroup>
                                        <col className="w-12" />
                                        <col className="w-24" />
                                        <col className="w-48" />
                                        {mapel_list.map((m) => (
                                            <>
                                                <col key={`${m}-t`} className="w-14" />
                                                <col key={`${m}-u`} className="w-14" />
                                                <col key={`${m}-a`} className="w-14" />
                                                <col key={`${m}-k`} className="w-16" />
                                            </>
                                        ))}
                                        <col className="w-20" />
                                    </colgroup>
                                    <thead>
                                        <tr className="bg-primary text-white">
                                            <th rowSpan={2} className="sticky left-0 z-20 bg-primary px-2 py-2 text-center text-[11px] font-bold uppercase align-middle border-r border-primary-light/30">
                                                No
                                            </th>
                                            <th rowSpan={2} className="sticky left-12 z-20 bg-primary px-3 py-2 text-left text-[11px] font-bold uppercase align-middle border-r border-primary-light/30">
                                                NIS
                                            </th>
                                            <th rowSpan={2} className="sticky left-36 z-20 bg-primary px-3 py-2 text-left text-[11px] font-bold uppercase align-middle border-r border-primary-light/30">
                                                Nama Siswa
                                            </th>
                                            {mapel_list.map((m) => (
                                                <th
                                                    key={m}
                                                    colSpan={4}
                                                    className="px-2 py-2 text-center text-[11px] font-bold uppercase border-l border-primary-light/30"
                                                >
                                                    {m}
                                                </th>
                                            ))}
                                            <th rowSpan={2} className="px-2 py-2 text-center text-[11px] font-bold uppercase align-middle border-l border-primary-light/30">
                                                Rata-rata
                                            </th>
                                        </tr>
                                        <tr className="bg-primary-600 text-white">
                                            {mapel_list.map((m) => (
                                                <Fragment key={`${m}-sub`}>
                                                    <th className="px-1 py-1 text-center text-[9px] font-medium border-l border-primary-light/30">Tgs</th>
                                                    <th className="px-1 py-1 text-center text-[9px] font-medium">UTS</th>
                                                    <th className="px-1 py-1 text-center text-[9px] font-medium">UAS</th>
                                                    <th className="px-1 py-1 text-center text-[9px] font-medium">Akhir</th>
                                                </Fragment>
                                            ))}
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100">
                                        {section.rows.length === 0 ? (
                                            <TableEmpty message={`Tidak ada siswa di kelas ${section.kelas}.`} colSpan={3 + mapel_list.length * 4 + 1} />
                                        ) : (
                                            section.rows.map((r, i) => (
                                                <tr key={r.siswa.nis} className={`group ${i % 2 === 0 ? 'bg-white' : 'bg-surface'} hover:bg-blue-50`}>
                                                    <td className={`sticky left-0 z-10 px-2 py-2 text-center text-xs border-r border-slate-200 ${i % 2 === 0 ? 'bg-white group-hover:bg-blue-50' : 'bg-surface group-hover:bg-blue-50'}`}>
                                                        {i + 1}
                                                    </td>
                                                    <td className={`sticky left-12 z-10 px-3 py-2 font-mono text-xs border-r border-slate-200 ${i % 2 === 0 ? 'bg-white group-hover:bg-blue-50' : 'bg-surface group-hover:bg-blue-50'}`}>
                                                        {r.siswa.nis}
                                                    </td>
                                                    <td className={`sticky left-36 z-10 px-3 py-2 font-medium border-r border-slate-200 truncate max-w-48 ${i % 2 === 0 ? 'bg-white group-hover:bg-blue-50' : 'bg-surface group-hover:bg-blue-50'}`}>
                                                        {r.siswa.nama_siswa}
                                                    </td>
                                                    {mapel_list.map((m) => {
                                                        const n = r.nilai_per_mapel[m];

                                                        return (
                                                            <Fragment key={`${r.siswa.nis}-${m}`}>
                                                                <td className="px-1 py-2 text-center text-xs">{n?.nilai_tugas ?? '—'}</td>
                                                                <td className="px-1 py-2 text-center text-xs">{n?.nilai_uts ?? '—'}</td>
                                                                <td className="px-1 py-2 text-center text-xs">{n?.nilai_uas ?? '—'}</td>
                                                                <td className="px-1 py-2 text-center text-xs border-r border-slate-200">
                                                                    {n?.nilai_akhir !== null && n?.nilai_akhir !== undefined ? (
                                                                        <Badge variant={n.status_lulus === 'Lulus' ? 'success' : 'danger'} className="text-[10px]">
                                                                            {n.nilai_akhir}
                                                                        </Badge>
                                                                    ) : (
                                                                        '—'
                                                                    )}
                                                                </td>
                                                            </Fragment>
                                                        );
                                                    })}
                                                    <td className="px-2 py-2 text-center font-bold text-navy text-xs">
                                                        {r.rata_rata !== null ? r.rata_rata.toFixed(2) : '—'}
                                                    </td>
                                                </tr>
                                            ))
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        </Card>
                    </div>
                ))
            )}
        </Container>
    );
}

ReportsPreview.layout = { title: 'Preview Laporan' };
