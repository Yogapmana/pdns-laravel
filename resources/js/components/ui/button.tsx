import { Slot } from '@radix-ui/react-slot';
import { cn } from '@/lib/utils';
import { forwardRef, type ButtonHTMLAttributes } from 'react';

type Variant = 'primary' | 'secondary' | 'danger' | 'success' | 'outline' | 'ghost';
type Size = 'sm' | 'md' | 'lg' | 'icon';

const variantClasses: Record<Variant, string> = {
    primary: 'bg-primary text-white hover:bg-primary-700 focus:ring-primary disabled:opacity-50',
    secondary: 'bg-secondary text-white hover:bg-gray-800 focus:ring-secondary disabled:opacity-50',
    danger: 'bg-danger text-white hover:bg-red-600 focus:ring-danger disabled:opacity-50',
    success: 'bg-success text-white hover:bg-emerald-600 focus:ring-success disabled:opacity-50',
    outline: 'border border-border text-secondary bg-white hover:bg-surface focus:ring-primary',
    ghost: 'text-secondary hover:bg-surface',
};

const sizeClasses: Record<Size, string> = {
    sm: 'px-3 py-1.5 text-xs',
    md: 'px-4 py-2 text-sm',
    lg: 'px-6 py-3 text-base',
    icon: 'p-2',
};

type ButtonProps = ButtonHTMLAttributes<HTMLButtonElement> & {
    variant?: Variant;
    size?: Size;
    asChild?: boolean;
};

export const Button = forwardRef<HTMLButtonElement, ButtonProps>(function Button(
    { className, variant = 'primary', size = 'md', asChild = false, ...props },
    ref,
) {
    const Comp = asChild ? Slot : 'button';

    return (
        <Comp
            ref={ref as never}
            className={cn(
                'inline-flex items-center justify-center gap-2 rounded-lg font-semibold transition focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:cursor-not-allowed',
                variantClasses[variant],
                sizeClasses[size],
                className,
            )}
            {...props}
        />
    );
});
