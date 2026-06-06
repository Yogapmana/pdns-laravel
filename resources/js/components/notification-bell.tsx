import { useEffect, useRef, useState } from 'react';
import { Bell, Check } from 'lucide-react';
import { useNotificationPolling } from '@/hooks/use-notification-polling';
import { cn } from '@/lib/utils';

type Props = {
    className?: string;
};

function formatRelative(iso: string | null): string {
    if (!iso) {
        return '';
    }

    const then = new Date(iso).getTime();

    if (Number.isNaN(then)) {
        return '';
    }

    const diffSec = Math.max(0, Math.floor((Date.now() - then) / 1000));

    if (diffSec < 60) {
        return 'baru saja';
    }

    const min = Math.floor(diffSec / 60);

    if (min < 60) {
        return `${min} menit lalu`;
    }

    const hr = Math.floor(min / 60);

    if (hr < 24) {
        return `${hr} jam lalu`;
    }

    const day = Math.floor(hr / 24);

    if (day < 30) {
        return `${day} hari lalu`;
    }

    return new Date(iso).toLocaleDateString('id-ID');
}

export function NotificationBell({ className }: Props) {
    const { unreadCount, items, markAllRead } = useNotificationPolling();
    const [open, setOpen] = useState(false);
    const containerRef = useRef<HTMLDivElement | null>(null);

    useEffect(() => {
        function onClickOutside(event: MouseEvent): void {
            if (!containerRef.current) {
                return;
            }
            if (!containerRef.current.contains(event.target as Node)) {
                setOpen(false);
            }
        }

        if (open) {
            document.addEventListener('mousedown', onClickOutside);
        }

        return () => {
            document.removeEventListener('mousedown', onClickOutside);
        };
    }, [open]);

    async function handleItemClick(link: string | null, id: number): Promise<void> {
        setOpen(false);

        if (link) {
            const { router } = await import('@inertiajs/react');
            router.visit(link);
        }

        try {
            await fetch(`/notifications/${id}/read`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null)?.content ?? '',
                    Accept: 'application/json',
                },
                credentials: 'same-origin',
            });
        } catch {
            // ignore; next poll reconciles
        }
    }

    return (
        <div ref={containerRef} className={cn('relative', className)}>
            <button
                type="button"
                onClick={() => setOpen((v) => !v)}
                className="relative text-muted-foreground hover:text-navy transition p-2 rounded-full hover:bg-slate-100 mr-2 md:mr-0"
                aria-label="Notifikasi"
                title="Notifikasi"
            >
                <Bell className="h-4 w-4 md:h-5 md:w-5" />
                {unreadCount > 0 && (
                    <span
                        data-testid="notification-badge"
                        className="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 rounded-full bg-danger text-white text-[10px] font-bold flex items-center justify-center border-2 border-white"
                    >
                        {unreadCount > 99 ? '99+' : unreadCount}
                    </span>
                )}
            </button>

            {open && (
                <div
                    data-testid="notification-dropdown"
                    className="absolute right-0 mt-2 w-80 max-h-[420px] overflow-y-auto rounded-lg border border-border bg-white shadow-lg z-50"
                >
                    <div className="flex items-center justify-between px-4 py-3 border-b border-border">
                        <h3 className="text-sm font-semibold text-navy">Notifikasi</h3>
                        {unreadCount > 0 && (
                            <button
                                type="button"
                                onClick={() => void markAllRead()}
                                className="text-xs text-primary hover:underline flex items-center gap-1"
                            >
                                <Check className="h-3 w-3" />
                                Tandai semua dibaca
                            </button>
                        )}
                    </div>

                    {items.length === 0 ? (
                        <div className="px-4 py-8 text-center text-sm text-muted-foreground">
                            Tidak ada notifikasi.
                        </div>
                    ) : (
                        <ul className="divide-y divide-border">
                            {items.map((item) => (
                                <li key={item.id}>
                                    <button
                                        type="button"
                                        onClick={() => void handleItemClick(item.link, item.id)}
                                        className={cn(
                                            'w-full text-left px-4 py-3 hover:bg-slate-50 transition flex flex-col gap-1',
                                            item.read_at === null && 'bg-primary/5',
                                        )}
                                    >
                                        <div className="flex items-start justify-between gap-2">
                                            <span className="text-sm font-medium text-navy">{item.title}</span>
                                            {item.read_at === null && (
                                                <span className="mt-1 h-2 w-2 rounded-full bg-primary shrink-0" aria-label="Belum dibaca" />
                                            )}
                                        </div>
                                        <p className="text-xs text-muted-foreground line-clamp-2">{item.body}</p>
                                        <span className="text-[10px] text-muted-foreground/80">{formatRelative(item.created_at)}</span>
                                    </button>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>
            )}
        </div>
    );
}

export default NotificationBell;
