import { Link } from '@inertiajs/react';
import { Users, FileEdit, CheckCircle, XCircle, BookOpen, AlertCircle, ChevronRight, TrendingUp, AlertTriangle, Edit3, Lock } from 'lucide-react';
import { Alert } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Card, CardHeader, CardContent } from '@/components/ui/card';
import { PageHeader, Container } from '@/components/ui/shared';
import { useFlashToast } from '@/hooks/use-flash-toast';

type Mengajar = { id: number; kelas: string; mata_pelajaran: string };

type Guru = { id: number; nama_guru: string; mengajar: Mengajar[] };

type Stats = {
    total_siswa: number;
    total_nilai: number;
    draft: number;
    final: number;
    lulus: number;
    tidak_lulus: number;
    rata_rata: number;
};

type PerCombo = {
    id_mengajar: number;
    kelas: string;
    mata_pelajaran: string;
    jumlah_siswa: number;
    jumlah_input: number;
    jumlah_final: number;
    jumlah_draft: number;
};

type NotifikasiItem = PerCombo & { sisa?: number };

type Notifikasi = {
    belum_diinput: NotifikasiItem[];
    masih_draft: NotifikasiItem[];
};

type Props = {
    guru: Guru;
    stats: Stats;
    mengajar: Mengajar[];
    per_combo_stats: PerCombo[];
    notifikasi: Notifikasi;
};

function formatComboList(items: NotifikasiItem[]): string {
    return items.map((it) => `${it.mata_pelajaran} ${it.kelas}`).join(', ');
}

function comboStateLabel(combo: PerCombo): { label: string; variant: 'neutral' | 'success' | 'warning' | 'info' | 'danger' } {
    if (combo.jumlah_siswa === 0) {
return { label: 'Kelas kosong', variant: 'neutral' };
}

    if (combo.jumlah_input < combo.jumlah_siswa) {
        const sisa = combo.jumlah_siswa - combo.jumlah_input;

        return { label: `${sisa} belum input`, variant: 'warning' };
    }

    if (combo.jumlah_draft > 0) {
        return { label: `${combo.jumlah_draft} Draft`, variant: 'warning' };
    }

    if (combo.jumlah_final === combo.jumlah_siswa) {
        return { label: 'Semua Final', variant: 'success' };
    }

    return { label: `${combo.jumlah_final}/${combo.jumlah_siswa} Final`, variant: 'info' };
}

