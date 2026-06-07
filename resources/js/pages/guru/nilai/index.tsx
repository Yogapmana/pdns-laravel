import { Form, router } from '@inertiajs/react';
import {
    Save,
    Lock,
    AlertCircle,
    Info,
    Search,
    Filter,
    FileSpreadsheet,
} from 'lucide-react';
import { useState } from 'react';
import { Alert } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Select } from '@/components/ui/select';
import { Container, DataTable, PageHeader } from '@/components/ui/shared';
import { useFlashToast } from '@/hooks/use-flash-toast';
import { calculateNilaiAkhir, calculateStatusLulus } from '@/lib/utils';
import { cn } from '@/lib/utils';

type Siswa = { nis: string; nama_siswa: string; kelas: { id: number; nama: string } | null };

type NilaiMap = Record<
    string,
    {
        nilai_tugas: number | null;
        nilai_uts: number | null;
        nilai_uas: number | null;
        nilai_akhir: number | null;
        status_lulus: string | null;
        status_validasi: string;
    }
>;

type Mengajar = { id: number; kelas: { id: number; nama: string } | null; mata_pelajaran: { id: number; nama: string } | null };

type Guru = { id: number; nama_guru: string; mengajar: Mengajar[] };

type Props = {
    guru: Guru;
    daftar_kelas: { id: number; nama: string }[];
    mapel_by_kelas: Record<string, { id: number; nama: string }[]>;
    kelas: string | null;
    kelas_id: number | null;
    mata_pelajaran: string | null;
    mata_pelajaran_id: number | null;
    siswa: Siswa[];
    nilai_map: NilaiMap;
    status_validasi_global: string;
    has_mengajar: boolean;
};

