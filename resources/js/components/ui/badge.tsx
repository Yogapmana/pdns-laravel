import { cn } from '@/lib/utils';
import type { HTMLAttributes, ReactNode } from 'react';

type Variant = 'default' | 'success' | 'danger' | 'warning' | 'info' | 'neutral';

const variantClasses: Record<Variant, string> = {
    default: 'bg-blue-100 text-blue-800',
    success: 'bg-green-100 text-green-800',
    danger: 'bg-red-100 text-red-800',
    warning: 'bg-yellow-100 text-yellow-800',
    info: 'bg-sky-100 text-sky-800',
    neutral: 'bg-slate-100 text-slate-500',
};

export function Badge({ children, variant = 'default', className, ...props }: HTMLAttributes<HTMLSpanElement> & { variant?: Variant; children: ReactNode }) {
    return (
        <span
            className={cn(
                'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold whitespace-nowrap shrink-0',
                variantClasses[variant],
                className,
            )}
            {...props}
        >
            {children}
        </span>
    );
}
