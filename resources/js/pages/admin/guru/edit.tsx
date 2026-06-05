import { Form, Link } from '@inertiajs/react';
import { ArrowLeft, Save, KeyRound, UserPlus, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select } from '@/components/ui/select';
import { InputError, PageHeader, Container } from '@/components/ui/shared';

type Mengajar = {
    id: number;
    kelas: string;
    mata_pelajaran: string;
};

type Guru = {
    id: number;
    nama_guru: string;
    user: { id: number; username: string; is_active: boolean } | null;
    mengajar: Mengajar[];
};

type Props = {
    guru: Guru;
    daftar_kelas: string[];
    daftar_mapel: string[];
};

type MengajarRow = {
    kelas: string;
    mata_pelajaran: string;
};

export default function GuruEdit({ guru, daftar_kelas, daftar_mapel }: Props) {
    const hasAccount = guru.user !== null;

    const initialRows: MengajarRow[] = guru.mengajar.length > 0
        ? guru.mengajar.map((m) => ({ kelas: m.kelas, mata_pelajaran: m.mata_pelajaran }))
        : [{ kelas: '', mata_pelajaran: '' }];

    const [rows, setRows] = useState<MengajarRow[]>(initialRows);

    function addRow() {
        setRows([...rows, { kelas: '', mata_pelajaran: '' }]);
    }

    function removeRow(index: number) {
        setRows(rows.filter((_, i) => i !== index));
    }

    function updateRow(index: number, field: keyof MengajarRow, value: string) {
        setRows(rows.map((r, i) => (i === index ? { ...r, [field]: value } : r)));
    }

    const mengajarSummary = guru.mengajar.length > 0
        ? guru.mengajar.map((m) => `${m.kelas} • ${m.mata_pelajaran}`).join(' | ')
        : 'Belum ada';

    return (
        <Container>
            <div className="flex items-center gap-3 mb-4">
                <Link href="/admin/guru" className="text-muted-foreground hover:text-foreground">
                    <ArrowLeft className="h-4 w-4" />
                </Link>
                <PageHeader
                    title="Edit Guru"
                    description={`${guru.nama_guru}${hasAccount ? ` • @${guru.user!.username}` : ' • Tanpa Akun'}`}
                />
            </div>

            <Card className="max-w-3xl">
                <CardContent>
                    <Form action={`/admin/guru/${guru.id}`} method="put" className="space-y-4">
                        {({ processing, errors }) => (
                            <>
                                <div>
                                    <label htmlFor="nama_guru" className="block text-sm font-medium text-secondary mb-2">
                                        Nama Lengkap <span className="text-danger">*</span>
                                    </label>
                                    <Input id="nama_guru" name="nama_guru" required defaultValue={guru.nama_guru} />
                                    <InputError message={errors.nama_guru} />
                                </div>

                                <div className="pt-4 border-t border-border">
                                    <div className="flex items-center justify-between mb-3">
                                        <div>
                                            <h3 className="text-sm font-bold text-secondary">Mengajar</h3>
                                            <p className="text-xs text-muted-foreground mt-0.5">
                                                Saat ini: <span className="font-medium">{mengajarSummary}</span>
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
                                </div>

                                {hasAccount && (
                                    <div className="rounded-lg border border-blue-200 bg-blue-50 p-3 text-sm text-blue-800 flex items-start gap-2">
                                        <KeyRound className="h-4 w-4 mt-0.5 flex-shrink-0" />
                                        <div>
                                            <p className="font-medium">Guru ini sudah punya akun login.</p>
                                            <p className="text-xs text-blue-700 mt-0.5">
                                                Untuk reset password atau nonaktifkan, buka <Link href="/admin/akun" className="underline">Manajemen Akun</Link>.
                                            </p>
                                        </div>
                                    </div>
                                )}

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

            {!hasAccount && (
                <div className="max-w-3xl mt-4">
                    <Link
                        href={`/admin/guru/${guru.id}/create-account`}
                        className="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-primary text-primary text-sm font-medium hover:bg-primary hover:text-white transition"
                    >
                        <UserPlus className="h-4 w-4" />
                        Buat Akun Login untuk {guru.nama_guru}
                    </Link>
                </div>
            )}
        </Container>
    );
}

GuruEdit.layout = { title: 'Edit Guru' };
