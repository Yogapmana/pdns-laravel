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

export function StatCard({ label, value, icon, color = 'primary' }: { label: string; value: string | number; icon?: ReactNode; color?: 'primary' | 'success' | 'warning' | 'danger' | 'accent' }) {
    const colorClasses = {
        primary: 'bg-blue-100 text-primary',
        success: 'bg-green-100 text-success',
        warning: 'bg-yellow-100 text-warning',
        danger: 'bg-red-100 text-danger',
        accent: 'bg-sky-100 text-accent',
    };

    return (
        <div className="bg-white rounded-xl shadow-sm border border-border p-6 flex items-center gap-4">
            {icon && (
                <div className={cn('p-3 rounded-lg', colorClasses[color])}>
                    {icon}
                </div>
            )}
            <div>
                <p className="text-3xl font-bold text-navy">{value}</p>
                <p className="text-sm text-muted-foreground mt-0.5">{label}</p>
            </div>
        </div>
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
