import { router } from '@inertiajs/react';
import {
    CheckCircle,
    XCircle,
    AlertCircle,
    Info,
    BookOpen,
    Filter,
    Search,
    FileSpreadsheet,
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

type Mengajar = { id: number; kelas: { id: number; nama: string } | null; mata_pelajaran: { id: number; nama: string } | null };

type Guru = { id: number; nama_guru: string; mengajar: Mengajar[] };

type Siswa = { nis: string; nama_siswa: string; kelas: { id: number; nama: string } | null };

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
    kelas_id: number | null;
    mata_pelajaran: string | null;
    mata_pelajaran_id: number | null;
    daftar_kelas: { id: number; nama: string }[];
    mapel_by_kelas: Record<string, { id: number; nama: string }[]>;
    rows: Row[];
    stats: { lulus: number; tidak_lulus: number; belum: number };
    has_mengajar: boolean;
};

export default function RekapIndex({
    guru,
    kelas,
    kelas_id,
    mata_pelajaran,
    mata_pelajaran_id,
    daftar_kelas,
    mapel_by_kelas,
    rows,
    stats,
    has_mengajar,
}: Props) {
    useFlashToast();
    const [selectedKelasId, setSelectedKelasId] = useState(kelas_id ? String(kelas_id) : '');
    const [selectedMapelId, setSelectedMapelId] = useState(mata_pelajaran_id ? String(mata_pelajaran_id) : '');

    const availableMapel = selectedKelasId
        ? (mapel_by_kelas[selectedKelasId] ?? [])
        : [];

    function changeKelas(newKelasId: string) {
        setSelectedKelasId(newKelasId);
        setSelectedMapelId('');
    }

    function applyFilter() {
        if (!selectedKelasId || !selectedMapelId) {
            return;
        }

        const kelasNama = daftar_kelas.find((k) => String(k.id) === selectedKelasId)?.nama;
        const mapelNama = availableMapel.find((m) => String(m.id) === selectedMapelId)?.nama;

        router.get('/guru/rekap', {
            kelas: kelasNama,
            kelas_id: Number(selectedKelasId),
            mata_pelajaran: mapelNama,
            mata_pelajaran_id: Number(selectedMapelId),
        });
    }

    return (
        <Container>
            <PageHeader title="Rekap Nilai" />

            {!has_mengajar && (
                <Alert variant="warning">
                    Belum ada jadwal mengajar. Hubungi admin.
                </Alert>
            )}

            {has_mengajar && (
                <div className="mb-6 overflow-hidden rounded-xl border border-border bg-white shadow-sm">
                    <div className="flex items-center gap-2 border-b border-border bg-slate-50/50 px-5 py-3">
                        <Filter className="h-4 w-4 text-muted-foreground" />
                        <h3 className="text-sm font-semibold text-secondary">
                            Filter Rekap Nilai
                        </h3>
                    </div>
                    <div className="p-5">
                        <div className="flex flex-col items-start gap-4 sm:flex-row sm:items-end">
                            <div className="w-full sm:flex-1">
                                <label
                                    htmlFor="kelas"
                                    className="mb-1.5 block text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                                >
                                    Kelas
                                </label>
                                <Select
                                    id="kelas"
                                    value={selectedKelasId}
                                    onChange={(e) =>
                                        changeKelas(e.target.value)
                                    }
                                    className="h-10"
                                >
                                    <option value="">Pilih kelas...</option>
                                    {daftar_kelas.map((k) => (
                                        <option key={k.id} value={k.id}>
                                            {k.nama}
                                        </option>
                                    ))}
                                </Select>
                            </div>
                            <div className="w-full sm:flex-1">
                                <label
                                    htmlFor="mapel"
                                    className="mb-1.5 block text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                                >
                                    Mata Pelajaran
                                </label>
                                <Select
                                    id="mapel"
                                    value={selectedMapelId}
                                    onChange={(e) =>
                                        setSelectedMapelId(e.target.value)
                                    }
                                    disabled={!selectedKelasId}
                                    className="h-10"
                                >
                                    <option value="">
                                        {selectedKelasId
                                            ? 'Pilih mata pelajaran...'
                                            : 'Pilih kelas dulu'}
                                    </option>
                                    {availableMapel.map((m) => (
                                        <option key={m.id} value={m.id}>
                                            {m.nama}
                                        </option>
                                    ))}
                                </Select>
                                {selectedKelasId &&
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
                                disabled={!selectedKelasId || !selectedMapelId}
                                className="h-10 w-full shrink-0 px-6 sm:w-auto"
                            >
                                <Search className="mr-2 h-4 w-4" />
                                Tampilkan
                            </Button>
                        </div>
                    </div>
                </div>
            )}

            {has_mengajar && (!kelas_id || !mata_pelajaran_id) && (
                <div className="mt-8 flex flex-col items-center justify-center rounded-xl border border-dashed border-border bg-slate-50/50 py-16 text-center">
                    <div className="rounded-full bg-slate-100 p-4">
                        <FileSpreadsheet className="h-8 w-8 text-slate-400" />
                    </div>
                    <h3 className="mt-4 text-lg font-semibold text-secondary">
                        Belum Ada Data yang Ditampilkan
                    </h3>
                    <p className="mx-auto mt-2 max-w-sm text-sm text-muted-foreground">
                        Silakan pilih <strong>Kelas</strong> dan{' '}
                        <strong>Mata Pelajaran</strong> terlebih dahulu pada
                        filter di atas untuk melihat rekap nilai.
                    </p>
                </div>
            )}

            {kelas_id && mata_pelajaran_id && (
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
                                    <tr className="bg-navy text-white">
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
        </Container>
    );
}

RekapIndex.layout = { title: 'Rekap Nilai' };
