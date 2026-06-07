import { Form, Link } from '@inertiajs/react';
import { ArrowLeft, Save, BookOpen, CheckSquare, Square } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { InputError, PageHeader, Container } from '@/components/ui/shared';

type Props = {
    semua_mapel: string[];
};

export default function KelasCreate({ semua_mapel }: Props) {
    const [selected, setSelected] = useState<string[]>([]);

    function toggle(nama: string) {
        setSelected((prev) => (prev.includes(nama) ? prev.filter((n) => n !== nama) : [...prev, nama]));
    }

    function selectAll() {
        setSelected([...semua_mapel]);
    }

    function clearAll() {
        setSelected([]);
    }

    return (
        <Container>
            <div className="flex items-center gap-3 mb-4">
                <Link href="/admin/kelas" className="text-muted-foreground hover:text-foreground">
                    <ArrowLeft className="h-4 w-4" />
                </Link>
                <PageHeader title="Tambah Kelas" description="Buat kelas baru dan tentukan mata pelajaran yang diizinkan" />
            </div>

            <Card className="max-w-3xl">
                <CardContent>
                    <Form action="/admin/kelas" method="post" className="space-y-6">
                        {({ processing, errors }) => (
                            <>
                                <div>
                                    <Label htmlFor="nama">
                                        Nama Kelas <span className="text-danger">*</span>
                                    </Label>
                                    <Input id="nama" name="nama" required placeholder="Contoh: X-A" maxLength={20} autoFocus />
                                    <p className="text-xs text-muted-foreground mt-1">Maksimal 20 karakter. Contoh format: X-A, XI-B, XII-IPA-1.</p>
                                    <InputError message={errors.nama} />
                                </div>

                                <div className="pt-4 border-t border-border">
                                    <div className="flex items-center justify-between mb-3">
                                        <div>
                                            <h3 className="text-sm font-bold text-secondary flex items-center gap-2">
                                                <BookOpen className="h-4 w-4" />
                                                Mata Pelajaran yang Diizinkan
                                            </h3>
                                            <p className="text-xs text-muted-foreground mt-0.5">
                                                Centang mata pelajaran yang diajarkan di kelas ini. Guru hanya bisa di-assign mengajar kombinasi yang ada di sini.
                                            </p>
                                        </div>
                                        <div className="flex gap-2">
                                            <Button type="button" variant="outline" size="sm" onClick={selectAll}>
                                                <CheckSquare className="h-4 w-4" />
                                                Pilih Semua
                                            </Button>
                                            <Button type="button" variant="outline" size="sm" onClick={clearAll}>
                                                <Square className="h-4 w-4" />
                                                Kosongkan
                                            </Button>
                                        </div>
                                    </div>

                                    {semua_mapel.length === 0 ? (
                                        <div className="rounded-lg border border-dashed border-border bg-surface p-4 text-sm text-muted-foreground">
                                            Belum ada mata pelajaran di master.{' '}
                                            <Link href="/admin/mata-pelajaran" className="text-primary hover:underline">Tambah mata pelajaran dulu</Link>.
                                        </div>
                                    ) : (
                                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                                            {semua_mapel.map((m) => {
                                                const checked = selected.includes(m);

                                                return (
                                                    <label
                                                        key={m}
                                                        className={`flex items-center gap-2 p-2.5 rounded-lg border cursor-pointer transition ${
                                                            checked ? 'border-primary bg-blue-50' : 'border-border bg-white hover:bg-surface'
                                                        }`}
                                                    >
                                                        <input
                                                            type="checkbox"
                                                            name="mata_pelajaran[]"
                                                            value={m}
                                                            checked={checked}
                                                            onChange={() => toggle(m)}
                                                            className="h-4 w-4 text-primary border-border rounded focus:ring-primary"
                                                        />
                                                        <span className="text-sm text-secondary">{m}</span>
                                                    </label>
                                                );
                                            })}
                                        </div>
                                    )}

                                    <p className="text-xs text-muted-foreground mt-3">
                                        {selected.length} mata pelajaran dipilih.{' '}
                                        {selected.length === 0 && 'Tanpa mata pelajaran, guru tidak dapat di-assign mengajar di kelas ini.'}
                                    </p>
                                </div>

                                <div className="flex gap-2 pt-4 border-t border-border">
                                    <Button type="submit" disabled={processing}>
                                        <Save className="h-4 w-4" />
                                        {processing ? 'Menyimpan...' : 'Simpan'}
                                    </Button>
                                    <Link href="/admin/kelas">
                                        <Button type="button" variant="outline">Batal</Button>
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

KelasCreate.layout = { title: 'Tambah Kelas' };
