import { clsx  } from 'clsx';
import type {ClassValue} from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function formatNumber(value: number | string | null | undefined, decimals = 2): string {
    if (value === null || value === undefined || value === '') {
return '—';
}

    const num = typeof value === 'string' ? parseFloat(value) : value;

    if (Number.isNaN(num)) {
return '—';
}

    return num.toFixed(decimals);
}

export function calculateNilaiAkhir(tugas: number | null, uts: number | null, uas: number | null): number | null {
    if (tugas === null || uts === null || uas === null) {
return null;
}

    if ([tugas, uts, uas].some((v) => v < 0 || v > 100)) {
return null;
}

    return (tugas * 0.3) + (uts * 0.3) + (uas * 0.4);
}

export function calculateStatusLulus(nilaiAkhir: number | null): 'Lulus' | 'Tidak Lulus' | null {
    if (nilaiAkhir === null) {
return null;
}

    return nilaiAkhir >= 70 ? 'Lulus' : 'Tidak Lulus';
}