export default function NilaiIndex({
    guru,
    daftar_kelas,
    mapel_by_kelas,
    kelas,
    kelas_id,
    mata_pelajaran,
    mata_pelajaran_id,
    siswa,
    nilai_map,
    status_validasi_global,
    has_mengajar,
}: Props) {
    useFlashToast();
    const [selectedKelasId, setSelectedKelasId] = useState(kelas_id ? String(kelas_id) : '');
    const [selectedMapelId, setSelectedMapelId] = useState(mata_pelajaran_id ? String(mata_pelajaran_id) : '');
    const isFinal = status_validasi_global === 'Final';

    const availableMapel = selectedKelasId
        ? (mapel_by_kelas[selectedKelasId] ?? [])
        : [];

    function applyFilter() {
        if (!selectedKelasId || !selectedMapelId) {
            return;
        }

        const kelasNama = daftar_kelas.find((k) => String(k.id) === selectedKelasId)?.nama;
        const mapelNama = availableMapel.find((m) => String(m.id) === selectedMapelId)?.nama;

        router.get('/guru/input-nilai', {
            kelas: kelasNama,
            kelas_id: Number(selectedKelasId),
            mata_pelajaran: mapelNama,
            mata_pelajaran_id: Number(selectedMapelId),
        });
    }

    function changeKelas(newKelasId: string) {
        setSelectedKelasId(newKelasId);
        setSelectedMapelId('');
    }

    function kelasNama(id: string): string {
        return daftar_kelas.find((k) => String(k.id) === id)?.nama ?? '';
    }

    function mapelNama(id: string): string {
        return availableMapel.find((m) => String(m.id) === id)?.nama ?? '';
    }

    return (
        <Container>
            <PageHeader title="Input Nilai" />

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
                            Filter Data Nilai
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
                                    <option value="">Pilih Kelas...</option>
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
                                            ? 'Pilih Mata Pelajaran...'
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
                                        <p className="absolute mt-1 flex items-center gap-1 text-xs text-warning">
                                            <Info className="h-3 w-3" /> Anda
                                            tidak mengajar mapel di kelas ini.
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
                        filter di atas untuk mulai menginput nilai.
                    </p>
                </div>
            )}

            {kelas_id && mata_pelajaran_id && siswa.length > 0 && (
                <>
                    {isFinal && (
                        <Alert variant="warning">
                            Nilai berstatus{' '}
                            <Badge variant="info" className="mx-1">
                                Final
                            </Badge>{' '}
                            dan terkunci. Hubungi admin untuk membuka.
                        </Alert>
                    )}

                    {!isFinal &&
                        nilai_map &&
                        Object.values(nilai_map).some(
                            (n) => n?.status_validasi === 'Final',
                        ) && (
                            <Alert variant="info">
                                Beberapa nilai terkunci. Lengkapi yang tersisa
                                dan klik <strong>Validasi Final</strong>.
                            </Alert>
                        )}

                    <Form action="/guru/input-nilai/save" method="post">
                        {({ processing }) => (
                            <>
                                <input
                                    type="hidden"
                                    name="kelas_id"
                                    value={kelas_id ?? ''}
                                />
                                <input
                                    type="hidden"
                                    name="mata_pelajaran_id"
                                    value={mata_pelajaran_id ?? ''}
                                />
                                <DataTable>
                                    <div className="overflow-x-auto">
                                        <table className="w-full min-w-[800px] table-fixed text-sm">
                                            <colgroup>
                                                <col className="w-24" />
                                                <col />
                                                <col className="w-32" />
                                                <col className="w-32" />
                                                <col className="w-32" />
                                                <col className="w-24" />
                                                <col className="w-28" />
                                            </colgroup>
                                            <thead>
                                                <tr className="bg-primary text-white">
                                                    <th className="px-3 py-3 text-left text-xs font-bold tracking-wide uppercase">
                                                        NIS
                                                    </th>
                                                    <th className="px-3 py-3 text-left text-xs font-bold tracking-wide uppercase">
                                                        Nama
                                                    </th>
                                                    <th className="px-2 py-3 text-center text-xs font-bold tracking-wide uppercase">
                                                        Tugas
                                                        <br />
                                                        <span className="text-[10px] font-normal opacity-80">
                                                            (30%)
                                                        </span>
                                                    </th>
                                                    <th className="px-2 py-3 text-center text-xs font-bold tracking-wide uppercase">
                                                        UTS
                                                        <br />
                                                        <span className="text-[10px] font-normal opacity-80">
                                                            (30%)
                                                        </span>
                                                    </th>
                                                    <th className="px-2 py-3 text-center text-xs font-bold tracking-wide uppercase">
                                                        UAS
                                                        <br />
                                                        <span className="text-[10px] font-normal opacity-80">
                                                            (40%)
                                                        </span>
                                                    </th>
                                                    <th className="px-3 py-3 text-center text-xs font-bold tracking-wide uppercase">
                                                        Akhir
                                                    </th>
                                                    <th className="px-3 py-3 text-center text-xs font-bold tracking-wide uppercase">
                                                        Status
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody className="divide-y divide-slate-100">
                                                {siswa.map((s) => {
                                                    const rowStatus =
                                                        nilai_map[s.nis]
                                                            ?.status_validasi;
                                                    const rowIsFinal =
                                                        rowStatus === 'Final';

                                                    return (
                                                        <NilaiRow
                                                            key={s.nis}
                                                            siswa={s}
                                                            initial={
                                                                nilai_map[s.nis]
                                                            }
                                                            disabled={isFinal}
                                                            rowLocked={
                                                                rowIsFinal
                                                            }
                                                        />
                                                    );
                                                })}
                                            </tbody>
                                        </table>
                                    </div>
                                </DataTable>

                                <div className="mt-4 flex justify-end">
                                    <Button
                                        type="submit"
                                        disabled={processing || isFinal}
                                    >
                                        <Save className="h-4 w-4" />
                                        {processing
                                            ? 'Menyimpan...'
                                            : 'Simpan sebagai Draft'}
                                    </Button>
                                </div>
                            </>
                        )}
                    </Form>

                    {!isFinal && (
                        <Form
                            action="/guru/input-nilai/validate-final"
                            method="post"
                        >
                            <input type="hidden" name="kelas_id" value={kelas_id ?? ''} />
                            <input
                                type="hidden"
                                name="mata_pelajaran_id"
                                value={mata_pelajaran_id ?? ''}
                            />
                            <Card className="mt-6 border-emerald-200 bg-emerald-50/50">
                                <CardContent className="p-4 sm:px-5">
                                    <div className="flex flex-col items-center justify-between gap-4 sm:flex-row">
                                        <div className="flex-1 text-center sm:text-left">
                                            <h3 className="flex items-center justify-center gap-2 text-base font-bold text-emerald-800 sm:justify-start">
                                                <Lock className="h-4 w-4" />
                                                Validasi Final
                                            </h3>
                                            <p className="mt-0.5 text-xs text-emerald-600">
                                                Mengunci semua nilai{' '}
                                                {mapelNama(selectedMapelId)} di kelas{' '}
                                                {kelasNama(selectedKelasId)} yang masih berstatus
                                                Draft.{' '}
                                                <strong className="font-semibold">
                                                    Nilai Final tidak dapat
                                                    diubah kembali.
                                                </strong>
                                            </p>
                                        </div>
                                        <Button
                                            type="submit"
                                            variant="success"
                                            className="h-11 w-full shrink-0 px-5 text-xs sm:w-auto"
                                        >
                                            <Lock className="mr-1.5 h-3.5 w-3.5" />
                                            Validasi Semua Nilai
                                        </Button>
                                    </div>
                                </CardContent>
                            </Card>
                        </Form>
                    )}
                </>
            )}

            {kelas_id && mata_pelajaran_id && siswa.length === 0 && (
                <Alert variant="info">Tidak ada siswa di kelas {kelasNama(selectedKelasId)}.</Alert>
            )}
        </Container>
    );
}

