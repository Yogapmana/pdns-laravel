import { Form, Link } from '@inertiajs/react';
import { ArrowLeft, Save } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { InputError, PageHeader, Container } from '@/components/ui/shared';

type Props = {
    mataPelajaran: { id: number; nama: string };
};

export default function MataPelajaranEdit({ mataPelajaran }: Props) {
    return (
        <Container>
            <div className="flex items-center gap-3 mb-4">
                <Link href="/admin/mata-pelajaran" className="text-muted-foreground hover:text-foreground">
                    <ArrowLeft className="h-4 w-4" />
                </Link>
                <PageHeader title="Edit Mata Pelajaran" description={`Mengubah nama mata pelajaran akan mempengaruhi tampilan di mengajar & nilai siswa.`} />
            </div>

            <Card className="max-w-xl">
                <CardContent>
                    <Form action={`/admin/mata-pelajaran/${mataPelajaran.id}`} method="put" className="space-y-4">
                        {({ processing, errors }) => (
                            <>
                                <div>
                                    <Label htmlFor="nama">
                                        Nama Mata Pelajaran <span className="text-danger">*</span>
                                    </Label>
                                    <Input id="nama" name="nama" required defaultValue={mataPelajaran.nama} maxLength={100} autoFocus />
                                    <p className="text-xs text-muted-foreground mt-1">Maksimal 100 karakter.</p>
                                    <InputError message={errors.nama} />
                                </div>

                                <div className="flex gap-2 pt-4 border-t border-border">
                                    <Button type="submit" disabled={processing}>
                                        <Save className="h-4 w-4" />
                                        {processing ? 'Menyimpan...' : 'Simpan'}
                                    </Button>
                                    <Link href="/admin/mata-pelajaran">
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

MataPelajaranEdit.layout = { title: 'Edit Mata Pelajaran' };
