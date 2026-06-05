import { Lock, CheckCircle, XCircle, BookOpen } from 'lucide-react';
import { Alert } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Card, CardHeader, CardContent } from '@/components/ui/card';
import { Container, PageHeader } from '@/components/ui/shared';
import { useFlashToast } from '@/hooks/use-flash-toast';

type Siswa = { nis: string; nama_siswa: string; kelas: string };
type Guru = { id: number; nama_guru: string };

type Nilai = {
    id: number;
    kelas: string;
    mata_pelajaran: string;
    nilai_tugas: number | null;
    nilai_uts: number | null;
    nilai_uas: number | null;
    nilai_akhir: number | null;
    status_lulus: string | null;
    status_validasi: string;
    id_guru: number;
};

type Props = {
    siswa: Siswa;
    nilai: Record<string, Nilai[]>;
    mapel_list: string[];
    guru_map: Record<string, Guru>;
};

export default function SiswaNilai({ siswa, nilai, mapel_list, guru_map }: Props) {
    useFlashToast();

    return (
        <Container>
            <PageHeader
                title="Nilai Saya"
                description={`${siswa.nama_siswa} — NIS: ${siswa.nis} — Kelas: ${siswa.kelas}`}
            />

            <Alert variant="info">
                <span className="flex items-center gap-2">
                    <Lock className="h-4 w-4" />
                    Halaman ini hanya dapat dilihat, tidak dapat diubah.
                </span>
            </Alert>

            {mapel_list.length === 0 ? (
                <Card>
                    <CardContent className="text-center text-muted-foreground py-12">
                        Belum ada nilai yang diinput.
                    </CardContent>
                </Card>
            ) : (
                mapel_list.map((mapel) => {
                    const entries = Object.entries(nilai).filter(([key]) => key.endsWith(`|${mapel}`));

                    return entries.map(([key, list]) => {
                        const kelas = list[0]?.kelas ?? '';
                        const namaGuru = guru_map[String(list[0]?.id_guru)]?.nama_guru ?? '—';

                        return (
                            <Card key={key} className="p-0 overflow-hidden">
                                <CardHeader>
                                    <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 w-full">
                                        <span className="flex items-center gap-2">
                                            <BookOpen className="h-4 w-4 text-primary" />
                                            {mapel}
                                        </span>
                                        <div className="flex items-center gap-2">
                                            <Badge variant="info">{kelas}</Badge>
                                            <span className="text-sm font-normal text-muted-foreground">
                                                Guru: <strong>{namaGuru}</strong>
                                            </span>
                                        </div>
                                    </div>
                                </CardHeader>
                                <div className="overflow-x-auto">
                                    <table className="w-full text-sm">
                                        <thead>
                                            <tr className="bg-surface text-secondary">
                                                <th className="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide">Komponen</th>
                                                <th className="px-4 py-3 text-center text-xs font-bold uppercase tracking-wide">Nilai</th>
                                                <th className="px-4 py-3 text-center text-xs font-bold uppercase tracking-wide">Bobot</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-slate-100">
                                            <tr>
                                                <td className="px-4 py-3">Tugas</td>
                                                <td className="px-4 py-3 text-center font-mono">{list[0]?.nilai_tugas ?? '—'}</td>
                                                <td className="px-4 py-3 text-center text-muted-foreground">30%</td>
                                            </tr>
                                            <tr>
                                                <td className="px-4 py-3">UTS</td>
                                                <td className="px-4 py-3 text-center font-mono">{list[0]?.nilai_uts ?? '—'}</td>
                                                <td className="px-4 py-3 text-center text-muted-foreground">30%</td>
                                            </tr>
                                            <tr>
                                                <td className="px-4 py-3">UAS</td>
                                                <td className="px-4 py-3 text-center font-mono">{list[0]?.nilai_uas ?? '—'}</td>
                                                <td className="px-4 py-3 text-center text-muted-foreground">40%</td>
                                            </tr>
                                            <tr className="bg-blue-50">
                                                <td className="px-4 py-3 font-semibold text-navy">Nilai Akhir</td>
                                                <td colSpan={2} className="px-4 py-3 text-center">
                                                    <div className="flex items-center justify-center gap-3">
                                                        <span className="text-2xl font-bold text-navy">
                                                            {list[0]?.nilai_akhir ?? '—'}
                                                        </span>
                                                        {list[0]?.status_lulus === 'Lulus' && (
                                                            <Badge variant="success">
                                                                <CheckCircle className="h-3 w-3 mr-1" />
                                                                Lulus
                                                            </Badge>
                                                        )}
                                                        {list[0]?.status_lulus === 'Tidak Lulus' && (
                                                            <Badge variant="danger">
                                                                <XCircle className="h-3 w-3 mr-1" />
                                                                Tidak Lulus
                                                            </Badge>
                                                        )}
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </Card>
                        );
                    });
                })
            )}
        </Container>
    );
}

SiswaNilai.layout = { title: 'Nilai Saya' };
