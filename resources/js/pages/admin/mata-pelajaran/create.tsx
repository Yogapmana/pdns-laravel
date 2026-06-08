import { Form, Link } from '@inertiajs/react';
import { ArrowLeft, Save } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { InputError, PageHeader, Container } from '@/components/ui/shared';
import { index, store } from '@/routes/admin/mata-pelajaran';

export default function MataPelajaranCreate() {
    return (
        <Container>
            <div className="flex items-center gap-3 mb-4">
                <Link href={index.url()} className="text-muted-foreground hover:text-foreground">
                    <ArrowLeft className="h-4 w-4" />
                </Link>
                <PageHeader title="Tambah Mata Pelajaran" description="Buat mata pelajaran baru untuk diajarkan dan dinilai" />
            </div>

            <Card className="max-w-xl">
                <CardContent>
                    <Form action={store.url()} method="post" className="space-y-4">
                        {({ processing, errors }) => (
                            <>
                                <div>
                                    <Label htmlFor="nama">
                                        Nama Mata Pelajaran <span className="text-danger">*</span>
                                    </Label>
                                    <Input id="nama" name="nama" required placeholder="Contoh: Matematika" maxLength={100} autoFocus />
                                    <p className="text-xs text-muted-foreground mt-1">Maksimal 100 karakter.</p>
                                    <InputError message={errors.nama} />
                                </div>

                                <div className="flex gap-2 pt-4 border-t border-border">
                                    <Button type="submit" disabled={processing}>
                                        <Save className="h-4 w-4" />
                                        {processing ? 'Menyimpan...' : 'Simpan'}
                                    </Button>
                                    <Link href={index.url()}>
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

MataPelajaranCreate.layout = { title: 'Tambah Mata Pelajaran' };
