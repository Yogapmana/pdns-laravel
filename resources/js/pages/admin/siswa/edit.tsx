import { Form, Link, usePage } from '@inertiajs/react';
import { ArrowLeft, Save } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select } from '@/components/ui/select';
import { InputError, PageHeader, Container } from '@/components/ui/shared';

type Props = {
    siswa: { nis: string; nama_siswa: string; kelas_id: number; kelas_nama?: string };
    daftar_kelas: { id: number; nama: string }[];
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
                                    <Label htmlFor="nis">NIS</Label>
                                    <Input id="nis" value={siswa.nis} disabled className="font-mono" />
                                    <p className="text-xs text-muted-foreground mt-1">NIS tidak dapat diubah.</p>
                                </div>

                                <div>
                                    <Label htmlFor="nama_siswa">
                                        Nama Lengkap <span className="text-danger">*</span>
                                    </Label>
                                    <Input id="nama_siswa" name="nama_siswa" required defaultValue={siswa.nama_siswa} />
                                    <InputError message={errors.nama_siswa} />
                                </div>

                                <div>
                                    <Label htmlFor="kelas_id">
                                        Kelas <span className="text-danger">*</span>
                                    </Label>
                                    <Select id="kelas_id" name="kelas_id" defaultValue={siswa.kelas_id} required>
                                        <option value="" disabled>Pilih kelas</option>
                                        {daftar_kelas.map((k) => (
                                            <option key={k.id} value={k.id}>{k.nama}</option>
                                        ))}
                                    </Select>
                                    <p className="text-xs text-muted-foreground mt-1">
                                        Belum ada kelas yang sesuai?{' '}
                                        <Link href="/admin/kelas/create" className="text-primary hover:underline">
                                            Tambah kelas baru
                                        </Link>
                                    </p>
                                    <InputError message={errors.kelas_id} />
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
