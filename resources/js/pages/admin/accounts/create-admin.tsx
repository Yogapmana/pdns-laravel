import { Form, Link } from '@inertiajs/react';
import { ArrowLeft, Save } from 'lucide-react';
import { Alert } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { InputError, PageHeader, Container } from '@/components/ui/shared';

export default function AccountCreateAdmin() {
    return (
        <Container>
            <div className="flex items-center gap-3 mb-4">
                <Link href="/admin/akun" className="text-muted-foreground hover:text-foreground">
                    <ArrowLeft className="h-4 w-4" />
                </Link>
                <PageHeader
                    title="Buat Akun Admin"
                    description="Daftarkan administrator baru dengan username dan password pilihan Anda"
                />
            </div>

            <Card className="max-w-2xl">
                <CardContent>
                    <Form action="/admin/akun/create-admin" method="post" className="space-y-4">
                        {({ processing, errors }) => (
                            <>
                                <Alert variant="info">
                                    Username dan password ditentukan oleh admin. Akun akan dibuat dengan role Admin dan status aktif.
                                </Alert>

                                <div>
                                    <Label htmlFor="username">
                                        Username <span className="text-danger">*</span>
                                    </Label>
                                    <Input
                                        id="username"
                                        name="username"
                                        required
                                        placeholder="contoh: admin.kepsek"
                                        autoComplete="off"
                                    />
                                    <InputError message={errors.username} />
                                </div>

                                <div>
                                    <Label htmlFor="name">
                                        Nama Tampilan <span className="text-xs text-muted-foreground">(opsional)</span>
                                    </Label>
                                    <Input
                                        id="name"
                                        name="name"
                                        placeholder="Contoh: Kepala Sekolah"
                                        autoComplete="off"
                                    />
                                    <InputError message={errors.name} />
                                </div>

                                <div>
                                    <Label htmlFor="password">
                                        Password <span className="text-danger">*</span>
                                    </Label>
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
                                    <Label htmlFor="password_confirmation">
                                        Konfirmasi Password <span className="text-danger">*</span>
                                    </Label>
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
                                    <Link href="/admin/akun">
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

AccountCreateAdmin.layout = { title: 'Buat Akun Admin' };
