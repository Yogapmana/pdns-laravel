import { cn } from '@/lib/utils';
import { X } from 'lucide-react';
import { useEffect, type ReactNode } from 'react';

type ModalProps = {
    open: boolean;
    onClose: () => void;
    title: string;
    description?: string;
    children?: ReactNode;
    footer?: ReactNode;
    className?: string;
};

export function Modal({ open, onClose, title, description, children, footer, className }: ModalProps) {
    useEffect(() => {
        function handleKey(e: KeyboardEvent) {
            if (e.key === 'Escape') onClose();
        }
        if (open) document.addEventListener('keydown', handleKey);
        return () => document.removeEventListener('keydown', handleKey);
    }, [open, onClose]);

    if (!open) return null;

    return (
        <>
            <div
                className="fixed inset-0 bg-black/50 z-40 transition-opacity"
                onClick={onClose}
                aria-hidden
            />
            <div
                className="fixed inset-0 z-50 flex items-center justify-center p-4"
                role="dialog"
                aria-modal="true"
                aria-labelledby="modal-title"
            >
                <div
                    className={cn('bg-white rounded-2xl shadow-2xl p-6 w-full max-w-md transform transition-all', className)}
                    onClick={(e) => e.stopPropagation()}
                >
                    <div className="flex items-start justify-between mb-2">
                        <h3 id="modal-title" className="text-lg font-bold text-navy">
                            {title}
                        </h3>
                        <button
                            type="button"
                            onClick={onClose}
                            className="text-muted-foreground hover:text-foreground p-1 rounded"
                            aria-label="Tutup"
                        >
                            <X className="h-4 w-4" />
                        </button>
                    </div>
                    {description && <p className="text-sm text-secondary mb-6">{description}</p>}
                    {children}
                    {footer && <div className="flex justify-end gap-3 mt-6">{footer}</div>}
                </div>
            </div>
        </>
    );
}
