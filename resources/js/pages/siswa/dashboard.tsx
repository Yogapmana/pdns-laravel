import { BookOpenCheck, Hash, Printer, School, User } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import {
    Container,
    PageHeader,
    StatCard,
    ActionCard,
} from '@/components/ui/shared';
import { useFlashToast } from '@/hooks/use-flash-toast';

type Siswa = {
    nis: string;
    nama_siswa: string;
    kelas: { id: number; nama: string } | null;
    kelas_nama?: string;
    user: { username: string } | null;
};

type Props = { siswa: Siswa; has_nilai: boolean };

export default function SiswaDashboard({ siswa, has_nilai }: Props) {
    useFlashToast();

    return (
        <Container>
            <PageHeader title={`Halo, ${siswa.nama_siswa}!`} />

            <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                <StatCard
                    label="NIS"
                    value={siswa.nis}
                    icon={<Hash className="h-4 w-4" />}
                    color="primary"
                    variant="colored"
                    description="Nomor Induk Siswa"
                />
                <StatCard
                    label="Kelas"
                    value={siswa.kelas_nama ?? siswa.kelas?.nama ?? '—'}
                    icon={<School className="h-4 w-4" />}
                    color="accent"
                    variant="colored"
                    description="Kelas saat ini"
                />
                <StatCard
                    label="Username"
                    value={siswa.user?.username ?? '—'}
                    icon={<User className="h-4 w-4" />}
                    color="warning"
                    variant="colored"
                    description="Username login"
                />
            </div>

            <div className="mt-6 flex flex-col gap-4">
                <ActionCard
                    icon={<BookOpenCheck />}
                    title="Lihat Nilai Anda"
                    description="Cek rincian nilai tugas, UTS, UAS, nilai akhir, dan status kelulusan untuk semua mata pelajaran."
                    href="/siswa/nilai"
                    variant="primary"
                />

                {has_nilai && (
                    <ActionCard
                        icon={<Printer />}
                        title="Cetak Rapor Digital"
                        description="Unduh rapor digital Anda dalam format PDF siap cetak. Sudah termasuk tabel nilai, ringkasan kelulusan, dan kolom tanda tangan."
                        href="/siswa/rapor/pdf"
                        variant="success"
                        external
                    />
                )}
            </div>
        </Container>
    );
}

SiswaDashboard.layout = { title: 'Dashboard Siswa' };
