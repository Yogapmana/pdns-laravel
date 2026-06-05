import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type PaginationProps = {
    links: PaginationLink[];
    className?: string;
};

function isPreviousLink(label: string): boolean {
    return /pagination\.previous|&lsaquo;|&laquo;|Previous/i.test(label);
}

function isNextLink(label: string): boolean {
    return /pagination\.next|&rsaquo;|&raquo;|Next/i.test(label);
}

function PaginationArrow({ direction, disabled }: { direction: 'prev' | 'next'; disabled?: boolean }) {
    const Icon = direction === 'prev' ? ChevronLeft : ChevronRight;
    const label = direction === 'prev' ? 'Sebelumnya' : 'Selanjutnya';

    return (
        <span className="inline-flex items-center gap-1">
            {direction === 'prev' && <Icon className="h-4 w-4" />}
            <span>{label}</span>
            {direction === 'next' && <Icon className="h-4 w-4" />}
        </span>
    );
}

function renderLabel(label: string) {
    if (isPreviousLink(label)) {
        return <PaginationArrow direction="prev" />;
    }
    if (isNextLink(label)) {
        return <PaginationArrow direction="next" />;
    }
    return label.replace(/&[lr]saquo;|&[lr]quo;/g, '').trim();
}

export function Pagination({ links, className }: PaginationProps) {
    if (!links || links.length === 0) return null;

    return (
        <nav className={cn('flex items-center gap-1', className)}>
            {links.map((link, i) => {
                const isPrevious = isPreviousLink(link.label);
                const isNext = isNextLink(link.label);

                if (link.url === null) {
                    return (
                        <span
                            key={i}
                            className={cn(
                                'px-3 py-1.5 text-sm rounded-md text-muted-foreground cursor-not-allowed opacity-50 inline-flex items-center',
                                (isPrevious || isNext) && 'gap-1',
                            )}
                            aria-label={isPrevious ? 'Halaman sebelumnya' : isNext ? 'Halaman selanjutnya' : undefined}
                        >
                            {renderLabel(link.label)}
                        </span>
                    );
                }
                return (
                    <Link
                        key={i}
                        href={link.url}
                        preserveScroll
                        className={cn(
                            'px-3 py-1.5 text-sm rounded-md transition inline-flex items-center',
                            (isPrevious || isNext) && 'gap-1',
                            link.active
                                ? 'bg-primary text-white font-semibold'
                                : 'text-secondary hover:bg-surface border border-border',
                        )}
                        aria-label={isPrevious ? 'Halaman sebelumnya' : isNext ? 'Halaman selanjutnya' : undefined}
                    >
                        {renderLabel(link.label)}
                    </Link>
                );
            })}
        </nav>
    );
}

export function PaginationFooter({
    from,
    to,
    total,
    links,
    className,
}: {
    from: number;
    to: number;
    total: number;
    links: PaginationLink[];
    className?: string;
}) {
    return (
        <div className={cn('px-6 py-4 border-t border-border flex flex-col sm:flex-row items-center justify-between gap-3', className)}>
            <p className="text-sm text-muted-foreground">
                Menampilkan <span className="font-semibold text-secondary">{from}</span>–
                <span className="font-semibold text-secondary">{to}</span> dari{' '}
                <span className="font-semibold text-secondary">{total}</span>
            </p>
            <Pagination links={links} />
        </div>
    );
}
