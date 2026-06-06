import { Form, Link } from '@inertiajs/react';
import { ArrowLeft, Save } from 'lucide-react';
import { Alert } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { InputError, PageHeader, Container } from '@/components/ui/shared';

type Props = {
    guru: {
        id: number;
        nama_guru: string;
        mata_pelajaran: string;
    };
};

export default function GuruCreateAccount({ guru }: Props) {
    return (
        <Container>
            <div className="flex items-center gap-3 mb-4">
                <Link href="/admin/guru" className="text-muted-foreground hover:text-foreground">
                    <ArrowLeft className="h-4 w-4" />
                </Link>
                <PageHeader
                    title="Buat Akun Login"
                    description={`Guru: ${guru.nama_guru} (${guru.mata_pelajaran})`}
                />
            </div>

            <Card className="max-w-2xl">
                <CardContent>
                    <Form
                        action={`/admin/guru/${guru.id}/create-account`}
                        method="post"
                        className="space-y-4"
                    >
                        {({ processing, errors }) => (
                            <>
                                <Alert variant="info">
                                    Guru dapat login menggunakan username dan password yang dibuat di bawah.
                                </Alert>

                                <div>
                                    <label htmlFor="username" className="block text-sm font-medium text-secondary mb-2">
                                        Username <span className="text-danger">*</span>
                                    </label>
                                    <Input
                                        id="username"
                                        name="username"
                                        required
                                        placeholder="contoh: guru.matematika"
                                        autoComplete="off"
                                    />
                                    <InputError message={errors.username} />
                                </div>

                                <div>
                                    <label htmlFor="name" className="block text-sm font-medium text-secondary mb-2">
                                        Nama Tampilan <span className="text-xs text-muted-foreground">(opsional)</span>
                                    </label>
                                    <Input
                                        id="name"
                                        name="name"
                                        placeholder="Kosongkan jika sama dengan nama guru"
                                        autoComplete="off"
                                    />
                                    <InputError message={errors.name} />
                                </div>

                                <div>
                                    <label htmlFor="password" className="block text-sm font-medium text-secondary mb-2">
                                        Password <span className="text-danger">*</span>
                                    </label>
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
                                    <label htmlFor="password_confirmation" className="block text-sm font-medium text-secondary mb-2">
                                        Konfirmasi Password <span className="text-danger">*</span>
                                    </label>
                                    <Input
                                        id="password_confirmation"
                                        name="password_confirmation"
                                        type="password"
                                        required
                                        placeholder="Ulangi password"
                                        autoComplete="new-password"
                                    />
                                    <InputError message={errors.password_confirmation} />
                                </div>

                                <div className="flex gap-2 pt-4 border-t border-border">
                                    <Button type="submit" disabled={processing}>
                                        <Save className="h-4 w-4" />
                                        {processing ? 'Membuat Akun...' : 'Buat Akun'}
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
        </Container>
    );
}

GuruCreateAccount.layout = { title: 'Buat Akun Guru' };
