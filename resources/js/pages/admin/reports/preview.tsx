import { Link } from '@inertiajs/react';
import {
    ArrowLeft,
    FileDown,
    Globe,
    BarChart3,
    CheckCircle,
    XCircle,
    FileSpreadsheet,
    FileText,
    Maximize2,
    Minimize2,
} from 'lucide-react';
import { Fragment, useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import {
    Container,
    DataTable,
    PageHeader,
    StatCard,
    TableEmpty,
} from '@/components/ui/shared';

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
    mapel_list: string[];
    rows: Row[];
    stats: {
        jumlah_siswa: number;
        jumlah_lulus: number;
        jumlah_tidak_lulus: number;
    };
};

type Props = {
    kelas_list: string[];
    sections: Section[];
    mapel_list: string[];
    stats: {
        jumlah_siswa: number;
        jumlah_lulus: number;
        jumlah_tidak_lulus: number;
    };
    tanggal_cetak: string;
};

function buildExportUrl(
    endpoint: string,
    params: Record<string, string | string[]>,
) {
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

export default function ReportsPreview({
    kelas_list,
    sections,
    mapel_list,
    stats,
    tanggal_cetak,
}: Props) {
    const [isCompact, setIsCompact] = useState(false);
    const isMulti = kelas_list.length > 1;
    const title = isMulti
        ? `Laporan Multi-Kelas (${kelas_list.length})`
        : `Laporan Kelas ${kelas_list[0]}`;

    const exportParams = { kelas: kelas_list };

    return (
        <Container>
            <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div className="flex items-center gap-3">
                    <Link
                        href="/admin/laporan"
                        className="text-muted-foreground hover:text-foreground"
                    >
                        <ArrowLeft className="h-4 w-4" />
                    </Link>
                    <PageHeader
                        title={title}
                        description={
                            isMulti
                                ? `Kelas: ${kelas_list.join(', ')} — ${tanggal_cetak}`
                                : `Tanggal cetak: ${tanggal_cetak}`
                        }
                    />
                </div>
                <div className="flex flex-wrap gap-2">
                    <a
                        href={buildExportUrl(
                            '/admin/laporan/export/pdf',
                            exportParams,
                        )}
                        target="_blank"
                        rel="noopener"
                    >
                        <Button variant="danger">
                            <FileDown className="h-4 w-4" />
                            PDF
                        </Button>
                    </a>

                    <a
                        href={buildExportUrl(
                            '/admin/laporan/export/csv',
                            exportParams,
                        )}
                        target="_blank"
                        rel="noopener"
                    >
                        <Button variant="success">
                            <FileText className="h-4 w-4" />
                            CSV
                        </Button>
                    </a>
                    <a
                        href={buildExportUrl(
                            '/admin/laporan/export/html',
                            exportParams,
                        )}
                        target="_blank"
                        rel="noopener"
                    >
                        <Button variant="outline">
                            <Globe className="h-4 w-4" />
                            HTML
                        </Button>
                    </a>
                </div>
            </div>

            <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <StatCard
                    label={isMulti ? 'Total Siswa (Semua)' : 'Total Siswa'}
                    value={stats.jumlah_siswa}
                    icon={<BarChart3 />}
                    color="primary"
                    variant="colored"
                    description={
                        isMulti
                            ? 'Gabungan seluruh kelas'
                            : 'Seluruh siswa di kelas'
                    }
                />
                <StatCard
                    label="Lulus"
                    value={stats.jumlah_lulus}
                    icon={<CheckCircle />}
                    color="success"
                    variant="colored"
                    description="Nilai ≥ KKM (70)"
                />
                <StatCard
                    label="Tidak Lulus"
                    value={stats.jumlah_tidak_lulus}
                    icon={<XCircle />}
                    color="danger"
                    variant="colored"
                    description="Nilai < KKM (70)"
                />
            </div>

            {sections.length > 0 && (
                <div className="mt-8 mb-4 flex items-center justify-between border-b border-border pb-4">
                    <h3 className="text-lg font-bold text-navy">
                        Rincian Nilai
                    </h3>
                    <Button
                        variant={isCompact ? 'primary' : 'outline'}
                        onClick={() => setIsCompact(!isCompact)}
                        className="h-9"
                    >
                        {isCompact ? (
                            <Maximize2 className="mr-2 h-4 w-4" />
                        ) : (
                            <Minimize2 className="mr-2 h-4 w-4" />
                        )}
                        {isCompact ? 'Tampilkan Rincian' : 'Mode Ringkas'}
                    </Button>
                </div>
            )}

            {sections.length === 0 ? (
                <Card>
                    <div className="py-12 text-center text-muted-foreground">
                        Tidak ada data untuk filter yang dipilih.
                    </div>
                </Card>
            ) : (
                sections.map((section) => (
                    <div key={section.kelas} className="space-y-3">
                        <div className="flex items-center justify-between">
                            <h2 className="text-lg font-bold text-navy">
                                Kelas {section.kelas}
                            </h2>
                            <div className="flex gap-3 text-sm text-muted-foreground">
                                <span>{section.stats.jumlah_siswa} siswa</span>
                                <span className="text-success">
                                    {section.stats.jumlah_lulus} lulus
                                </span>
                                <span className="text-danger">
                                    {section.stats.jumlah_tidak_lulus} tidak
                                    lulus
                                </span>
                            </div>
                        </div>

                        <DataTable>
                            <div className="overflow-x-auto">
                                <table className="w-full min-w-[1000px] table-fixed border-collapse text-sm">
                                    <colgroup>
                                        <col className="w-12" />
                                        <col className="w-24" />
                                        <col className="w-48" />
                                        {section.mapel_list.map((m) => (
                                            <Fragment key={`${m}-cols`}>
                                                {!isCompact && (
                                                    <col className="w-12" />
                                                )}
                                                {!isCompact && (
                                                    <col className="w-12" />
                                                )}
                                                {!isCompact && (
                                                    <col className="w-12" />
                                                )}
                                                <col
                                                    className={
                                                        isCompact
                                                            ? 'w-24'
                                                            : 'w-16'
                                                    }
                                                />
                                            </Fragment>
                                        ))}
                                        <col className="w-20" />
                                    </colgroup>
                                    <thead>
                                        <tr className="bg-navy text-white">
                                            <th
                                                rowSpan={isCompact ? 1 : 2}
                                                className="border-navy-light/30 sticky left-0 z-20 border-r bg-navy px-2 py-2 text-center align-middle text-[11px] font-bold uppercase"
                                            >
                                                No
                                            </th>
                                            <th
                                                rowSpan={isCompact ? 1 : 2}
                                                className="border-navy-light/30 sticky left-12 z-20 border-r bg-navy px-3 py-2 text-left align-middle text-[11px] font-bold uppercase"
                                            >
                                                NIS
                                            </th>
                                            <th
                                                rowSpan={isCompact ? 1 : 2}
                                                className="border-navy-light/30 sticky left-36 z-20 border-r bg-navy px-3 py-2 text-left align-middle text-[11px] font-bold uppercase"
                                            >
                                                Nama Siswa
                                            </th>
                                            {section.mapel_list.map(
                                                (m, mIdx) => (
                                                    <th
                                                        key={m}
                                                        colSpan={
                                                            isCompact ? 1 : 4
                                                        }
                                                        className={`border-navy-light/30 border-l px-2 py-2 text-center text-[11px] font-bold uppercase ${mIdx % 2 === 0 ? 'bg-navy' : 'bg-navy-light'}`}
                                                        title={m}
                                                    >
                                                        {isCompact ? (
                                                            <span className="line-clamp-1">
                                                                {m}
                                                            </span>
                                                        ) : (
                                                            m
                                                        )}
                                                    </th>
                                                ),
                                            )}
                                            <th
                                                rowSpan={isCompact ? 1 : 2}
                                                className="border-navy-light/30 border-l px-2 py-2 text-center align-middle text-[11px] font-bold uppercase"
                                            >
                                                Rata-rata
                                            </th>
                                        </tr>
                                        {!isCompact && (
                                            <tr className="bg-navy text-white">
                                                {section.mapel_list.map(
                                                    (m, mIdx) => (
                                                        <Fragment
                                                            key={`${m}-sub`}
                                                        >
                                                            <th
                                                                className={`border-navy-light/30 border-l px-1 py-1 text-center text-[9px] font-medium ${mIdx % 2 === 0 ? 'bg-navy' : 'bg-navy-light'}`}
                                                            >
                                                                Tgs
                                                            </th>
                                                            <th
                                                                className={`px-1 py-1 text-center text-[9px] font-medium ${mIdx % 2 === 0 ? 'bg-navy' : 'bg-navy-light'}`}
                                                            >
                                                                UTS
                                                            </th>
                                                            <th
                                                                className={`px-1 py-1 text-center text-[9px] font-medium ${mIdx % 2 === 0 ? 'bg-navy' : 'bg-navy-light'}`}
                                                            >
                                                                UAS
                                                            </th>
                                                            <th
                                                                className={`px-1 py-1 text-center text-[9px] font-medium ${mIdx % 2 === 0 ? 'bg-navy' : 'bg-navy-light'}`}
                                                            >
                                                                Akhir
                                                            </th>
                                                        </Fragment>
                                                    ),
                                                )}
                                            </tr>
                                        )}
                                    </thead>
                                    <tbody className="divide-y divide-slate-100">
                                        {section.rows.length === 0 ? (
                                            <TableEmpty
                                                message={`Tidak ada siswa di kelas ${section.kelas}.`}
                                                colSpan={
                                                    3 +
                                                    section.mapel_list.length *
                                                        (isCompact ? 1 : 4) +
                                                    1
                                                }
                                            />
                                        ) : (
                                            section.rows.map((r, i) => (
                                                <tr
                                                    key={r.siswa.nis}
                                                    className={`group ${i % 2 === 0 ? 'bg-white' : 'bg-surface'} hover:bg-blue-50`}
                                                >
                                                    <td
                                                        className={`sticky left-0 z-10 border-r border-slate-200 px-2 py-2 text-center text-xs ${i % 2 === 0 ? 'bg-white group-hover:bg-blue-50' : 'bg-surface group-hover:bg-blue-50'}`}
                                                    >
                                                        {i + 1}
                                                    </td>
                                                    <td
                                                        className={`sticky left-12 z-10 border-r border-slate-200 px-3 py-2 font-mono text-xs ${i % 2 === 0 ? 'bg-white group-hover:bg-blue-50' : 'bg-surface group-hover:bg-blue-50'}`}
                                                    >
                                                        {r.siswa.nis}
                                                    </td>
                                                    <td
                                                        className={`sticky left-36 z-10 max-w-48 truncate border-r border-slate-200 px-3 py-2 font-medium ${i % 2 === 0 ? 'bg-white group-hover:bg-blue-50' : 'bg-surface group-hover:bg-blue-50'}`}
                                                    >
                                                        {r.siswa.nama_siswa}
                                                    </td>
                                                    {section.mapel_list.map(
                                                        (m, mIdx) => {
                                                            const n =
                                                                r
                                                                    .nilai_per_mapel[
                                                                    m
                                                                ];
                                                            const bgClass =
                                                                mIdx % 2 === 0
                                                                    ? ''
                                                                    : 'bg-slate-50/50 group-hover:bg-blue-100/30';

                                                            return (
                                                                <Fragment
                                                                    key={`${r.siswa.nis}-${m}`}
                                                                >
                                                                    {!isCompact && (
                                                                        <td
                                                                            className={`border-l border-slate-100 px-1 py-2 text-center text-xs ${bgClass}`}
                                                                        >
                                                                            {n?.nilai_tugas ??
                                                                                '—'}
                                                                        </td>
                                                                    )}
                                                                    {!isCompact && (
                                                                        <td
                                                                            className={`px-1 py-2 text-center text-xs ${bgClass}`}
                                                                        >
                                                                            {n?.nilai_uts ??
                                                                                '—'}
                                                                        </td>
                                                                    )}
                                                                    {!isCompact && (
                                                                        <td
                                                                            className={`px-1 py-2 text-center text-xs ${bgClass}`}
                                                                        >
                                                                            {n?.nilai_uas ??
                                                                                '—'}
                                                                        </td>
                                                                    )}
                                                                    <td
                                                                        className={`border-r border-slate-200 px-1 py-2 text-center text-xs ${isCompact ? 'border-l border-slate-100' : ''} ${bgClass}`}
                                                                    >
                                                                        {n?.nilai_akhir !==
                                                                            null &&
                                                                        n?.nilai_akhir !==
                                                                            undefined ? (
                                                                            <Badge
                                                                                variant={
                                                                                    n.status_lulus ===
                                                                                    'Lulus'
                                                                                        ? 'success'
                                                                                        : 'danger'
                                                                                }
                                                                                className="text-[10px]"
                                                                            >
                                                                                {
                                                                                    n.nilai_akhir
                                                                                }
                                                                            </Badge>
                                                                        ) : (
                                                                            '—'
                                                                        )}
                                                                    </td>
                                                                </Fragment>
                                                            );
                                                        },
                                                    )}
                                                    <td className="px-2 py-2 text-center text-xs font-bold text-navy">
                                                        {r.rata_rata !== null
                                                            ? r.rata_rata.toFixed(
                                                                  2,
                                                              )
                                                            : '—'}
                                                    </td>
                                                </tr>
                                            ))
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        </DataTable>
                    </div>
                ))
            )}
        </Container>
    );
}

ReportsPreview.layout = { title: 'Preview Laporan' };
