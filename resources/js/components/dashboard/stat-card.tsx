import { ReactNode } from 'react';
import { cn } from '@/lib/utils';

export function DashboardStatCard({
    label,
    value,
    icon,
    valueColor = 'text-navy',
    iconColor = 'text-primary',
    iconBg = 'bg-blue-50',
    className,
}: {
    label: ReactNode;
    value: string | number;
    icon: ReactNode;
    valueColor?: string;
    iconColor?: string;
    iconBg?: string;
    className?: string;
}) {
    return (
        <div className={cn('bg-white border border-border shadow-sm rounded-xl p-5 flex items-center gap-4', className)}>
            <div className={cn('w-12 h-12 rounded-xl flex items-center justify-center shrink-0', iconBg, iconColor)}>
                <span className="[&>svg]:h-5 [&>svg]:w-5">{icon}</span>
            </div>
            <div className="min-w-0 flex flex-col justify-center">
                <p className={cn('text-[28px] leading-tight font-bold', valueColor)}>{value}</p>
                <div className="text-[11px] leading-snug text-muted-foreground mt-0.5">{label}</div>
            </div>
        </div>
    );
}
