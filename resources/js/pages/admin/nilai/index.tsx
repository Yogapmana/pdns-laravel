import { router } from '@inertiajs/react';
import { Lock, Search, X, Loader2, Unlock, History, Clock, UserCog } from 'lucide-react';
import { useState } from 'react';
import { Alert } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Modal } from '@/components/ui/modal';
import { Select } from '@/components/ui/select';
import { InputError } from '@/components/ui/shared';
import { PageHeader, TableEmpty } from '@/components/ui/shared';
import { useFlashToast } from '@/hooks/use-flash-toast';
import { useInertiaSearch } from '@/hooks/use-inertia-search';

type Combo = {
    id_guru: number;
    nama_guru: string;
    kelas: string;
    mata_pelajaran: string;
    total_siswa: number;
    validated_at: string | null;
};

type Log = {
    id: number;
    admin_name: string;
    nama_guru: string;
    kelas: string;
    mata_pelajaran: string;
    affected_rows: number;
    reason: string;
    created_at: string | null;
};

type Props = {
    combos: Combo[];
    logs: Log[];
    kelas_options: string[];
    filters: { search: string | null; kelas: string | null };
    errors?: Record<string, string>;
};

function formatTanggalIndo(iso: string | null): string {
    if (!iso) {
return '—';
}

    const d = new Date(iso);

    if (isNaN(d.getTime())) {
return '—';
}

    return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) +
        ' ' +
        d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
}

