import { router } from '@inertiajs/react';
import { useCallback, useRef, useState } from 'react';

type UseInertiaSearchOptions = {
    url: string;
    initialFilters: Record<string, string | null | undefined>;
    only?: string[];
    debounceMs?: number;
};

export type SearchFilterUpdater = Record<string, string>;

export function useInertiaSearch({
    url,
    initialFilters,
    only = [],
    debounceMs = 300,
}: UseInertiaSearchOptions) {
    const initial: Record<string, string> = {};

    for (const [key, value] of Object.entries(initialFilters)) {
        initial[key] = value ?? '';
    }

    const [filters, setFilters] = useState<Record<string, string>>(initial);
    const [loading, setLoading] = useState(false);
    const debounceRef = useRef<ReturnType<typeof setTimeout> | null>(null);

    const buildParams = useCallback((next: Record<string, string>) => {
        const params: Record<string, string> = {};

        for (const [key, value] of Object.entries(next)) {
            if (value) {
                params[key] = value;
            }
        }

        return params;
    }, []);

    const navigate = useCallback(
        (next: Record<string, string>) => {
            if (debounceRef.current) {
                clearTimeout(debounceRef.current);
            }

            debounceRef.current = setTimeout(() => {
                setLoading(true);
                router.get(url, buildParams(next), {
                    preserveState: true,
                    replace: true,
                    only: only.length > 0 ? only : undefined,
                    preserveScroll: true,
                    onFinish: () => setLoading(false),
                });
            }, debounceMs);
        },
        [url, only, debounceMs, buildParams],
    );

    const setFilter = useCallback(
        (key: string, value: string) => {
            setFilters((prev) => {
                const next = { ...prev, [key]: value };

                navigate(next);

                return next;
            });
        },
        [navigate],
    );

    const reset = useCallback(() => {
        if (debounceRef.current) {
            clearTimeout(debounceRef.current);
        }

        const cleared: Record<string, string> = {};

        for (const key of Object.keys(filters)) {
            cleared[key] = '';
        }

        setFilters(cleared);
        setLoading(true);
        router.get(
            url,
            {},
            {
                preserveState: true,
                replace: true,
                only: only.length > 0 ? only : undefined,
                preserveScroll: true,
                onFinish: () => setLoading(false),
            },
        );
    }, [url, only, filters]);

    const hasFilter = Object.values(filters).some((v) => v !== '');

    return { filters, loading, hasFilter, setFilter, reset };
}
