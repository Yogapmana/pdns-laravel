import { useEffect, useState } from 'react';
import { router } from '@inertiajs/react';

export type NotificationItem = {
    id: number;
    type: string;
    title: string;
    body: string;
    link: string | null;
    read_at: string | null;
    created_at: string | null;
};

export type NotificationsResponse = {
    data: NotificationItem[];
    meta: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
};

type State = {
    unreadCount: number;
    items: NotificationItem[];
    loading: boolean;
};

type Options = {
    intervalMs?: number;
    enabled?: boolean;
};

const DEFAULT_INTERVAL = 60_000;

async function fetchJson<T>(url: string, init?: RequestInit): Promise<T> {
    const headers: Record<string, string> = {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    };
    if (init?.method && init.method !== 'GET') {
        headers['X-CSRF-TOKEN'] = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null)?.content ?? '';
    }

    const response = await fetch(url, {
        credentials: 'same-origin',
        ...init,
        headers: { ...headers, ...(init?.headers as Record<string, string> | undefined) },
    });

    if (!response.ok) {
        throw new Error(`Request failed: ${response.status}`);
    }

    return (await response.json()) as T;
}

export function useNotificationPolling(options: Options = {}): State & {
    refresh: () => Promise<void>;
    markRead: (id: number) => Promise<void>;
    markAllRead: () => Promise<void>;
    handleClick: (item: NotificationItem) => void;
} {
    const intervalMs = options.intervalMs ?? DEFAULT_INTERVAL;
    const enabled = options.enabled ?? true;
    const [state, setState] = useState<State>({ unreadCount: 0, items: [], loading: false });

    async function refresh(): Promise<void> {
        if (typeof document !== 'undefined' && document.visibilityState !== 'visible') {
            return;
        }

        setState((s) => ({ ...s, loading: true }));

        try {
            const [count, list] = await Promise.all([
                fetchJson<{ count: number }>('/notifications/unread-count'),
                fetchJson<NotificationsResponse>('/notifications'),
            ]);

            setState({ unreadCount: count.count, items: list.data, loading: false });
        } catch {
            setState((s) => ({ ...s, loading: false }));
        }
    }

    async function markRead(id: number): Promise<void> {
        try {
            await fetchJson(`/notifications/${id}/read`, { method: 'POST' });
        } catch {
            // best-effort; the next poll will reconcile state
        }
        await refresh();
    }

    async function markAllRead(): Promise<void> {
        try {
            await fetchJson('/notifications/read-all', { method: 'POST' });
        } catch {
            // best-effort
        }
        await refresh();
    }

    useEffect(() => {
        if (!enabled || typeof window === 'undefined') {
            return undefined;
        }

        void refresh();

        const interval = window.setInterval(() => {
            void refresh();
        }, intervalMs);

        const onVisibilityChange = (): void => {
            if (document.visibilityState === 'visible') {
                void refresh();
            }
        };

        document.addEventListener('visibilitychange', onVisibilityChange);

        return () => {
            window.clearInterval(interval);
            document.removeEventListener('visibilitychange', onVisibilityChange);
        };
    }, [enabled, intervalMs]);

    function handleClick(item: NotificationItem): void {
        void markRead(item.id);
        if (item.link) {
            router.visit(item.link);
        }
    }

    return { ...state, refresh, markRead, markAllRead, handleClick };
}
