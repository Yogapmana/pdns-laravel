import { router } from '@inertiajs/react';
import {
    Search,
    FileDown,
    Globe,
    FileSpreadsheet,
    FileText,
    CheckSquare,
    Square,
} from 'lucide-react';
import { useState } from 'react';
import { Alert } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Container, PageHeader } from '@/components/ui/shared';
import { useFlashToast } from '@/hooks/use-flash-toast';
import { cn } from '@/lib/utils';

type Props = { daftar_kelas: string[]; daftar_mapel: string[] };

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

    function selectAllKelas() {
        setSelectedKelas(
            selectedKelas.length === daftar_kelas.length
                ? []
                : [...daftar_kelas],
        );
    }

    function selectAllMapel() {
        setSelectedMapel(
            selectedMapel.length === daftar_mapel.length
                ? []
                : [...daftar_mapel],
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
                <Card>
                    <CardContent>
                        <div className="mb-3 flex items-center justify-between">
                            <div>
                                <h3 className="text-sm font-semibold text-navy">
                                    Kelas <span className="text-danger">*</span>
                                </h3>
                                <p className="text-xs text-muted-foreground">
                                    Pilih 1 atau lebih kelas.
                                </p>
                            </div>
                            <Button
                                onClick={selectAllKelas}
                                variant="outline"
                                size="sm"
                            >
                                {selectedKelas.length === daftar_kelas.length
                                    ? 'Hapus Semua'
                                    : 'Pilih Semua'}
                            </Button>
                        </div>
                        <div className="grid grid-cols-2 gap-2">
                            {daftar_kelas.map((k) => {
                                const isSelected = selectedKelas.includes(k);

                                return (
                                    <button
                                        key={k}
                                        type="button"
                                        onClick={() =>
                                            toggle(
                                                selectedKelas,
                                                setSelectedKelas,
                                                k,
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
                                        {k}
                                    </button>
                                );
                            })}
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent>
                        <div className="mb-3 flex items-center justify-between">
                            <div>
                                <h3 className="text-sm font-semibold text-navy">
                                    Mata Pelajaran (opsional)
                                </h3>
                                <p className="text-xs text-muted-foreground">
                                    Kosongkan untuk semua mapel.
                                </p>
                            </div>
                            <Button
                                onClick={selectAllMapel}
                                variant="outline"
                                size="sm"
                            >
                                {selectedMapel.length === daftar_mapel.length
                                    ? 'Hapus Semua'
                                    : 'Pilih Semua'}
                            </Button>
                        </div>
                        <div className="grid max-h-64 grid-cols-1 gap-2 overflow-y-auto">
                            {daftar_mapel.map((m) => {
                                const isSelected = selectedMapel.includes(m);

                                return (
                                    <button
                                        key={m}
                                        type="button"
                                        onClick={() =>
                                            toggle(
                                                selectedMapel,
                                                setSelectedMapel,
                                                m,
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
                                        {m}
                                    </button>
                                );
                            })}
                        </div>
                    </CardContent>
                </Card>
            </div>

            <Card>
                <CardContent>
                    <div className="flex flex-wrap gap-2">
                        <Button
                            onClick={preview}
                            disabled={selectedKelas.length === 0}
                        >
                            <Search className="h-4 w-4" />
                            Generate Laporan
                        </Button>
                        <span className="mx-1 border-l border-border" />
                        <Button
                            onClick={() =>
                                exportTo('/admin/laporan/export/pdf')
                            }
                            disabled={selectedKelas.length === 0}
                            variant="success"
                        >
                            <FileDown className="h-4 w-4" />
                            PDF
                        </Button>
                        <Button
                            onClick={() =>
                                exportTo('/admin/laporan/export/xlsx')
                            }
                            disabled={selectedKelas.length === 0}
                            variant="primary"
                        >
                            <FileSpreadsheet className="h-4 w-4" />
                            Excel
                        </Button>
                        <Button
                            onClick={() =>
                                exportTo('/admin/laporan/export/csv')
                            }
                            disabled={selectedKelas.length === 0}
                            variant="outline"
                        >
                            <FileText className="h-4 w-4" />
                            CSV
                        </Button>
                        <Button
                            onClick={() =>
                                exportTo('/admin/laporan/export/html')
                            }
                            disabled={selectedKelas.length === 0}
                            variant="outline"
                        >
                            <Globe className="h-4 w-4" />
                            HTML
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </Container>
    );
}

ReportsIndex.layout = { title: 'Laporan Nilai' };
