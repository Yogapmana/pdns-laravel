import { cn } from '@/lib/utils';
import { forwardRef, type SelectHTMLAttributes } from 'react';

export const Select = forwardRef<HTMLSelectElement, SelectHTMLAttributes<HTMLSelectElement>>(function Select(
    { className, children, ...props },
    ref,
) {
    return (
        <select
            ref={ref}
            className={cn(
                'w-full border border-border rounded-lg px-3 py-2 text-sm text-foreground bg-white',
                'focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary',
                'disabled:bg-surface disabled:cursor-not-allowed',
                className,
            )}
            {...props}
        >
            {children}
        </select>
    );
});