export default function AdminNilaiIndex({ combos, logs, kelas_options, filters, errors: serverErrors }: Props) {
    useFlashToast();
    const { filters: state, loading, hasFilter, setFilter, reset } = useInertiaSearch({
        url: '/admin/nilai',
        initialFilters: { search: filters.search, kelas: filters.kelas },
        only: ['combos', 'logs', 'kelas_options', 'filters'],
    });

    const [unlockTarget, setUnlockTarget] = useState<Combo | null>(null);
    const [reason, setReason] = useState('');
    const [submitting, setSubmitting] = useState(false);
    const [reasonError, setReasonError] = useState<string | null>(null);

    function openModal(c: Combo) {
        setUnlockTarget(c);
        setReason('');
        setReasonError(null);
    }

    function closeModal() {
        if (submitting) {
return;
}

        setUnlockTarget(null);
        setReason('');
        setReasonError(null);
    }

    function doUnlock() {
        if (!unlockTarget) {
return;
}

        if (reason.trim().length < 10) {
            setReasonError('Alasan minimal 10 karakter.');

            return;
        }

        setSubmitting(true);
        router.post(
            '/admin/nilai/unlock',
            {
                id_guru: unlockTarget.id_guru,
                kelas: unlockTarget.kelas,
                mata_pelajaran: unlockTarget.mata_pelajaran,
                reason: reason.trim(),
            },
            {
                preserveScroll: true,
                onFinish: () => {
                    setSubmitting(false);
                    setUnlockTarget(null);
                    setReason('');
                    setReasonError(null);
                },
            },
        );
    }

    const reasonMinOk = reason.trim().length >= 10;

    return (
        <div className="space-y-6">
            <PageHeader
                title="Manajemen Nilai"
                description="Buka kunci nilai berstatus Final untuk memperbolehkan guru melakukan perubahan. Setiap tindakan dicatat di log audit."
            />

            <Alert variant="info">
                Fitur ini akan mengembalikan nilai berstatus <strong>Final</strong> ke status <strong>Draft</strong>, sehingga guru
                mata pelajaran terkait dapat mengeditnya kembali. Tindakan ini <strong>tidak menghapus data</strong>, hanya
                membuka kunci edit dan akan tercatat di log audit (siapa, kapan, berapa baris, alasan).
            </Alert>

            <Card className="p-0">
                <div className="p-4 border-b border-border flex flex-col md:flex-row gap-3">
                    <div className="relative flex-1">
                        <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                        <Input
                            type="search"
                            placeholder="Cari nama guru, mata pelajaran, atau kelas..."
                            value={state.search}
                            onChange={(e) => setFilter('search', e.target.value)}
                            className="pl-9 pr-9"
                        />
                        {loading ? (
                            <Loader2 className="absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground animate-spin" />
                        ) : state.search ? (
                            <button
                                type="button"
                                onClick={() => setFilter('search', '')}
                                className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                                aria-label="Bersihkan pencarian"
                            >
                                <X className="h-4 w-4" />
                            </button>
                        ) : null}
                    </div>
                    <Select value={state.kelas} onChange={(e) => setFilter('kelas', e.target.value)} className="md:w-48">
                        <option value="">Semua Kelas</option>
                        {kelas_options.map((k) => (
                            <option key={k} value={k}>
                                {k}
                            </option>
                        ))}
                    </Select>
                    {hasFilter && (
                        <Button onClick={reset} variant="outline">
                            <X className="h-4 w-4" />
                            Reset
                        </Button>
                    )}
                </div>

                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="bg-primary text-white">
                                <th className="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide">Guru</th>
                                <th className="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide">Mata Pelajaran</th>
                                <th className="px-4 py-3 text-center text-xs font-bold uppercase tracking-wide">Kelas</th>
                                <th className="px-4 py-3 text-center text-xs font-bold uppercase tracking-wide">Siswa</th>
                                <th className="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide">Divalidasi</th>
                                <th className="px-4 py-3 text-center text-xs font-bold uppercase tracking-wide w-44">Aksi</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {combos.length === 0 ? (
                                <TableEmpty
                                    message={
                                        hasFilter
                                            ? 'Tidak ada combo Final yang cocok dengan filter.'
                                            : 'Tidak ada nilai berstatus Final saat ini. Guru belum memvalidasi nilai ke Final.'
                                    }
                                    colSpan={6}
                                />
                            ) : (
                                combos.map((c, i) => (
                                    <tr key={`${c.id_guru}-${c.kelas}-${c.mata_pelajaran}`} className={`${i % 2 === 0 ? 'bg-white' : 'bg-surface'} hover:bg-blue-50 transition-colors`}>
                                        <td className="px-4 py-3 font-medium">{c.nama_guru}</td>
                                        <td className="px-4 py-3">{c.mata_pelajaran}</td>
                                        <td className="px-4 py-3 text-center">
                                            <Badge variant="info">{c.kelas}</Badge>
                                        </td>
                                        <td className="px-4 py-3 text-center font-mono">{c.total_siswa}</td>
                                        <td className="px-4 py-3 text-xs text-muted-foreground">
                                            <span className="inline-flex items-center gap-1">
                                                <Clock className="h-3 w-3" />
                                                {formatTanggalIndo(c.validated_at)}
                                            </span>
                                        </td>
                                        <td className="px-4 py-3 text-center">
                                            <Button size="sm" variant="danger" onClick={() => openModal(c)}>
                                                <Unlock className="h-3 w-3" />
                                                Buka Kunci
                                            </Button>
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>
            </Card>

            <Card className="p-0">
                <div className="px-6 py-4 border-b border-border flex items-center gap-2">
                    <History className="h-4 w-4 text-primary" />
                    <h2 className="text-lg font-semibold text-navy">Log Pembukaan Kunci (10 Terbaru)</h2>
                </div>
                {logs.length === 0 ? (
                    <div className="p-8 text-center text-muted-foreground text-sm">
                        Belum ada tindakan pembukaan kunci nilai.
                    </div>
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="bg-slate-50 text-secondary">
                                    <th className="px-4 py-2.5 text-left text-xs font-bold uppercase tracking-wide">Waktu</th>
                                    <th className="px-4 py-2.5 text-left text-xs font-bold uppercase tracking-wide">Admin</th>
                                    <th className="px-4 py-2.5 text-left text-xs font-bold uppercase tracking-wide">Target</th>
                                    <th className="px-4 py-2.5 text-center text-xs font-bold uppercase tracking-wide">Baris</th>
                                    <th className="px-4 py-2.5 text-left text-xs font-bold uppercase tracking-wide">Alasan</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {logs.map((l) => (
                                    <tr key={l.id} className="hover:bg-blue-50/50 transition-colors">
                                        <td className="px-4 py-3 text-xs whitespace-nowrap">{formatTanggalIndo(l.created_at)}</td>
                                        <td className="px-4 py-3 text-xs">
                                            <span className="inline-flex items-center gap-1">
                                                <UserCog className="h-3 w-3 text-muted-foreground" />
                                                {l.admin_name}
                                            </span>
                                        </td>
                                        <td className="px-4 py-3 text-xs">
                                            <div className="font-medium">{l.nama_guru}</div>
                                            <div className="text-muted-foreground">
                                                {l.mata_pelajaran} · <Badge variant="info">{l.kelas}</Badge>
                                            </div>
                                        </td>
                                        <td className="px-4 py-3 text-center">
                                            <Badge variant={l.affected_rows > 0 ? 'success' : 'neutral'}>{l.affected_rows}</Badge>
                                        </td>
                                        <td className="px-4 py-3 text-xs text-secondary max-w-md">
                                            <p className="line-clamp-2">{l.reason}</p>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </Card>

            <Modal
                open={!!unlockTarget}
                onClose={closeModal}
                title="Konfirmasi Buka Kunci Nilai"
                description={
                    unlockTarget
                        ? `Buka kunci nilai ${unlockTarget.mata_pelajaran} kelas ${unlockTarget.kelas} (guru: ${unlockTarget.nama_guru})?`
                        : ''
                }
                footer={
                    <>
                        <Button variant="outline" onClick={closeModal} disabled={submitting}>
                            Batal
                        </Button>
                        <Button variant="danger" onClick={doUnlock} disabled={!reasonMinOk || submitting}>
                            {submitting ? (
                                <>
                                    <Loader2 className="h-3 w-3 animate-spin" />
                                    Memproses...
                                </>
                            ) : (
                                <>
                                    <Lock className="h-3 w-3" />
                                    Buka Kunci
                                </>
                            )}
                        </Button>
                    </>
                }
            >
                <div className="space-y-3">
                    <Alert variant="warning">
                        <p className="text-xs font-normal">
                            <strong>Peringatan:</strong> Guru akan dapat mengedit nilai-nilai ini kembali. Tindakan ini akan dicatat di
                            log audit dengan alasan yang Anda berikan.
                        </p>
                    </Alert>
                    <div>
                        <label htmlFor="reason" className="block text-sm font-medium text-secondary mb-2">
                            Alasan Pembukaan Kunci <span className="text-danger">*</span>
                        </label>
                        <textarea
                            id="reason"
                            rows={4}
                            value={reason}
                            onChange={(e) => {
                                setReason(e.target.value);

                                if (reasonError) {
setReasonError(null);
}
                            }}
                            placeholder="Contoh: Koreksi nilai UAS karena ada kesalahan input, akan diedit ulang."
                            className="w-full border border-border rounded-lg px-3 py-2 text-sm text-foreground bg-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary placeholder:text-muted-foreground"
                        />
                        <div className="flex items-center justify-between mt-1">
                            <p className="text-xs text-muted-foreground">Minimal 10 karakter, akan tercatat di log audit.</p>
                            <p className={`text-xs ${reasonMinOk ? 'text-success' : 'text-muted-foreground'}`}>
                                {reason.trim().length}/10
                            </p>
                        </div>
                        <InputError message={reasonError ?? serverErrors?.reason} />
                    </div>
                </div>
            </Modal>
        </div>
    );
}

AdminNilaiIndex.layout = { title: 'Manajemen Nilai' };
