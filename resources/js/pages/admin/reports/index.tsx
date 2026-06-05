import { router } from '@inertiajs/react';
import { Search, FileDown, Globe } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Select } from '@/components/ui/select';
import { Container, PageHeader } from '@/components/ui/shared';
import { useFlashToast } from '@/hooks/use-flash-toast';

type Props = { daftar_kelas: string[] };

export default function ReportsIndex({ daftar_kelas }: Props) {
    useFlashToast();
    const [kelas, setKelas] = useState('');

    function preview() {
        if (!kelas) {
return;
}

        router.get('/admin/laporan/preview', { kelas });
    }

    function exportPdf() {
        if (!kelas) {
return;
}

        window.location.href = `/admin/laporan/export/pdf?kelas=${encodeURIComponent(kelas)}`;
    }

    function exportHtml() {
        if (!kelas) {
return;
}

        window.location.href = `/admin/laporan/export/html?kelas=${encodeURIComponent(kelas)}`;
    }

    return (
        <Container>
            <PageHeader title="Laporan Nilai" description="Generate rekapitulasi nilai per kelas" />

            <Card className="max-w-2xl">
                <CardContent>
                    <div className="space-y-4">
                        <div>
                            <label htmlFor="kelas" className="block text-sm font-medium text-secondary mb-2">
                                Pilih Kelas <span className="text-danger">*</span>
                            </label>
                            <Select id="kelas" value={kelas} onChange={(e) => setKelas(e.target.value)}>
                                <option value="">Pilih kelas...</option>
                                {daftar_kelas.map((k) => (
                                    <option key={k} value={k}>{k}</option>
                                ))}
                            </Select>
                        </div>

                        <div className="flex flex-wrap gap-2 pt-4 border-t border-border">
                            <Button onClick={preview} disabled={!kelas}>
                                <Search className="h-4 w-4" />
                                Generate Laporan
                            </Button>
                            <Button onClick={exportPdf} disabled={!kelas} variant="success">
                                <FileDown className="h-4 w-4" />
                                Ekspor PDF
                            </Button>
                            <Button onClick={exportHtml} disabled={!kelas} variant="outline">
                                <Globe className="h-4 w-4" />
                                Ekspor HTML
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <div className="bg-blue-50 border-l-4 border-primary p-4 rounded-lg text-sm text-blue-800">
                <p className="font-medium">Petunjuk:</p>
                <ul className="list-disc list-inside mt-1 space-y-1 text-xs">
                    <li>Pilih kelas terlebih dahulu, lalu klik "Generate Laporan" untuk preview di browser</li>
                    <li>"Ekspor PDF" akan mengunduh file PDF dalam format landscape A4</li>
                    <li>"Ekspor HTML" akan mengunduh file HTML yang bisa dibuka di browser / dicetak ke PDF</li>
                </ul>
            </div>
        </Container>
    );
}

ReportsIndex.layout = { title: 'Laporan Nilai' };
