import { router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import type { FlashToast } from '@/types/ui';

export function initializeFlashToast(): void {
    router.on('flash', (event) => {
        const flash = (event as CustomEvent).detail?.flash;
        const data = flash?.toast as FlashToast | undefined;

        if (!data || !data.message) {
            return;
        }

        const variant = data.type in toast ? data.type : 'info';
        toast[variant](data.message);
    });

    router.on('httpException', (event) => {
        const response = (event as CustomEvent).detail?.response as
            | { status?: number }
            | undefined;
        const status = response?.status;

        if (status === 403) {
            toast.error('You do not have permission to perform this action.');
        } else if (status === 404) {
            toast.error('The requested record could not be found.');
        } else if (status === 419) {
            toast.warning(
                'Your session has expired. Please refresh the page and try again.',
            );
        } else if (status === 429) {
            toast.warning(
                'Too many requests. Please wait a moment and try again.',
            );
        } else if (status && status >= 500) {
            toast.error(
                'An unexpected server error occurred. Please try again or contact an administrator.',
            );
        }
    });

    router.on('networkError', () => {
        toast.error(
            'Unable to reach the server. Please check your internet connection and try again.',
        );
    });
}
