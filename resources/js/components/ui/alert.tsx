import { cn } from '@/lib/utils';
import { AlertCircle, CheckCircle, Info, AlertTriangle } from 'lucide-react';
import type { ReactNode } from 'react';

type Variant = 'success' | 'error' | 'warning' | 'info';

const variantConfig: Record<Variant, { bg: string; border: string; text: string; icon: ReactNode }> = {
    success: {
        bg: 'bg-green-50',
        border: 'border-success',
        text: 'text-green-800',
        icon: <CheckCircle className="h-4 w-4" />,
    },
    error: {
        bg: 'bg-red-50',
        border: 'border-danger',
        text: 'text-red-800',
        icon: <AlertCircle className="h-4 w-4" />,
    },
    warning: {
        bg: 'bg-yellow-50',
        border: 'border-warning',
        text: 'text-yellow-800',
        icon: <AlertTriangle className="h-4 w-4" />,
    },
    info: {
        bg: 'bg-blue-50',
        border: 'border-primary',
        text: 'text-blue-800',
        icon: <Info className="h-4 w-4" />,
    },
};

export function Alert({ children, variant = 'info', className }: { children: ReactNode; variant?: Variant; className?: string }) {
    const config = variantConfig[variant];
    return (
        <div className={cn('border-y border-r border-l-4 p-4 rounded-xl shadow-sm', config.bg, config.border, config.text, className)}>
            <div className="flex items-start gap-3">
                <div className="flex-shrink-0 mt-0.5">{config.icon}</div>
                <div className="text-sm font-medium flex-1 leading-relaxed">{children}</div>
            </div>
        </div>
    );
}
