import { Form, Link } from '@inertiajs/react';
import { ArrowLeft, Save, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select } from '@/components/ui/select';
import { InputError, PageHeader, Container } from '@/components/ui/shared';

type Props = {
    daftar_kelas: string[];
    daftar_mapel: string[];
};

type MengajarRow = {
    kelas: string;
    mata_pelajaran: string;
};

export default function GuruCreate({ daftar_kelas, daftar_mapel }: Props) {
    const [rows, setRows] = useState<MengajarRow[]>([
        { kelas: '', mata_pelajaran: '' },
    ]);

    function addRow() {
        setRows([...rows, { kelas: '', mata_pelajaran: '' }]);
    }

    function removeRow(index: number) {
        setRows(rows.filter((_, i) => i !== index));
    }

    function updateRow(index: number, field: keyof MengajarRow, value: string) {
        setRows(rows.map((r, i) => (i === index ? { ...r, [field]: value } : r)));
    }

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
                                                Tentukan kelas dan mata pelajaran yang diajar. Guru hanya bisa menilai siswa di kombinasi ini.
                                            </p>
                                        </div>
                                        <Button type="button" variant="outline" size="sm" onClick={addRow}>
                                            <Plus className="h-4 w-4" />
                                            Tambah Baris
                                        </Button>
                                    </div>

                                    <InputError message={errors.mengajar} />

                                    <div className="space-y-3">
                                        {rows.map((row, i) => (
                                            <div key={i} className="grid grid-cols-12 gap-2 items-start p-3 rounded-lg border border-border bg-surface">
                                                <div className="col-span-12 sm:col-span-5">
                                                    <label className="block text-xs font-medium text-muted-foreground mb-1">
                                                        Kelas <span className="text-danger">*</span>
                                                    </label>
                                                    <Select
                                                        name={`mengajar[${i}][kelas]`}
                                                        value={row.kelas}
                                                        onChange={(e) => updateRow(i, 'kelas', e.target.value)}
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
                                                    >
                                                        <option value="" disabled>Pilih mata pelajaran</option>
                                                        {daftar_mapel.map((m) => (
                                                            <option key={m} value={m}>{m}</option>
                                                        ))}
                                                    </Select>
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
                                        ))}
                                    </div>

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
