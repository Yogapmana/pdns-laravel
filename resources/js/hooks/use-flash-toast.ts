import { usePage } from '@inertiajs/react';
import { useEffect } from 'react';
import { toast } from 'sonner';

type Flash = { success?: string; error?: string };

export function useFlashToast() {
    const { props } = usePage<{ flash?: Flash }>();
    const flash = props.flash;

    useEffect(() => {
        if (flash?.success) {
toast.success(flash.success);
}

        if (flash?.error) {
toast.error(flash.error);
}
    }, [flash]);
}
