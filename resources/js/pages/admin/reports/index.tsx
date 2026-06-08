import { router } from '@inertiajs/react';
import {
    Search,
    FileDown,
    Globe,
    FileText,
    CheckSquare,
    Square,
} from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Container, PageHeader } from '@/components/ui/shared';
import { useFlashToast } from '@/hooks/use-flash-toast';
import { cn } from '@/lib/utils';

type Props = { daftar_kelas: { id: number; nama: string }[]; daftar_mapel: { id: number; nama: string }[] };

function buildUrl(endpoint: string, kelas: string[], mapel: string[]) {
    const search = new URLSearchParams();
    kelas.forEach((k) => search.append('kelas[]', k));
    mapel.forEach((m) => search.append('mata_pelajaran[]', m));

    return `${endpoint}?${search.toString()}`;
}

function toggle(list: string[], setter: (v: string[]) => void, value: string) {
    setter(
        list.includes(value)
            ? list.filter((v) => v !== value)
            : [...list, value],
    );
}

export default function ReportsIndex({ daftar_kelas, daftar_mapel }: Props) {
    useFlashToast();
    const [selectedKelas, setSelectedKelas] = useState<string[]>([]);
    const [selectedMapel, setSelectedMapel] = useState<string[]>([]);

    const kelasNamaList = daftar_kelas.map((k) => k.nama);
    const mapelNamaList = daftar_mapel.map((m) => m.nama);

    function selectAllKelas() {
        setSelectedKelas(
            selectedKelas.length === kelasNamaList.length
                ? []
                : [...kelasNamaList],
        );
    }

    function selectAllMapel() {
        setSelectedMapel(
            selectedMapel.length === mapelNamaList.length
                ? []
                : [...mapelNamaList],
        );
    }

    function preview() {
        if (selectedKelas.length === 0) {
            return;
        }

        router.get('/admin/laporan/preview', {
            kelas: selectedKelas,
            mata_pelajaran: selectedMapel,
        });
    }

    function exportTo(endpoint: string) {
        if (selectedKelas.length === 0) {
            return;
        }

        window.location.href = buildUrl(endpoint, selectedKelas, selectedMapel);
    }

    return (
        <Container>
            <PageHeader
                title="Laporan Nilai"
                description="Generate rekapitulasi nilai per kelas & mata pelajaran"
            />

            <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
                <Card className="shadow-sm border-slate-200/60">
                    <CardContent className="p-4">
                        <div className="mb-3 flex items-start justify-between">
                            <div>
                                <h3 className="text-base font-bold text-navy flex items-center gap-1.5">
                                    Kelas <span className="text-danger">*</span>
                                </h3>
                                <p className="text-sm text-muted-foreground mt-0.5">
                                    Pilih 1 atau lebih kelas.
                                </p>
                            </div>
                            <Button
                                onClick={selectAllKelas}
                                variant="outline"
                                size="sm"
                                className="text-xs h-8"
                            >
                                {selectedKelas.length === kelasNamaList.length
                                    ? 'Hapus Semua'
                                    : 'Pilih Semua'}
                            </Button>
                        </div>
                        <div className="grid grid-cols-2 sm:grid-cols-3 gap-3">
                            {daftar_kelas.map((k) => {
                                const isSelected = selectedKelas.includes(k.nama);

                                return (
                                    <button
                                        key={k.id}
                                        type="button"
                                        onClick={() =>
                                            toggle(
                                                selectedKelas,
                                                setSelectedKelas,
                                                k.nama,
                                            )
                                        }
                                        className={cn(
                                            'flex items-center gap-2 rounded-lg border p-2.5 text-left text-sm transition',
                                            isSelected
                                                ? 'border-primary bg-blue-50 font-semibold text-primary'
                                                : 'border-border hover:bg-surface',
                                        )}
                                    >
                                        {isSelected ? (
                                            <CheckSquare className="h-4 w-4 flex-shrink-0" />
                                        ) : (
                                            <Square className="h-4 w-4 flex-shrink-0" />
                                        )}
                                        {k.nama}
                                    </button>
                                );
                            })}
                        </div>
                    </CardContent>
                </Card>

                <Card className="shadow-sm border-slate-200/60">
                    <CardContent className="p-4">
                        <div className="mb-3 flex items-start justify-between">
                            <div>
                                <h3 className="text-base font-bold text-navy">
                                    Mata Pelajaran <span className="text-muted-foreground font-normal text-sm">(opsional)</span>
                                </h3>
                                <p className="text-sm text-muted-foreground mt-0.5">
                                    Kosongkan untuk semua mapel.
                                </p>
                            </div>
                            <Button
                                onClick={selectAllMapel}
                                variant="outline"
                                size="sm"
                                className="text-xs h-8"
                            >
                                {selectedMapel.length === mapelNamaList.length
                                    ? 'Hapus Semua'
                                    : 'Pilih Semua'}
                            </Button>
                        </div>
                        <div className="grid max-h-[300px] grid-cols-1 sm:grid-cols-2 gap-3 overflow-y-auto pr-2 pb-1">
                            {daftar_mapel.map((m) => {
                                const isSelected = selectedMapel.includes(m.nama);

                                return (
                                    <button
                                        key={m.id}
                                        type="button"
                                        onClick={() =>
                                            toggle(
                                                selectedMapel,
                                                setSelectedMapel,
                                                m.nama,
                                            )
                                        }
                                        className={cn(
                                            'flex items-center gap-2 rounded-lg border p-2.5 text-left text-sm transition',
                                            isSelected
                                                ? 'border-primary bg-blue-50 font-semibold text-primary'
                                                : 'border-border hover:bg-surface',
                                        )}
                                    >
                                        {isSelected ? (
                                            <CheckSquare className="h-4 w-4 flex-shrink-0" />
                                        ) : (
                                            <Square className="h-4 w-4 flex-shrink-0" />
                                        )}
                                        {m.nama}
                                    </button>
                                );
                            })}
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div className="flex flex-wrap items-center gap-3 mt-2">
                <Button
                    onClick={preview}
                    disabled={selectedKelas.length === 0}
                >
                    <Search className="h-4 w-4 mr-1.5" />
                    Generate Laporan
                </Button>
                
                <span className="mx-1 border-l border-slate-300 h-6" />

                <Button
                    onClick={() =>
                        exportTo('/admin/laporan/export/pdf')
                    }
                    disabled={selectedKelas.length === 0}
                    variant="danger"
                    className="shadow-sm"
                >
                    <FileDown className="h-4 w-4 mr-1.5" />
                    PDF
                </Button>
                <Button
                    onClick={() =>
                        exportTo('/admin/laporan/export/csv')
                    }
                    disabled={selectedKelas.length === 0}
                    variant="success"
                    className="shadow-sm"
                >
                    <FileText className="h-4 w-4 mr-1.5" />
                    CSV
                </Button>
                <Button
                    onClick={() =>
                        exportTo('/admin/laporan/export/html')
                    }
                    disabled={selectedKelas.length === 0}
                    variant="outline"
                    className="shadow-sm bg-white"
                >
                    <Globe className="h-4 w-4 mr-1.5" />
                    HTML
                </Button>
            </div>
        </Container>
    );
}

ReportsIndex.layout = { title: 'Laporan Nilai' };
