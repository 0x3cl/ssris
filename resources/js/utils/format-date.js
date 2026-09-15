const dateFormatter = new Intl.DateTimeFormat('en-PH', { year: 'numeric', month: 'long', day: 'numeric' });
const timeFormatter = new Intl.DateTimeFormat('en-PH', { hour: 'numeric', minute: '2-digit', hour12: true });

/**
 * Format an ISO date string (e.g. "2026-09-18") as readable text (e.g. "September 18, 2026").
 * Returns the original value if it can't be parsed.
 */
export function formatDate(dateStr) {
    if (!dateStr) return '';

    const date = new Date(`${dateStr}T00:00:00`);

    return Number.isNaN(date.getTime()) ? dateStr : dateFormatter.format(date);
}

/**
 * Format a 24-hour time string (e.g. "22:00" or "22:00:00") as readable text (e.g. "10:00 PM").
 * Returns the original value if it can't be parsed.
 */
export function formatTime(timeStr) {
    if (!timeStr) return '';

    const [hours, minutes] = timeStr.split(':').map(Number);

    if (Number.isNaN(hours) || Number.isNaN(minutes)) return timeStr;

    const time = new Date();
    time.setHours(hours, minutes, 0, 0);

    return timeFormatter.format(time);
}

/**
 * Format a date and time pair as readable text (e.g. "September 18, 2026 at 10:00 PM").
 */
export function formatDateTime(dateStr, timeStr) {
    const date = formatDate(dateStr);
    const time = formatTime(timeStr);

    if (date && time) return `${date} at ${time}`;

    return date || time;
}
