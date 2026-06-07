import { router } from '@inertiajs/react';
import {
    Lock,
    Search,
    X,
    Loader2,
    Unlock,
    History,
    Clock,
    UserCog,
} from 'lucide-react';
import { useState } from 'react';
import { Alert } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Modal } from '@/components/ui/modal';
import { PaginationFooter } from '@/components/ui/pagination';
import { Select } from '@/components/ui/select';
import {
    DataTable,
    InputError,
    PageHeader,
    TableEmpty,
} from '@/components/ui/shared';
import { useFlashToast } from '@/hooks/use-flash-toast';
import { useInertiaSearch } from '@/hooks/use-inertia-search';

type Combo = {
    id_guru: number;
    kelas_id: number;
    mata_pelajaran_id: number;
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

type Paginated<T> = {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    from: number;
    to: number;
    total: number;
};

type Props = {
    combos: Paginated<Combo>;
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

    return (
        d.toLocaleDateString('id-ID', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
        }) +
        ' ' +
        d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
    );
}

export default function AdminNilaiIndex({
    combos,
    logs,
    kelas_options,
    filters,
    errors: serverErrors,
}: Props) {
    useFlashToast();
    const {
        filters: state,
        loading,
        hasFilter,
        setFilter,
        reset,
    } = useInertiaSearch({
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
                kelas_id: unlockTarget.kelas_id,
                mata_pelajaran_id: unlockTarget.mata_pelajaran_id,
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
                description={`${combos.total} combo nilai Final`}
            />

            <div className="mb-4 flex flex-col gap-3 rounded-xl border border-border bg-white p-3 shadow-sm md:flex-row">
                <div className="relative flex-1">
                    <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        type="search"
                        placeholder="Cari nama guru, mata pelajaran, atau kelas..."
                        value={state.search}
                        onChange={(e) => setFilter('search', e.target.value)}
                        className="pr-9 pl-9"
                    />
                    {loading ? (
                        <Loader2 className="absolute top-1/2 right-3 h-4 w-4 -translate-y-1/2 animate-spin text-muted-foreground" />
                    ) : state.search ? (
                        <button
                            type="button"
                            onClick={() => setFilter('search', '')}
                            className="absolute top-1/2 right-3 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                            aria-label="Bersihkan pencarian"
                        >
                            <X className="h-4 w-4" />
                        </button>
                    ) : null}
                </div>
                <Select
                    value={state.kelas}
                    onChange={(e) => setFilter('kelas', e.target.value)}
                    className="md:w-48"
                >
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

            <DataTable>
                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="bg-primary text-white">
                                <th className="px-4 py-3 text-left text-xs font-bold tracking-wide uppercase">
                                    Guru
                                </th>
                                <th className="px-4 py-3 text-left text-xs font-bold tracking-wide uppercase">
                                    Mata Pelajaran
                                </th>
                                <th className="px-4 py-3 text-center text-xs font-bold tracking-wide uppercase">
                                    Kelas
                                </th>
                                <th className="px-4 py-3 text-center text-xs font-bold tracking-wide uppercase">
                                    Siswa
                                </th>
                                <th className="px-4 py-3 text-left text-xs font-bold tracking-wide uppercase">
                                    Divalidasi
                                </th>
                                <th className="w-44 px-4 py-3 text-center text-xs font-bold tracking-wide uppercase">
                                    Aksi
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {combos.data.length === 0 ? (
                                <TableEmpty
                                    message={
                                        hasFilter
                                            ? 'Tidak ada combo Final yang cocok dengan filter.'
                                            : 'Tidak ada nilai berstatus Final saat ini. Guru belum memvalidasi nilai ke Final.'
                                    }
                                    colSpan={6}
                                />
                            ) : (
                                combos.data.map((c, i) => (
                                    <tr
                                        key={`${c.id_guru}-${c.kelas_id}-${c.mata_pelajaran_id}`}
                                        className={`${i % 2 === 0 ? 'bg-white' : 'bg-surface'} transition-colors hover:bg-blue-50`}
                                    >
                                        <td className="px-4 py-3 font-medium">
                                            {c.nama_guru}
                                        </td>
                                        <td className="px-4 py-3">
                                            {c.mata_pelajaran}
                                        </td>
                                        <td className="px-4 py-3 text-center">
                                            <Badge variant="info">
                                                {c.kelas}
                                            </Badge>
                                        </td>
                                        <td className="px-4 py-3 text-center font-mono">
                                            {c.total_siswa}
                                        </td>
                                        <td className="px-4 py-3 text-xs text-muted-foreground">
                                            <span className="inline-flex items-center gap-1">
                                                <Clock className="h-3 w-3" />
                                                {formatTanggalIndo(
                                                    c.validated_at,
                                                )}
                                            </span>
                                        </td>
                                        <td className="px-4 py-3 text-center">
                                            <Button
                                                size="sm"
                                                variant="danger"
                                                onClick={() => openModal(c)}
                                            >
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
                <PaginationFooter
                    from={combos.from ?? 0}
                    to={combos.to ?? 0}
                    total={combos.total}
                    links={combos.links}
                />
            </DataTable>

            <DataTable>
                <div className="flex items-center gap-2 border-b border-border bg-slate-50/50 px-4 py-3">
                    <History className="h-4 w-4 text-primary" />
                    <h2 className="text-sm font-bold text-navy">
                        Log Pembukaan Kunci (10 Terbaru)
                    </h2>
                </div>
                {logs.length === 0 ? (
                    <div className="p-8 text-center text-sm text-muted-foreground">
                        Belum ada tindakan pembukaan kunci nilai.
                    </div>
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="bg-slate-50 text-secondary">
                                    <th className="px-4 py-2.5 text-left text-xs font-bold tracking-wide uppercase">
                                        Waktu
                                    </th>
                                    <th className="px-4 py-2.5 text-left text-xs font-bold tracking-wide uppercase">
                                        Admin
                                    </th>
                                    <th className="px-4 py-2.5 text-left text-xs font-bold tracking-wide uppercase">
                                        Target
                                    </th>
                                    <th className="px-4 py-2.5 text-center text-xs font-bold tracking-wide uppercase">
                                        Baris
                                    </th>
                                    <th className="px-4 py-2.5 text-left text-xs font-bold tracking-wide uppercase">
                                        Alasan
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {logs.map((l) => (
                                    <tr
                                        key={l.id}
                                        className="transition-colors hover:bg-blue-50/50"
                                    >
                                        <td className="px-4 py-3 text-xs whitespace-nowrap">
                                            {formatTanggalIndo(l.created_at)}
                                        </td>
                                        <td className="px-4 py-3 text-xs">
                                            <span className="inline-flex items-center gap-1">
                                                <UserCog className="h-3 w-3 text-muted-foreground" />
                                                {l.admin_name}
                                            </span>
                                        </td>
                                        <td className="px-4 py-3 text-xs">
                                            <div className="font-medium">
                                                {l.nama_guru}
                                            </div>
                                            <div className="text-muted-foreground">
                                                {l.mata_pelajaran} ·{' '}
                                                <Badge variant="info">
                                                    {l.kelas}
                                                </Badge>
                                            </div>
                                        </td>
                                        <td className="px-4 py-3 text-center">
                                            <Badge
                                                variant={
                                                    l.affected_rows > 0
                                                        ? 'success'
                                                        : 'neutral'
                                                }
                                            >
                                                {l.affected_rows}
                                            </Badge>
                                        </td>
                                        <td className="max-w-md px-4 py-3 text-xs text-secondary">
                                            <p className="line-clamp-2">
                                                {l.reason}
                                            </p>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </DataTable>

            <Modal
                open={!!unlockTarget}
                onClose={closeModal}
                title="Konfirmasi Buka Kunci Nilai"
                footer={
                    <>
                        <Button
                            variant="outline"
                            onClick={closeModal}
                            disabled={submitting}
                        >
                            Batal
                        </Button>
                        <Button
                            variant="danger"
                            onClick={doUnlock}
                            disabled={!reasonMinOk || submitting}
                        >
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
                        Nilai dapat diedit kembali oleh guru. Tindakan terekam
                        di log audit.
                    </Alert>
                    <div>
                        <Label htmlFor="reason">
                            Alasan Pembukaan Kunci{' '}
                            <span className="text-danger">*</span>
                        </Label>
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
                            className="w-full rounded-lg border border-border bg-white px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:border-primary focus:ring-2 focus:ring-primary focus:outline-none"
                        />
                        <div className="mt-1 flex items-center justify-between">
                            <p className="text-xs text-muted-foreground">
                                Minimal 10 karakter, akan tercatat di log audit.
                            </p>
                            <p
                                className={`text-xs ${reasonMinOk ? 'text-success' : 'text-muted-foreground'}`}
                            >
                                {reason.trim().length}/10
                            </p>
                        </div>
                        <InputError
                            message={reasonError ?? serverErrors?.reason}
                        />
                    </div>
                </div>
            </Modal>
        </div>
    );
}

AdminNilaiIndex.layout = { title: 'Manajemen Nilai' };
