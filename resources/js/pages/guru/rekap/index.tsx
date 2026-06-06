import { router } from '@inertiajs/react';
import {
    CheckCircle,
    XCircle,
    AlertCircle,
    Info,
    BookOpen,
} from 'lucide-react';
import { useState } from 'react';
import { Alert } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Select } from '@/components/ui/select';
import {
    Container,
    DataTable,
    PageHeader,
    StatCard,
    TableEmpty,
} from '@/components/ui/shared';
import { useFlashToast } from '@/hooks/use-flash-toast';

type Mengajar = { id: number; kelas: string; mata_pelajaran: string };

type Guru = { id: number; nama_guru: string; mengajar: Mengajar[] };

type Siswa = { nis: string; nama_siswa: string; kelas: string };

type Nilai = {
    id: number;
    nilai_tugas: number | null;
    nilai_uts: number | null;
    nilai_uas: number | null;
    nilai_akhir: number | null;
    status_lulus: string | null;
    status_validasi: string;
};

type Row = { siswa: Siswa; nilai: Nilai | null };

type Props = {
    guru: Guru;
    kelas: string | null;
    mata_pelajaran: string | null;
    daftar_kelas: string[];
    mapel_by_kelas: Record<string, string[]>;
    rows: Row[];
    stats: { lulus: number; tidak_lulus: number; belum: number };
    has_mengajar: boolean;
};

