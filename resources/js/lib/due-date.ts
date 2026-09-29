export const DUE_DATE_OPTIONS = [
    { value: 'overdue', label: 'Overdue' },
    { value: 'today', label: 'Today' },
    { value: 'tomorrow', label: 'Tomorrow' },
    { value: 'next_7_days', label: 'Next 7 days' },
    { value: 'this_week', label: 'This week' },
    { value: 'next_week', label: 'Next week' },
    { value: 'this_month', label: 'This month' },
    { value: 'next_month', label: 'Next month' },
] as const;

export type DueDateFilter = (typeof DUE_DATE_OPTIONS)[number]['value'];

export function dueDateLabel(value: string): string {
    return DUE_DATE_OPTIONS.find((option) => option.value === value)?.label ?? value;
}

export type DueTone = 'overdue' | 'today' | 'soon' | 'later';

/**
 * Parse the 'MM/DD/YYYY' due date sent by the server into a local date at midnight.
 */
export function parseDueDate(mdy: string): Date | null {
    const [month, day, year] = mdy.split('/').map(Number);

    return month && day && year ? new Date(year, month - 1, day) : null;
}

/**
 * Describe a due date relative to today, e.g. "3 days overdue", "Due today", "Tomorrow", "Oct 4".
 */
export function describeDueDate(mdy: string): { label: string; tone: DueTone } {
    const dueDate = parseDueDate(mdy);

    if (!dueDate) {
        return { label: mdy, tone: 'later' };
    }

    const today = new Date();
    today.setHours(0, 0, 0, 0);

    const daysAway = Math.round((dueDate.getTime() - today.getTime()) / 86_400_000);

    if (daysAway < 0) {
        return { label: daysAway === -1 ? '1 day overdue' : `${-daysAway} days overdue`, tone: 'overdue' };
    }

    if (daysAway === 0) {
        return { label: 'Due today', tone: 'today' };
    }

    if (daysAway === 1) {
        return { label: 'Tomorrow', tone: 'soon' };
    }

    const options: Intl.DateTimeFormatOptions = { month: 'short', day: 'numeric' };

    if (dueDate.getFullYear() !== today.getFullYear()) {
        options.year = 'numeric';
    }

    return { label: dueDate.toLocaleDateString(undefined, options), tone: daysAway <= 7 ? 'soon' : 'later' };
}

export function dueToneClass(tone: DueTone): string {
    switch (tone) {
        case 'overdue':
            return 'text-red-600 dark:text-red-400 font-medium';
        case 'today':
            return 'text-amber-600 dark:text-amber-400 font-medium';
        default:
            return 'text-muted-foreground';
    }
}
