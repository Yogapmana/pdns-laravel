import { Link } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';
import { cn } from '@/lib/utils';
import type { HTMLAttributes, ReactNode } from 'react';

export function Card({ className, children, ...props }: HTMLAttributes<HTMLDivElement>) {
    return (
        <div
            className={cn('bg-white rounded-xl shadow-sm border border-border p-6', className)}
            {...props}
        >
            {children}
        </div>
    );
}

export function CardHeader({ children, action, className }: { children: ReactNode; action?: ReactNode; className?: string }) {
    return (
        <div className={cn('flex items-center justify-between px-6 py-4 border-b border-border', className)}>
            <h2 className="text-lg font-semibold text-navy">{children}</h2>
            {action}
        </div>
    );
}

export function CardContent({ className, children, ...props }: HTMLAttributes<HTMLDivElement>) {
    return (
        <div className={cn('p-6', className)} {...props}>
            {children}
        </div>
    );
}

export function PageHeader({ title, description, action, className }: { title: React.ReactNode; description?: React.ReactNode; action?: React.ReactNode; className?: string }) {
    return (
        <div className={cn('flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6', className)}>
            <div>
                <h1 className="text-2xl font-bold text-navy">{title}</h1>
                {description && <div className="text-sm text-muted-foreground mt-1.5">{description}</div>}
            </div>
            {action && <div className="flex-shrink-0">{action}</div>}
        </div>
    );
}

export type StatCardColor = 'primary' | 'secondary' | 'neutral' | 'success' | 'warning' | 'danger' | 'accent';

export function StatCard({
    label,
    value,
    icon,
    description,
    color = 'primary',
    variant = 'default',
    className,
}: {
    label: string;
    value: string | number;
    icon?: ReactNode;
    description?: string;
    color?: StatCardColor;
    variant?: 'default' | 'colored';
    className?: string;
}) {
    const iconBoxClasses: Record<StatCardColor, string> = {
        primary: 'bg-blue-100 text-primary',
        secondary: 'bg-slate-200 text-slate-700',
        neutral: 'bg-slate-100 text-slate-700',
        success: 'bg-green-100 text-success',
        warning: 'bg-yellow-100 text-warning',
        danger: 'bg-red-100 text-danger',
        accent: 'bg-sky-100 text-accent',
    };

    const tileBgClasses: Record<StatCardColor, string> = {
        primary: 'bg-blue-50 text-primary',
        secondary: 'bg-slate-50 text-slate-700',
        neutral: 'bg-slate-50 text-slate-700',
        success: 'bg-green-50 text-success',
        warning: 'bg-yellow-50 text-warning',
        danger: 'bg-red-50 text-danger',
        accent: 'bg-sky-50 text-accent',
    };

    if (variant === 'colored') {
        return (
            <div className={cn('p-5 rounded-xl', tileBgClasses[color], className)}>
                {icon && (
                    <div className="flex items-center gap-2 mb-2">
                        <span className="[&>svg]:h-4 [&>svg]:w-4">{icon}</span>
                        <p className="text-sm font-medium text-muted-foreground">{label}</p>
                    </div>
                )}
                {!icon && (
                    <p className="text-sm font-medium text-muted-foreground mb-2">{label}</p>
                )}
                <p className="text-3xl font-bold">{value}</p>
                {description && <p className="text-xs text-muted-foreground mt-2">{description}</p>}
            </div>
        );
    }

    return (
        <div className={cn('bg-white rounded-xl shadow-sm border border-border p-6 flex items-center gap-4', className)}>
            {icon && (
                <div className={cn('p-3 rounded-lg shrink-0', iconBoxClasses[color])}>
                    {icon}
                </div>
            )}
            <div className="min-w-0">
                <p className="text-3xl font-bold text-navy">{value}</p>
                <p className="text-sm text-muted-foreground mt-0.5">{label}</p>
                {description && <p className="text-xs text-muted-foreground mt-1">{description}</p>}
            </div>
        </div>
    );
}

