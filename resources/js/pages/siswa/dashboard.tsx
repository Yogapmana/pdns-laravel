import { BookOpenCheck, GraduationCap, Printer } from 'lucide-react';
import { Container, PageHeader, StatCard, ActionCard } from '@/components/ui/shared';
import { useFlashToast } from '@/hooks/use-flash-toast';

type Siswa = { nis: string; nama_siswa: string; kelas: string; user: { username: string } | null };

type Props = { siswa: Siswa; has_nilai: boolean };

export default function SiswaDashboard({ siswa, has_nilai }: Props) {
    useFlashToast();

    return (
        <Container>
            <PageHeader
                title={`Halo, ${siswa.nama_siswa}!`}
                description={`NIS: ${siswa.nis} — Kelas: ${siswa.kelas}`}
            />

            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                <StatCard label="NIS" value={siswa.nis} icon={<GraduationCap className="h-4 w-4" />} color="primary" variant="colored" description="Nomor Induk Siswa" />
                <StatCard label="Kelas" value={siswa.kelas} icon={<GraduationCap className="h-4 w-4" />} color="accent" variant="colored" description="Kelas saat ini" />
                <StatCard label="Username" value={siswa.user?.username ?? '—'} icon={<GraduationCap className="h-4 w-4" />} color="warning" variant="colored" description="Username login" />
            </div>

            <ActionCard
                icon={<BookOpenCheck />}
                title="Lihat Nilai Anda"
                description="Cek rincian nilai tugas, UTS, UAS, nilai akhir, dan status kelulusan untuk semua mata pelajaran."
                actionLabel="Buka Nilai Saya"
                href="/siswa/nilai"
                variant="primary"
            />

            {has_nilai && (
                <ActionCard
                    icon={<Printer />}
                    title="Cetak Rapor Digital"
                    description="Unduh rapor digital Anda dalam format PDF siap cetak. Sudah termasuk tabel nilai, ringkasan kelulusan, dan kolom tanda tangan."
                    actionLabel="Cetak Rapor (PDF)"
                    href="/siswa/rapor/pdf"
                    variant="success"
                    external
                />
            )}
        </Container>
    );
}

SiswaDashboard.layout = { title: 'Dashboard Siswa' };
