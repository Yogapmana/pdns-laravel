import { cn } from '@/lib/utils';
import { forwardRef, type InputHTMLAttributes } from 'react';

export const Input = forwardRef<HTMLInputElement, InputHTMLAttributes<HTMLInputElement>>(function Input(
    { className, ...props },
    ref,
) {
    return (
        <input
            ref={ref}
            className={cn(
                'w-full border border-border rounded-lg px-3 py-2 text-sm text-foreground bg-white',
                'focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary',
                'disabled:bg-surface disabled:cursor-not-allowed',
                'placeholder:text-muted-foreground',
                className,
            )}
            {...props}
        />
    );
});