export default function RekapIndex({
    guru,
    kelas,
    mata_pelajaran,
    daftar_kelas,
    mapel_by_kelas,
    rows,
    stats,
    has_mengajar,
}: Props) {
    useFlashToast();
    const [selectedKelas, setSelectedKelas] = useState(kelas ?? '');
    const [selectedMapel, setSelectedMapel] = useState(mata_pelajaran ?? '');

    const availableMapel = selectedKelas
        ? (mapel_by_kelas[selectedKelas] ?? [])
        : [];

    function changeKelas(newKelas: string) {
        setSelectedKelas(newKelas);
        setSelectedMapel('');
    }

    function applyFilter() {
        if (!selectedKelas || !selectedMapel) {
            return;
        }

        router.get('/guru/rekap', {
            kelas: selectedKelas,
            mata_pelajaran: selectedMapel,
        });
    }

    return (
        <Container>
            <PageHeader
                title="Rekap Nilai"
                description={
                    <div className="flex items-center gap-2">
                        <span>{guru.nama_guru}</span>
                    </div>
                }
            />

            {!has_mengajar && (
                <Alert variant="warning">
                    Belum ada jadwal mengajar. Hubungi admin.
                </Alert>
            )}

            {has_mengajar && (
                <Card>
                    <CardContent>
                        <div className="grid grid-cols-1 items-end gap-3 md:grid-cols-3">
                            <div>
                                <label
                                    htmlFor="kelas"
                                    className="mb-2 block text-sm font-medium text-secondary"
                                >
                                    Pilih Kelas
                                </label>
                                <Select
                                    id="kelas"
                                    value={selectedKelas}
                                    onChange={(e) =>
                                        changeKelas(e.target.value)
                                    }
                                >
                                    <option value="">Pilih kelas...</option>
                                    {daftar_kelas.map((k) => (
                                        <option key={k} value={k}>
                                            {k}
                                        </option>
                                    ))}
                                </Select>
                            </div>
                            <div>
                                <label
                                    htmlFor="mapel"
                                    className="mb-2 block text-sm font-medium text-secondary"
                                >
                                    Pilih Mata Pelajaran
                                </label>
                                <Select
                                    id="mapel"
                                    value={selectedMapel}
                                    onChange={(e) =>
                                        setSelectedMapel(e.target.value)
                                    }
                                    disabled={!selectedKelas}
                                >
                                    <option value="">
                                        {selectedKelas
                                            ? 'Pilih mata pelajaran...'
                                            : 'Pilih kelas dulu'}
                                    </option>
                                    {availableMapel.map((m) => (
                                        <option key={m} value={m}>
                                            {m}
                                        </option>
                                    ))}
                                </Select>
                                {selectedKelas &&
                                    availableMapel.length === 0 && (
                                        <p className="mt-1 flex items-center gap-1 text-xs text-warning">
                                            <Info className="h-3 w-3" /> Anda
                                            tidak mengajar mata pelajaran apapun
                                            di kelas ini.
                                        </p>
                                    )}
                            </div>
                            <Button
                                onClick={applyFilter}
                                disabled={!selectedKelas || !selectedMapel}
                            >
                                Tampilkan Rekap
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            )}

            {kelas && mata_pelajaran && (
                <>
                    <div className="flex items-center gap-2 rounded-lg border border-border bg-white px-4 py-3">
                        <BookOpen className="h-4 w-4 text-primary" />
                        <p className="text-sm">
                            <span className="font-semibold text-navy">
                                {mata_pelajaran}
                            </span>{' '}
                            di kelas{' '}
                            <span className="font-semibold">{kelas}</span>
                        </p>
                    </div>

                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <StatCard
                            label="Lulus"
                            value={stats.lulus}
                            icon={<CheckCircle />}
                            color="success"
                            variant="colored"
                        />
                        <StatCard
                            label="Tidak Lulus"
                            value={stats.tidak_lulus}
                            icon={<XCircle />}
                            color="danger"
                            variant="colored"
                        />
                        <StatCard
                            label="Belum Dinilai"
                            value={stats.belum}
                            icon={<AlertCircle />}
                            color="warning"
                            variant="colored"
                        />
                    </div>

                    <DataTable>
                        <div className="overflow-x-auto">
                            <table className="w-full min-w-[800px] text-sm">
                                <thead>
                                    <tr className="bg-primary text-white">
                                        <th className="px-3 py-3 text-left text-xs font-bold tracking-wide uppercase">
                                            NIS
                                        </th>
                                        <th className="px-3 py-3 text-left text-xs font-bold tracking-wide uppercase">
                                            Nama Siswa
                                        </th>
                                        <th className="px-3 py-3 text-center text-xs font-bold tracking-wide uppercase">
                                            Tugas
                                        </th>
                                        <th className="px-3 py-3 text-center text-xs font-bold tracking-wide uppercase">
                                            UTS
                                        </th>
                                        <th className="px-3 py-3 text-center text-xs font-bold tracking-wide uppercase">
                                            UAS
                                        </th>
                                        <th className="px-3 py-3 text-center text-xs font-bold tracking-wide uppercase">
                                            Akhir
                                        </th>
                                        <th className="px-3 py-3 text-center text-xs font-bold tracking-wide uppercase">
                                            Status
                                        </th>
                                        <th className="px-3 py-3 text-center text-xs font-bold tracking-wide uppercase">
                                            Validasi
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {rows.length === 0 ? (
                                        <TableEmpty
                                            message="Tidak ada siswa di kelas ini."
                                            colSpan={8}
                                        />
                                    ) : (
                                        rows.map((r, i) => (
                                            <tr
                                                key={r.siswa.nis}
                                                className={`${i % 2 === 0 ? 'bg-white' : 'bg-surface'} hover:bg-blue-50`}
                                            >
                                                <td className="px-3 py-3 font-mono text-xs">
                                                    {r.siswa.nis}
                                                </td>
                                                <td className="px-3 py-3 font-medium">
                                                    {r.siswa.nama_siswa}
                                                </td>
                                                <td className="px-3 py-3 text-center">
                                                    {r.nilai?.nilai_tugas ??
                                                        '—'}
                                                </td>
                                                <td className="px-3 py-3 text-center">
                                                    {r.nilai?.nilai_uts ?? '—'}
                                                </td>
                                                <td className="px-3 py-3 text-center">
                                                    {r.nilai?.nilai_uas ?? '—'}
                                                </td>
                                                <td className="px-3 py-3 text-center font-semibold text-navy">
                                                    {r.nilai?.nilai_akhir !==
                                                        null &&
                                                    r.nilai?.nilai_akhir !==
                                                        undefined
                                                        ? r.nilai.nilai_akhir
                                                        : '—'}
                                                </td>
                                                <td className="px-3 py-3 text-center">
                                                    {!r.nilai ? (
                                                        <Badge variant="neutral">
                                                            Belum
                                                        </Badge>
                                                    ) : r.nilai.status_lulus ===
                                                      'Lulus' ? (
                                                        <Badge variant="success">
                                                            Lulus
                                                        </Badge>
                                                    ) : (
                                                        <Badge variant="danger">
                                                            Tidak Lulus
                                                        </Badge>
                                                    )}
                                                </td>
                                                <td className="px-3 py-3 text-center">
                                                    {!r.nilai ? (
                                                        <Badge variant="neutral">
                                                            —
                                                        </Badge>
                                                    ) : r.nilai
                                                          .status_validasi ===
                                                      'Final' ? (
                                                        <Badge variant="info">
                                                            Final
                                                        </Badge>
                                                    ) : (
                                                        <Badge variant="warning">
                                                            Draft
                                                        </Badge>
                                                    )}
                                                </td>
                                            </tr>
                                        ))
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </DataTable>
                </>
            )}

            {!kelas && !mata_pelajaran && has_mengajar && (
                <Card>
                    <CardContent className="py-12 text-center text-muted-foreground">
                        Pilih kelas dan mata pelajaran terlebih dahulu untuk
                        melihat rekap.
                    </CardContent>
                </Card>
            )}
        </Container>
    );
}

RekapIndex.layout = { title: 'Rekap Nilai' };
