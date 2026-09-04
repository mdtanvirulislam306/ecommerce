const DEFAULT_TIMEZONE = 'Asia/Dhaka';

export const formatDateTime = (value, timeZone = DEFAULT_TIMEZONE) => {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleString(undefined, {
        timeZone,
        dateStyle: 'medium',
        timeStyle: 'short',
    });
};