export default function GuruDashboard({ guru, stats, per_combo_stats, notifikasi }: Props) {
    useFlashToast();

    const mengajar = guru.mengajar ?? [];
    const totalKelas = new Set(mengajar.map((m) => m.kelas)).size;
    const totalMapel = new Set(mengajar.map((m) => m.mata_pelajaran)).size;

    const belumDiinput = notifikasi?.belum_diinput ?? [];
    const masihDraft = notifikasi?.masih_draft ?? [];
    const adaNotifikasi = belumDiinput.length > 0 || masihDraft.length > 0;

    const comboFinal = per_combo_stats.filter((c) => c.jumlah_siswa > 0 && c.jumlah_final === c.jumlah_siswa).length;
    const comboSebagian = per_combo_stats.filter((c) => c.jumlah_siswa > 0 && c.jumlah_draft > 0 && c.jumlah_final > 0).length;
    const comboBelumInput = per_combo_stats.filter((c) => c.jumlah_siswa > 0 && c.jumlah_input < c.jumlah_siswa).length;
    const comboKosong = per_combo_stats.filter((c) => c.jumlah_siswa === 0).length;

    return (
        <Container>
            <PageHeader
                title={`Selamat Datang, ${guru.nama_guru}`}
                description={`${mengajar.length} kombinasi mengajar • ${totalKelas} kelas • ${totalMapel} mata pelajaran`}
                action={
                    <Link href="/guru/input-nilai" className="inline-flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg text-sm font-semibold hover:bg-primary-700 transition">
                        <Edit3 className="h-4 w-4" />
                        Input Nilai
                    </Link>
                }
            />

            {adaNotifikasi && (
                <div className="space-y-3">
                    {belumDiinput.length > 0 && (
                        <Alert variant="warning" className="mb-0">
                            <div className="flex flex-col gap-2">
                                <p>
                                    <span className="font-bold">Perhatian:</span> Terdapat {belumDiinput.length} kelas
                                    ({formatComboList(belumDiinput)}) yang nilainya
                                    <strong> belum Anda input atau belum lengkap</strong>.
                                </p>
                                <div className="flex flex-wrap gap-2">
                                    {belumDiinput.map((it) => (
                                        <Link
                                            key={it.id_mengajar}
                                            href={`/guru/input-nilai?kelas=${encodeURIComponent(it.kelas)}&mata_pelajaran=${encodeURIComponent(it.mata_pelajaran)}`}
                                            className="inline-flex items-center gap-1.5 px-2.5 py-1 bg-white border border-yellow-300 rounded-md text-xs font-medium text-yellow-900 hover:bg-yellow-100 transition"
                                        >
                                            <Badge variant="info" className="!text-[10px] !px-1.5 !py-0">{it.kelas}</Badge>
                                            <span>{it.mata_pelajaran}</span>
                                            <span className="text-yellow-700">({it.jumlah_input}/{it.jumlah_siswa})</span>
                                            <ChevronRight className="h-3 w-3" />
                                        </Link>
                                    ))}
                                </div>
                            </div>
                        </Alert>
                    )}

                    {masihDraft.length > 0 && (
                        <Alert variant="error" className="mb-0">
                            <div className="flex flex-col gap-2">
                                <p>
                                    <span className="font-bold">Tindak Lanjuti:</span> Terdapat {masihDraft.length} kelas
                                    ({formatComboList(masihDraft)}) yang nilainya sudah lengkap diinput tetapi
                                    <strong> masih berstatus Draft</strong>. Klik tombol "Validasi Final" untuk mengunci nilai.
                                </p>
                                <div className="flex flex-wrap gap-2">
                                    {masihDraft.map((it) => (
                                        <Link
                                            key={it.id_mengajar}
                                            href={`/guru/input-nilai?kelas=${encodeURIComponent(it.kelas)}&mata_pelajaran=${encodeURIComponent(it.mata_pelajaran)}`}
                                            className="inline-flex items-center gap-1.5 px-2.5 py-1 bg-white border border-red-300 rounded-md text-xs font-medium text-red-900 hover:bg-red-100 transition"
                                        >
                                            <Badge variant="info" className="!text-[10px] !px-1.5 !py-0">{it.kelas}</Badge>
                                            <span>{it.mata_pelajaran}</span>
                                            <Badge variant="warning" className="!text-[10px] !px-1.5 !py-0">{it.jumlah_draft} Draft</Badge>
                                            <ChevronRight className="h-3 w-3" />
                                        </Link>
                                    ))}
                                </div>
                            </div>
                        </Alert>
                    )}
                </div>
            )}

            <Card>
                <CardHeader>Ringkasan Nilai</CardHeader>
                <CardContent>
                    <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
                        <div className="p-4 bg-blue-50 rounded-lg">
                            <div className="flex items-center gap-2 mb-1">
                                <Users className="h-4 w-4 text-primary" />
                                <p className="text-xs text-muted-foreground">Total Siswa</p>
                            </div>
                            <p className="text-3xl font-bold text-primary">{stats.total_siswa}</p>
                            <p className="text-[11px] text-muted-foreground mt-1">Siswa di kelas yang Anda ajar</p>
                        </div>
                        <div className="p-4 bg-slate-50 rounded-lg">
                            <div className="flex items-center gap-2 mb-1">
                                <FileEdit className="h-4 w-4 text-secondary" />
                                <p className="text-xs text-muted-foreground">Total Nilai</p>
                            </div>
                            <p className="text-3xl font-bold text-secondary">{stats.total_nilai}</p>
                            <p className="text-[11px] text-muted-foreground mt-1">Baris nilai yang tersimpan</p>
                        </div>
                        <div className="p-4 bg-yellow-50 rounded-lg">
                            <div className="flex items-center gap-2 mb-1">
                                <Edit3 className="h-4 w-4 text-warning" />
                                <p className="text-xs text-muted-foreground">Status Draft</p>
                            </div>
                            <p className="text-3xl font-bold text-warning">{stats.draft}</p>
                            <p className="text-[11px] text-muted-foreground mt-1">Nilai yang masih bisa diedit</p>
                        </div>
                        <div className="p-4 bg-green-50 rounded-lg">
                            <div className="flex items-center gap-2 mb-1">
                                <Lock className="h-4 w-4 text-success" />
                                <p className="text-xs text-muted-foreground">Status Final</p>
                            </div>
                            <p className="text-3xl font-bold text-success">{stats.final}</p>
                            <p className="text-[11px] text-muted-foreground mt-1">Nilai yang sudah dikunci</p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <Card>
                    <CardHeader>Ringkasan Kelulusan</CardHeader>
                    <CardContent>
                        <div className="space-y-3">
                            <div className="flex items-center gap-3 p-3 bg-green-50 rounded-lg">
                                <CheckCircle className="h-5 w-5 text-success shrink-0" />
                                <div className="flex-1 min-w-0">
                                    <p className="text-xs text-muted-foreground">Lulus (≥ KKM 70)</p>
                                    <p className="text-2xl font-bold text-success">{stats.lulus}</p>
                                </div>
                            </div>
                            <div className="flex items-center gap-3 p-3 bg-red-50 rounded-lg">
                                <XCircle className="h-5 w-5 text-danger shrink-0" />
                                <div className="flex-1 min-w-0">
                                    <p className="text-xs text-muted-foreground">Tidak Lulus (&lt; KKM 70)</p>
                                    <p className="text-2xl font-bold text-danger">{stats.tidak_lulus}</p>
                                </div>
                            </div>
                            <div className="flex items-center gap-3 p-3 bg-blue-50 rounded-lg">
                                <TrendingUp className="h-5 w-5 text-primary shrink-0" />
                                <div className="flex-1 min-w-0">
                                    <p className="text-xs text-muted-foreground">Rata-rata Nilai</p>
                                    <p className="text-2xl font-bold text-primary">{stats.rata_rata}</p>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card className="lg:col-span-2">
                    <CardHeader>Status per Mengajar</CardHeader>
                    <CardContent>
                        {per_combo_stats.length === 0 ? (
                            <p className="text-sm text-muted-foreground text-center py-4">
                                Belum ada kombinasi mengajar. Hubungi admin.
                            </p>
                        ) : (
                            <div className="overflow-x-auto -mx-2">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="text-secondary">
                                            <th className="px-2 py-2 text-left text-xs font-bold uppercase tracking-wide">Kelas</th>
                                            <th className="px-2 py-2 text-left text-xs font-bold uppercase tracking-wide">Mata Pelajaran</th>
                                            <th className="px-2 py-2 text-center text-xs font-bold uppercase tracking-wide">Siswa</th>
                                            <th className="px-2 py-2 text-center text-xs font-bold uppercase tracking-wide">Input</th>
                                            <th className="px-2 py-2 text-center text-xs font-bold uppercase tracking-wide">Status</th>
                                            <th className="px-2 py-2 text-right text-xs font-bold uppercase tracking-wide w-20">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100">
                                        {per_combo_stats.map((c) => {
                                            const state = comboStateLabel(c);

                                            return (
                                                <tr key={c.id_mengajar} className="hover:bg-blue-50/50">
                                                    <td className="px-2 py-2">
                                                        <Badge variant="info">{c.kelas}</Badge>
                                                    </td>
                                                    <td className="px-2 py-2 font-medium">{c.mata_pelajaran}</td>
                                                    <td className="px-2 py-2 text-center font-mono">{c.jumlah_siswa}</td>
                                                    <td className="px-2 py-2 text-center font-mono">{c.jumlah_input}/{c.jumlah_siswa}</td>
                                                    <td className="px-2 py-2 text-center">
                                                        <Badge variant={state.variant}>{state.label}</Badge>
                                                    </td>
                                                    <td className="px-2 py-2 text-right">
                                                        <Link
                                                            href={`/guru/input-nilai?kelas=${encodeURIComponent(c.kelas)}&mata_pelajaran=${encodeURIComponent(c.mata_pelajaran)}`}
                                                            className="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium text-primary hover:bg-blue-100 rounded transition"
                                                            title="Buka Input Nilai"
                                                        >
                                                            Buka <ChevronRight className="h-3 w-3" />
                                                        </Link>
                                                    </td>
                                                </tr>
                                            );
                                        })}
                                    </tbody>
                                </table>
                            </div>
                        )}

                        {per_combo_stats.length > 0 && (
                            <div className="mt-4 pt-3 border-t border-border flex flex-wrap gap-2 text-xs text-muted-foreground">
                                <span className="inline-flex items-center gap-1">
                                    <CheckCircle className="h-3 w-3 text-success" />
                                    {comboFinal} combo Final
                                </span>
                                {comboSebagian > 0 && (
                                    <span className="inline-flex items-center gap-1">
                                        <Edit3 className="h-3 w-3 text-warning" />
                                        {comboSebagian} combo sebagian
                                    </span>
                                )}
                                {comboBelumInput > 0 && (
                                    <span className="inline-flex items-center gap-1">
                                        <AlertTriangle className="h-3 w-3 text-warning" />
                                        {comboBelumInput} combo belum input
                                    </span>
                                )}
                                {comboKosong > 0 && (
                                    <span className="inline-flex items-center gap-1">
                                        <BookOpen className="h-3 w-3 text-muted-foreground" />
                                        {comboKosong} combo kelas kosong
                                    </span>
                                )}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>

            <Card>
                <CardHeader>Menu Cepat</CardHeader>
                <CardContent className="space-y-3">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <Link href="/guru/input-nilai" className="flex items-center gap-3 p-3 rounded-lg border border-border hover:bg-surface transition">
                            <div className="p-2 bg-blue-100 text-primary rounded-lg">
                                <Edit3 className="h-5 w-5" />
                            </div>
                            <div>
                                <p className="text-sm font-semibold">Input Nilai</p>
                                <p className="text-xs text-muted-foreground">Input nilai untuk siswa di kelas & mapel yang Anda ajar</p>
                            </div>
                        </Link>
                        <Link href="/guru/rekap" className="flex items-center gap-3 p-3 rounded-lg border border-border hover:bg-surface transition">
                            <div className="p-2 bg-green-100 text-success rounded-lg">
                                <BookOpen className="h-5 w-5" />
                            </div>
                            <div>
                                <p className="text-sm font-semibold">Rekap Nilai</p>
                                <p className="text-xs text-muted-foreground">Lihat rekap nilai yang sudah diinput</p>
                            </div>
                        </Link>
                    </div>
                    <div className="p-3 bg-blue-50 border border-blue-200 rounded-lg flex items-start gap-2">
                        <AlertCircle className="h-4 w-4 text-primary mt-0.5 shrink-0" />
                        <p className="text-xs text-blue-800">
                            <strong>Tips:</strong> Setelah selesai input nilai untuk satu kelas + mata pelajaran, klik tombol <strong>"Validasi Final"</strong> di halaman Input Nilai untuk mengunci nilai. Nilai yang sudah Final tidak dapat diedit kecuali dibuka kunci oleh Admin.
                        </p>
                    </div>
                </CardContent>
            </Card>
        </Container>
    );
}

GuruDashboard.layout = { title: 'Dashboard Guru' };
