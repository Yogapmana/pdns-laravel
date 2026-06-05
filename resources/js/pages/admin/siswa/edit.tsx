import { Form, Link, usePage } from '@inertiajs/react';
import { ArrowLeft, Save } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select } from '@/components/ui/select';
import { InputError, PageHeader, Container } from '@/components/ui/shared';

type Props = {
    siswa: { nis: string; nama_siswa: string; kelas: string };
    daftar_kelas: string[];
};

export default function SiswaEdit({ siswa, daftar_kelas }: Props) {
    const { props } = usePage<{ errors: Record<string, string> }>();
    const errors = props.errors ?? {};

    return (
        <Container>
            <div className="flex items-center gap-3 mb-4">
                <Link href="/admin/siswa" className="text-muted-foreground hover:text-foreground">
                    <ArrowLeft className="h-4 w-4" />
                </Link>
                <PageHeader title="Edit Siswa" description={`NIS: ${siswa.nis} (tidak dapat diubah)`} />
            </div>

            <Card className="max-w-2xl">
                <CardContent>
                    <Form action={`/admin/siswa/${siswa.nis}`} method="put" className="space-y-4">
                        {({ processing }) => (
                            <>
                                <div>
                                    <label htmlFor="nis" className="block text-sm font-medium text-secondary mb-2">
                                        NIS
                                    </label>
                                    <Input id="nis" value={siswa.nis} disabled className="font-mono" />
                                    <p className="text-xs text-muted-foreground mt-1">NIS tidak dapat diubah.</p>
                                </div>

                                <div>
                                    <label htmlFor="nama_siswa" className="block text-sm font-medium text-secondary mb-2">
                                        Nama Lengkap <span className="text-danger">*</span>
                                    </label>
                                    <Input id="nama_siswa" name="nama_siswa" required defaultValue={siswa.nama_siswa} />
                                    <InputError message={errors.nama_siswa} />
                                </div>

                                <div>
                                    <label htmlFor="kelas" className="block text-sm font-medium text-secondary mb-2">
                                        Kelas <span className="text-danger">*</span>
                                    </label>
                                    <Select id="kelas" name="kelas" defaultValue={siswa.kelas}>
                                        <option value="">Pilih kelas atau isi baru di bawah</option>
                                        {daftar_kelas.map((k) => (
                                            <option key={k} value={k}>{k}</option>
                                        ))}
                                    </Select>
                                    <p className="text-xs text-muted-foreground mt-1">Atau isi kelas baru:</p>
                                    <Input id="kelas_baru" name="kelas_baru" placeholder="Contoh: X-A" className="mt-1" />
                                    <InputError message={errors.kelas} />
                                </div>

                                <div className="flex gap-2 pt-4 border-t border-border">
                                    <Button type="submit" disabled={processing}>
                                        <Save className="h-4 w-4" />
                                        {processing ? 'Menyimpan...' : 'Simpan'}
                                    </Button>
                                    <Link href="/admin/siswa">
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

SiswaEdit.layout = { title: 'Edit Siswa' };
