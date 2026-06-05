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

function parseLabel(label: string): string {
    return label
        .replace(/&laquo;/g, '«')
        .replace(/&raquo;/g, '»')
        .replace(/&lsaquo;/g, '‹')
        .replace(/&rsaquo;/g, '›')
        .replace(/Previous/g, 'Sebelumnya')
        .replace(/Next/g, 'Selanjutnya');
}

export function Pagination({ links, className }: PaginationProps) {
    if (!links || links.length === 0) return null;

    return (
        <nav className={cn('flex items-center gap-1', className)}>
            {links.map((link, i) => {
                if (link.url === null) {
                    return (
                        <span
                            key={i}
                            className="px-3 py-1.5 text-sm rounded-md text-muted-foreground cursor-not-allowed opacity-50"
                            dangerouslySetInnerHTML={{ __html: parseLabel(link.label) }}
                        />
                    );
                }
                return (
                    <Link
                        key={i}
                        href={link.url}
                        preserveScroll
                        className={cn(
                            'px-3 py-1.5 text-sm rounded-md transition',
                            link.active
                                ? 'bg-primary text-white font-semibold'
                                : 'text-secondary hover:bg-surface border border-border',
                        )}
                        dangerouslySetInnerHTML={{ __html: parseLabel(link.label) }}
                    />
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
