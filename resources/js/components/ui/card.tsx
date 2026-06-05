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
