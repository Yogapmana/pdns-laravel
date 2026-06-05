import { Link } from '@inertiajs/react';
import { BookOpenCheck, GraduationCap, Printer } from 'lucide-react';
import { Card, CardContent } from '@/components/ui/card';
import { Container, PageHeader, StatCard } from '@/components/ui/shared';
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
                <StatCard label="NIS" value={siswa.nis} icon={<GraduationCap className="h-6 w-6" />} color="primary" />
                <StatCard label="Kelas" value={siswa.kelas} icon={<GraduationCap className="h-6 w-6" />} color="accent" />
                <StatCard label="Username" value={siswa.user?.username ?? '—'} icon={<GraduationCap className="h-6 w-6" />} color="warning" />
            </div>

            <Card>
                <CardContent>
                    <div className="flex flex-col sm:flex-row items-center gap-4">
                        <div className="p-4 bg-blue-100 text-primary rounded-full">
                            <BookOpenCheck className="h-8 w-8" />
                        </div>
                        <div className="flex-1 text-center sm:text-left">
                            <h2 className="text-lg font-bold text-navy">Lihat Nilai Anda</h2>
                            <p className="text-sm text-muted-foreground mt-1">
                                Cek rincian nilai tugas, UTS, UAS, nilai akhir, dan status kelulusan untuk semua mata pelajaran.
                            </p>
                        </div>
                        <Link href="/siswa/nilai" className="inline-flex items-center gap-2 px-5 py-2.5 bg-primary text-white rounded-lg text-sm font-semibold hover:bg-primary-700 transition">
                            <BookOpenCheck className="h-4 w-4" />
                            Buka Nilai Saya
                        </Link>
                    </div>
                </CardContent>
            </Card>

            {has_nilai && (
                <Card>
                    <CardContent>
                        <div className="flex flex-col sm:flex-row items-center gap-4">
                            <div className="p-4 bg-emerald-100 text-emerald-600 rounded-full">
                                <Printer className="h-8 w-8" />
                            </div>
                            <div className="flex-1 text-center sm:text-left">
                                <h2 className="text-lg font-bold text-navy">Cetak Rapor Digital</h2>
                                <p className="text-sm text-muted-foreground mt-1">
                                    Unduh rapor digital Anda dalam format PDF siap cetak. Sudah termasuk tabel nilai, ringkasan kelulusan, dan
                                    kolom tanda tangan.
                                </p>
                            </div>
                            <a
                                href="/siswa/rapor/pdf"
                                target="_blank"
                                rel="noopener noreferrer"
                                className="inline-flex items-center gap-2 px-5 py-2.5 bg-emerald-600 text-white rounded-lg text-sm font-semibold hover:bg-emerald-700 transition"
                            >
                                <Printer className="h-4 w-4" />
                                Cetak Rapor (PDF)
                            </a>
                        </div>
                    </CardContent>
                </Card>
            )}
        </Container>
    );
}

SiswaDashboard.layout = { title: 'Dashboard Siswa' };
