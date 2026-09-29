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
