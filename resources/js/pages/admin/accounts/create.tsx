import { Form, Link, usePage } from '@inertiajs/react';
import { ArrowLeft, Save } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select } from '@/components/ui/select';
import { InputError, PageHeader, Container } from '@/components/ui/shared';

type SiswaTanpaAkun = { nis: string; nama_siswa: string; kelas: string };
type Mengajar = { kelas: string; mata_pelajaran: string };
type GuruTanpaAkun = { id: number; nama_guru: string; mengajar: Mengajar[] };

type Props = { siswa_tanpa_akun: SiswaTanpaAkun[]; guru_tanpa_akun: GuruTanpaAkun[] };

const ROLE_LABELS: Record<string, string> = {
    admin: 'Admin',
    guru: 'Guru',
    siswa: 'Siswa',
};

function mengajarLabel(g: GuruTanpaAkun): string {
    if (!g.mengajar || g.mengajar.length === 0) {
return 'Belum ada mengajar';
}

    const mapels = [...new Set(g.mengajar.map((m) => m.mata_pelajaran))];

    return mapels.join(', ');
}

export default function AccountsCreate({ siswa_tanpa_akun, guru_tanpa_akun }: Props) {
    const { props } = usePage<{ errors: Record<string, string> }>();
    const errors = props.errors ?? {};
    const [role, setRole] = useState<'admin' | 'guru' | 'siswa'>('siswa');
    const [name, setName] = useState('');
    const [selectedNis, setSelectedNis] = useState('');
    const [selectedGuruId, setSelectedGuruId] = useState('');

    function handleRoleChange(next: 'admin' | 'guru' | 'siswa') {
        setRole(next);
        setName('');
        setSelectedNis('');
        setSelectedGuruId('');
    }

    function handleSiswaChange(nis: string) {
        setSelectedNis(nis);
        const found = siswa_tanpa_akun.find((s) => s.nis === nis);

        if (found) {
            setName(found.nama_siswa);
        }
    }

    function handleGuruChange(id: string) {
        setSelectedGuruId(id);
        const found = guru_tanpa_akun.find((g) => String(g.id) === id);

        if (found) {
            setName(found.nama_guru);
        }
    }

    return (
        <Container>
            <div className="flex items-center gap-3 mb-4">
                <Link href="/admin/akun" className="text-muted-foreground hover:text-foreground">
                    <ArrowLeft className="h-4 w-4" />
                </Link>
                <PageHeader title="Buat Akun Baru" description="Tambahkan akun login untuk admin, guru, atau siswa" />
            </div>

            <Card className="max-w-2xl">
                <CardContent>
                    <Form action="/admin/akun" method="post" className="space-y-4">
                        {({ processing }) => (
                            <>
                                <div>
                                    <label htmlFor="role" className="block text-sm font-medium text-secondary mb-2">
                                        Role <span className="text-danger">*</span>
                                    </label>
                                    <Select
                                        id="role"
                                        name="role"
                                        value={role}
                                        onChange={(e) => handleRoleChange(e.target.value as 'admin' | 'guru' | 'siswa')}
                                    >
                                        <option value="admin">Admin</option>
                                        <option value="guru">Guru</option>
                                        <option value="siswa">Siswa</option>
                                    </Select>
                                </div>

                                <div>
                                    <label htmlFor="username" className="block text-sm font-medium text-secondary mb-2">
                                        Username <span className="text-danger">*</span>
                                    </label>
                                    <Input id="username" name="username" required placeholder="Username untuk login" />
                                    <InputError message={errors.username} />
                                </div>

                                <div>
                                    <label htmlFor="name" className="block text-sm font-medium text-secondary mb-2">
                                        Nama Lengkap <span className="text-danger">*</span>
                                    </label>
                                    <Input
                                        id="name"
                                        name="name"
                                        required
                                        placeholder="Nama tampilan"
                                        value={name}
                                        onChange={(e) => setName(e.target.value)}
                                    />
                                    {(selectedNis || selectedGuruId) && (
                                        <p className="text-xs text-muted-foreground mt-1">
                                            Otomatis terisi dari {ROLE_LABELS[role]} yang dipilih. Bisa diganti.
                                        </p>
                                    )}
                                    <InputError message={errors.name} />
                                </div>

                                <div>
                                    <label htmlFor="password" className="block text-sm font-medium text-secondary mb-2">
                                        Password <span className="text-danger">*</span>
                                    </label>
                                    <Input id="password" name="password" type="text" required placeholder="Minimal 6 karakter" />
                                    <p className="text-xs text-muted-foreground mt-1">Akan di-hash otomatis. Bisa diganti nanti.</p>
                                    <InputError message={errors.password} />
                                </div>

                                {role === 'siswa' && (
                                    <div>
                                        <label htmlFor="nis" className="block text-sm font-medium text-secondary mb-2">
                                            Pilih Siswa <span className="text-danger">*</span>
                                        </label>
                                        <Select
                                            id="nis"
                                            name="nis"
                                            value={selectedNis}
                                            onChange={(e) => handleSiswaChange(e.target.value)}
                                        >
                                            <option value="">Pilih siswa...</option>
                                            {siswa_tanpa_akun.map((s) => (
                                                <option key={s.nis} value={s.nis}>
                                                    {s.nis} {s.nama_siswa} ({s.kelas})
                                                </option>
                                            ))}
                                        </Select>
                                        {siswa_tanpa_akun.length === 0 && (
                                            <p className="text-xs text-warning mt-1">Semua siswa sudah memiliki akun. Tambah siswa baru dulu.</p>
                                        )}
                                        <InputError message={errors.nis} />
                                    </div>
                                )}

                                {role === 'guru' && (
                                    <div>
                                        <label htmlFor="guru_id" className="block text-sm font-medium text-secondary mb-2">
                                            Pilih Guru <span className="text-danger">*</span>
                                        </label>
                                        <Select
                                            id="guru_id"
                                            name="guru_id"
                                            value={selectedGuruId}
                                            onChange={(e) => handleGuruChange(e.target.value)}
                                        >
                                            <option value="">Pilih guru...</option>
                                            {guru_tanpa_akun.map((g) => (
                                                <option key={g.id} value={g.id}>
                                                    {g.nama_guru} ({mengajarLabel(g)})
                                                </option>
                                            ))}
                                        </Select>
                                        {guru_tanpa_akun.length === 0 && (
                                            <p className="text-xs text-warning mt-1">Semua guru sudah memiliki akun. Tambah guru baru dulu.</p>
                                        )}
                                        <InputError message={errors.guru_id} />
                                    </div>
                                )}

                                <div className="flex gap-2 pt-4 border-t border-border">
                                    <Button type="submit" disabled={processing}>
                                        <Save className="h-4 w-4" />
                                        {processing ? 'Menyimpan...' : 'Buat Akun'}
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

AccountsCreate.layout = { title: 'Buat Akun' };
