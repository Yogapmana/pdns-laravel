import { Form, Link } from '@inertiajs/react';
import {
    ArrowLeft,
    Save,
    Plus,
    Trash2,
    AlertTriangle,
    Info,
} from 'lucide-react';
import { useState } from 'react';
import { Alert } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select } from '@/components/ui/select';
import { InputError, PageHeader, Container } from '@/components/ui/shared';

type Props = {
    daftar_kelas: { id: number; nama: string }[];
    daftar_mapel: { id: number; nama: string }[];
    mapel_by_kelas: Record<string, { id: number; nama: string }[]>;
};

type MengajarRow = {
    kelas_id: string;
    mata_pelajaran_id: string;
};

export default function GuruCreate({ daftar_kelas, daftar_mapel, mapel_by_kelas }: Props) {
    const [rows, setRows] = useState<MengajarRow[]>([
        { kelas_id: '', mata_pelajaran_id: '' },
    ]);

    function addRow() {
        setRows((prev) => [...prev, { kelas_id: '', mata_pelajaran_id: '' }]);
    }

    function removeRow(index: number) {
        setRows((prev) => prev.filter((_, i) => i !== index));
    }

    function updateRow(index: number, field: keyof MengajarRow, value: string) {
        setRows((prev) =>
            prev.map((r, i) => (i === index ? { ...r, [field]: value } : r)),
        );
    }

    function mapelForKelas(kelasId: string): { id: number; nama: string }[] {
        return mapel_by_kelas[kelasId] ?? [];
    }

    function kelasNama(kelasId: string): string {
        return daftar_kelas.find((k) => String(k.id) === kelasId)?.nama ?? '';
    }

    const emptyKelas = daftar_kelas.filter(
        (k) => mapelForKelas(String(k.id)).length === 0,
    );

    return (
        <Container>
            <div className="mb-4 flex items-center gap-3">
                <Link
                    href="/admin/guru"
                    className="text-muted-foreground hover:text-foreground"
                >
                    <ArrowLeft className="h-4 w-4" />
                </Link>
                <PageHeader
                    title="Tambah Guru"
                    description="Daftarkan guru baru beserta kelas & mata pelajaran yang diajar"
                />
            </div>

            <Card className="max-w-3xl">
                <CardContent>
                    <Form
                        action="/admin/guru"
                        method="post"
                        className="space-y-4"
                    >
                        {({ processing, errors }) => (
                            <>
                                <div>
                                    <Label htmlFor="nama_guru">
                                        Nama Lengkap <span className="text-danger">*</span>
                                    </Label>
                                    <Input
                                        id="nama_guru"
                                        name="nama_guru"
                                        required
                                        placeholder="Nama lengkap guru"
                                    />
                                    <InputError message={errors.nama_guru} />
                                </div>

                                <div className="space-y-3 rounded-lg border border-border bg-surface p-3">
                                    <div>
                                        <Label htmlFor="password">
                                            Password <span className="text-danger">*</span>
                                        </Label>
                                        <Input
                                            id="password"
                                            name="password"
                                            type="password"
                                            required
                                            placeholder="Minimal 6 karakter"
                                            autoComplete="new-password"
                                        />
                                        <InputError message={errors.password} />
                                    </div>
                                    <div>
                                        <Label htmlFor="password_confirmation">
                                            Konfirmasi Password <span className="text-danger">*</span>
                                        </Label>
                                        <Input
                                            id="password_confirmation"
                                            name="password_confirmation"
                                            type="password"
                                            required
                                            placeholder="Ulangi password"
                                            autoComplete="new-password"
                                        />
                                        <InputError
                                            message={
                                                errors.password_confirmation
                                            }
                                        />
                                    </div>
                                </div>

                                <div className="border-t border-border pt-4">
                                    <div className="mb-3 flex items-center justify-between">
                                        <div>
                                            <h3 className="text-sm font-bold text-secondary">
                                                Mengajar
                                            </h3>
                                        </div>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            onClick={addRow}
                                        >
                                            <Plus className="h-4 w-4" />
                                            Tambah Baris
                                        </Button>
                                    </div>

                                    <InputError message={errors.mengajar} />

                                    <div className="space-y-3">
                                        {rows.map((row, i) => {
                                            const allowedMapel = mapelForKelas(
                                                row.kelas_id,
                                            );
                                            const kelasHasMapel =
                                                row.kelas_id !== '' &&
                                                allowedMapel.length === 0;

                                            return (
                                                <div
                                                    key={i}
                                                    className="grid grid-cols-12 items-start gap-2 rounded-lg border border-border bg-surface p-3"
                                                >
                                                    <div className="col-span-12 sm:col-span-5">
                                                        <label className="mb-1 block text-xs font-medium text-muted-foreground">
                                                            Kelas{' '}
                                                            <span className="text-danger">
                                                                *
                                                            </span>
                                                        </label>
                                                        <Select
                                                            name={`mengajar[${i}][kelas_id]`}
                                                            value={row.kelas_id}
                                                            onChange={(e) => {
                                                                updateRow(
                                                                    i,
                                                                    'kelas_id',
                                                                    e.target
                                                                        .value,
                                                                );

                                                                if (
                                                                    e.target
                                                                        .value !==
                                                                    row.kelas_id
                                                                ) {
                                                                    updateRow(
                                                                        i,
                                                                        'mata_pelajaran_id',
                                                                        '',
                                                                    );
                                                                }
                                                            }}
                                                            required
                                                        >
                                                            <option
                                                                value=""
                                                                disabled
                                                            >
                                                                Pilih kelas
                                                            </option>
                                                            {daftar_kelas.map(
                                                                (k) => (
                                                                    <option
                                                                        key={k.id}
                                                                        value={
                                                                            k.id
                                                                        }
                                                                    >
                                                                        {k.nama}
                                                                    </option>
                                                                ),
                                                            )}
                                                        </Select>
                                                        <InputError
                                                            message={
                                                                errors[
                                                                    `mengajar.${i}.kelas_id`
                                                                ]
                                                            }
                                                        />
                                                    </div>
                                                    <div className="col-span-10 sm:col-span-6">
                                                        <label className="mb-1 block text-xs font-medium text-muted-foreground">
                                                            Mata Pelajaran{' '}
                                                            <span className="text-danger">
                                                                *
                                                            </span>
                                                        </label>
                                                        <Select
                                                            name={`mengajar[${i}][mata_pelajaran_id]`}
                                                            value={
                                                                row.mata_pelajaran_id
                                                            }
                                                            onChange={(e) =>
                                                                updateRow(
                                                                    i,
                                                                    'mata_pelajaran_id',
                                                                    e.target
                                                                        .value,
                                                                )
                                                            }
                                                            required
                                                            disabled={
                                                                !row.kelas_id
                                                            }
                                                        >
                                                            <option
                                                                value=""
                                                                disabled
                                                            >
                                                                {!row.kelas_id
                                                                    ? 'Pilih kelas dulu'
                                                                    : allowedMapel.length ===
                                                                        0
                                                                      ? 'Tidak ada mapel diizinkan'
                                                                      : 'Pilih mata pelajaran'}
                                                            </option>
                                                            {allowedMapel.map(
                                                                (m) => (
                                                                    <option
                                                                        key={m.id}
                                                                        value={m.id}
                                                                    >
                                                                        {m.nama}
                                                                    </option>
                                                                ),
                                                            )}
                                                        </Select>
                                                        {kelasHasMapel && (
                                                            <p className="mt-1 flex items-center gap-1 text-xs text-amber-700">
                                                                <AlertTriangle className="h-3 w-3" />
                                                                Kelas "
                                                                {kelasNama(row.kelas_id)}"
                                                                belum punya
                                                                mapel diizinkan.{' '}
                                                                <Link
                                                                    href="/admin/kelas"
                                                                    className="underline"
                                                                >
                                                                    Atur di
                                                                    Manajemen
                                                                    Kelas
                                                                </Link>
                                                                .
                                                            </p>
                                                        )}
                                                        <InputError
                                                            message={
                                                                errors[
                                                                    `mengajar.${i}.mata_pelajaran_id`
                                                                ]
                                                            }
                                                        />
                                                    </div>
                                                    <div className="col-span-2 flex h-full items-end justify-end sm:col-span-1">
                                                        {rows.length > 1 && (
                                                            <button
                                                                type="button"
                                                                onClick={() =>
                                                                    removeRow(i)
                                                                }
                                                                className="rounded p-2 text-danger transition hover:bg-red-50"
                                                                aria-label="Hapus baris"
                                                            >
                                                                <Trash2 className="h-4 w-4" />
                                                            </button>
                                                        )}
                                                    </div>
                                                </div>
                                            );
                                        })}
                                    </div>

                                    {emptyKelas.length > 0 && (
                                        <Alert variant="warning" className="mt-3">
                                            <p className="font-medium">
                                                Kelas berikut belum punya mata
                                                pelajaran yang diizinkan:
                                            </p>
                                            <p className="mt-1">
                                                {emptyKelas.map((k) => k.nama).join(', ')}.{' '}
                                                <Link
                                                    href="/admin/kelas"
                                                    className="underline font-medium"
                                                >
                                                    Atur mata pelajaran di
                                                    Manajemen Kelas
                                                </Link>{' '}
                                                agar bisa di-assign.
                                            </p>
                                        </Alert>
                                    )}

                                    <p className="mt-3 text-xs text-muted-foreground">
                                        Belum ada kelas atau mata pelajaran yang
                                        sesuai?{' '}
                                        <Link
                                            href="/admin/kelas"
                                            className="text-primary hover:underline"
                                        >
                                            Kelola kelas
                                        </Link>
                                        {' atau '}
                                        <Link
                                            href="/admin/mata-pelajaran"
                                            className="text-primary hover:underline"
                                        >
                                            kelola mata pelajaran
                                        </Link>
                                        .
                                    </p>
                                </div>

                                <div className="flex gap-2 border-t border-border pt-4">
                                    <Button type="submit" disabled={processing}>
                                        <Save className="h-4 w-4" />
                                        {processing ? 'Menyimpan...' : 'Simpan'}
                                    </Button>
                                    <Link href="/admin/guru">
                                        <Button type="button" variant="outline">
                                            Batal
                                        </Button>
                                    </Link>
                                </div>
                            </>
                        )}
                    </Form>
                </CardContent>
            </Card>
        </Container>
    );
}

GuruCreate.layout = { title: 'Tambah Guru' };
