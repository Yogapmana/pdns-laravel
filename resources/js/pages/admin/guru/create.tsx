import { Form, Link } from '@inertiajs/react';
import { ArrowLeft, Save, Plus, Trash2, AlertTriangle } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select } from '@/components/ui/select';
import { InputError, PageHeader, Container } from '@/components/ui/shared';

type Props = {
    daftar_kelas: string[];
    mapel_by_kelas: Record<string, string[]>;
};

type MengajarRow = {
    kelas: string;
    mata_pelajaran: string;
};

export default function GuruCreate({ daftar_kelas, mapel_by_kelas }: Props) {
    const [rows, setRows] = useState<MengajarRow[]>([
        { kelas: '', mata_pelajaran: '' },
    ]);

    function addRow() {
        setRows((prev) => [...prev, { kelas: '', mata_pelajaran: '' }]);
    }

    function removeRow(index: number) {
        setRows((prev) => prev.filter((_, i) => i !== index));
    }

    function updateRow(index: number, field: keyof MengajarRow, value: string) {
        setRows((prev) => prev.map((r, i) => (i === index ? { ...r, [field]: value } : r)));
    }

    function mapelForKelas(kelas: string): string[] {
        return mapel_by_kelas[kelas] ?? [];
    }

    const emptyKelas = daftar_kelas.filter((k) => mapelForKelas(k).length === 0);

    return (
        <Container>
            <div className="flex items-center gap-3 mb-4">
                <Link href="/admin/guru" className="text-muted-foreground hover:text-foreground">
                    <ArrowLeft className="h-4 w-4" />
                </Link>
                <PageHeader title="Tambah Guru" description="Daftarkan guru baru beserta kelas & mata pelajaran yang diajar" />
            </div>

            <Card className="max-w-3xl">
                <CardContent>
                    <Form action="/admin/guru" method="post" className="space-y-4">
                        {({ processing, errors }) => (
                            <>
                                <div>
                                    <label htmlFor="nama_guru" className="block text-sm font-medium text-secondary mb-2">
                                        Nama Lengkap <span className="text-danger">*</span>
                                    </label>
                                    <Input id="nama_guru" name="nama_guru" required placeholder="Nama lengkap guru" />
                                    <InputError message={errors.nama_guru} />
                                </div>

                                <div className="pt-4 border-t border-border">
                                    <div className="flex items-center justify-between mb-3">
                                        <div>
                                            <h3 className="text-sm font-bold text-secondary">Mengajar</h3>
                                            <p className="text-xs text-muted-foreground mt-0.5">
                                                Pilih kelas, lalu mata pelajaran akan terfilter otomatis sesuai mapel yang diizinkan untuk kelas tersebut.
                                            </p>
                                        </div>
                                        <Button type="button" variant="outline" size="sm" onClick={addRow}>
                                            <Plus className="h-4 w-4" />
                                            Tambah Baris
                                        </Button>
                                    </div>

                                    <InputError message={errors.mengajar} />

                                    <div className="space-y-3">
                                        {rows.map((row, i) => {
                                            const allowedMapel = mapelForKelas(row.kelas);
                                            const kelasHasMapel = row.kelas !== '' && allowedMapel.length === 0;

                                            return (
                                                <div key={i} className="grid grid-cols-12 gap-2 items-start p-3 rounded-lg border border-border bg-surface">
                                                    <div className="col-span-12 sm:col-span-5">
                                                        <label className="block text-xs font-medium text-muted-foreground mb-1">
                                                            Kelas <span className="text-danger">*</span>
                                                        </label>
                                                        <Select
                                                            name={`mengajar[${i}][kelas]`}
                                                            value={row.kelas}
                                                            onChange={(e) => {
                                                                updateRow(i, 'kelas', e.target.value);

                                                                if (e.target.value !== row.kelas) {
                                                                    updateRow(i, 'mata_pelajaran', '');
                                                                }
                                                            }}
                                                            required
                                                        >
                                                            <option value="" disabled>Pilih kelas</option>
                                                            {daftar_kelas.map((k) => (
                                                                <option key={k} value={k}>{k}</option>
                                                            ))}
                                                        </Select>
                                                        <InputError message={errors[`mengajar.${i}.kelas`]} />
                                                    </div>
                                                    <div className="col-span-10 sm:col-span-6">
                                                        <label className="block text-xs font-medium text-muted-foreground mb-1">
                                                            Mata Pelajaran <span className="text-danger">*</span>
                                                        </label>
                                                        <Select
                                                            name={`mengajar[${i}][mata_pelajaran]`}
                                                            value={row.mata_pelajaran}
                                                            onChange={(e) => updateRow(i, 'mata_pelajaran', e.target.value)}
                                                            required
                                                            disabled={!row.kelas}
                                                        >
                                                            <option value="" disabled>
                                                                {!row.kelas ? 'Pilih kelas dulu' : allowedMapel.length === 0 ? 'Tidak ada mapel diizinkan' : 'Pilih mata pelajaran'}
                                                            </option>
                                                            {allowedMapel.map((m) => (
                                                                <option key={m} value={m}>{m}</option>
                                                            ))}
                                                        </Select>
                                                        {kelasHasMapel && (
                                                            <p className="text-xs text-amber-700 mt-1 flex items-center gap-1">
                                                                <AlertTriangle className="h-3 w-3" />
                                                                Kelas "{row.kelas}" belum punya mapel diizinkan.{' '}
                                                                <Link href="/admin/kelas" className="underline">Atur di Manajemen Kelas</Link>.
                                                            </p>
                                                        )}
                                                        <InputError message={errors[`mengajar.${i}.mata_pelajaran`]} />
                                                    </div>
                                                    <div className="col-span-2 sm:col-span-1 flex items-end justify-end h-full">
                                                        {rows.length > 1 && (
                                                            <button
                                                                type="button"
                                                                onClick={() => removeRow(i)}
                                                                className="p-2 text-danger hover:bg-red-50 rounded transition"
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
                                        <div className="mt-3 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800 flex items-start gap-2">
                                            <AlertTriangle className="h-4 w-4 mt-0.5 flex-shrink-0" />
                                            <div>
                                                <p className="font-medium">Kelas berikut belum punya mata pelajaran yang diizinkan:</p>
                                                <p className="text-xs mt-0.5">
                                                    {emptyKelas.join(', ')}.{' '}
                                                    <Link href="/admin/kelas" className="underline">Atur mata pelajaran di Manajemen Kelas</Link> agar bisa di-assign.
                                                </p>
                                            </div>
                                        </div>
                                    )}

                                    <p className="text-xs text-muted-foreground mt-3">
                                        Belum ada kelas atau mata pelajaran yang sesuai?{' '}
                                        <Link href="/admin/kelas" className="text-primary hover:underline">Kelola kelas</Link>
                                        {' atau '}
                                        <Link href="/admin/mata-pelajaran" className="text-primary hover:underline">kelola mata pelajaran</Link>
                                        .
                                    </p>
                                </div>

                                <div className="flex gap-2 pt-4 border-t border-border">
                                    <Button type="submit" disabled={processing}>
                                        <Save className="h-4 w-4" />
                                        {processing ? 'Menyimpan...' : 'Simpan'}
                                    </Button>
                                    <Link href="/admin/guru">
                                        <Button type="button" variant="outline">Batal</Button>
                                    </Link>
                                </div>
                            </>
                        )}
                    </Form>
                </CardContent>
            </Card>

            <div className="max-w-3xl mt-4">
                <p className="text-xs text-muted-foreground">
                    Akun login untuk guru dapat dibuat terpisah dari halaman&nbsp;
                    <Link href="/admin/akun" className="text-primary hover:underline font-medium">
                        Manajemen Akun
                    </Link>
                    .
                </p>
            </div>
        </Container>
    );
}

GuruCreate.layout = { title: 'Tambah Guru' };