type NilaiInputProps = {
    name: string;
    nis: string;
    value: number | null;
    onChange: (v: number | null) => void;
    disabled?: boolean;
};

function isInvalidScore(n: number | null): boolean {
    return n !== null && (n < 0 || n > 100);
}

function NilaiInput({ name, nis, value, onChange, disabled }: NilaiInputProps) {
    const invalid = isInvalidScore(value);

    return (
        <td className="px-2 py-2 text-center align-middle">
            <input type="hidden" name={`nilai[${nis}][nis]`} value={nis} />
            <input
                type="number"
                min={0}
                max={100}
                step="0.01"
                name={name}
                value={value ?? ''}
                onChange={(e) =>
                    onChange(
                        e.target.value === ''
                            ? null
                            : parseFloat(e.target.value),
                    )
                }
                disabled={disabled}
                className={cn(
                    'w-24 rounded-md border px-2 py-1.5 text-center font-mono text-sm focus:ring-2 focus:outline-none disabled:cursor-not-allowed disabled:bg-surface',
                    invalid
                        ? 'border-danger focus:ring-danger'
                        : 'border-border focus:ring-primary',
                )}
                placeholder="0-100"
            />
        </td>
    );
}

function NilaiRow({
    siswa,
    initial,
    disabled,
    rowLocked,
}: {
    siswa: Siswa;
    initial?: {
        nilai_tugas: number | null;
        nilai_uts: number | null;
        nilai_uas: number | null;
        nilai_akhir: number | null;
        status_lulus: string | null;
        status_validasi: string;
    };
    disabled: boolean;
    rowLocked?: boolean;
}) {
    const [tugas, setTugas] = useState<number | null>(
        initial?.nilai_tugas ?? null,
    );
    const [uts, setUts] = useState<number | null>(initial?.nilai_uts ?? null);
    const [uas, setUas] = useState<number | null>(initial?.nilai_uas ?? null);

    const akhir = calculateNilaiAkhir(tugas, uts, uas);
    const status = calculateStatusLulus(akhir);

    const hasInvalid =
        isInvalidScore(tugas) || isInvalidScore(uts) || isInvalidScore(uas);

    const inputDisabled = disabled || (rowLocked ?? false);

    return (
        <tr
            className={cn(
                'hover:bg-blue-50/50',
                inputDisabled && initial && rowLocked ? 'bg-surface' : '',
            )}
        >
            <td className="px-3 py-2 font-mono text-xs whitespace-nowrap">
                {siswa.nis}
            </td>
            <td className="px-3 py-2 font-medium whitespace-nowrap">
                <div className="flex items-center gap-2">
                    {siswa.nama_siswa}
                    {rowLocked && (
                        <Badge variant="success" className="!text-[10px]">
                            Final
                        </Badge>
                    )}
                </div>
            </td>
            <NilaiInput
                name={`nilai[${siswa.nis}][nilai_tugas]`}
                nis={siswa.nis}
                value={tugas}
                onChange={setTugas}
                disabled={inputDisabled}
            />
            <NilaiInput
                name={`nilai[${siswa.nis}][nilai_uts]`}
                nis={siswa.nis}
                value={uts}
                onChange={setUts}
                disabled={inputDisabled}
            />
            <NilaiInput
                name={`nilai[${siswa.nis}][nilai_uas]`}
                nis={siswa.nis}
                value={uas}
                onChange={setUas}
                disabled={inputDisabled}
            />
            <td className="px-3 py-2 text-center font-mono font-semibold whitespace-nowrap text-navy">
                {hasInvalid ? (
                    <span className="inline-flex items-center gap-1 text-danger">
                        <AlertCircle className="h-3 w-3" /> —
                    </span>
                ) : akhir !== null ? (
                    akhir.toFixed(2)
                ) : (
                    '—'
                )}
            </td>
            <td className="px-3 py-2 text-center">
                {status === null ? (
                    <Badge variant="neutral">—</Badge>
                ) : status === 'Lulus' ? (
                    <Badge variant="success">Lulus</Badge>
                ) : (
                    <Badge variant="danger">Tidak Lulus</Badge>
                )}
            </td>
        </tr>
    );
}

NilaiIndex.layout = { title: 'Input Nilai' };