export function ActionCard({
    icon,
    title,
    description,
    href,
    variant = 'primary',
    external = false,
    method = 'get',
}: {
    icon: ReactNode;
    title: string;
    description: string;
    href: string;
    variant?: 'primary' | 'success' | 'warning' | 'danger';
    external?: boolean;
    method?: 'get' | 'post';
}) {
    const variantClasses = {
        primary: { border: 'hover:border-primary', iconBox: 'bg-blue-50 text-primary group-hover:bg-primary group-hover:text-white' },
        success: { border: 'hover:border-emerald-500', iconBox: 'bg-emerald-50 text-emerald-600 group-hover:bg-emerald-600 group-hover:text-white' },
        warning: { border: 'hover:border-warning', iconBox: 'bg-yellow-50 text-warning group-hover:bg-warning group-hover:text-white' },
        danger: { border: 'hover:border-danger', iconBox: 'bg-red-50 text-danger group-hover:bg-danger group-hover:text-white' },
    };
    const v = variantClasses[variant];

    const content = (
        <div className={cn('bg-white rounded-xl shadow-sm border border-border p-5 flex items-center gap-4 transition-all duration-200 cursor-pointer group hover:shadow-md', v.border)}>
            <div className={cn('p-3 rounded-xl shrink-0 transition-colors', v.iconBox)}>
                <span className="[&>svg]:h-6 [&>svg]:w-6 block">{icon}</span>
            </div>
            <div className="flex-1 min-w-0">
                <h2 className="text-base font-bold text-navy truncate">{title}</h2>
                <p className="text-sm text-muted-foreground line-clamp-1 sm:line-clamp-none mt-0.5">{description}</p>
            </div>
            <div className="shrink-0 text-muted-foreground group-hover:text-navy transition-colors">
                <ChevronRight className="h-5 w-5" />
            </div>
        </div>
    );

    return external ? (
        <a href={href} target="_blank" rel="noopener noreferrer" className="block outline-none">
            {content}
        </a>
    ) : (
        <Link href={href} method={method} className="block outline-none">
            {content}
        </Link>
    );
}

export function TableEmpty({ message, colSpan = 6 }: { message: string; colSpan?: number }) {
    return (
        <tr>
            <td colSpan={colSpan} className="px-4 py-12 text-center text-muted-foreground">
                {message}
            </td>
        </tr>
    );
}

export function DataTable({ children, className }: { children: ReactNode; className?: string }) {
    return (
        <div className={cn('rounded-xl border border-border bg-white shadow-sm overflow-hidden', className)}>
            {children}
        </div>
    );
}

export type MenuLinkColor = 'primary' | 'success' | 'warning' | 'danger' | 'accent';

export function MenuLink({
    href,
    icon,
    title,
    description,
    color = 'primary',
    external = false,
    prefetch = false,
}: {
    href: string;
    icon: ReactNode;
    title: string;
    description: string;
    color?: MenuLinkColor;
    external?: boolean;
    prefetch?: boolean;
}) {
    const iconBoxClasses: Record<MenuLinkColor, string> = {
        primary: 'bg-blue-100 text-primary group-hover:bg-primary group-hover:text-white',
        success: 'bg-green-100 text-success group-hover:bg-success group-hover:text-white',
        warning: 'bg-yellow-100 text-warning group-hover:bg-warning group-hover:text-white',
        danger: 'bg-red-100 text-danger group-hover:bg-danger group-hover:text-white',
        accent: 'bg-sky-100 text-accent group-hover:bg-accent group-hover:text-white',
    };
    const hoverBorderClasses: Record<MenuLinkColor, string> = {
        primary: 'hover:border-primary',
        success: 'hover:border-success',
        warning: 'hover:border-warning',
        danger: 'hover:border-danger',
        accent: 'hover:border-accent',
    };

    const className = cn(
        'group flex items-center gap-3 p-3 rounded-lg border border-border hover:bg-surface transition',
        hoverBorderClasses[color],
    );

    const content = (
        <>
            <div className={cn('p-2 rounded-lg transition shrink-0', iconBoxClasses[color])}>
                <span className="[&>svg]:h-5 [&>svg]:w-5 block">{icon}</span>
            </div>
            <div className="min-w-0">
                <p className="text-sm font-semibold text-navy">{title}</p>
                <p className="text-xs text-muted-foreground">{description}</p>
            </div>
        </>
    );

    if (external) {
        return (
            <a href={href} target="_blank" rel="noopener noreferrer" className={className}>
                {content}
            </a>
        );
    }

    return (
        <Link href={href} prefetch={prefetch} className={className}>
            {content}
        </Link>
    );
}

export function InputError({ message }: { message?: string }) {
    if (!message) return null;
    return <p className="mt-1 text-xs text-danger">{message}</p>;
}

export function Container({ children, className, ...props }: HTMLAttributes<HTMLDivElement>) {
    return (
        <div className={cn('space-y-6', className)} {...props}>
            {children}
        </div>
    );
}
