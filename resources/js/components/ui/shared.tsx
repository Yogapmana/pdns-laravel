import { Link } from '@inertiajs/react';
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

export function PageHeader({ title, description, action, className }: { title: string; description?: string; action?: React.ReactNode; className?: string }) {
    return (
        <div className={cn('flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6', className)}>
            <div>
                <h1 className="text-2xl font-bold text-navy">{title}</h1>
                {description && <p className="text-sm text-muted-foreground mt-1">{description}</p>}
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
            <div className={cn('p-4 rounded-lg', tileBgClasses[color], className)}>
                {icon && (
                    <div className="flex items-center gap-2 mb-1">
                        <span className="[&>svg]:h-4 [&>svg]:w-4">{icon}</span>
                        <p className="text-xs font-medium text-muted-foreground">{label}</p>
                    </div>
                )}
                {!icon && (
                    <p className="text-xs font-medium text-muted-foreground mb-1">{label}</p>
                )}
                <p className="text-3xl font-bold">{value}</p>
                {description && <p className="text-[11px] text-muted-foreground mt-1">{description}</p>}
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
                {description && <p className="text-[11px] text-muted-foreground mt-0.5">{description}</p>}
            </div>
        </div>
    );
}

export function ActionCard({
    icon,
    title,
    description,
    actionLabel,
    href,
    variant = 'primary',
    external = false,
    method = 'get',
}: {
    icon: ReactNode;
    title: string;
    description: string;
    actionLabel: string;
    href: string;
    variant?: 'primary' | 'success' | 'warning' | 'danger';
    external?: boolean;
    method?: 'get' | 'post';
}) {
    const variantClasses: Record<typeof variant, { iconBox: string; button: string }> = {
        primary: { iconBox: 'bg-blue-100 text-primary', button: 'bg-primary text-white hover:bg-primary-700' },
        success: { iconBox: 'bg-emerald-100 text-emerald-600', button: 'bg-emerald-600 text-white hover:bg-emerald-700' },
        warning: { iconBox: 'bg-yellow-100 text-warning', button: 'bg-warning text-white hover:bg-yellow-600' },
        danger: { iconBox: 'bg-red-100 text-danger', button: 'bg-danger text-white hover:bg-red-700' },
    };
    const v = variantClasses[variant];

    const buttonClass = cn('inline-flex items-center gap-2 px-5 py-2.5 rounded-lg text-sm font-semibold transition', v.button);

    const iconBox = (
        <div className={cn('p-4 rounded-full shrink-0', v.iconBox)}>
            <span className="[&>svg]:h-8 [&>svg]:w-8 block">{icon}</span>
        </div>
    );

    const button = external ? (
        <a href={href} target="_blank" rel="noopener noreferrer" className={buttonClass}>
            <span className="[&>svg]:h-4 [&>svg]:w-4">{icon}</span>
            {actionLabel}
        </a>
    ) : (
        <Link href={href} method={method} className={buttonClass}>
            <span className="[&>svg]:h-4 [&>svg]:w-4">{icon}</span>
            {actionLabel}
        </Link>
    );

    return (
        <Card>
            <CardContent>
                <div className="flex flex-col sm:flex-row items-center gap-4">
                    {iconBox}
                    <div className="flex-1 text-center sm:text-left">
                        <h2 className="text-lg font-bold text-navy">{title}</h2>
                        <p className="text-sm text-muted-foreground mt-1">{description}</p>
                    </div>
                    {button}
                </div>
            </CardContent>
        </Card>
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
