import {
    CheckCircle,
    XCircle,
    BookOpen,
    Printer,
    FileSpreadsheet,
} from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { CardHeader } from '@/components/ui/card';
import { Container, DataTable, PageHeader } from '@/components/ui/shared';
import { useFlashToast } from '@/hooks/use-flash-toast';

type Guru = { id: number; nama_guru: string };

type Nilai = {
    id: number;
    kelas: { id: number; nama: string } | null;
    mataPelajaran: { id: number; nama: string } | null;
    nilai_tugas: number | null;
    nilai_uts: number | null;
    nilai_uas: number | null;
    nilai_akhir: number | null;
    status_lulus: string | null;
    status_validasi: string;
    id_guru: number;
};

type Props = {
    nilai: Record<string, Nilai[]>;
    mapel_list: string[];
    guru_map: Record<string, Guru>;
};

export default function SiswaNilai({ nilai, mapel_list, guru_map }: Props) {
    useFlashToast();

    const hasData = mapel_list.length > 0;

    return (
        <Container>
            <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <PageHeader title="Nilai Saya" />
                {hasData && (
                    <Button asChild className="flex-shrink-0">
                        <a
                            href="/siswa/rapor/pdf"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <Printer className="h-4 w-4" />
                            Cetak Rapor (PDF)
                        </a>
                    </Button>
                )}
            </div>

            {!hasData ? (
                <div className="mt-8 flex flex-col items-center justify-center rounded-xl border border-dashed border-border bg-slate-50/50 py-16 text-center">
                    <div className="rounded-full bg-slate-100 p-4">
                        <FileSpreadsheet className="h-8 w-8 text-slate-400" />
                    </div>
                    <h3 className="mt-4 text-lg font-semibold text-secondary">
                        Belum Ada Nilai
                    </h3>
                    <p className="mx-auto mt-2 max-w-sm text-sm text-muted-foreground">
                        Saat ini belum ada nilai mata pelajaran yang diinput
                        oleh guru untuk Anda. Silakan periksa kembali nanti.
                    </p>
                </div>
            ) : (
                mapel_list.map((mapel) => {
                    const entries = Object.entries(nilai).filter(([key]) =>
                        key.endsWith(`|${mapel}`),
                    );

                    return entries.map(([key, list]) => {
                        const kelas = list[0]?.kelas?.nama ?? '';
                        const namaGuru =
                            guru_map[String(list[0]?.id_guru)]?.nama_guru ??
                            '—';

                        return (
                            <DataTable key={key}>
                                <CardHeader>
                                    <div className="flex w-full flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                        <span className="flex items-center gap-2">
                                            <BookOpen className="h-4 w-4 text-primary" />
                                            {mapel}
                                        </span>
                                        <div className="flex items-center gap-2">
                                            <Badge variant="info">
                                                {kelas}
                                            </Badge>
                                            <span className="text-sm font-normal text-muted-foreground">
                                                Guru:{' '}
                                                <strong>{namaGuru}</strong>
                                            </span>
                                        </div>
                                    </div>
                                </CardHeader>
                                <div className="overflow-x-auto">
                                    <table className="w-full text-sm">
                                        <thead>
                                            <tr className="bg-surface text-secondary">
                                                <th className="px-4 py-3 text-left text-xs font-bold tracking-wide uppercase">
                                                    Komponen
                                                </th>
                                                <th className="px-4 py-3 text-center text-xs font-bold tracking-wide uppercase">
                                                    Nilai
                                                </th>
                                                <th className="px-4 py-3 text-center text-xs font-bold tracking-wide uppercase">
                                                    Bobot
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-slate-100">
                                            <tr>
                                                <td className="px-4 py-3">
                                                    Tugas
                                                </td>
                                                <td className="px-4 py-3 text-center font-mono">
                                                    {list[0]?.nilai_tugas ??
                                                        '—'}
                                                </td>
                                                <td className="px-4 py-3 text-center text-muted-foreground">
                                                    30%
                                                </td>
                                            </tr>
                                            <tr>
                                                <td className="px-4 py-3">
                                                    UTS
                                                </td>
                                                <td className="px-4 py-3 text-center font-mono">
                                                    {list[0]?.nilai_uts ??
                                                        '—'}
                                                </td>
                                                <td className="px-4 py-3 text-center text-muted-foreground">
                                                    30%
                                                </td>
                                            </tr>
                                            <tr>
                                                <td className="px-4 py-3">
                                                    UAS
                                                </td>
                                                <td className="px-4 py-3 text-center font-mono">
                                                    {list[0]?.nilai_uas ??
                                                        '—'}
                                                </td>
                                                <td className="px-4 py-3 text-center text-muted-foreground">
                                                    40%
                                                </td>
                                            </tr>
                                            <tr className="bg-blue-50">
                                                <td className="px-4 py-3 font-semibold text-navy">
                                                    Nilai Akhir
                                                </td>
                                                <td
                                                    colSpan={2}
                                                    className="px-4 py-3 text-center"
                                                >
                                                    <div className="flex items-center justify-center gap-3">
                                                        <span className="text-2xl font-bold text-navy">
                                                            {list[0]
                                                                ?.nilai_akhir ??
                                                                '—'}
                                                        </span>
                                                        {list[0]
                                                            ?.status_lulus ===
                                                            'Lulus' && (
                                                            <Badge variant="success">
                                                                <CheckCircle className="mr-1 h-3 w-3" />
                                                                Lulus
                                                            </Badge>
                                                        )}
                                                        {list[0]
                                                            ?.status_lulus ===
                                                            'Tidak Lulus' && (
                                                            <Badge variant="danger">
                                                                <XCircle className="mr-1 h-3 w-3" />
                                                                Tidak Lulus
                                                            </Badge>
                                                        )}
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </DataTable>
                        );
                    });
                })
            )}
        </Container>
    );
}

SiswaNilai.layout = { title: 'Nilai Saya' };
