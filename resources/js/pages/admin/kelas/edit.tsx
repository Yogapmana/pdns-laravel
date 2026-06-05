import { Form, Link } from '@inertiajs/react';
import { ArrowLeft, Save } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { InputError, PageHeader, Container } from '@/components/ui/shared';

type Props = {
    kelas: { id: number; nama: string };
};

export default function KelasEdit({ kelas }: Props) {
    return (
        <Container>
            <div className="flex items-center gap-3 mb-4">
                <Link href="/admin/kelas" className="text-muted-foreground hover:text-foreground">
                    <ArrowLeft className="h-4 w-4" />
                </Link>
                <PageHeader title="Edit Kelas" description={`Mengubah nama kelas "${kelas.nama}" akan mempengaruhi tampilan di siswa & mengajar.`} />
            </div>

            <Card className="max-w-xl">
                <CardContent>
                    <Form action={`/admin/kelas/${kelas.id}`} method="put" className="space-y-4">
                        {({ processing, errors }) => (
                            <>
                                <div>
                                    <label htmlFor="nama" className="block text-sm font-medium text-secondary mb-2">
                                        Nama Kelas <span className="text-danger">*</span>
                                    </label>
                                    <Input id="nama" name="nama" required defaultValue={kelas.nama} maxLength={20} autoFocus />
                                    <p className="text-xs text-muted-foreground mt-1">Maksimal 20 karakter.</p>
                                    <InputError message={errors.nama} />
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

KelasEdit.layout = { title: 'Edit Kelas' };
