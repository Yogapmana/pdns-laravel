import { Form, router } from '@inertiajs/react';
import { Save, Lock, AlertCircle, Info } from 'lucide-react';
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

type Siswa = { nis: string; nama_siswa: string; kelas: string };

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

type Mengajar = { id: number; kelas: string; mata_pelajaran: string };

type Guru = { id: number; nama_guru: string; mengajar: Mengajar[] };

type Props = {
    guru: Guru;
    daftar_kelas: string[];
    mapel_by_kelas: Record<string, string[]>;
    kelas: string | null;
    mata_pelajaran: string | null;
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
    mata_pelajaran,
    siswa,
    nilai_map,
    status_validasi_global,
    has_mengajar,
}: Props) {
    useFlashToast();
    const [selectedKelas, setSelectedKelas] = useState(kelas ?? '');
    const [selectedMapel, setSelectedMapel] = useState(mata_pelajaran ?? '');
    const isFinal = status_validasi_global === 'Final';

    const availableMapel = selectedKelas
        ? (mapel_by_kelas[selectedKelas] ?? [])
        : [];

    function applyFilter() {
        if (!selectedKelas || !selectedMapel) {
            return;
        }

        router.get('/guru/input-nilai', {
            kelas: selectedKelas,
            mata_pelajaran: selectedMapel,
        });
    }

    function changeKelas(newKelas: string) {
        setSelectedKelas(newKelas);
        setSelectedMapel('');
    }

    return (
        <Container>
            <PageHeader
                title="Input Nilai"
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
                                    Kelas
                                </label>
                                <Select
                                    id="kelas"
                                    value={selectedKelas}
                                    onChange={(e) =>
                                        changeKelas(e.target.value)
                                    }
                                >
                                    <option value="">Pilih Kelas...</option>
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
                                    Mata Pelajaran
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
                                            ? 'Pilih Mata Pelajaran...'
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
                                Tampilkan Daftar Siswa
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            )}

            {kelas && mata_pelajaran && siswa.length > 0 && (
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

                    <DataTable>
                        <div className="border-b border-border bg-surface px-6 py-3">
                            <p className="text-sm">
                                <div className="flex flex-wrap items-center gap-2">
                                    <span className="font-semibold text-navy">
                                        {mata_pelajaran}
                                    </span>
                                    <Badge variant="info">Kelas {kelas}</Badge>
                                    <Badge variant="neutral">
                                        {siswa.length} siswa
                                    </Badge>
                                </div>
                            </p>
                        </div>
                        <Form action="/guru/input-nilai/save" method="post">
                            {({ processing }) => (
                                <>
                                    <input
                                        type="hidden"
                                        name="kelas"
                                        value={kelas}
                                    />
                                    <input
                                        type="hidden"
                                        name="mata_pelajaran"
                                        value={mata_pelajaran}
                                    />
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
                                    <div className="flex flex-wrap justify-end gap-2 border-t border-border px-6 py-4">
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
                    </DataTable>

                    {!isFinal && (
                        <Form
                            action="/guru/input-nilai/validate-final"
                            method="post"
                        >
                            <input type="hidden" name="kelas" value={kelas} />
                            <input
                                type="hidden"
                                name="mata_pelajaran"
                                value={mata_pelajaran}
                            />
                            <Card>
                                <CardContent>
                                    <div className="flex items-center justify-between gap-4">
                                        <div className="flex-1">
                                            <p className="text-sm font-semibold text-navy">
                                                Validasi Final
                                            </p>
                                            <p className="mt-1 text-xs text-muted-foreground">
                                                Mengunci semua nilai{' '}
                                                {mata_pelajaran} di kelas{' '}
                                                {kelas} yang masih berstatus
                                                Draft. Nilai Final tidak dapat
                                                diubah.
                                            </p>
                                        </div>
                                        <Button type="submit" variant="success">
                                            <Lock className="h-4 w-4" />
                                            Validasi Final
                                        </Button>
                                    </div>
                                </CardContent>
                            </Card>
                        </Form>
                    )}
                </>
            )}

            {kelas && mata_pelajaran && siswa.length === 0 && (
                <Alert variant="info">Tidak ada siswa di kelas {kelas}.</Alert>
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

    const hasInvalid = isInvalidScore(tugas) || isInvalidScore(uts) || isInvalidScore(uas);

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
