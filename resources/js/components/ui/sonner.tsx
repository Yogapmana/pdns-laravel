import { Toaster as SonnerToaster } from 'sonner';

export function Toaster() {
    return (
        <SonnerToaster
            position="bottom-right"
            richColors
            closeButton
            toastOptions={{
                className: 'rounded-xl shadow-2xl text-sm font-medium',
            }}
        />
    );
}
