import { Form, Link, usePage } from '@inertiajs/react';
import { ArrowLeft, Save, Info } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select } from '@/components/ui/select';
import { InputError, PageHeader, Container } from '@/components/ui/shared';

type Props = { daftar_kelas: string[] };

export default function SiswaCreate({ daftar_kelas }: Props) {
    const { props } = usePage<{ errors: Record<string, string> }>();
    const errors = props.errors ?? {};

    return (
        <Container>
            <div className="mb-4 flex items-center gap-3">
                <Link
                    href="/admin/siswa"
                    className="text-muted-foreground hover:text-foreground"
                >
                    <ArrowLeft className="h-4 w-4" />
                </Link>
                <PageHeader
                    title="Tambah Siswa"
                    description="Daftarkan siswa baru ke sistem"
                />
            </div>

            <Card className="max-w-2xl">
                <CardContent>
                    <Form
                        action="/admin/siswa"
                        method="post"
                        className="space-y-4"
                    >
                        {({ processing }) => (
                            <>
                                <div>
                                    <Label htmlFor="nis">
                                        NIS <span className="text-danger">*</span>
                                    </Label>
                                    <Input
                                        id="nis"
                                        name="nis"
                                        required
                                        placeholder="Contoh: 00001"
                                    />
                                    <InputError message={errors.nis} />
                                </div>

                                <div>
                                    <Label htmlFor="nama_siswa">
                                        Nama Lengkap <span className="text-danger">*</span>
                                    </Label>
                                    <Input
                                        id="nama_siswa"
                                        name="nama_siswa"
                                        required
                                        placeholder="Nama lengkap siswa"
                                    />
                                    <InputError message={errors.nama_siswa} />
                                </div>

                                <div>
                                    <Label htmlFor="kelas">
                                        Kelas <span className="text-danger">*</span>
                                    </Label>
                                    <Select
                                        id="kelas"
                                        name="kelas"
                                        defaultValue=""
                                        required
                                    >
                                        <option value="" disabled>
                                            Pilih kelas
                                        </option>
                                        {daftar_kelas.map((k) => (
                                            <option key={k} value={k}>
                                                {k}
                                            </option>
                                        ))}
                                    </Select>
                                    <p className="mt-1 text-xs text-muted-foreground">
                                        Belum ada kelas yang sesuai?{' '}
                                        <Link
                                            href="/admin/kelas/create"
                                            className="text-primary hover:underline"
                                        >
                                            Tambah kelas baru
                                        </Link>
                                    </p>
                                    <InputError message={errors.kelas} />
                                </div>

                                <div className="space-y-3 rounded-lg border border-border bg-surface p-3">
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
                                        <InputError
                                            message={
                                                errors.password_confirmation
                                            }
                                        />
                                    </div>
                                </div>

                                <div className="flex gap-2 border-t border-border pt-4">
                                    <Button type="submit" disabled={processing}>
                                        <Save className="h-4 w-4" />
                                        {processing ? 'Menyimpan...' : 'Simpan'}
                                    </Button>
                                    <Link href="/admin/siswa">
                                        <Button type="button" variant="outline">
                                            Batal
                                        </Button>
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

SiswaCreate.layout = { title: 'Tambah Siswa' };
