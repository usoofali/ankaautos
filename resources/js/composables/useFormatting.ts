import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

export function useFormatting() {
    const page = usePage();

    const currencySymbol = computed(
        () => page.props.business?.currency_symbol ?? '₦',
    );
    const timezone = computed(
        () => page.props.business?.timezone ?? 'Africa/Lagos',
    );

    function formatMoney(
        value: string | number | null | undefined,
        options?: { compact?: boolean },
    ): string {
        if (value === null || value === undefined || value === '') {
            return `${currencySymbol.value}0.00`;
        }

        const numeric = typeof value === 'string' ? Number(value) : value;
        if (Number.isNaN(numeric)) {
            return `${currencySymbol.value}0.00`;
        }

        const formatted = new Intl.NumberFormat('en-NG', {
            minimumFractionDigits: options?.compact ? 0 : 2,
            maximumFractionDigits: options?.compact ? 0 : 2,
        }).format(Math.abs(numeric));

        return `${numeric < 0 ? '-' : ''}${currencySymbol.value}${formatted}`;
    }

    function formatDate(value: string | null | undefined): string {
        if (!value) {
            return '—';
        }

        const date = new Date(value);
        if (Number.isNaN(date.getTime())) {
            return '—';
        }

        return new Intl.DateTimeFormat('en-GB', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            timeZone: timezone.value,
        }).format(date);
    }

    function formatDateTime(value: string | null | undefined): string {
        if (!value) {
            return '—';
        }

        const date = new Date(value);
        if (Number.isNaN(date.getTime())) {
            return '—';
        }

        return new Intl.DateTimeFormat('en-GB', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            hour12: true,
            timeZone: timezone.value,
        }).format(date);
    }

    function formatRelativeTime(value: string | null | undefined): string {
        if (!value) {
            return 'Never';
        }

        const date = new Date(value);
        if (Number.isNaN(date.getTime())) {
            return '—';
        }

        const diffSeconds = Math.round((date.getTime() - Date.now()) / 1000);
        const absSeconds = Math.abs(diffSeconds);
        const rtf = new Intl.RelativeTimeFormat('en', { numeric: 'auto' });

        if (absSeconds < 60) {
            return rtf.format(diffSeconds, 'second');
        }
        if (absSeconds < 3600) {
            return rtf.format(Math.round(diffSeconds / 60), 'minute');
        }
        if (absSeconds < 86400) {
            return rtf.format(Math.round(diffSeconds / 3600), 'hour');
        }
        if (absSeconds < 604800) {
            return rtf.format(Math.round(diffSeconds / 86400), 'day');
        }

        return formatDate(value);
    }

    return {
        currencySymbol,
        timezone,
        formatMoney,
        formatDate,
        formatDateTime,
        formatRelativeTime,
    };
}
