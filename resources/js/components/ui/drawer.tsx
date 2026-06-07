import { cn } from '@/lib/utils';
import { X } from 'lucide-react';
import { useEffect, type ReactNode } from 'react';

type DrawerProps = {
    open: boolean;
    onClose: () => void;
    title: string;
    description?: string;
    children?: ReactNode;
    footer?: ReactNode;
};

export function Drawer({ open, onClose, title, description, children, footer }: DrawerProps) {
    useEffect(() => {
        function handleKey(e: KeyboardEvent) {
            if (e.key === 'Escape') {
                onClose();
            }
        }
        if (open) {
            document.addEventListener('keydown', handleKey);
        }
        return () => document.removeEventListener('keydown', handleKey);
    }, [open, onClose]);

    useEffect(() => {
        if (open) {
            const prev = document.body.style.overflow;
            document.body.style.overflow = 'hidden';
            return () => {
                document.body.style.overflow = prev;
            };
        }
    }, [open]);

    return (
        <>
            <div
                className={cn(
                    'fixed inset-0 z-40 bg-black/50 transition-opacity duration-300',
                    open ? 'opacity-100' : 'pointer-events-none opacity-0',
                )}
                onClick={onClose}
                aria-hidden
            />
            <div
                className={cn(
                    'fixed inset-0 z-50 flex justify-end',
                    open ? 'pointer-events-auto' : 'pointer-events-none',
                )}
                role="dialog"
                aria-modal="true"
                aria-labelledby="drawer-title"
            >
                <div
                    className={cn(
                        'flex h-full w-full max-w-md flex-col bg-white shadow-2xl transition-transform duration-300 ease-in-out',
                        open ? 'translate-x-0' : 'translate-x-full',
                    )}
                >
                    <div className="flex items-start justify-between gap-3 border-b border-border px-6 py-4">
                        <div>
                            <h3 id="drawer-title" className="text-lg font-bold text-navy">
                                {title}
                            </h3>
                            {description && (
                                <p className="mt-0.5 text-sm text-muted-foreground">
                                    {description}
                                </p>
                            )}
                        </div>
                        <button
                            type="button"
                            onClick={onClose}
                            className="shrink-0 rounded p-1 text-muted-foreground transition hover:bg-slate-100 hover:text-foreground"
                            aria-label="Tutup"
                        >
                            <X className="h-4 w-4" />
                        </button>
                    </div>
                    <div className="flex-1 overflow-y-auto px-6 py-5">{children}</div>
                    {footer && (
                        <div className="flex justify-end gap-3 border-t border-border bg-slate-50/50 px-6 py-4">
                            {footer}
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}
